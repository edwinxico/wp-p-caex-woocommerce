<?php
namespace caex_woocommerce\Admin\Settings;

class Caex_Settings_Csv {
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
            'Caex Generate Trackings in Bulk', 
            'manage_woocommerce',
            'caex-csv', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'caex_csv_data' );
        ?>
        <div class="wrap">
            <h1>Cargo Expreso - Genear Guías</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'caex_csv_group' );
                do_settings_sections( 'caex-csv' );
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
            'caex_csv_group', // Option group
            'caex_csv_data', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'caex_csv_section_upload', // ID
            'Caex API - Subir Documentos', // API Password
            array( $this, 'print_section_info' ), // Callback
            'caex-csv' // Page
        );

        add_settings_field(
            'generate_trackings', // ID
            'Generar guías & PDF', // API Password 
            array( $this, 'generate_trackings_callback' ), // Callback
            'caex-csv', // Page
            'caex_csv_section_upload' // Section           
        );      

        add_settings_field(
            'combine_pdfs', 
            'Obtener PDF combinado', 
            array( $this, 'combine_pdfs_callback' ), 
            'caex-csv', 
            'caex_csv_section_upload'
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
        if( isset( $input['generate_trackings'] ) )
            $new_input['generate_trackings'] = sanitize_text_field( $input['generate_trackings'] );

        if( isset( $input['combine_pdfs'] ) )
            $new_input['combine_pdfs'] = sanitize_text_field( $input['combine_pdfs'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        print __('Add the information bellow:', 'wp-caex-woocommerce');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function generate_trackings_callback() {
        /*
        printf(
            '<input type="text" id="generate_trackings" name="caex_csv_data[generate_trackings]" value="%s" />',
            isset( $this->options['generate_trackings'] ) ? esc_attr( $this->options['generate_trackings']) : ''
        );
        */
        echo '<input id="uploadImage" type="file" accept="image/*" name="image" />
              <input class="btn btn-success btn-caex-generate-trackings" type="submit" value="Generar Guías">';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function combine_pdfs_callback() {
        /*
        printf(
            '<input type="text" id="combine_pdfs" name="caex_csv_data[combine_pdfs]" value="%s" />',
            isset( $this->options['combine_pdfs'] ) ? esc_attr( $this->options['combine_pdfs']) : ''
        );
        */

        echo '<input id="uploadImage" type="file" accept="image/*" name="image" />
              <input class="btn btn-success btn-caex-generate-pdfs" type="submit" value="Obtener PDF\'s combinados">';
        echo '<style> .settings_page_caex-csv #submit{ display: none } </style>';
    }

}