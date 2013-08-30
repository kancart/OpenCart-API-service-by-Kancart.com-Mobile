<?php

class KancartErrorHandler {

    // CATCHABLE ERRORS
    public static function captureNormal($number, $message, $file, $line) {
        $errormsg = "ERROR #$number: $message in $file on line $line ";
        die(json_encode(array('result' => 'fail', 'code' => '0xFFFF', 'info' => $errormsg)));
        return true;
    }

    // EXCEPTIONS
    public static function captureException($exception) {
        // Display content $exception variable
        $errormsg = "Uncaught Exception: #{$exception->getCode()} {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()} ";
        die(json_encode(array('result' => 'fail', 'code' => '0xFFFF', 'info' => $errormsg)));
    }

    // UNCATCHABLE ERRORS
    public static function captureFatalError() {
        $error = error_get_last();
        if ($error) {
            if ($error['type'] == E_ERROR) {
                $errormsg = "FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']} ";
                die(json_encode(array('result' => 'fail', 'code' => '0xFFFF', 'info' => $errormsg)));
            }
        }
    }

}

if(!defined('E_DEPRECATED'))define ('E_DEPRECATED', 8192);
set_error_handler(array('KancartErrorHandler', 'captureNormal'), E_ALL ^ E_WARNING ^ E_NOTICE ^ E_DEPRECATED);
set_exception_handler(array('KancartErrorHandler', 'captureException'));
register_shutdown_function(array('KancartErrorHandler', 'captureFatalError'));
?>