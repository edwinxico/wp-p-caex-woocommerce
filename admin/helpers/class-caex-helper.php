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