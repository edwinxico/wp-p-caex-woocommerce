<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Api {

	function __construct() {

		$this->caex_helper = new Caex_Helper();
		$this->caex_settings = get_option ( 'caex_api_credentials' );
		$this->logger = new Util\Logger('dl-caex-api');
		$this->debub_mode = false;
		if( defined('CAEX_API_DEBUG_MODE') ) {
			$this->debub_mode = CAEX_API_DEBUG_MODE;
		}

	}

	function set_debub_mode($debub_mode) {
		if( $debub_mode ) {
			$this->debub_mode = $debub_mode;
		}
		$this->debub_mode = false;
	}

	function get_debub_mode() {
		if( $this->debub_mode ) {
			return $this->debub_mode;
		}
		return false;
	}

	public function requestTracking( $order ) {
        $response['result'] = true;
        $response['message'] = "Solicitud exitosa";

		// crear objeto para llamada del helper del
		$xml_requext = $this->caex_helper->generate_tracking_requext( $order, $this->caex_settings );

		// Hacer llamada a api para

		// obtener y devolver respuesta

      

		if ( $this->debub_mode ) {
			$this->logger->log( "xml enviado: "  );
			$this->logger->log( "respuesta servicio: " );
		}



        $response['dte'] = array(
            'uuid' 			=> "",
            'serie' 		=> "",
            'numero' 		=> "",
			'issued_date' 	=> "",
			'cert_date'		=> "",
			'nit' 			=> "",
        );

        return $response;
        // generar objetos para la orden
    }

	public function cancelInvoice( $order ) {

		$response['result'] = true;
        $response['message'] = "Solicitud de anulación exitosa";

		if( $respuesta_servicio->getResultado() != 1 ) {
            $response['result'] = false;
			$response['message'] = 'An error occured when requested the invoice:<br>';
			$error_index = 1;
			foreach($respuesta_servicio->getDescripcionErrores() as $descripcion_error ) {
				if( isset( $descripcion_error->mensaje_error )  ) {
					$response['message'] .= "<br>( " . $error_index . " )->" . $descripcion_error->mensaje_error;
				}
				$error_index++;
			}

            return $response;
        }

		$response['dte'] = $caex_dte_to_cancel;
		$response['dte']['canceled_date'] = $anulacion_fel->getFechaHoraAnulacion();

		return $response;
	}
}