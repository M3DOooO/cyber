<?php
// QR page bootstrap fixes:
// 1) define legacy variable expected by includes/config.php to avoid undefined-variable warnings
// 2) keep visible error output for diagnosis without converting every warning to fatal exception
if (!isset($mac)) {
    $mac = '';
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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
