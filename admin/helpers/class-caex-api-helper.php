<?php

namespace caex_woocommerce\Admin\Helpers;

use caex_woocommerce\Admin\Util;

class Caex_Api_Helper {

	function __construct( ) {
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
                            <ser:RemitenteDireccion>" . ( ( isset( $caex_settings['address'] ) ) ? $caex_settings['address'] : "" ) . "</ser:RemitenteDireccion>
                            <ser:RemitenteTelefono>" . ( ( isset( $caex_settings['phone'] ) ) ? $caex_settings['phone'] : "" ) .  "</ser:RemitenteTelefono>
                            <ser:CodigoPobladoOrigen>" . ( ( isset( $caex_settings['codigo_poblado_origen'] ) ) ? $caex_settings['codigo_poblado_origen'] : "" ) . "</ser:CodigoPobladoOrigen>
                            <ser:FormatoImpresion>" . ( ( isset( $caex_settings['formato_impresion'] ) ) ? $caex_settings['formato_impresion'] : "" ) . "</ser:FormatoImpresion>
                            <ser:CodigoCredito>" . ( ( isset( $caex_settings['codigo_credito'] ) ) ? $caex_settings['codigo_credito'] : "" ) . "</ser:CodigoCredito>\n";
        return $request_xml;
    }

    public function get_xml_destinatario_section( $order ) {
        $request_xml = "
                            <ser:DestinatarioNombre>" . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "</ser:DestinatarioNombre>
                            <ser:DestinatarioDireccion>" . $order->get_shipping_address_1() . " " . $order->get_shipping_address_2() . "</ser:DestinatarioDireccion>
                            <ser:DestinatarioTelefono>" . $order->get_billing_phone() . "</ser:DestinatarioTelefono>
                            <ser:DestinatarioContacto>" . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "</ser:DestinatarioContacto>
                            <ser:DestinatarioNIT>" . get_post_meta( $order->get_id(), '_billing_nit', true ) . "</ser:DestinatarioNIT>
                            <ser:ReferenciaCliente1> Orden: #" . $order->get_id() . " </ser:ReferenciaCliente1>
                            <ser:ReferenciaCliente2></ser:ReferenciaCliente2>
                            <ser:CodigoPobladoDestino>" . get_post_meta( $order->get_id(), '_caex_town_id', true ) . "</ser:CodigoPobladoDestino>
                            <ser:Observaciones>" . $order->get_customer_note() . "</ser:Observaciones>
                            <ser:CodigoReferencia>" . get_current_user_id() . "</ser:CodigoReferencia>";

        if( $order->get_payment_method() == 'cod') {
            $caex_tipo_servicio = "3"; // COD Cash on delivery
            $request_xml .= "
                            <ser:TipoServicio>" . $caex_tipo_servicio . "</ser:TipoServicio>
                            <ser:MontoCOD>" . $order->get_total() . "</ser:MontoCOD>\n";
        } else {
            $caex_tipo_servicio = "1"; //Servicio estándar
            $request_xml .= "
                            <ser:TipoServicio>" . $caex_tipo_servicio . "</ser:TipoServicio>\n";
        }

        return $request_xml;
    }

    public function get_xml_piezas_section( $order, $caex_settings ) {

        
        $pieza_counter = 1;
        $order_weight = 0;
        foreach ( $order->get_items() as $order_item_key => $order_item ) {
            $product_variation_id = $order_item['variation_id'];
            if ($product_variation_id) { 
                $product = wc_get_product($order_item['variation_id']);
            } else {
              $product = new \WC_Product($order_item['product_id']);
            }
            if( $product->has_weight() && $product->get_weight() != 0 ) {
                $order_weight += floatval($product->get_weight() * $order_item['quantity']);
            } else {
                $order_weight += floatval($caex_settings['peso_predeterminado'] * $order_item['quantity']);
            }
            
        }
        $request_xml = "
                            <ser:Piezas>
                                <ser:Pieza>
                                    <ser:NumeroPieza>" . $pieza_counter++ . "</ser:NumeroPieza>
                                    <ser:TipoPieza>" . "2" . "</ser:TipoPieza>
                                    <ser:PesoPieza>" . $order_weight . "</ser:PesoPieza>\n";
        if( $order->get_payment_method() == 'cod') {
            $request_xml .= "
                                    <ser:MontoCOD>" . $order->get_total() . "</ser:MontoCOD>\n";
        }

        $request_xml .= "
                                </ser:Pieza>
                            </ser:Piezas>\n";
        
        return $request_xml;
    }

    public function generate_tracking_request( $order, $caex_settings, $delivery_type = 1, $delivery_date = null ) {
        $fechaRecoleccion = "
                            <ser:FechaRecoleccion>" . date('Y-m-d')  . "</ser:FechaRecoleccion>"; // yyyy-mm-dd
        if( is_string( $delivery_date ) ) {
            $fechaRecoleccion = "
            <ser:FechaRecoleccion>" . $delivery_date . "</ser:FechaRecoleccion>"; // yyyy-mm-dd
        }
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
                            <ser:TipoEntrega>" . $delivery_type . "</ser:TipoEntrega>
                            " . ( ( $delivery_type == 2 ) ? $fechaRecoleccion : "" )
                            . $this->get_xml_piezas_section( $order, $caex_settings ) . "
                        </ser:DatosRecoleccion>
                    </ser:ListaRecolecciones>
                </ser:GenerarGuia>
            </soapenv:Body>
        </soapenv:Envelope>";
        return $request_xml;
    }

    public function generate_cancel_tracking_request( $caex_tracking_to_cancel, $caex_settings ) {
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:AnularGuia>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . 
                    "
                    <ser:NumeroGuia>" . $caex_tracking_to_cancel['NumeroGuia'] . "</ser:NumeroGuia>
                    <ser:CodigoCredito>" . $caex_settings['codigo_credito'] . "</ser:CodigoCredito>
                </ser:AnularGuia>
            </soapenv:Body>
        </soapenv:Envelope>";
        return $request_xml;
    }

    public function generate_update_tracking_request( $caex_tracking_to_update, $caex_settings ) {
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:ObtenerTrackingGuia>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . 
                    "
                    <ser:NumeroGuia>" . $caex_tracking_to_update['NumeroGuia'] . "</ser:NumeroGuia>
                </ser:ObtenerTrackingGuia>
            </soapenv:Body>
        </soapenv:Envelope>";
        return $request_xml;
    }

    public function generate_states_requext( $caex_settings ) {
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:ObtenerListadoDepartamentos>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . "
                </ser:ObtenerListadoDepartamentos>
            </soapenv:Body>
        </soapenv:Envelope>";
        return $request_xml;
    }

    public function generate_municipalities_requext( $caex_settings ) {
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:ObtenerListadoMunicipios>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . "
                </ser:ObtenerListadoMunicipios>
            </soapenv:Body>
        </soapenv:Envelope>";
        return $request_xml;
    }

    public function generate_towns_requext( $caex_settings ) {
        $request_xml = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"
        xmlns:ser=\"http://www.caexlogistics.com/ServiceBus\">
        <soapenv:Header/>
            <soapenv:Body>
                <ser:ObtenerListadoPoblados>\n"
                    . $this->get_xml_authentication_section( $caex_settings ) . "
                </ser:ObtenerListadoPoblados>
            </soapenv:Body>
        </soapenv:Envelope>";
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
        return $caex_transaction_id_pretty;
    }

}