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
            <h1>iFacere API</h1>
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
            'usuario_firma', // ID
            'UsuarioFirma', // API Password 
            array( $this, 'usuario_firma_callback' ), // Callback
            'caex-api', // Page
            'caex_api_section_credentials' // Section           
        );      

        add_settings_field(
            'llave_firma', 
            'LlaveFirma', 
            array( $this, 'llave_firma_callback' ), 
            'caex-api', 
            'caex_api_section_credentials'
        );  

        add_settings_field(
            'usuario_api', // ID
            'UsuarioAPI', // API Password 
            array( $this, 'usuario_api_callback' ), // Callback
            'caex-api', // Page
            'caex_api_section_credentials' // Section           
        );      

        add_settings_field(
            'llave_api', 
            'LlaveAPI', 
            array( $this, 'llave_api_callback' ), 
            'caex-api', 
            'caex_api_section_credentials'
        );  

        /*
        add_settings_field(
            'identificador', 
            'Identificador', 
            array( $this, 'identificador_callback' ), 
            'caex-api', 
            'caex_api_section_credentials'
        );   
        */

        // Sección de campos de datos de Emisor que no se obtienen de WooCommerce
        add_settings_section(
            'caex_api_section_emisor', // ID
            'Caex API - Emisor', // API Password
            array( $this, 'print_section_info' ), // Callback
            'caex-api' // Page
        );

        add_settings_field(
            'nit_emisor', 
            'NITEmisor', 
            array( $this, 'nit_emisor_callback' ), 
            'caex-api', 
            'caex_api_section_emisor'
        );

        add_settings_field(
            'email_emisor', 
            'Email de Emisor', 
            array( $this, 'email_emisor_callback' ), 
            'caex-api', 
            'caex_api_section_emisor'
        );

        add_settings_field(
            'nombre_emisor', 
            'Nombre de Emisor', 
            array( $this, 'nombre_emisor_callback' ), 
            'caex-api', 
            'caex_api_section_emisor'
        );

        add_settings_field(
            'nombre_comercial', 
            'Nombre Comercial', 
            array( $this, 'nombre_comercial_callback' ), 
            'caex-api', 
            'caex_api_section_emisor'
        );

        // Sección de campos adicionales
        add_settings_section(
            'caex_api_section_misc', // ID
            'Caex API - Misc', // API Password
            array( $this, 'print_section_info' ), // Callback
            'caex-api' // Page
        );

        add_settings_field(  
            'enable_nit',  
            'Seleccionar para habilitar campo de NIT',  
            array( $this, 'enable_nit_callback' ),   
            'caex-api',  
            'caex_api_section_misc'  
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
        if( isset( $input['usuario_firma'] ) )
            $new_input['usuario_firma'] = sanitize_text_field( $input['usuario_firma'] );

        if( isset( $input['llave_firma'] ) )
            $new_input['llave_firma'] = sanitize_text_field( $input['llave_firma'] );

        if( isset( $input['usuario_api'] ) )
            $new_input['usuario_api'] = sanitize_text_field( $input['usuario_api'] );

        if( isset( $input['llave_api'] ) )
            $new_input['llave_api'] = sanitize_text_field( $input['llave_api'] );

            /*
        if( isset( $input['identificador'] ) )
            $new_input['identificador'] = sanitize_text_field( $input['identificador'] );
*/
        if( isset( $input['nit_emisor'] ) )
            $new_input['nit_emisor'] = sanitize_text_field( $input['nit_emisor'] );

        if( isset( $input['enable_nit'] ) )
            $new_input['enable_nit'] = sanitize_text_field( $input['enable_nit'] );

        if( isset( $input['email_emisor'] ) )
            $new_input['email_emisor'] = sanitize_text_field( $input['email_emisor'] );

        if( isset( $input['nombre_emisor'] ) )
            $new_input['nombre_emisor'] = sanitize_text_field( $input['nombre_emisor'] );

        if( isset( $input['nombre_comercial'] ) )
            $new_input['nombre_comercial'] = sanitize_text_field( $input['nombre_comercial'] );

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
    public function usuario_firma_callback()
    {
        printf(
            '<input type="text" id="usuario_firma" name="caex_api_credentials[usuario_firma]" value="%s" />',
            isset( $this->options['usuario_firma'] ) ? esc_attr( $this->options['usuario_firma']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function llave_firma_callback()
    {
        printf(
            '<input type="text" id="llave_firma" name="caex_api_credentials[llave_firma]" value="%s" />',
            isset( $this->options['llave_firma'] ) ? esc_attr( $this->options['llave_firma']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function usuario_api_callback()
    {
        printf(
            '<input type="text" id="usuario_api" name="caex_api_credentials[usuario_api]" value="%s" />',
            isset( $this->options['usuario_api'] ) ? esc_attr( $this->options['usuario_api']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function llave_api_callback()
    {
        printf(
            '<input type="text" id="llave_api" name="caex_api_credentials[llave_api]" value="%s" />',
            isset( $this->options['llave_api'] ) ? esc_attr( $this->options['llave_api']) : ''
        );
    }


    /*
     * Get the settings option array and print one of its values
     
    public function identificador_callback()
    {
        printf(
            '<input type="text" id="identificador" name="caex_api_credentials[identificador]" value="%s" />',
            isset( $this->options['identificador'] ) ? esc_attr( $this->options['identificador']) : ''
        );
    }
    */

    /** 
     * Get the settings option array and print one of its values
     */
    public function nit_emisor_callback()
    {
        printf(
            '<input type="text" id="nit_emisor" name="caex_api_credentials[nit_emisor]" value="%s" />',
            isset( $this->options['nit_emisor'] ) ? esc_attr( $this->options['nit_emisor']) : ''
        );
    }

    public function enable_nit_callback() {

        printf(
            '<input type="checkbox" id="enable_nit" name="caex_api_credentials[enable_nit]" value="1" ' . checked(1, isset( $this->options['enable_nit'] ) ? esc_attr( $this->options['enable_nit']) : '', true) . ' />'
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function email_emisor_callback()
    {
        printf(
            '<input type="text" id="email_emisor" name="caex_api_credentials[email_emisor]" value="%s" />',
            isset( $this->options['email_emisor'] ) ? esc_attr( $this->options['email_emisor']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function nombre_emisor_callback()
    {
        printf(
            '<input type="text" id="nombre_emisor" name="caex_api_credentials[nombre_emisor]" value="%s" />',
            isset( $this->options['nombre_emisor'] ) ? esc_attr( $this->options['nombre_emisor']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function nombre_comercial_callback()
    {
        printf(
            '<input type="text" id="nombre_comercial" name="caex_api_credentials[nombre_comercial]" value="%s" />',
            isset( $this->options['nombre_comercial'] ) ? esc_attr( $this->options['nombre_comercial']) : ''
        );
    }


}