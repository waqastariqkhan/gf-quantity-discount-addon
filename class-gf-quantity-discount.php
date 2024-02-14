<?php
/**
 * Qunatity Addon using GF Feed-addon framework
 *
 * @since 1.0.0
 *
 * @package GF Feed Addon
 */

GFForms::include_feed_addon_framework();

if ( class_exists( 'GF_Field' ) ) {
	require_once( 'class-gf-field-coupon.php' );
}

/**
 * Class forQunatity Addon
 *
 * @since 1.0.0
 *
 * @package GF Feed Addon
 */
class GF_Quantity_Discount extends GFFeedAddOn {

	/**
	 * Version of the addon
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_version = GF_QUANTITY_DISCOUNT_ADDON_VERSION;


	/**
	 * Minumim gravity forms version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9.16';

	/**
	 * Slug
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_slug = 'gf-quantity-discount';

	/**
	 * Path to the file
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_path = 'gf-quantity-discount/gf-quantity-discount.php';

	/**
	 * Path variable
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the Addon
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms Quantity Discount Add-on';

	/**
	 * Short title for sidebar
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $_short_title = 'Quantity Discount';

	/**
	 * Insatnce
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GF_Quantity_Discount
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new GF_Quantity_Discount();
		}

		return self::$_instance;
	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to service x only when payment is received.', 'gf-quantity-discount' ),
			)
		);

		add_filter( 'gform_product_info', array( $this, 'calc_add_discount' ), 10, 3 );
		add_action( 'wp_ajax_get_feed_data', array( $this, 'get_feed_data' ) );
		add_action( 'wp_ajax_nopriv_get_feed_data', array( $this, 'get_feed_data' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_js') );
	}
	
	
	public function enqueue_admin_js() {
		wp_enqueue_script( 'gf-quantity-addon-admin', plugin_dir_url( __FILE__ ) . 'admin/assets/js/script.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ), 0.1, true );
		wp_enqueue_script( 'gf-reapeatable-fields-admin', plugin_dir_url( __FILE__ ) . 'admin/assets/js/repeatable-fields.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ), 0.1, true );
		wp_register_style( 'gf-quantity-addon-admin-style', plugin_dir_url( __FILE__ ) . 'admin/assets/css/style.css', false, '1.0.0' );
		wp_enqueue_style( 'gf-quantity-addon-admin-style' );
		wp_enqueue_style( 'wp-ld-transcript-toaster-css', plugin_dir_url( __FILE__ ) . 'admin/assets/toaster/toastr.min.css', array(), false );
		wp_enqueue_script( 'wp-ld-transcript-toaster', plugin_dir_url( __FILE__ ) . 'admin/assets/toaster/toastr.min.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ) );
	}

	/**
	 * Gets the form_id and returns the feed requested via ajax
	 *
	 * @return void
	 */
	public function get_feed_data() {
		$form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : null;
		$feeds    = GFAPI::get_feeds( null, $form_id );
		$form_field = GFAPI::get_form( $form_id );
		echo wp_json_encode(
			array(
				'feed' => $feeds,
				'field' => $form_field['fields']
			)
		);
		wp_die();
	}
	
	
	/**
	 * Searches the coupon details array for user entered coupon and returns its value
	 *
	 * @param string $couponCode
	 * @param array  $coupon_details
	 * @return list  [$coupon_value, $coupon_quantity]
	 */
	public function searchCoupon($coupon_code, $coupon_details) {
		foreach ($coupon_details as $coupon) {
			if ($coupon['cN'] === $coupon_code) {
				$coupon_value = $coupon['cD'];
				$coupon_quantity = $coupon['cQ'];
				break; // exit the loop if the coupon is found
			}
		}
		return [$coupon_value, $coupon_quantity];
	}


	/**
	 * Calculates and adds discount
	 *
	 * @param array  $product_info  The array containing the product information. This array is in the following format.
	 * @param Object $form         Object The form currently being processed.
	 *
	 * @return object $product_info
	 */
	public function calc_add_discount( $product_info, $form, $entry ) {

		$feed             		 = GFAPI::get_feeds( null, $form['id'] );
		$minimum_quantity 		 = $feed[0]['meta']['minimum_quantity'];
		$minimum_discount_value  = $feed[0]['meta']['minimum_discount_value'];
		$discount_type           = $feed[0]['meta']['discount_type'];
		$discount_method    	 = $feed[0]['meta']['discount_method'];
		$coupon_details    		 = $feed[0]['meta']['coupon_details'];
		$product_id				 = (int) floatval($feed[0]['meta']['mappedFields_product_name']);
		$product 		  	     = $product_info['products'][$product_id];
		$total_w_currency 	     = (int) preg_replace( '/\..+$/i', '', preg_replace( '/[^0-9\.]/i', '', $product['price'] ) );
		$total_product_value     = (int) $total_w_currency * $product['quantity'];			
		
		if ( 'quantity_discount' === $discount_method ) {
			if ( $product['quantity'] >= $minimum_quantity ) {
				if ( 'percent' === $discount_type ) {
					$discount_value = $total_product_value * ( $minimum_discount_value / 100 );
				} elseif ( 'cash' === $discount_type ) {
					$discount_value = $minimum_discount_value;
				}
			}
		}
		else if ( 'coupon_discount' === $discount_method  ){
			$coupon_field = GFAPI::get_fields_by_type( $form, 'coupon' );
			$coupon_entry = $entry[$coupon_field[0]->id];
			list($coupon_value, $coupon_quantity) = $this->searchCoupon($coupon_entry, $coupon_details);
			
			if( $product['quantity'] >= $coupon_quantity ) {
				if ( 'percent' === $discount_type ) {
					$discount_value = $total_product_value * ( $coupon_value / 100 );
				} elseif ( 'cash' === $discount_type ) {
					$discount_value = $coupon_value;
				}
			}
		}
		
		$discount_name = $discount_method === 'coupon_discount' ? 'Coupon ' . $coupon_entry : 'Quntity Discount';
		
		$discount_symbol = $discount_type === 'percent' ? '%' : '$';

		$product_info['products']['Discount'] = array(
			'name'      => $discount_name . ' @ ' . $product['name'] . ' - ' . $coupon_value . $discount_symbol,
			'price'     => - $discount_value,
			'quantity'  => 1,
			'isProduct' => false,
		);

		return $product_info;
	}

	/**
	 * Creates a custom page for this add-on.
	 */
	public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'GF Quantity Discount', 'gf-quantity-discount' ),
				'fields' => array(
					array(
						'name'    => 'textbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'gf-quantity-discount' ),
						'label'   => esc_html__( 'This is the label', 'gf-quantity-discount' ),
						'type'    => 'text',
						'class'   => 'small',
					),
				),
			),
		);
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Quantity Discount Add-on area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return array(
			
			array(
				'title'  => esc_html__( 'Discount Method Settings', 'gf-quantity-discount' ),
				'id'     => 'grading_settings_section',
				'fields' => array(
					
					array(
						'label' => esc_html__( 'Feed name', 'gf-quantity-discount' ),
						'type'  => 'text',
						'name'  => 'feedName',
						'class' => 'small',
					),
					
					array(
						'label' => esc_html__( 'Discount Method', 'gf-quantity-discount' ),
						'name'          => 'discount_method',
						'type'          => 'radio',
						'horizontal'    => true,
						'class'         => 'gquiz-grading',
						'choices'       => array(
							array(
								'value'   => 'coupon_discount',
								'label'   => esc_html__( 'Coupon Discount', 'gf-quantity-discount' ),
								'tooltip' => '<h6>' . esc_html__( 'Select for Coupon Discount', 'gf-quantity-discount' ) . '</h6>',
							),
							array(
								'value'   => 'quantity_discount',
								'label'   => esc_html__( 'Quantity Discount', 'gf-quantity-discount' ),
								'tooltip' => '<h6>' . esc_html__( 'Select for Quantity Discount', 'gf-quantity-discount' ) . '</h6>',
							),
						),
					),
					
					array(
						'label'    => 'Discount Type',
						'type'     => 'select',
						'name'     => 'discount_type',
						'tooltip'  => 'Select the Type of discount you want to give',
						'required' => 'true',
						'choices'  => array(
							array(
								'label' => 'Flat Percent',
								'value' => 'percent',
							),
							array(
								'label' => 'Cash Value',
								'value' => 'cash',
							),
						),
					),
					
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Select Product (Name) field the feed should be applied to', 'gf-quantity-discount' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'     => 'product_name',
								'label'    => esc_html__( 'Product Name', 'gf-quantity-discount' ),
								'required' => 0,
							),
							array(
								'name'     => 'product_quantity',
								'label'    => esc_html__( 'Product Quantity', 'gf-quantity-discount' ),
								'required' => 0,
							),
							array(
								'name'     => 'product_price',
								'label'    => esc_html__( 'Product Price', 'gf-quantity-discount' ),
								'required' => 0,
							),
						),
					),
				),
			),
			
			
			array(
				'title'  => esc_html__( 'Quantity Discount Settings', 'gf-quantity-discount' ),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field'  => 'discount_method',
							'values' => array( 'quantity_discount' ),
						),
					),
				),
				'fields' => array(
					array(
						'label'    => esc_html__( 'Minimum Quantity', 'gf-quantity-discount' ),
						'type'     => 'text',
						'name'     => 'minimum_quantity',
						'tooltip'  => esc_html__( 'This must be a number', 'gf-quantity-discount' ),
						'class'    => 'small',
						'required' => 'true',
					),
					
					array(
						'label'    => esc_html__( 'Discount Value', 'gf-quantity-discount' ),
						'type'     => 'text',
						'name'     => 'minimum_discount_value',
						'tooltip'  => esc_html__( 'Please do not add currency sign', 'gf-quantity-discount' ),
						'class'    => 'small',
						'required' => 'true',
					),
				),
			),
			
			
			
			array(
				'title'  => esc_html__( 'Coupon Discount Settings', 'gf-quantity-discount' ),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field'  => 'discount_method',
							'values' => array( 'coupon_discount' ),
						),
					),
				),
				
				'fields' => array(	
								  
					array(
						'label' => 'Add coupons and associated values',
						'type'  => 'coupon_field_type',
						'name'  => 'feed_coupon_field',
					),		
					
					array(
						'label'    => esc_html__( 'Coupon Details', 'gf-quantity-discount' ),
						'type'     => 'hidden',
						'name'     => 'coupon_details',
						'tooltip'  => esc_html__( 'This must be a number', 'gf-quantity-discount' ),
						'class'    => 'small',
						'required' => 'true',
					),
									
				),
			),
		);
	}
	
	public function settings_coupon_field_type() {	
			ob_start(); 
			?>
			<div class="repeat">
				<table class="wrapper" width="100%">
					<thead>
						<tr class="add-button-row">
							<td width="10%" colspan="4"><span class="add">Add</span></td>
						</tr>
						<tr>
							<th> <?php esc_html__( 'Coupon Details', 'gf-quantity-discount' ) ?></th>
							<th> <?php esc_html__( 'Coupon Discount Value ', 'gf-quantity-discount' ) ?> </th>
							<th> <?php esc_html__( 'Minimum Quantity', 'gf-quantity-discount' ) ?></th>
						</tr>
					</thead>
					<tbody class="container">
						<tr class="template row">				
							<td width="50%">
								<input type="text" placeholder="Coupon Name" name="coupon-name[]" id="coupoun-name-{{row-count-placeholder}}" />
							</td>
							<td width="15%">
								<input type="number" placeholder="Discount Value" name="coupon-discount[]" id="coupon-discount-{{row-count-placeholder}}" />
							</td>
							<td width="15%">
								<input type="number" placeholder="Minimum Qunatity" name="coupon-minimum-quantity[]" id="coupon-minimum-quantity-{{row-count-placeholder}}" />
							</td>
							<td width="20%"><span id="removebutton" class="remove">Remove</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			$html = ob_get_clean(); // Get the buffered content and clean the buffer
			return $html;
	}
	
	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__( 'Name', 'gf-quantity-discount' ),
			'mytextbox' => esc_html__( 'Gravity form Quantity Discount', 'gf-quantity-discount' ),
		);
	}

	/**
	 * Format the value to be displayed in the mytextbox column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_mytextbox( $feed ) {
		return '<b>' . rgars( $feed, 'meta/mytextbox' ) . '</b>';
	}

	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// Access a specific setting e.g. an api key.
		$key = rgar( $settings, 'apiKey' );

		return true;
	}
}
