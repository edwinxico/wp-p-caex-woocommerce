<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Helper {

	function __construct( ) {
		$this->logger = new Util\Logger('dl-caex-api');
		$this->debub_mode = false;
		if( defined('CAEX_API_DEBUG_MODE') ) {
			$this->debub_mode = CAEX_API_DEBUG_MODE;
		}
	}

    static $caexApi;
    static $Logger;

    public static function sync_caex_locations(){
        self::$Logger->log("iniciando sincronización caex");
        $caexApi_states = self::$caexApi->getStatesList();
        $caexApi_municipalities = self::$caexApi->getMunicipalitiesList();
        $caexApi_towns = self::$caexApi->getTownsList();
        self::$Logger->log("Validando si las tablas ya cuentan con las columnas, sino agregarlas" );
        global $wpdb;
        $mysql_now = $wpdb->get_results( 'SELECT DATE_SUB(NOW(), INTERVAL 1 MINUTE);', 'ARRAY_A' );
        $caex_api_credentials = get_option('caex_api_credentials');
        $caex_api_credentials['locations_sync_date'] = array_values( $mysql_now[0] )[0] ;			
        update_option('caex_api_credentials', $caex_api_credentials);

        $row = $wpdb->get_results(  "SHOW COLUMNS FROM `{$wpdb->prefix}dl_wc_gt_departamento` LIKE 'codigo_caex_departamento';" );
        if(empty($row)){
            $wpdb->query("ALTER TABLE {$wpdb->prefix}dl_wc_gt_departamento ADD codigo_caex_departamento varchar(6) NOT NULL DEFAULT '00'");
        }

        $row = $wpdb->get_results(  "SHOW COLUMNS FROM `{$wpdb->prefix}dl_wc_gt_municipio` LIKE 'codigo_caex_municipio';"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE {$wpdb->prefix}dl_wc_gt_municipio ADD codigo_caex_municipio varchar(6) NOT NULL DEFAULT '00'");
        }

        $row = $wpdb->get_results(  "SHOW COLUMNS FROM `{$wpdb->prefix}dl_wc_gt_ciudad` LIKE 'codigo_caex_ciudad';"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE {$wpdb->prefix}dl_wc_gt_ciudad ADD codigo_caex_ciudad varchar(6) NOT NULL DEFAULT '00'");
        }

        self::$Logger->log("Finalizó validación." );

        $dl_wc_gt_states = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_departamento" );
        $dl_wc_gt_municipalities = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_municipio" );
        $dl_wc_gt_towns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad" );

        foreach($caexApi_states['states'] as $caex_state_key => $caex_state ) {
            if( !isset( $caex_state['Nombre'] ) ) {
                error_log( "caex_state: " . print_r( $caex_state, true ) );
            }
            $caex_state_name = strtoupper( self::dl_strip_special_chars( $caex_state['Nombre'] ) );
            foreach($dl_wc_gt_states as $dl_wc_gt_state ) {
                $dl_wc_gt_state_name = strtoupper( self::dl_strip_special_chars( $dl_wc_gt_state->nombre_departamento ) );
                if( $caex_state_name == $dl_wc_gt_state_name ) {

                    // Si hace match, agregar caex_id al departamento
                    $wpdb->update( "{$wpdb->prefix}dl_wc_gt_departamento", array( 'codigo_caex_departamento' => $caex_state['Codigo'] ), array( 'codigo_postal_departamento' => $dl_wc_gt_state->codigo_postal_departamento ) );
                    $caexApi_states['states'][$caex_state_key]['found'] = true;

                    // Buscar municipios del estado
                    $dl_wc_gt_municipalities = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_municipio WHERE codigo_postal_departamento = {$dl_wc_gt_state->codigo_postal_departamento}" );

                    foreach( $caexApi_municipalities['municipalities'] as $caex_municipality_key => $caex_municipality ) {
                        $caex_municipality_name = strtoupper( self::dl_strip_special_chars( $caex_municipality['Nombre'] ) );
                        foreach($dl_wc_gt_municipalities as $dl_wc_gt_municipality ) {
                            $dl_wc_gt_municipality_name = strtoupper( self::dl_strip_special_chars( $dl_wc_gt_municipality->nombre_municipio ) );
                            if( $caex_municipality_name == $dl_wc_gt_municipality_name ) {

                                $caexApi_municipalities['municipalities'][$caex_municipality_key]['found'] = true;

                                // Si hace match, agregar caex_id al municipio
                                $wpdb->update( "{$wpdb->prefix}dl_wc_gt_municipio", array( 'codigo_caex_municipio' => $caex_municipality['Codigo'] ), array( 'codigo_postal_municipio' => $dl_wc_gt_municipality->codigo_postal_municipio ) );

                                // Buscar localidades del municipio
                                $dl_wc_gt_towns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad WHERE codigo_postal_municipio = {$dl_wc_gt_municipality->codigo_postal_municipio}" );

                                foreach( $caexApi_towns['towns'] as $caex_town_key => $caex_town ) {
                                    $caex_town_name = strtoupper( self::dl_strip_special_chars( $caex_town['Nombre'] ) );
                                    foreach($dl_wc_gt_towns as $dl_wc_gt_town ) {
                                        $dl_wc_gt_town_name = strtoupper( self::dl_strip_special_chars( $dl_wc_gt_town->nombre_ciudad ) );
                                        if( $caex_town_name == $dl_wc_gt_town_name ) {

                                            $caexApi_towns['towns'][$caex_town_key]['found'] = true;
                                            // Si hace match, agregar caex_id a la localidad
                                            $wpdb->update( "{$wpdb->prefix}dl_wc_gt_ciudad", array( 'codigo_caex_ciudad' => $caex_town['Codigo'] ), array( 'codigo_postal_ciudad' => $dl_wc_gt_town->codigo_postal_ciudad ) );

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return  $caex_api_credentials['locations_sync_date'];
    }

    public static function dl_strip_special_chars($cadena){
		
        $cadena = preg_replace('/\s+/', ' ', $cadena);
        //Reemplazamos la A y a
        $cadena = str_replace(
        array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
        array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
        $cadena
        );
    
        //Reemplazamos la E y e
        $cadena = str_replace(
        array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
        array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
        $cadena );
    
        //Reemplazamos la I y i
        $cadena = str_replace(
        array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
        array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
        $cadena );
    
        //Reemplazamos la O y o
        $cadena = str_replace(
        array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
        array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
        $cadena );
    
        //Reemplazamos la U y u
        $cadena = str_replace(
        array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
        array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
        $cadena );
    
        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
        array('Ñ', 'ñ', 'Ç', 'ç'),
        array('N', 'n', 'C', 'c'),
        $cadena
        );
        
        return $cadena;
    }

}