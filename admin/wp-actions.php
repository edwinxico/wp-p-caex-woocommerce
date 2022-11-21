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

	// add "mark printed" custom action
    if( $enable_caex ) {
        if( !get_post_meta( $theorder->get_id(), '_wc_order_caex_tracking', true ) || $caex_last_action == 'invoice_cancelled' ) {
            $actions['wc_caex_request_tracking_1'] = 'Caex | ' . __( 'Generate tracking ID (Normal delivery)', 'wp-caex-woocommerce' );
			$actions['wc_caex_request_tracking_2'] = 'Caex | ' . __( 'Generate tracking ID (SameDay delivery)', 'wp-caex-woocommerce' );
        } else {
			$actions['wc_caex_update_tracking_status'] = 'Caex | ' . __( 'Update tracking ID status', 'wp-caex-woocommerce' );
            $actions['wc_caex_cancel_tracking'] = 'Caex | ' . __( 'Cancel previosly generated tracking ID', 'wp-caex-woocommerce' );
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
function dl_wc_process_order_meta_box_request_tracking_action( $order, $delivery_type = 1 ) {
    $caexApi = new Helpers\Caex_Api();
	$Logger = new Util\Logger('dl-caex');
	$Logger->log("caex-woocommerce: Requesting tracking id for order: " . $order->get_id() );
    $invoice_response = $caexApi->requestTracking($order, $delivery_type);
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

function dl_wc_process_order_meta_box_request_tracking_action_1( $order ) {
	global $woocommerce_settings;
	$Logger = new Util\Logger('dl-caex');
	$Logger->log( "woocommerce_settings: " . get_option( 'wc_settings_tab_demo_title', true )  );
	dl_wc_process_order_meta_box_request_tracking_action( $order );
}
add_action( 'woocommerce_order_action_wc_caex_request_tracking_1', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_request_tracking_action_1' );

function dl_wc_process_order_meta_box_request_tracking_action_2( $order ) {
	dl_wc_process_order_meta_box_request_tracking_action( $order, 2 );
}
add_action( 'woocommerce_order_action_wc_caex_request_tracking_2', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_request_tracking_action_2' );


/**
 * Cancel invoice from inflie service
 * Add an order note whe action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function dl_wc_process_order_meta_box_cancel_tracking_action( $order ) {
	$Logger = new Util\Logger('dl-caex');
    $caexApi = new Helpers\Caex_Api();

	$caex_tracking_to_cancel = get_post_meta( $order->get_id(), '_wc_order_caex_tracking' );
	$caex_tracking_to_cancel = json_decode( $caex_tracking_to_cancel[count($caex_tracking_to_cancel)-1], true );

    $invoice_response = $caexApi->cancelTracking($caex_tracking_to_cancel);
    if( !$invoice_response['result'] ) {
        // error ,agregar nota al pedido sobre la razón del error
        $order->add_order_note( $invoice_response['message'] );
        return;
    }

	add_post_meta( $order->get_id(), '_wc_order_caex_cancelled_trackings', json_encode( $invoice_response['tracking_data'] ) );
	if( get_post_meta( $order->get_id(), '_caex_last_action', true ) ) {
		update_post_meta( $order->get_id(), '_caex_last_action', 'invoice_cancelled' );
	} else {
		add_post_meta( $order->get_id(), '_caex_last_action', 'invoice_cancelled', true );
	}

	$message = sprintf( __( 'Tracking ID cancellation requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
}
add_action( 'woocommerce_order_action_wc_caex_cancel_tracking', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_cancel_tracking_action' );

// Función para obtener estado de caex api y actualizar orden
function dl_wc_update_tracking_status( $order ) {
	$Logger = new Util\Logger('dl-caex');
    $caexApi = new Helpers\Caex_Api();

	$caex_tracking_to_update = get_post_meta( $order->get_id(), '_wc_order_caex_tracking' );
	$caex_tracking_to_update = json_decode( $caex_tracking_to_update[count($caex_tracking_to_update)-1], true );

    $invoice_response = $caexApi->updateTrackingStatus($caex_tracking_to_update);
    if( !$invoice_response['result'] ) {
        // error ,agregar nota al pedido sobre la razón del error
        $order->add_order_note( "CAEX | " . $invoice_response['message'] );
        return;
    }

	$caex_tracking_to_update['status'] = $invoice_response['tracking_status'];
	$caex_tracking_to_update['status_desc'] = $invoice_response['tracking_status_desc'];

	if( $caex_tracking_to_update['status'] == 'R' ) {
		//Recolectado, mover orden a En ruta de entrega
		$order->update_status( 'on-route' );
	}
	if( $caex_tracking_to_update['status'] == 'A' || $caex_tracking_to_update['status'] == 'AA') {
		//Entregado, mover orden a completado
		$order->update_status( 'completed' );
	}
	update_post_meta( $order->get_id(), '_wc_order_caex_tracking', json_encode( $caex_tracking_to_update ) );
	
	$message = sprintf( __( 'Invoice status update requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( "CAEX | " . $message );
}
add_action( 'woocommerce_order_action_wc_caex_update_tracking_status', __NAMESPACE__ . '\\dl_wc_update_tracking_status' );

// Adding admin js script for ajax synchronization of states
function dl_wc_caex_admin_scripts() {
	wp_enqueue_script( 'pdf-lib', CAEX_API_PLUGIN_URL . 'dist/node_modules/pdf-lib/dist/pdf-lib.min.js', array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'downloadjs', CAEX_API_PLUGIN_URL . 'dist/node_modules/downloadjs/download.min.js', array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'dl_wc_caex_admin_script', CAEX_API_PLUGIN_URL . 'dist/assets/js/admin.min.js', array( 'jquery' ), '1.0.0', true );
	wp_localize_script( 'dl_wc_caex_admin_script', 'dl_wc_caex_admin_script', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'dl_wc_caex_admin_script' ),
	) );
	wp_enqueue_style( 'custom-css', CAEX_API_PLUGIN_URL . 'dist/assets/css/custom-admin.css', array(), filemtime( CAEX_API_PLUGIN_PATH . 'dist/assets/css/custom-admin.css' ) );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\dl_wc_caex_admin_scripts' );

/* Add client Scripts */
function dl_p_cuotas_load_plugin_scripts() {
	wp_enqueue_script('dl_wc_caex_js', CAEX_API_PLUGIN_URL . 'dist/assets/js/app.min.js', array( 'jquery' ), filemtime( CAEX_API_PLUGIN_URL . 'dist/assets/js/app.min.js' ), true);
}
add_action( 'wp_enqueue_scripts',__NAMESPACE__ . '\\dl_p_cuotas_load_plugin_scripts');

function add_type_attribute($tag, $handle, $src) {
    // if not your script, do nothing and return original $tag
    if ( 'dl_wc_caex_admin_script' !== $handle ) {
        return $tag;
    }
    // change the script tag by adding type="module" and return it.
    $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    return $tag;
}
add_filter('script_loader_tag', __NAMESPACE__ . '\\add_type_attribute' , 10, 3);

function dl_save_caex_town_id( $order_id ) {
	$Logger = new Util\Logger('dl-caex');
	$Logger->log("entering saving method");

    $order = new \WC_Order( $order_id );
	$Logger->log("after creating order");
    $order_shipping_postcode = $order->get_shipping_postcode();
    global $wpdb;
	$Logger->log("before wppdb request");
	$dl_wc_gt_town = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dl_wc_gt_ciudad WHERE codigo_postal_ciudad = {$order_shipping_postcode}" );
	if( $dl_wc_gt_town ) {
		$Logger->log("town found, returned caex town id");
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
                    _e("No tacking id requested yet.", true);   
                } else {
					$caex_dte = json_decode( $caex_dte[count($caex_dte)-1], true );
					echo '<p><strong>' . __('RecolleccionID', 'wp-caex-woocommerce') . ':</strong> <a href="' . $caex_dte['URLRecoleccion'] . '">' . $caex_dte['RecoleccionID'] . '</a></p>';
					echo '<p><strong> ' . __('NumeroGuia', 'wp-caex-woocommerce') . ':</strong> <a href="' . $caex_dte['URLConsulta'] . '">' . $caex_dte['NumeroGuia'] . '</a></p>';
					//echo '<p><strong>' . __('MontoTarifa', 'wp-caex-woocommerce') . ':</strong> ' . $caex_dte['MontoTarifa'] . '</p>';
					//echo '<p><strong>' . __('NumeroPieza' , 'wp-caex-woocommerce') . ':</strong>' . $caex_dte['NumeroPieza'] . '</p>';
					if( isset( $caex_dte['status'] ) ) {
						echo '<p><strong>' . __('Status', 'wp-caex-woocommerce') . ':</strong> ' . $caex_dte['status'] . " - " . $caex_dte['status_desc'] . '</p>';
					}
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
					if( isset( $caex_cancelled_dte['RecoleccionID'] ) && isset( $caex_cancelled_dte['NumeroGuia'])) {
						echo '<p>( ' . $caex_cancelled_dte_index++ .' ) <a href="' . $caex_cancelled_dte['URLRecoleccion'] . '" target="_blank">' . $caex_cancelled_dte['RecoleccionID'] . '</a> / <a href="' . $caex_cancelled_dte['URLConsulta'] . '" target="_blank">' . $caex_cancelled_dte['NumeroGuia'] . '</a></p>';
					}

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


// Agregar link de guia a detalle de órden
function dl_add_caex_invoice_to_order_details( $order ) {
	$caex_dte = get_post_meta( $order->get_id(), '_wc_order_caex_tracking' );
	$caex_last_action = get_post_meta( $order->get_id() , '_caex_last_action', true );
	if( $caex_dte && $caex_last_action != 'invoice_cancelled' ) {
		$caex_dte = json_decode( $caex_dte[count($caex_dte)-1], true );
		?>
		<div class="d-flex flex-wrap order-info order-tracking_id m-b-xl m-t-xs p-t-lg">
			<div class="order-item">
				<strong><?= __('Tracking ID', 'wp-caex-woocommerce') ?></strong>
				<mark class="font-weight-bold order-total"><a href="https://www.cargoexpreso.com/tracking/?guia=<?= $caex_dte['NumeroGuia'] ?>" target="_blank"><?= $caex_dte['NumeroGuia'] ?></a></mark>
			</div>
		</div>
		<?php
	}
}
add_action('woocommerce_order_details_before_order_table', __NAMESPACE__ . '\\dl_add_caex_invoice_to_order_details');


function register_custom_order_status() {
    register_post_status( 'wc-on-route', array(
        'label'                     => _('On Route', 'wp-caex-woocommerce'),
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'On Route <span class="count">(%s)</span>', 'On Route <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', __NAMESPACE__ . '\\register_custom_order_status' );


function dl_update_all_pending_orders_status() {
	$Logger = new Util\Logger('dl-caex');
	$Logger->log("Cronjob inicio ejecutandose");
	$orders = wc_get_orders( array(
		'limit' => -1
	) );
	foreach( $orders as $order ) {
		if( $order->get_status() == 'completed' ) {
			continue;
		}
		$Logger->log("cronjob ejecutandose. Orden: " . $order->get_id() . " estado: " . $order->get_status() );
		dl_wc_update_tracking_status( $order );
	}
	$Logger->log("cronjob finalizó de ejecutarse");
}
add_action( 'dl_update_tracking_status', __NAMESPACE__ . '\\dl_update_all_pending_orders_status', 1, 3);
