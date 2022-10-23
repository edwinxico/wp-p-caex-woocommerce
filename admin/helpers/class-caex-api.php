<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Api {

	function __construct() {

		$this->caex_api_helper = new Caex_Api_Helper();
		$this->caex_settings = get_option ( 'caex_api_credentials' );
		$this->url = "http://ws.caexlogistics.com/wsCAEXLogisticsSB/wsCAEXLogisticsSB.asmx";
		$this->logger = new Util\Logger('dl-caex-api');
		$this->debug_mode = false;
		if( defined('DL_DEBUG') ) {
			$this->debug_mode = DL_DEBUG;
		}

	}

	function send_curl_request( $xml_request, $soap_action, $url = null ) {
		if ( $this->debug_mode ) {
			$this->logger->log( "xml enviado: " . $xml_request );
		}
		if( $url == null ) {
			$url = $this->url;
		}
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $xml_request,
			CURLOPT_HTTPHEADER => array(
				'SOAPAction: http://www.caexlogistics.com/ServiceBus/' . $soap_action,
				'Content-Type: text/xml'
			),
		));

		$response = curl_exec($curl);
		if( curl_errno( $curl ) ) {
			$this->logger->log( "Error en llamada api: " . curl_error( $curl ) );
			return false;
		}

		curl_close($curl);

		if ( $this->debug_mode ) {
			$this->logger->log( "respuesta servicio: "  . print_r( $response, true) );
		}
		if( empty( $response ) ) {
			return false;
		}

		$response_body = $this->get_response_body($response);
		return  $response_body;
	}

	function get_response_body($response) {
		$response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
		$xml = new \SimpleXMLElement($response);
		$body = $xml->xpath('//soapBody')[0];
		$array = json_decode(json_encode((array)$body), TRUE); 
		if( isset( $array['AnularGuiaResponse']['AnularGuiaResult']['AnularGuia'] ) ) {
			$xml = simplexml_load_string($array['AnularGuiaResponse']['AnularGuiaResult']['AnularGuia'], "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$array['AnularGuiaResponse']['AnularGuiaResult']['AnularGuia'] = json_decode($json,TRUE);
		}
		return  $array;
	}

	function set_debug_mode($debug_mode) {
		if( $debug_mode ) {
			$this->debug_mode = $debug_mode;
		}
		$this->debug_mode = false;
	}

	function get_debug_mode() {
		if( $this->debug_mode ) {
			return $this->debug_mode;
		}
		return false;
	}

	public function requestTracking( $order, $delivery_type = 1, $delivery_date = null ) {
        $response['result'] = true;
        $response['message'] = "Solicitud exitosa";

		// crear objeto para llamada del helper del
		$xml_request = $this->caex_api_helper->generate_tracking_request( $order, $this->caex_settings, $delivery_type, $delivery_date );
		$api_response = $this->send_curl_request( $xml_request, 'GenerarGuia' );
		$this->logger->log( "respuesta array:" . print_r( $api_response, true ) );
		// Hacer llamada a api para
		try {
			$response['result'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['ResultadoExitoso'];
			$response['result'] = filter_var( $response['result'], FILTER_VALIDATE_BOOLEAN);
			if( $response['result'] ) {
				$response['tracking_data'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion'];
			} else {
				if( isset( $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['MensajeError'] ) ) {
					$response['message'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['MensajeError'];
					$response['response_code'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['CodigoRespuesta'];
				} else {
					if ( isset( $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ResultadoOperacionMultiple']['MensajeError'] ) ) {
						$response['message'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ResultadoOperacionMultiple']['MensajeError'];
						$response['response_code'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ResultadoOperacionMultiple']['CodigoRespuesta'];
					}
				}
				
			}
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		// obtener y devolver respuesta
        return $response;
        // generar objetos para la orden
    }

	public function cancelTracking( $caex_tracking_to_cancel ) {
		$response['result'] = true;
        $response['message'] = "Solicitud exitosa";

		// crear objeto para llamada del helper del
		$xml_request = $this->caex_api_helper->generate_cancel_tracking_request( $caex_tracking_to_cancel, $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'AnularGuia' );
		$this->logger->log("respuesta ya en array: " . print_r( $api_response, true) );
		// Hacer llamada a api para
		try {
			$response['result'] = $api_response['AnularGuiaResponse']['AnularGuiaResult']['AnularGuia']['Resultado']; 
			$response['result'] = filter_var( $response['result'], FILTER_VALIDATE_BOOLEAN);			
			if( $response['result'] ) {
				$response['tracking_data'] = $caex_tracking_to_cancel;
			} else {
				$response['message'] = $api_response['AnularGuiaResponse']['AnularGuiaResult']['AnularGuia']['Mensaje'];
			}
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		// obtener y devolver respuesta
        return $response;
        // generar objetos para la orden
	}

	public function updateTrackingStatus( $caex_tracking_to_update ) {
		$response['result'] = true;
        $response['message'] = __("Tracking id status update successfull.", 'wp-caex-woocommerce');

		// crear objeto para llamada del helper del
		$url = "https://tracking.caexlogistics.com/wsCAEXLogisticsSB/wsCAEXLogisticsSB.asmx";
		$xml_request = $this->caex_api_helper->generate_update_tracking_request( $caex_tracking_to_update, $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerTrackingGuia', $url );
		$this->logger->log("respuesta ya en array: " . print_r( $api_response, true) );

		if( $this->debug_mode ) {
			$response['result'] = true;
			$response['tracking_status'] = "Recolectado"; // Sin Recolectar, Recolectado, Almacenado en bodega, Entregado, Entregado - Liquidado, Devolución, Devolución Entregado, Anomalía, Ruta hacia bodega destino,
			return $response;
		}

		// Hacer llamada a api para
		if( isset( $api_response['ObtenerTrackingGuiaResponse']['ResultadoObtenerTrackingGuia']['ResultadoOperacion']['ResultadoExitoso'] ) ) {
			$response['result'] = $api_response['ObtenerTrackingGuiaResponse']['ResultadoObtenerTrackingGuia']['ResultadoOperacion']['ResultadoExitoso']; 
			$response['result'] = filter_var( $response['result'], FILTER_VALIDATE_BOOLEAN);			
			if( $response['result'] ) {
				$response['tracking_status'] = $api_response['ObtenerTrackingGuiaResponse']['ResultadoObtenerTrackingGuia']['DatosGuia']['PODStatusDes'];
			} else {
				$response['message'] = print_r( $api_response['ObtenerTrackingGuiaResponse']['ResultadoObtenerTrackingGuia']['ResultadoOperacion']['MensajeError'], true);
			}
		} else {
			$response['result'] = false;
			$response['message'] = __('Error getting tracking id status.', 'wp-caex-woocommerce');
		}
		// obtener y devolver respuesta
        return $response;
        // generar objetos para la orden
	}

	public function getStatesList() {
		$response = array(
			'result' => true,
			'message' => 'Solicitud exitosa',
		);
		$xml_request = $this->caex_api_helper->generate_states_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoDepartamentos' );
		try {
			$response['states'] = $api_response['ObtenerListadoDepartamentosResponse']['ResultadoObtenerDepartamentos']['ListadoDepartamentos']['Departamento'];
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		return $response;	
	}

	public function getMunicipalitiesList() {
		$response = array(
			'result' => true,
			'message' => 'Solicitud exitosa',
		);
		$xml_request = $this->caex_api_helper->generate_municipalities_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoMunicipios' );
		try {
			$response['municipalities'] = $api_response['ObtenerListadoMunicipiosResponse']['ResultadoObtenerMunicipios']['ListadoMunicipios']['Municipio'];
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		return $response;	
	}

	public function getTownsList() {
		$response = array(
			'result' => true,
			'message' => 'Solicitud exitosa',
		);
		$xml_request = $this->caex_api_helper->generate_towns_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoPoblados' );
		try {
			$this->logger->log("towns: " . print_r($api_response, true));
			$response['towns'] = $api_response['ObtenerListadoPobladosResponse']['ResultadoObtenerPoblados']['ListadoPoblados']['Poblado'];
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		return $response;	
	}
}