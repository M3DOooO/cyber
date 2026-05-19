<?php
include('includes/config.php');

$db_ok = true;
$conn = mysql_connect("$host", "$user", "$pass");
if (!$conn) {
    $db_ok = false;
} elseif (!mysql_select_db("$db")) {
    $db_ok = false;
}

if ($db_ok) {
    $create_sql = "CREATE TABLE IF NOT EXISTS qr_device_requests (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        device_name VARCHAR(255) NOT NULL,
        request_type VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL
    )";
    if (!mysql_query($create_sql)) {
        $db_ok = false;
    }
}

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
$device_name = 'Unknown Device';
$error_message = '';
$success_message = '';

if ($db_ok && $device_id > 0) {
    $r = mysql_query("SELECT `Device Name` FROM devices WHERE ID='".$device_id."' LIMIT 1");
    if ($r && ($row = mysql_fetch_assoc($r))) {
        $device_name = $row['Device Name'];
    } else {
        $error_message = 'الجهاز غير موجود.';
    }
} elseif ($device_id <= 0) {
    $error_message = 'رابط غير صحيح، من فضلك اعمل Scan من QR الجهاز.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_type = isset($_POST['request_type']) ? trim($_POST['request_type']) : '';

    if (!$db_ok) {
        $error_message = 'حصلت مشكلة في قاعدة البيانات. برجاء المحاولة مرة أخرى.';
    } elseif ($device_id <= 0 || $device_name == 'Unknown Device') {
        $error_message = 'لا يمكن إرسال الطلب بدون جهاز صحيح.';
    } elseif ($posted_type == '') {
        $error_message = 'من فضلك اختار نوع الطلب.';
    } else {
        $allowed = array('شاي','قهوة','مشروب غازي','مياه','شيشة','مساعدة');
        if (!in_array($posted_type, $allowed)) {
            $error_message = 'نوع الطلب غير مسموح.';
        } else {
            $request_type = mysql_real_escape_string($posted_type);
            $safe_name = mysql_real_escape_string($device_name);
            $insert = mysql_query("INSERT INTO qr_device_requests (device_id,device_name,request_type,status,created_at) VALUES ('".$device_id."','$safe_name','$request_type','new',NOW())");
            if ($insert) {
                $success_message = '✅ تم إرسال الطلب بنجاح.';
            } else {
                $error_message = 'تعذر إرسال الطلب حاليا. حاول مرة أخرى.';
            }
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>طلب خارجي</title>
<style>body{font-family:tahoma;padding:20px;direction:rtl}.box{max-width:380px;margin:auto;border:1px solid #ddd;padding:18px;border-radius:10px}button{width:100%;padding:10px;background:#2d89ef;color:#fff;border:0;border-radius:8px}.err{color:#b30000}.ok{color:green}</style>
</head><body><div class="box"><h3>جهاز: <?php echo htmlspecialchars($device_name);?></h3>
<p>اختار نوع الطلب الخارجي:</p>
<form method="post">
<select name="request_type" style="width:100%;padding:10px;margin-bottom:12px" required>
<option value="">-- اختار الطلب --</option>
<option>شاي</option><option>قهوة</option><option>مشروب غازي</option><option>مياه</option><option>شيشة</option><option>مساعدة</option>
</select>
<button type="submit">إرسال الطلب</button>
</form>
<?php if($success_message!=''){ ?><p class="ok"><?php echo $success_message;?></p><?php } ?>
<?php if($error_message!=''){ ?><p class="err"><?php echo htmlspecialchars($error_message);?></p><?php } ?>
</div></body></html>
