<?php

namespace caex_woocommerce\Admin;


define('CAEX_SECRET_KEY', '6028446ece1983.75376194');
define('CAEX_LICENSE_SERVER_URL', 'https://market.digitallabs.agency');
define('CAEX_ITEM_REFERENCE', 'caex-woocommerce');

add_action('admin_menu', __NAMESPACE__ . '\\caex_api_menu');

function caex_api_menu() {
    add_options_page('Digital Labs License Activation Menu', 'Digital Labs "Cargo Expreso" License', 'manage_options', 'dl-caex-license', __NAMESPACE__ . '\\caex_api_management_page');
}

function caex_api_management_page () {
    echo '<div class="wrap">';
    echo '<h2>' . __('Digital Labs Plugins Licence Management', 'wp-caex-woocommerce') . '</h2>';

    /*** License activate button was clicked ***/
    if (isset($_REQUEST['activate_license'])) {
        $license_key = $_REQUEST['caex_api_key'];
        // API query parameters
        $api_params = array(
            'slm_action' => 'slm_activate',
            'secret_key' => CAEX_SECRET_KEY,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode(CAEX_ITEM_REFERENCE),
        );

        // Send query to the license manager server
        $query = esc_url_raw(add_query_arg($api_params, CAEX_LICENSE_SERVER_URL));
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        // Check for error in the response
        if (is_wp_error($response)){
            echo "Unexpected Error! The query returned with an error.";
        }

        //var_dump($response);//uncomment it if you want to look at the full response
        
        // License data.
        $license_data = json_decode(wp_remote_retrieve_body($response));
        
        // TODO - Do something with it.
        //var_dump($license_data);//uncomment it to look at the data
        
        if( $license_data->result == 'success' ){//Success was returned for the license activation
            //Uncomment the followng line to see the message that returned from the license server
            wp_schedule_event( time(), 'daily', 'caex_api_action_hook' );
            echo '<br />The following message was returned from the server: <span style="color:green;">'.$license_data->message . '</span>';
            echo '<div class="notice notice-success">
				<p>' . __('Visit', 'wp-caex-woocommerce') . ' <a href="' . admin_url( 'options-general.php?page=caex-api' ) . '"> ' . __('the plugin settings page and enter your Cargo Expreso API keys.', 'wp-caex-woocommerce') . '</p>
            </div>';
            echo '<style>
				.notice-error{display:none;}
            </style>';

            //Save the license key in the options table
            update_option('caex_api_key', $license_key); 
        }
        else {
            //Show error to the user. Probably entered incorrect license key.
            
            //Uncomment the followng line to see the message that returned from the license server
			wp_clear_scheduled_hook( 'caex_api_action_hook' );
            echo '<br />The following message was returned from the server: <span style="color:red;">'.$license_data->message . '</span>';
        }

    }
    /*** End of license activation ***/
    
    /*** License activate button was clicked ***/
    if (isset($_REQUEST['deactivate_license'])) {
        $license_key = $_REQUEST['caex_api_key'];

        // API query parameters
        $api_params = array(
            'slm_action' => 'slm_deactivate',
            'secret_key' => CAEX_SECRET_KEY,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode(CAEX_ITEM_REFERENCE),
        );

        // Send query to the license manager server
        $query = esc_url_raw(add_query_arg($api_params, CAEX_LICENSE_SERVER_URL));
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        // Check for error in the response
        if (is_wp_error($response)){
            echo "Unexpected Error! The query returned with an error.";
        }

        //var_dump($response);//uncomment it if you want to look at the full response
        
        // License data.
        $license_data = json_decode(wp_remote_retrieve_body($response));
        
        // TODO - Do something with it.
        //var_dump($license_data);//uncomment it to look at the data
        
        if($license_data->result == 'success'){//Success was returned for the license activation
            wp_clear_scheduled_hook( 'caex_api_action_hook' );
            //Uncomment the followng line to see the message that returned from the license server
            echo '<br />The following message was returned from the server: <span style="color:green;">'.$license_data->message . '</span>';
            echo '<style>
				.notice-error{display:none;}
            </style>';
            //Remove the licensse key from the options table. It will need to be activated again.
            update_option('caex_api_key', '');
        }
        else{
            //Show error to the user. Probably entered incorrect license key.
            delete_option( 'caex_api_key' );
			//wp_clear_scheduled_hook( 'caex_api_action_hook' );
            //Uncomment the followng line to see the message that returned from the license server
            echo '<br />The following message was returned from the server: <span style="color:red;">'.$license_data->message . '</span>';
        }
        
    }
    /**
     * End of sample license deactivation
     */
    
    ?>
    <p> <?php _e('Enter the license key for <strong>Cargo Expreso Invoicing for WooCommerce</strong> to activate it. You can find the license on the email you received when you purchased the plugin, or in your account at: https://market.digitallabs.agency.'); ?></p>
    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th style="width:100px;"><label for="caex_api_key">License Key</label></th>
                <td ><input class="regular-text" type="text" id="caex_api_key" name="caex_api_key"  value="<?php echo get_option('caex_api_key'); ?>" ></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="activate_license" value="Activate" class="button-primary" />
            <input type="submit" name="deactivate_license" value="Deactivate" class="button" />
        </p>
    </form>
    <?php
    
    echo '</div>';
}