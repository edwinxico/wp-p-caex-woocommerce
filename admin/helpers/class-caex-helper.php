<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Helper {

	function __construct() {
		$this->logger = new Util\Logger('dl-caex-api');
		$this->debub_mode = false;
		if( defined('CAEX_API_DEBUG_MODE') ) {
			$this->debub_mode = CAEX_API_DEBUG_MODE;
		}
	}

    public function get_xml_authentication_section( $caex_settings ) {
        $request_xml = "
                    <ser:Autenticacion>
                        <ser:Login>" . $caex_settings['login'] . "</ser:Login>
                        <ser:Password>" . $caex_settings['password'] . "</ser:Password>
                    </ser:Autenticacion>\n";
        return $request_xml;
    }

    public function get_xml_remitente_section( $caex_settings ) {
        $request_xml = "
                            <ser:RemitenteNombre>" . get_bloginfo( 'name' ) .  "</ser:RemitenteNombre>
                            <ser:RemitenteDireccion>" . get_option( 'woocommerce_store_address' ) . "</ser:RemitenteDireccion>
                            <ser:RemitenteTelefono>" . ( ( isset( $caex_settings['phone'] ) ) ? $caex_settings['phone'] : "" ) .  "</ser:RemitenteTelefono>
                            <ser:CodigoPobladoOrigen>" . ( ( isset( $caex_settings['codigo_poblado_origen'] ) ) ? $caex_settings['codigo_poblado_origen'] : "" ) . "</ser:CodigoPobladoOrigen>
                            <ser:TipoServicio>" . ( ( isset( $caex_settings['tipo_servicio'] ) ) ? $caex_settings['tipo_servicio'] : "" ) . "</ser:TipoServicio>
                            <ser:FormatoImpresion>" . ( ( isset( $caex_settings['formato_impresion'] ) ) ? $caex_settings['formato_impresion'] : "" ) . "</ser:FormatoImpresion>
                            <ser:CodigoCredito>" . ( ( isset( $caex_settings['codigo_credito'] ) ) ? $caex_settings['codigo_credito'] : "" ) . "</ser:CodigoCredito>
                            \n";
        return $request_xml;
    }

    public function get_xml_destinatario_section( $order ) {
        $request_xml = "
                            <ser:DestinatarioNombre>" . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "</ser:DestinatarioNombre>
                            <ser:DestinatarioDireccion>" . $order->get_shipping_address_1() . " " . $order->get_shipping_address_2() . "</ser:DestinatarioDireccion>
                            <ser:DestinatarioTelefono>" . $order->get_billing_phone() . "</ser:DestinatarioTelefono>
                            <ser:DestinatarioContacto>" . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "</ser:DestinatarioContacto>
                            <ser:DestinatarioNIT>" . get_post_meta( $order->get_id(), '_billing_nit', true ) . "</ser:DestinatarioNIT>
                            <ser:CodigoPobladoDestino>string</ser:CodigoPobladoDestino>
                            <ser:Observaciones>" . $order->get_customer_note() . "</ser:Observaciones>
                            <ser:CodigoReferencia>" . get_current_user_id() . "</ser:CodigoReferencia>
                            \n";
        /*
        $request_xml .= "
                            <ser:ReferenciaCliente1>string</ser:ReferenciaCliente1>
                            <ser:ReferenciaCliente2>string</ser:ReferenciaCliente2>";
        */
        return $request_xml;
    }

    public function get_xml_piezas_section( $order ) {

        $request_xml = "
                            <ser:Piezas>\n";
        $pieza_counter = 1;
        foreach ( $order->get_items() as $order_item_key => $order_item ) {
            if ($product_variation_id) { 
                $product = wc_get_product($order_item['variation_id']);
            } else {
              $product = new \WC_Product($order_item['product_id']);
            }
            $request_xml .= "
                                <ser:Pieza>
                                    <ser:NumeroPieza>" . $pieza_counter++ . "</ser:NumeroPieza>
                                    <ser:TipoPieza>" . "2" . "</ser:TipoPieza>
                                    <ser:PesoPieza>" . $product->get_weight() . "</ser:PesoPieza>
                                </ser:Pieza>\n";
        }

        $request_xml .= "
                            </ser:Piezas>
            \n";
        
        return $request_xml;
    }

    public function generate_tracking_requext( $order, $caex_settings ) {
        $tipoEntrega = 1;
        $fechaRecoleccion = "
                            <ser:FechaRecoleccion>" . date('Y-m-d')  . "</ser:FechaRecoleccion>"; // yyyy-mm-dd
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:GenerarGuia>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . 
                    "
                    <ser:ListaRecolecciones>
                        <ser:DatosRecoleccion>
                            <ser:RecoleccionID>" . $this->getCaexTransactionId( $order ) . "</ser:RecoleccionID>"
                            . $this->get_xml_remitente_section( $caex_settings ) .
                            $this->get_xml_destinatario_section( $order ) . "
                            <ser:TipoEntrega>" . $tipoEntrega . "</ser:TipoEntrega>
                            " . ( ( $tipoEntrega == 2 ) ? $fechaRecoleccion : "" )
                            . $this->get_xml_piezas_section( $order ) . "
                        </ser:DatosRecoleccion>
                    </ser:ListaRecolecciones>
                </ser:GenerarGuia>
            </soapenv:Body>
        </soapenv:Envelope>";

        $this->logger->log( "xml: " . $request_xml );
        return $request_xml;

    }

    public function getCaexTransactionId( $order ) {
        $order_id = $order->get_id();
        $caex_transaction_id = 1;
        $caex_transaction_id_pretty = sprintf( "%07d", $order_id ) . '_' . sprintf( "%03d", $caex_transaction_id );

        if( !get_post_meta( $order_id, 'caex_transaction_id', true ) ) {
            add_post_meta( $order_id, 'caex_transaction_id', $caex_transaction_id, true );
            add_post_meta( $order_id, 'caex_transaction_id_pretty', $caex_transaction_id_pretty, true );
        } else {
            $caex_transaction_id = get_post_meta( $order_id, 'caex_transaction_id', true );
            $caex_transaction_id++;
            $caex_transaction_id_pretty = sprintf( "%07d", $order_id ) . '_' . sprintf( "%03d", $caex_transaction_id );
            update_post_meta( $order_id, 'caex_transaction_id', $caex_transaction_id );
            update_post_meta( $order_id, 'caex_transaction_id_pretty', $caex_transaction_id_pretty );
        }
        error_log( 'caex_transaction_id: ' . $caex_transaction_id );
        error_log( 'caex_transaction_id_pretty: ' . $caex_transaction_id_pretty );
        return $caex_transaction_id_pretty;
    }

}