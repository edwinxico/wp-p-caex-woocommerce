<?php
namespace caex_woocommerce\Admin\Settings;

class Caex_Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Caex API', 
            'manage_woocommerce',
            'caex-api', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'caex_api_credentials' );
        ?>
        <div class="wrap">
            <h1>Cargo Expreso API</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'caex_api_group' );
                do_settings_sections( 'caex-api' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'caex_api_group', // Option group
            'caex_api_credentials', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'caex_api_section_credentials', // ID
            'Caex API - Credenciales', // API Password
            array( $this, 'print_section_info' ), // Callback
            'caex-api' // Page
        );

        add_settings_field(
            'login', // ID
            'Login', // API Password 
            array( $this, 'login_callback' ), // Callback
            'caex-api', // Page
            'caex_api_section_credentials' // Section           
        );      

        add_settings_field(
            'password', 
            'Password', 
            array( $this, 'password_callback' ), 
            'caex-api', 
            'caex_api_section_credentials'
        );  
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['login'] ) )
            $new_input['login'] = sanitize_text_field( $input['login'] );

        if( isset( $input['password'] ) )
            $new_input['password'] = sanitize_text_field( $input['password'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print __('Add the information bellow:', 'wp-caex-woocommerce');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function login_callback()
    {
        printf(
            '<input type="text" id="login" name="caex_api_credentials[login]" value="%s" />',
            isset( $this->options['login'] ) ? esc_attr( $this->options['login']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function password_callback()
    {
        printf(
            '<input type="text" id="password" name="caex_api_credentials[password]" value="%s" />',
            isset( $this->options['password'] ) ? esc_attr( $this->options['password']) : ''
        );
    }

}