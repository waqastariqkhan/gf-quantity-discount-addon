<?php

GFForms::include_feed_addon_framework();

class GFQuantityDiscountAddon extends GFFeedAddOn {

	protected $_version                  = GF_QUANTITY_DISCOUNT_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9.16';
	protected $_slug                     = 'gf-quantity-discount';
	protected $_path                     = 'gf-quantity-discount/gf-quantity-discount.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Gravity Forms Quantity Discount Add-on';
	protected $_short_title              = 'Quantity Discount';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFQuantityDiscountAddon
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFQuantityDiscountAddon();
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
	}

	/**
	 * Calculates and Add Discount to the entry pages.
	 */
	public function calc_add_discount( $product_info, $form, $lead ) {

		$feed             = GFAPI::get_feeds( null, $form->ID );
		$minimum_quantity = $feed[0]['meta']['minimum_quantity'];
		$discount_amount  = $feed[0]['meta']['discount_amount'];
		$discount_type    = $feed[0]['meta']['discount_type'];

		$quantity = $product_info['products'][1]['quantity'];
		$price    = $product_info['products'][1]['price'];

		$total_w_currency = (int) preg_replace( '/\..+$/i', '', preg_replace( '/[^0-9\.]/i', '', $price ) );

		$total_order_value = (int) $total_w_currency * $quantity;

		if ( $discount_type == 'percent' ) {
			$discount_value = $total_order_value * ( $discount_amount / 100 );
		} elseif ( $discount_type == 'cash' ) {
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
				'title'  => esc_html__( 'Simple Add-On Settings', 'gf-quantity-discount' ),
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
			'mytextbox' => esc_html__( 'My Textbox', 'gf-quantity-discount' ),
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

		// Access a specific setting e.g. an api key
		$key = rgar( $settings, 'apiKey' );

		return true;
	}
}
