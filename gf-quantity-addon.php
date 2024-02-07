<?php
/*
Plugin Name: Gravity Forms Simple Feed Add-On
Plugin URI: http://www.gravityforms.com
Description: A simple add-on to demonstrate the use of the Add-On Framework
Version: 2.0
Author: Rocketgenius
Author URI: http://www.rocketgenius.com
*/


define( 'GF_QUANTITY_DISCOUNT_ADDON_VERSION;', '1.0' );

add_action( 'gform_loaded', array( 'GF_Quantity_Discount_Bootstrap', 'load' ), 5 );

class GF_Quantity_Discount_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}
		require_once 'class-gf-quantity-discount.php';
		wp_enqueue_script( 'gf-quantity-addon-frontend', plugin_dir_url( __FILE__ ) . 'assets/js/gf-quantity-addon-frontend.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ) );
		GFAddOn::register( 'GFQuantityDiscountAddon' );
	}
}

function gf_simple_feed_addon() {
	return GFQuantityDiscountAddon::get_instance();
}
