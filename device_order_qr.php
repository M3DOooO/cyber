<?php
// Temporary debug mode for this page to diagnose HTTP 500 errors.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo '<h2>Fatal error in device_order_qr.php</h2>';
        echo '<pre>' . htmlspecialchars(print_r($error, true), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
});

try {
    include 'device_order_form.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Exception in device_order_qr.php</h2>';
    echo '<pre>' . htmlspecialchars($e->__toString(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
