<?php 
/**
* @since 1.0.0
* @package wp-caex-woocommerce
* @author xicoofficial
* 
* Plugin Name: Cargo Expreso - WooCommerce Invoicing
* Plugin URI: https://digitallabs.agency
* Description: Generate invoices with a click of a button on WooCommerce with this custom invoicing integration with Cargo Expreso.
* Version: 1.0.0
* Author: Digital Labs
* Author URI: https://digitallabs.agency
* Licence: GPL-3.0+
* Text Domain: wp-caex-woocommerce
* Domain Path: /languages/
* WC requires at least: 3.0.0 
* WC tested up to: 6.8.44444
*/
 
function dl_p_caex_load_textdomain() {
    load_plugin_textdomain( 'wp-caex-woocommerce', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'dl_p_caex_load_textdomain' );

define( 'CAEX_API_DEBUG_MODE', true);
define( 'DL_DEBUG', true);
define( 'CAEX_API_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CAEX_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


// If this file is accessed directory, then abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// require_once('admin/wp-functions.php');

defined( 'ABSPATH' ) or exit;
// Make sure WooCommerce is active

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	require_once( trailingslashit( dirname( __FILE__ ) ) . 'admin/caex.php' );

	if( $caex_api_key = get_option('caex_api_key') ) {
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'inc/autoloader.php' );
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'admin/wp-filters.php');
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'admin/wp-actions.php');
	} else {
		add_action('admin_notices', 'dl_p_caex_add_api_notice', 10);		
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'dl_p_caex_activate_action_link' );
		return;
	}
} else {
	add_action( 'admin_notices', 'dl_p_caex_add_error_notice', 10 );
	return;
}

use caex_woocommerce\Admin\Util;
use caex_woocommerce\Admin\Helpers;
use caex_woocommerce\Admin\Settings;

function dl_p_caex_activate_action_link( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'options-general.php?page=dl-caex-license' ) . '">' . __( 'Activate', 'wp-caex-woocommerce' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

/**
* Add custom action links
*/
function dl_p_caex_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'options-general.php?page=caex-api' ) . '">' . __( 'Settings', 'wp-caex-woocommerce' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'dl_p_caex_action_links' );


function dl_p_caex_add_error_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'Cargo Expreso - WooCommerce Invoicing requires WooCommerce to work', 'wp-caex-woocommerce' ); ?></p>
    </div>
    <?php
}

function dl_p_caex_add_api_notice () {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo __('Please', 'wp-caex-woocommerce') . ' <a href="' . admin_url() . 'options-general.php?page=caex-api">' . __('activate', 'wp-caex-woocommerce') . ' </a> ' . __('the Cargo Expreso for WooCommerce Plugin Licence to start using this plugin!', 'wp-caex-woocommerce'); ?></p>
    </div>
    <?php
}


function dl_p_caex_api() {

	$Logger = new Util\Logger();
	$ChicoteAPI = new Helpers\Caex_API();
	if( is_admin() ) {
		$my_settings_page_ifacere = new Settings\Caex_Settings();
	}

}
add_action( 'plugins_loaded', 'dl_p_caex_api' );