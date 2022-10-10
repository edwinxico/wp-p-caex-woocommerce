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
		"message" => "Error al sincronizar localidades"
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
						$csvRecollectionDate = $getData[1];
						$csvDeliveryType = $getData[2];
		
						// If user already exists in the database with the same email
						error_log("acá debo llamar metodo de generar guía");
						error_log("orderid: " . $csvOrderId );
						error_log("recollectionDate: " . $csvRecollectionDate );
						error_log("deliveryType: " . $csvDeliveryType );

						$getData[] = 'New Column';
				        $newCsvData[] = $getData;
					}
		
					// Close opened CSV file
					fclose($csvFile);
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