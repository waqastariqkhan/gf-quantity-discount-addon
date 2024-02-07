<?php
/**
 * Qunatity Addon using GF Feed-addon framework
 *
 * @since 1.0.0
 *
 * @package GF Feed Addon
 */

GFForms::include_feed_addon_framework();

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
	}


	/**
	 * Gets the form_id and returns the feed requested via ajax
	 *
	 * @return void
	 */
	public function get_feed_data() {
		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : null;
		$feed    = GFAPI::get_feeds( $form_id );
		echo wp_json_encode(
			array(
				'feed' => $feed,
			)
		);
		wp_die();
	}

	/**
	 * Calculates and adds discount
	 *
	 * @param array  $product_info  The array containing the product information. This array is in the following format.
	 * @param Object $form         Object The form currently being processed.
	 *
	 * @return object $product_info
	 */
	public function calc_add_discount( $product_info, $form ) {

		$feed             = GFAPI::get_feeds( $form['ID'] );
		$minimum_quantity = $feed[0]['meta']['minimum_quantity'];
		$discount_amount  = $feed[0]['meta']['discount_amount'];
		$discount_type    = $feed[0]['meta']['discount_type'];

		$product = array_values( $product_info['products'] )[0];

		$price    = $product['price'];
		$quantity = $product['quantity'];

		$total_w_currency = (int) preg_replace( '/\..+$/i', '', preg_replace( '/[^0-9\.]/i', '', $price ) );

		$total_order_value = (int) $total_w_currency * $quantity;

		if ( 'percent' === $discount_type ) {
			$discount_value = $total_order_value * ( $discount_amount / 100 );
		} elseif ( 'cash' === $discount_type ) {
			$discount_value = $discount_amount;
		}

		if ( $quantity > $minimum_quantity ) {
			$product_info['products']['Discount'] = array(
				'name'      => 'Quantity Discount',
				'price'     => - $discount_value,
				'quantity'  => 1,
				'isProduct' => false,
			);
		}
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
				'title'  => esc_html__( 'Quantity Discount Settings', 'gf-quantity-discount' ),
				'fields' => array(
					array(
						'label' => esc_html__( 'Feed name', 'gf-quantity-discount' ),
						'type'  => 'text',
						'name'  => 'feedName',
						'class' => 'small',
					),

					array(
						'label'    => esc_html__( 'Minimum Quantity', 'gf-quantity-discount' ),
						'type'     => 'text',
						'name'     => 'minimum_quantity',
						'tooltip'  => esc_html__( 'This must be a number', 'gf-quantity-discount' ),
						'class'    => 'small',
						'required' => 'true',
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
						'label'    => esc_html__( 'Discount Value', 'gf-quantity-discount' ),
						'type'     => 'text',
						'name'     => 'discount_amount',
						'tooltip'  => esc_html__( 'Please do not add currency sign', 'gf-quantity-discount' ),
						'class'    => 'small',
						'required' => 'true',
					),

					array(
						'name'           => 'condition',
						'label'          => esc_html__( 'Condition', 'gf-quantity-discount' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'gf-quantity-discount' ),
						'instructions'   => esc_html__( 'Process this simple feed if', 'gf-quantity-discount' ),
					),
				),
			),
		);
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
