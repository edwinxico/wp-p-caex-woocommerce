<?php 

namespace caex_woocommerce\Admin;

use caex_woocommerce\Admin\Helpers;
use caex_woocommerce\Admin\Util;

function dl_wc_caex_sync_locations() {
    // Check for nonce security
	error_log("Llamada ajax para sinc locations");
    $nonce = sanitize_text_field( $_POST['nonce'] );
    if ( ! wp_verify_nonce( $nonce, 'dl_wc_caex_admin_script' ) ) {
        die ( 'Busted!');
    }
	$response = array(
		"result" => false,
		"message" => "Error al sincronizar localidades"
	);

	Helpers\Caex_Helper::$caexApi = new Helpers\Caex_Api();
	Helpers\Caex_Helper::$Logger = new Util\Logger('caex-woocommerce');
	$response['locations_sync_date'] = Helpers\Caex_Helper::sync_caex_locations();
	$response['result'] = 'success';
	$response['message'] = "Finalizó sync de ubicaciones CAEX exitosamente";
	error_log( "finalizó creación o sync de locations" );
    echo json_encode( $response );
    wp_die();
}
add_action( 'wp_ajax_nopriv_caex_sync_locations', __NAMESPACE__ . '\\dl_wc_caex_sync_locations' );
add_action( 'wp_ajax_caex_sync_locations', __NAMESPACE__ . '\\dl_wc_caex_sync_locations' );

function dl_wc_caex_generate_trackings() {
    // Check for nonce security
	error_log("Llamada ajax para generar trackings");

    $nonce = sanitize_text_field( $_POST['nonce'] );
    if ( ! wp_verify_nonce( $nonce, 'dl_wc_caex_admin_script' ) ) {
        die ( 'Busted!');
    }
	$response = array(
		"result" => false,
		"message" => "Error al generar guías",
		"pdfs" => array()
	);

    if(!empty($_FILES["file_0"]["name"])) {
		
			// Allowed mime types
			$fileMimes = array(
				'text/x-comma-separated-values',
				'text/comma-separated-values',
				'application/octet-stream',
				'application/vnd.ms-excel',
				'application/x-csv',
				'text/x-csv',
				'text/csv',
				'application/csv',
				'application/excel',
				'application/vnd.msexcel',
				'text/plain'
			);
			// Validate whether selected file is a CSV file
			if (!empty($_FILES['file_0']['name']) && in_array($_FILES['file_0']['type'], $fileMimes)) {
					// Open uploaded CSV file with read-only mode
					$csvFile = fopen($_FILES['file_0']['tmp_name'], 'r');
					// Skip the first line
					fgetcsv($csvFile);
					$newCsvData = array();
					
					// Parse data from CSV file line by line
					while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
						// Get row data
						$csvOrderId = $getData[0];
						$csvDeliveryType = $getData[1];
		
						// If user already exists in the database with the same email
						error_log("acá debo llamar metodo de generar guía");

						$caexApi = new Helpers\Caex_Api();
						$Logger = new Util\Logger('caex-woocommerce');
						
						$order = wc_get_order( $csvOrderId );

						if( !$order ) {
							$getData[] = __("Order not found in WooCommerce.", 'wp-caex-woocommerce');
							$newCsvData[] = $getData;
							continue;
						}

						// Crear carpeta donde en el futuro se agregan los pdfs
						if( !file_exists( WP_CONTENT_DIR . "/uploads/guias-caex/" ) ) {
							mkdir( WP_CONTENT_DIR . "/uploads/guias-caex/", 0755, true );
						}

						$caex_dte = get_post_meta( $order->get_id() , '_wc_order_caex_tracking' );
						$caex_last_action = get_post_meta( $order->get_id() , '_caex_last_action', true );
						if( $caex_dte && $caex_last_action == 'invoice_requested' ) {
							$caex_dte = json_decode( $caex_dte[count($caex_dte)-1], true );

							$getData[] = __("Order already had a tracking id.", 'wp-caex-woocommerce');
							$invoice_response = array(
								'tracking_data' => $caex_dte,
								'result' => true
							);
						} else {

							if( $csvDeliveryType == 2 ) {
								$csvRecollectionDate = $getData[2];
								$invoice_response = $caexApi->requestTracking($order, $csvDeliveryType, $csvRecollectionDate);
							} else {
								$invoice_response = $caexApi->requestTracking($order, $csvDeliveryType);
							}

							error_log("Respusta caex: " . print_r( $invoice_response, true ) );
							$getData[] = $invoice_response['message']; // Alojar si generacion de guia es exitoso o no.

							if( $invoice_response['result'] ) {
								// exito, agregar datos de invoice a la órden
								add_post_meta( $order->get_id(), '_wc_order_caex_tracking', json_encode( $invoice_response['tracking_data'] ) );
								if( get_post_meta( $order->get_id(), '_caex_last_action', true ) ) {
									update_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested' );
								} else {
									add_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested', true );
								}
								$message = sprintf( __( 'Tracking ID from Caex requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
								$order->add_order_note( $message );								
							} else {
								$order->add_order_note( "Error CAEX:" . $invoice_response['response_code'] . " - " . $invoice_response['message'] );
							}
						}

						if( $invoice_response['result'] ) {
							$response['result'] = true;
							$getData[] = $invoice_response['tracking_data']['NumeroGuia']; //Alojar numero de guía,
							$getData[] = $invoice_response['tracking_data']['RecoleccionID']; // Alojar recollectionID
							$getData[] = $invoice_response['tracking_data']['URLConsulta']; // Alojar url consulta,
							
							$tmp_pdf_url = content_url() . "/uploads/guias-caex/" . $invoice_response['tracking_data']['NumeroGuia'] . ".pdf";
							$tmp_pdf_path =  WP_CONTENT_DIR . "/uploads/guias-caex/" . $invoice_response['tracking_data']['NumeroGuia'] . ".pdf";
							file_put_contents( $tmp_pdf_path , file_get_contents( $invoice_response['tracking_data']['URLConsulta']  ));
							$response['pdfs'][] = $tmp_pdf_url;
						}
						
						$newCsvData[] = $getData;
					}
		
					// Close opened CSV file
					fclose($csvFile);
					$response['message'] = "Se han procesado el archivo correctamente.";
					$response['data'] = $newCsvData;
					error_log( "Exito" );

				
			} else {
				error_log( "Error1: Archivo no válido");
			}
		} else {
		error_log(  "Error2: No ha seleccionado ningún archivo" );  
		}

	Helpers\Caex_Helper::$caexApi = new Helpers\Caex_Api();
	Helpers\Caex_Helper::$Logger = new Util\Logger('caex-woocommerce');
    // $response['locations_sync_date'] = Helpers\Caex_Helper::generate_trackings();
	error_log( "finalizó creación de trackings" );
    echo json_encode( $response );
    wp_die();
}
add_action( 'wp_ajax_nopriv_caex_generate_trackings', __NAMESPACE__ . '\\dl_wc_caex_generate_trackings' );
add_action( 'wp_ajax_caex_generate_trackings', __NAMESPACE__ . '\\dl_wc_caex_generate_trackings' );