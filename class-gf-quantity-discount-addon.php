<?php
/**
 * Plugin Name: Gravity Forms Quantity Discount Addon
 * Plugin URI: https://github.com/waqastariqkhan/gf-quantity-discount-addon
 * Description: A simple add-on to demonstrate the use of the Add-On Framework
 * Version: 1.0
 * Author: Waqas Tariq
 * Author URI: https://github.com/waqastariqkhan/
 *
 * @package none
 */

define( 'GF_QUANTITY_DISCOUNT_ADDON_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_Quantity_Discount_Addon', 'load' ), 5 );


/**
 * Bootstrapper for loading the Quantity Addon class.
 *
 * @return void
 */
class GF_Quantity_Discount_Addon {

	/**
	 * Loads the addon frameowork, enqueues custom script and register add with Gravity forms
	 *
	 * @return void
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		wp_enqueue_script( 'gf-quantity-addon-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/gf-quantity-addon-frontend.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ), 0.1, true );
		wp_enqueue_style( 'gf-quantity-addon-frontend-style',   plugin_dir_url( __FILE__ ) .  'assets/css/style.css' );

		require_once 'class-gf-quantity-discount.php';
		GFAddOn::register( 'GF_Quantity_Discount' );
	}
}
