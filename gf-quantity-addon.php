<?php
/*
Plugin Name: Gravity Forms Quantity Discount Addon
Plugin URI: https://github.com/waqastariqkhan/gf-quantity-discount-addon
Description: A simple add-on to demonstrate the use of the Add-On Framework
Version: 1.0
Author: Waqas Tariq
Author URI: https://github.com/waqastariqkhan/
*/


define( 'GF_QUANTITY_DISCOUNT_ADDON_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_Quantity_Discount_Bootstrap', 'load' ), 5 );

class GF_Quantity_Discount_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}
		
		wp_enqueue_script( 'gf-quantity-addon-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/gf-quantity-addon-frontend.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ) );
		
		require_once 'class-gf-quantity-discount.php';
		GFAddOn::register( 'GFQuantityDiscountAddon' );
	}
}

function gf_simple_feed_addon() {
	return GFQuantityDiscountAddon::get_instance();
}
