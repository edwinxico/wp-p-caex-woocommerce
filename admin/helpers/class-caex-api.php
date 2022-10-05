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

	public function requestInvoice( $order ) {
        $response['result'] = true;
        $response['message'] = "Solicitud exitosa";
        include_once("caex-sdk-php/clases.php"); 

        $documento_fel = new \DocumentoFel();
	    $documento_fel->setDatosEmisor( $this->caex_helper->getDatosEmisor( $this->caex_settings ) );
        $documento_fel->setDatosGenerales( $this->caex_helper->getDatosGenerales() );
        $documento_fel->setDatosReceptor( $this->caex_helper->getDatosReceptor( $order ) );
        $documento_fel->setFrases( $this->caex_helper->getFrases() );
		$this->logger->log('before setting items array');
        $documento_fel->setItemsArray( $this->caex_helper->getItemsArray( $order ) );
		$documento_fel->setImpuestosResumen( $this->caex_helper->getImpuestosResumen( $order ) );
		$documento_fel->setTotales( $this->caex_helper->getTotales( $order ) );

		foreach( $this->caex_helper->getAdendaX() as $adenda ) {
			$documento_fel->setAdendaX( $adenda );

		}
		$generar_xml = new \GenerarXml();
		$respuesta = $generar_xml->ToXml($documento_fel);

		$conexion = $this->caex_helper->getConexion($this->caex_settings, $order);
	
		$servicio = new \ServicioFel();
		$respuesta_servicio = $servicio->ProcesoUnificado($conexion,$respuesta->getXml());

		if ( $this->debub_mode ) {
			$this->logger->log( "xml enviado: " . $respuesta->getXml() );
			$this->logger->log( "respuesta servicio: " . print_r($respuesta_servicio, true) );
		}

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

        $response['dte'] = array(
            'uuid' 			=> $respuesta_servicio->getUuid(),
            'serie' 		=> $respuesta_servicio->getSerie(),
            'numero' 		=> $respuesta_servicio->getNumero(),
			'issued_date' 	=> $documento_fel->getDatosGenerales()->getFechaHoraEmision(),
			'cert_date'		=> $respuesta_servicio->getFecha(),
			'nit' 			=> $documento_fel->getDatosReceptor()->getIdReceptor(),
        );

        return $response;
        // generar objetos para la orden
    }

	public function cancelInvoice( $order ) {

		$response['result'] = true;
        $response['message'] = "Solicitud de anulación exitosa";

    	include_once("caex-sdk-php/clases.php"); 

		$generar_xml = new \GenerarXml();
		$caex_dte = get_post_meta( $order->get_id() , '_wc_order_caex_dte' );

		$caex_dte_to_cancel = json_decode( $caex_dte[count($caex_dte)-1], true );

		$anulacion_fel = $this->caex_helper->getAnulacionFel( $caex_dte_to_cancel, $this->caex_settings );
		$respuesta = $generar_xml->ToXml( $anulacion_fel );
		$conexion = $this->caex_helper->getConexion($this->caex_settings, $order);

		$servicio = new \ServicioFel();
		$respuesta_servicio = $servicio->ProcesoUnificado($conexion,$respuesta->getXml());

		if ( $this->debub_mode ) {
			$this->logger->log( "xml enviado: " . $respuesta->getXml() );
			$this->logger->log( "respuesta servicio: " . print_r($respuesta_servicio, true) );
		}

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