<?php

namespace caex_woocommerce\Admin\Util;

class Logger {

    function __construct($log_file = 'dl-debug') {
        $this->log_file = $log_file;
    }



    public function log($message) {
        if( !defined('DL_DEBUG') || DL_DEBUG != TRUE ) {
            return;
        }
        $date = new \DateTime('now');
        $message = $date->format('D M d, Y G:i') . ": " . $message . "\n";
        // echo $message . '<br><br>';
        error_log( $message , 3, WP_CONTENT_DIR . "/" . $this->log_file . ".log");
    }
}