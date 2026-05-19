<?php
session_start();
if (!isset($_SESSION['ps_user'])) { include('login.php'); die(); }
include('includes/config.php');
mysql_connect("$host", "$user", "$pass") or die('db');
mysql_select_db("$db") or die('db');
$hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$res = mysql_query("SELECT ID, `Device Name` FROM devices ORDER BY orderby");
?><!doctype html><html><head><meta charset="utf-8"><title>QR الأجهزة</title><style>body{font-family:tahoma;direction:rtl;padding:15px}.card{display:inline-block;width:220px;border:1px solid #ddd;border-radius:8px;padding:10px;margin:8px;text-align:center}</style></head><body>
<h2>QR لكل جهاز (طلبات خارجية)</h2>
<?php while($d=mysql_fetch_assoc($res)){ $url=$hostUrl.'/qr_device_order.php?device_id='.$d['ID']; $qr='https://quickchart.io/qr?size=200&text='.urlencode($url); ?>
<div class="card"><b><?php echo htmlspecialchars($d['Device Name']);?></b><br><img src="<?php echo $qr;?>" width="170" height="170"><br><small>ID: <?php echo (int)$d['ID'];?></small></div>
<?php } ?>
</body></html>
