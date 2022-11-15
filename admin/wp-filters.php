<?php
namespace caex_woocommerce\Admin;
use caex_woocommerce\Admin\Util;


// Our hooked in function – $fields is passed via the filter!
function dl_p_add_nit_to_checkout_fields( $fields ) {

    $caex_settings = get_option ( 'caex_api_credentials' );

	if ( isset( $caex_settings['enable_nit'] ) && $caex_settings['enable_nit'] == 1 ) {
		$fields['billing']['billing_nit'] = array(
			'label'     => __('NIT', 'woocommerce'),
			'placeholder'   => _x('C/F', 'placeholder', 'wp-caex-woocommerce'),
			'required'  => false,
			'class'     => array('form-row-wide'),
			'clear'     => true,
			'priority'	=> 25,
		);
	}

    return $fields;
}
add_filter( 'woocommerce_checkout_fields' , __NAMESPACE__ . '\\dl_p_add_nit_to_checkout_fields' );



// Register email notification action for caex
function dl_p_caex_email_notification_action( $email_classes ) {
	//* Custom welcome email to customer when purchasing online training program
	$email_classes['Caex_Email_Notification'] = new Util\Caex_Email_Notification(); // add to the list of email classes that WooCommerce loads
	return $email_classes;
}
add_filter( 'woocommerce_email_classes', __NAMESPACE__ . '\\dl_p_caex_email_notification_action' );



function dl_adon_plugin_template( $template, $template_name, $template_path ) {
     global $woocommerce;
     $_template = $template;
     if ( ! $template_path ) 
        $template_path = $woocommerce->template_url;
 
     $plugin_path  =  CAEX_API_PLUGIN_PATH . 'woocommerce/';
 
    // Look within passed path within the theme - this is priority
    $template = locate_template(
    array(
      $template_path . $template_name,
      $template_name
    )
   );
 
   if( ! $template && file_exists( $plugin_path . $template_name ) )
    $template = $plugin_path . $template_name;
 
   if ( ! $template )
    $template = $_template;

   return $template;
}
add_filter( 'woocommerce_locate_template', __NAMESPACE__ . '\\dl_adon_plugin_template', 1, 3 );


function add_custom_order_status( $order_statuses ) {
  $new_order_statuses = array();
  foreach ( $order_statuses as $key => $status ) {
      $new_order_statuses[ $key ] = $status;
      if ( 'wc-on-hold' === $key ) {
          $new_order_statuses['wc-on-route'] = __('On Route', 'wp-caex-woocommerce');
      }
  }
  return $new_order_statuses;
}
add_filter( 'wc_order_statuses', __NAMESPACE__ . '\\add_custom_order_status' );


/**
 * @snippet       Tracking @ My Account Orders
 * @author        Edwin Xico
 * @compatible    WooCommerce 6
 */   
function dl_caex_add_tracking_btn( $actions, $order ) {
  $caex_tracking = get_post_meta( $order->get_id(), '_wc_order_caex_tracking' );
  $caex_last_action = get_post_meta( $order->get_id() , '_caex_last_action', true );
  if( $caex_tracking && $caex_last_action != 'invoice_cancelled'  ) {
    $caex_tracking = json_decode( $caex_tracking[count($caex_tracking)-1], true );
    $actions['tracking'] = array(
      'url' => 'https://www.cargoexpreso.com/tracking/?guia=' . $caex_tracking['NumeroGuia'],
      'name' => __( 'Tracking', 'wp-caex-woocommerce' ),
    );
  }

    return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', __NAMESPACE__ . '\\dl_caex_add_tracking_btn', 9999, 2 );


function wc_cart_totals_order_total_html() {
  return '<strong>' . WC()->cart->get_total() . '</strong> ';
}
add_filter( 'woocommerce_cart_totals_order_total_html', __NAMESPACE__ . '\\wc_cart_totals_order_total_html', 9999, 2 );