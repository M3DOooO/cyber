<?php
include('includes/config.php');
mysql_connect("$host", "$user", "$pass") or die('db');
mysql_select_db("$db") or die('db');
mysql_query("CREATE TABLE IF NOT EXISTS qr_device_requests (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    request_type VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
$device_name = 'Unknown';
$r = mysql_query("SELECT `Device Name` FROM devices WHERE ID='".$device_id."' LIMIT 1");
if ($row = mysql_fetch_assoc($r)) { $device_name = $row['Device Name']; }
if (isset($_POST['request_type']) && $_POST['request_type'] != '') {
    $request_type = mysql_real_escape_string($_POST['request_type']);
    $safe_name = mysql_real_escape_string($device_name);
    mysql_query("INSERT INTO qr_device_requests (device_id,device_name,request_type,status) VALUES ('".$device_id."','$safe_name','$request_type','new')");
    $ok = true;
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>طلب خارجي</title>
<style>body{font-family:tahoma;padding:20px;direction:rtl}.box{max-width:380px;margin:auto;border:1px solid #ddd;padding:18px;border-radius:10px}button{width:100%;padding:10px;background:#2d89ef;color:#fff;border:0;border-radius:8px}</style>
</head><body><div class="box"><h3>جهاز: <?php echo htmlspecialchars($device_name);?></h3>
<p>اختار نوع الطلب الخارجي:</p>
<form method="post">
<select name="request_type" style="width:100%;padding:10px;margin-bottom:12px" required>
<option value="">-- اختار الطلب --</option>
<option>شاي</option><option>قهوة</option><option>مشروب غازي</option><option>مياه</option><option>شيشة</option><option>مساعدة</option>
</select>
<button type="submit">إرسال الطلب</button>
</form>
<?php if(isset($ok)){ ?><p style="color:green">✅ تم إرسال الطلب بنجاح.</p><?php } ?>
</div></body></html>
