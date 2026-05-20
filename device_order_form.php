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
        qty INT NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL
    )";
    if (!mysql_query($create_sql)) {
        $db_ok = false;
    }

    // مهم: CREATE TABLE IF NOT EXISTS لا يضيف الأعمدة في الجداول القديمة.
    // لذلك نتأكد أن عمود qty موجود حتى لا يفشل INSERT في البيئات القديمة.
    if ($db_ok) {
        $qty_col = mysql_query("SHOW COLUMNS FROM qr_device_requests LIKE 'qty'");
        if ($qty_col && mysql_num_rows($qty_col) == 0) {
            if (!mysql_query("ALTER TABLE qr_device_requests ADD COLUMN qty INT NOT NULL DEFAULT 1 AFTER request_type")) {
                $db_ok = false;
            }
        }
    }
}

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
$stock_items = array();
if ($db_ok) {
    $sres = mysql_query("SELECT name, (SUM(stock)-SUM(sold)) AS qty_left FROM stock WHERE name != '' GROUP BY name HAVING qty_left > 0 ORDER BY name ASC");
    if ($sres) {
        while ($sr = mysql_fetch_assoc($sres)) {
            $stock_items[] = $sr;
        }
    }
}


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
    $posted_qty = 1;

    if (!$db_ok) {
        $error_message = 'حصلت مشكلة في قاعدة البيانات. برجاء المحاولة مرة أخرى.';
    } elseif ($device_id <= 0 || $device_name == 'Unknown Device') {
        $error_message = 'لا يمكن إرسال الطلب بدون جهاز صحيح.';
    } elseif ($posted_type == '') {
        $error_message = 'من فضلك اختار نوع الطلب.';
    } else {
        $request_type = mysql_real_escape_string($posted_type);
        $safe_name = mysql_real_escape_string($device_name);
        $insert = mysql_query("INSERT INTO qr_device_requests (device_id,device_name,request_type,qty,status,created_at) VALUES ('".$device_id."','$safe_name','$request_type','".$posted_qty."','new',NOW())");
            if ($insert) {
                $order_sent_to_device = false;
                $device_res = mysql_query("SELECT `session_id`, `Device Status` FROM devices WHERE ID='".$device_id."' LIMIT 1");
                $stock_res = mysql_query("SELECT `catagory`,`sub_cat`,`price` FROM stock WHERE name='".$request_type."' LIMIT 1");
                if ($device_res && $stock_res) {
                    $drow = mysql_fetch_assoc($device_res);
                    $srow = mysql_fetch_assoc($stock_res);
                    if ($drow && $srow && $drow['Device Status'] == 'On' && (int)$drow['session_id'] > 0) {
                        $cfg = mysql_query("SELECT `current_shift`,`shift_day`,`shift_month` FROM config LIMIT 1");
                        $c = $cfg ? mysql_fetch_assoc($cfg) : false;
                        $current_shift = $c ? $c['current_shift'] : '';
                        $shift_day = $c ? (int)$c['shift_day'] : (int)date('d');
                        $shift_month = $c ? (int)$c['shift_month'] : (int)date('m');
                        $year = (int)date('Y');
                        $hour = (int)date('H');
                        $unit_price = (float)$srow['price'];
                        $total = $unit_price * $posted_qty;
                        $cat = mysql_real_escape_string($srow['catagory']);
                        $sub_cat = mysql_real_escape_string($srow['sub_cat']);
                        $name = mysql_real_escape_string($posted_type);
                        $session_id = (int)$drow['session_id'];
                        $ps_insert = mysql_query("INSERT INTO `ps_orders` (`catagory`, `sub_cat`,`name`, `price`, `num` , `ps_id` ,`session_id`,`day`,`month`,`year`,`shift`,`hour` ) VALUES ('$cat', '$sub_cat', '$name','$total','$posted_qty','$device_id','$session_id','$shift_day','$shift_month','$year','$current_shift','$hour')");
                        if ($ps_insert) { $order_sent_to_device = true; }
                    }
                }
                if ($order_sent_to_device) {
                    $success_message = '✅ تم إرسال الطلب بنجاح وتم إضافته لطلبات الجهاز.';
                } else {
                    $success_message = '✅ تم إرسال الطلب بنجاح (تنبيه: الجهاز غير مفتوح حالياً أو تعذر إضافته مباشرة لطلبات الجهاز).';
                }
            } else {
                $error_message = 'تعذر إرسال الطلب حاليا. حاول مرة أخرى. ('.mysql_error().')';
            }
    }
}

?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>طلب خارجي</title>
<style>body{font-family:tahoma;padding:20px;direction:rtl}.box{max-width:380px;margin:auto;border:1px solid #ddd;padding:18px;border-radius:10px}button{width:100%;padding:10px;background:#2d89ef;color:#fff;border:0;border-radius:8px}.err{color:#b30000}.ok{color:green}</style>
</head><body><div class="box"><h3>جهاز: <?php echo htmlspecialchars($device_name);?></h3>
<p>اختار المنتج من المخزن:</p>
<form method="post" action="device_order_qr.php?device_id=<?php echo (int)$device_id; ?>">
<select name="request_type" style="width:100%;padding:10px;margin-bottom:12px" required>
<option value="">-- اختار المنتج --</option>
<?php foreach($stock_items as $it){ ?>
<option value="<?php echo htmlspecialchars($it['name']);?>"><?php echo htmlspecialchars($it['name']);?> (متاح: <?php echo (int)$it['qty_left'];?>)</option>
<?php } ?>
</select>
<button type="submit">إرسال الطلب</button>
</form>
<?php if($success_message!=''){ ?><p class="ok"><?php echo $success_message;?></p><?php } ?>
<?php if($error_message!=''){ ?><p class="err"><?php echo htmlspecialchars($error_message);?></p><?php } ?>
</div></body></html>
