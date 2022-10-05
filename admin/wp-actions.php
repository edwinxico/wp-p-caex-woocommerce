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
        if( !get_post_meta( $theorder->get_id(), '_wc_order_caex_dte', true ) || $caex_last_action == 'invoice_cancelled' ) {
            $actions['wc_caex_request_invoice'] = 'Caex | ' . __( 'Generate tracking ID', 'wp-caex-woocommerce' );
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
function dl_wc_process_order_meta_box_request_invoice_action( $order ) {
    $caexApi = new Helpers\Caex_Api();
	$Logger = new Util\Logger('caex-woocommerce');
	$Logger->log("caex-woocommerce: Requesting invoice for order: " . $order->get_id() );
    $invoice_response = $caexApi->requestInvoice($order);
    if( !$invoice_response['result'] ) {
        // error ,agregar nota al pedido sobre la razón del error
        $order->add_order_note( $invoice_response['message'] );
        return;
    }
	// exito, agregar datos de invoice a la órden
	add_post_meta( $order->get_id(), '_wc_order_caex_dte', json_encode( $invoice_response['dte'] ) );
	if( get_post_meta( $order->get_id(), '_caex_last_action', true ) ) {
		update_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested' );
	} else {
		add_post_meta( $order->get_id(), '_caex_last_action', 'invoice_requested', true );
	}
	$message = sprintf( __( 'Invoice from Caex requested by %s.', 'wp-caex-woocommerce' ), wp_get_current_user()->display_name );
	$order->add_order_note( $message );
}
add_action( 'woocommerce_order_action_wc_caex_request_invoice', __NAMESPACE__ . '\\dl_wc_process_order_meta_box_request_invoice_action' );

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

	add_post_meta( $order->get_id(), '_wc_order_caex_cancelled_invoices', json_encode( $invoice_response['dte'] ) );
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





