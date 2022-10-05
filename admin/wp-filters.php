<?php

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
 
     $plugin_path  =  INFILE_API_PLUGIN_PATH . 'woocommerce/';
 
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