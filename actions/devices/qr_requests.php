<?php
session_start();
if (!isset($_SESSION['ps_user'])) {
    header('Content-Type: application/json');
    echo json_encode(array('ok' => false, 'error' => 'unauthorized'));
    exit;
}
include('../../includes/config.php');
$conn = mysql_connect("$host", "$user", "$pass");
if (!$conn || !mysql_select_db("$db")) {
    header('Content-Type: application/json');
    echo json_encode(array('ok' => false, 'error' => 'db_connection'));
    exit;
}
$create_ok = mysql_query("CREATE TABLE IF NOT EXISTS qr_device_requests (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    request_type VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL
)");
if (!$create_ok) {
    header('Content-Type: application/json');
    echo json_encode(array('ok' => false, 'error' => 'db_table'));
    exit;
}
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
if ($action == 'close') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    mysql_query("UPDATE qr_device_requests SET status='closed' WHERE id='".$id."'");
    header('Content-Type: application/json');
    echo json_encode(array('ok' => true));
    exit;
}
$res = mysql_query("SELECT * FROM qr_device_requests WHERE status='new' ORDER BY id DESC LIMIT 20");
$out = array();
while($row = mysql_fetch_assoc($res)) { $out[] = $row; }
header('Content-Type: application/json');
echo json_encode(array('ok' => true, 'items' => $out));
