<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://dhrubokinfotech.com/
 * @since      1.0.0
 *
 * @package    Product_Stock_Alert_Woocommerce
 * @subpackage Product_Stock_Alert_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Product_Stock_Alert_Woocommerce
 * @subpackage Product_Stock_Alert_Woocommerce/includes
 * @author     Dhrubok Infotech <info@dhrubokinfotech.com>
 */
class Product_Stock_Alert_Woocommerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'product-stock-alert-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
