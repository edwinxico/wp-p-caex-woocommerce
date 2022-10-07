<?php 

namespace caex_woocommerce\Admin;

use caex_woocommerce\Admin\Helpers;
use caex_woocommerce\Admin\Util;

/**
 * Add a custom action to order actions select box on edit order page
 * Only added for paid orders that haven't fired this action yet
 *
 * @param array $actions order actions array to display
 * @return array - updated actions
 */
function dl_wc_add_order_meta_box_action( $actions ) {
	global $theorder;

	$caex_last_action = get_post_meta( $theorder->get_id() , '_caex_last_action', true );

    $enable_caex = true;

    foreach( $theorder->get_items( 'shipping' ) as $item_id => $item ) {
        $item_data = $item->get_data();
        $shipping_data_method_id = $item_data['method_id'];
        if( $shipping_data_method_id == "local_pickup" ) {
            $enable_caex = false;
        }
    }
	$actions['wc_caex_request_tracking'] = 'Caex | ' . __( 'Generate tracking ID', 'wp-caex-woocommerce' );

	// add "mark printed" custom action
    if( $enable_caex ) {
        if( !get_post_meta( $theorder->get_id(), '_wc_order_caex_tracking', true ) || $caex_last_action == 'invoice_cancelled' ) {
            $actions['wc_caex_request_tracking'] = 'Caex | ' . __( 'Generate tracking ID', 'wp-caex-woocommerce' );
        } else {
            $actions['wc_caex_send_invoice_to_client'] = 'Caex | ' . __( 'Send tracking ID to client', 'wp-caex-woocommerce' );
            $actions['wc_caex_cancel_invoice'] = 'Caex | ' . __( 'Cancel previosly generated tracking ID', 'wp-caex-woocommerce' );
        }
    }

	return $actions;
}
add_action( 'woocommerce_order_actions', __NAMESPACE__ . '\\dl_wc_add_order_meta_box_action' );


/**
 * Request invoice from inflie service
 * Add an order note whe action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function dl_wc_process_order_meta_box_request_tracking_action( $order ) {
    $caexApi = new Helpers\Caex_Api();
	$Logger = new Util\Logger('caex-woocommerce');
	$Logger->log("caex-woocommerce: Requesting tracking id for order: " . $order->get_id() );
    $invoice_response = $caexApi->requestTracking($order);
    if( !$invoice_response['result'] ) {
        // error ,agregar nota al pedido sobre la razón del error
        $order->add_order_note( "Error CAEX:" . $invoice_response['response_code'] . " - " . $invoice_response['message'] );
        return;
    }
	// exito, agregar datos de invoice a la órden
	add_post_meta( $order->get_id(), '_wc_order_caex_tracking', json_encode( $invoice_response['tracking_data'] ) );
	if( get_post_meta( $order->get_id(), '_caex_last_action', true ) ) {
		update_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested' );
	} else {
		add_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested', true );
	}
	$message = sprintf( __( 'Tracking ID from Caex requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
}
add_action( 'woocommerce_order_action_wc_caex_request_tracking', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_request_tracking_action' );

/**
 * Cancel invoice from inflie service
 * Add an order note whe action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function dl_wc_process_order_meta_box_cancel_invoice_action( $order ) {
	$Logger = new Util\Logger('dl-caex');
    $caexApi = new Helpers\Caex_Api();
    $invoice_response = $caexApi->cancelInvoice($order);
    if( !$invoice_response['result'] ) {
        // error ,agregar nota al pedido sobre la razón del error
        $order->add_order_note( $invoice_response['message'] );
        return;
    }

	add_post_meta( $order->get_id(), '_wc_order_caex_cancelled_trackings', json_encode( $invoice_response['dte'] ) );
	if( get_post_meta( $order->get_id(), '_caex_last_action', true ) ) {
		update_post_meta( $order->get_id(), '_caex_last_action', 'invoice_cancelled' );
	} else {
		add_post_meta( $order->get_id(), '_caex_last_action', 'invoice_cancelled', true );
	}

	$message = sprintf( __( 'Invoice cancellation requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
}
add_action( 'woocommerce_order_action_wc_caex_cancel_invoice', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_cancel_invoice_action' );

/**
 * Send invoice from inflie service
 * Add an order note whe action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function dl_wc_process_order_meta_box_send_invoice_to_client_action( $order ) {
	$Logger = new Util\Logger('dl-caex');
	$Logger->log( 'action to send invoice to client');
	$wc_emails = WC()->mailer()->get_emails();

	$email_action_response = $wc_emails['Caex_Email_Notification']->trigger( $order->get_id() );
	
	if( $email_action_response ) {
		$Logger->log( 'action to send invoice to client true');

	} else {
		$Logger->log( 'action to send invoice to client false');

	}

	$message = sprintf( __( 'Invoice sent to the client by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
}
add_action( 'woocommerce_order_action_wc_caex_send_invoice_to_client', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_send_invoice_to_client_action' );



// Adding admin js script for ajax synchronization of states
function dl_wc_caex_admin_scripts() {
	wp_enqueue_script( 'dl_wc_caex_admin_script', CAEX_API_PLUGIN_URL . 'dist/assets/js/admin.min.js', array( 'jquery' ), '1.0.0', true );
	wp_localize_script( 'dl_wc_caex_admin_script', 'dl_wc_caex_admin_script', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'dl_wc_caex_admin_script' ),
	) );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\dl_wc_caex_admin_scripts' );



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
	
	$caexApi = new Helpers\Caex_Api();
	$Logger = new Util\Logger('caex-woocommerce');
	$Logger->log("iniciando sincronización caex");
    $caexApi_states = $caexApi->getStatesList();
	$caexApi_municipalities = $caexApi->getMunicipalitiesList();
	$caexApi_towns = $caexApi->getTownsList();

	// TODO, agregar columna a tabla de estados y agregar codigo caex
	// TODO, agregar columna a tabla de municipios y agregar codigo caex
	// TODO, agregar columna a tabla de localidades y agregar codigo caex

	// TODO, elimimnar todas las filas de las tablas que notenga codigo caex

	$Logger->log("Validando si las tablas ya cuentan con las columnas, sino agregarlas" );
	
	global $wpdb;


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

	$Logger->log("Finalizó validación." );

	$dl_wc_gt_states = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_departamento" );
	$dl_wc_gt_municipalities = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_municipio" );
	$dl_wc_gt_towns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad" );

	foreach($caexApi_states['states'] as $caex_state_key => $caex_state ) {
		if( !isset( $caex_state['Nombre'] ) ) {
			error_log( "caex_state: " . print_r( $caex_state, true ) );
		}
		$caex_state_name = strtoupper( dl_strip_special_chars( $caex_state['Nombre'] ) );
		foreach($dl_wc_gt_states as $dl_wc_gt_state ) {
			$dl_wc_gt_state_name = strtoupper( dl_strip_special_chars( $dl_wc_gt_state->nombre_departamento ) );
			if( $caex_state_name == $dl_wc_gt_state_name ) {

				// Si hace match, agregar caex_id al departamento
				$wpdb->update( "{$wpdb->prefix}dl_wc_gt_departamento", array( 'codigo_caex_departamento' => $caex_state['Codigo'] ), array( 'codigo_postal_departamento' => $dl_wc_gt_state->codigo_postal_departamento ) );
				$caexApi_states['states'][$caex_state_key]['found'] = true;

				// Buscar municipios del estado
				$dl_wc_gt_municipalities = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_municipio WHERE codigo_postal_departamento = {$dl_wc_gt_state->codigo_postal_departamento}" );

				foreach( $caexApi_municipalities['municipalities'] as $caex_municipality_key => $caex_municipality ) {
					$caex_municipality_name = strtoupper( dl_strip_special_chars( $caex_municipality['Nombre'] ) );
					foreach($dl_wc_gt_municipalities as $dl_wc_gt_municipality ) {
						$dl_wc_gt_municipality_name = strtoupper( dl_strip_special_chars( $dl_wc_gt_municipality->nombre_municipio ) );
						if( $caex_municipality_name == $dl_wc_gt_municipality_name ) {

							$caexApi_municipalities['municipalities'][$caex_municipality_key]['found'] = true;

							// Si hace match, agregar caex_id al municipio
							$wpdb->update( "{$wpdb->prefix}dl_wc_gt_municipio", array( 'codigo_caex_municipio' => $caex_municipality['Codigo'] ), array( 'codigo_postal_municipio' => $dl_wc_gt_municipality->codigo_postal_municipio ) );

							// Buscar localidades del municipio
							$dl_wc_gt_towns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad WHERE codigo_postal_municipio = {$dl_wc_gt_municipality->codigo_postal_municipio}" );

							foreach( $caexApi_towns['towns'] as $caex_town_key => $caex_town ) {
								$caex_town_name = strtoupper( dl_strip_special_chars( $caex_town['Nombre'] ) );
								foreach($dl_wc_gt_towns as $dl_wc_gt_town ) {
									$dl_wc_gt_town_name = strtoupper( dl_strip_special_chars( $dl_wc_gt_town->nombre_ciudad ) );
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
	error_log( "finalizó creación o sync de locations" );

	// loop en caex locations para agregar los que no se hayan agregado previamente


    echo json_encode( $response );
    wp_die();
}
add_action( 'wp_ajax_nopriv_caex_sync_locations', __NAMESPACE__ . '\\dl_wc_caex_sync_locations' );
add_action( 'wp_ajax_caex_sync_locations', __NAMESPACE__ . '\\dl_wc_caex_sync_locations' );




function dl_save_caex_town_id( $order_id ) {
	error_log("entering saving method");
    $order = new \WC_Order( $order_id );
	error_log("after creating order");
    $order_shipping_postcode = $order->get_shipping_postcode();
    global $wpdb;
	error_log("before wppdb request");
	$dl_wc_gt_town = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad WHERE codigo_postal_ciudad = {$order_shipping_postcode}" );
	if( $dl_wc_gt_town ) {
		error_log("town found, returned caex town id");
		update_post_meta( $order_id, '_caex_town_id', $dl_wc_gt_town->codigo_caex_ciudad );
	}
}
add_action( 'woocommerce_new_order', __NAMESPACE__ . '\\dl_save_caex_town_id', 1, 1 );

function misha_editable_order_meta_billing( $order ){
	$caex_town_id = get_post_meta( $order->get_id(), '_caex_town_id', true );
    ?>
		<div class="address">
			<p<?php if( ! $caex_town_id ) { echo ' class="none_set"'; } ?>>
				<strong>CAEX Destino:</strong>
				<?php echo $caex_town_id ? esc_html( $caex_town_id ) : 'CAEX Town not set.' ?>
			</p>
		</div>
		<div class="edit_address">
			<?php
				woocommerce_wp_text_input( array(
					'id' => '_caex_town_id',
					'label' => 'CAEX Codigo de Ciudad',
                    'placeholder' => '00',
					'wrapper_class' => 'form-field-wide',
					'value' => $caex_town_id,
					'description' => 'Código de acuerdo a catálogo de CAEX.',
				) );
			?>
		</div>
	<?php
	
	$caex_dte = get_post_meta( $order->get_id() , '_wc_order_caex_tracking' );
	$caex_last_action = get_post_meta( $order->get_id() , '_caex_last_action', true );
	?>
		<div class="address">
			<p<?php if( ! $caex_dte ) { echo ' class="none_set"'; } ?>>
				<h3><?php _e('CAEX Tracking Id Information', 'wp-caex-woocommerce') ?></h3>
				<?php

                if( !$caex_dte || $caex_last_action == 'invoice_cancelled'  ) {
                    _e("No Infile invoice requested yet.", true);   
                } else {
					$caex_dte = json_decode( $caex_dte[count($caex_dte)-1], true );
					$caex_recollection_id = get_post_meta( $order->get_id(), 'caex_transaction_id_pretty', true);
					echo '<p><strong>' . __('RecolleccionID', 'wp-caex-woocommerce') . ':</strong> <a href="' . $caex_dte['URLRecoleccion'] . '">' . $caex_recollection_id . '</a></p>';
					echo '<p><strong> ' . __('NumeroGuia', 'wp-caex-woocommerce') . ':</strong> <a href="' . $caex_dte['URLConsulta'] . '">' . $caex_dte['NumeroGuia'] . '</a></p>';
					echo '<p><strong>' . __('MontoTarifa', 'wp-caex-woocommerce') . ':</strong> ' . $caex_dte['MontoTarifa'] . '</p>';
					echo '<p><strong>' . __('NumeroPieza' , 'wp-caex-woocommerce') . ':</strong>' . $caex_dte['NumeroPieza'] . '</p>';
                }
                ?>
			</p>
		</div>
		<?php
			$caex_cancelled_dtes = get_post_meta( $order->get_id() , '_wc_order_caex_cancelled_trackings' );
			if( $caex_cancelled_dtes ) {
				?>
		<div class="address">
			<p<?php if( ! $caex_dte ) { echo ' class="none_set"'; } ?>>
				<h3><?php _e('Cancelled Tracking IDs', 'wp-caex-woocommerce') ?></h3>
				<?php
				$caex_cancelled_dte_index = 1;
				foreach( $caex_cancelled_dtes as $caex_cancelled_dte ) {
					$caex_cancelled_dte = json_decode( $caex_cancelled_dte, true );
                    echo '<p>( ' . $caex_cancelled_dte_index++ .' ) <a href="https://report.feel.com.gt/ingfacereport/ingfacereport_documento?uuid=' . $caex_cancelled_dte['uuid'] . '" target="_blank">' . $caex_cancelled_dte['uuid'] . '</a></p>';
				}
                ?>
			</p>
		</div>
		<?php
			}
		?>
	<?php
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', __NAMESPACE__ . '\\misha_editable_order_meta_billing' );

function save_custom_shipping_fields( $order_id ) {
    if ( isset( $_POST['_caex_town_id'] ) && ! empty( $_POST['_caex_town_id'] ) ) {
        update_post_meta( $order_id, '_caex_town_id', sanitize_text_field( $_POST['_caex_town_id'] ) );
    }
}
add_action( 'woocommerce_update_order', __NAMESPACE__ . '\\save_custom_shipping_fields' );


function dl_strip_special_chars($cadena){
		
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