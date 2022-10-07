<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Api {

	function __construct() {

		$this->caex_helper = new Caex_Helper();
		$this->caex_settings = get_option ( 'caex_api_credentials' );
		$this->url = "http://ws.caexlogistics.com/wsCAEXLogisticsSB/wsCAEXLogisticsSB.asmx";
		$this->logger = new Util\Logger('dl-caex-api');
		$this->debub_mode = false;
		if( defined('CAEX_API_DEBUG_MODE') ) {
			$this->debub_mode = CAEX_API_DEBUG_MODE;
		}

	}

	function send_curl_request( $xml_request, $soap_action ) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'http://ws.caexlogistics.com/wsCAEXLogisticsSB/wsCAEXLogisticsSB.asmx',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
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
		curl_close($curl);
		return  $response;
	}

	function get_response_body($response) {
		$response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
		$xml = new \SimpleXMLElement($response);
		$body = $xml->xpath('//soapBody')[0];
		$array = json_decode(json_encode((array)$body), TRUE); 
		return  $array;
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
		$xml_request = $this->caex_helper->generate_tracking_requext( $order, $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'GenerarGuia' );
		$api_response = $this->get_response_body($api_response);
		if ( $this->debub_mode ) {
			$this->logger->log( "xml enviado: " . $xml_request );
			$this->logger->log( "respuesta servicio: "  . print_r( $api_response, true) );
		}

		// Hacer llamada a api para
		try {
			$response['result'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['ResultadoExitoso'];
			$response['result'] = filter_var( $response['result'], FILTER_VALIDATE_BOOLEAN);
			if( $response['result'] ) {
				$this->logger->log('Resultado exitoso.');
				$response['tracking_data'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion'];
			} else {
				$this->logger->log("Resultaod falso, error");
				$response['message'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['MensajeError'];
				$response['response_code'] = $api_response['GenerarGuiaResponse']['ResultadoGenerarGuia']['ListaRecolecciones']['DatosRecoleccion']['ResultadoOperacion']['CodigoRespuesta'];
				$this->logger->log("Error: " . $response['message']);
			}
		} catch (Exception $e) {
			$response['result'] = false;
			$response['message'] = 'Error al obtener la lista de departamentos';
		}
		// obtener y devolver respuesta
        return $response;
        // generar objetos para la orden
    }

	public function cancelInvoice( $order ) {
		return $response;
	}

	public function getStatesList() {
		$response = array(
			'result' => true,
			'message' => 'Solicitud exitosa',
		);
		$xml_request = $this->caex_helper->generate_states_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoDepartamentos' );
		$api_response = $this->get_response_body($api_response);
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
		$xml_request = $this->caex_helper->generate_municipalities_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoMunicipios' );
		$api_response = $this->get_response_body($api_response);
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
		$xml_request = $this->caex_helper->generate_towns_requext( $this->caex_settings );
		$api_response = $this->send_curl_request( $xml_request, 'ObtenerListadoPoblados' );
		$api_response = $this->get_response_body($api_response);
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