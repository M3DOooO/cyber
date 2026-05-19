<?php
session_start();
if (!isset($_SESSION['ps_user'])) { include('login.php'); die(); }
include('includes/config.php');

mysql_connect("$host", "$user", "$pass") or die('DB Connection Error');
mysql_select_db("$db") or die('DB Select Error');

$selected_device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;
$devices = array();
$res = mysql_query("SELECT ID, `Device Name` FROM devices ORDER BY orderby");
while ($row = mysql_fetch_assoc($res)) { $devices[] = $row; }

if ($selected_device_id <= 0 && count($devices) > 0) {
    $selected_device_id = (int)$devices[0]['ID'];
}

$selected_device_name = '';
foreach ($devices as $d) {
    if ((int)$d['ID'] === $selected_device_id) {
        $selected_device_name = $d['Device Name'];
        break;
    }
}
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>QR الأجهزة</title>
<style>
body{font-family:tahoma;direction:rtl;padding:15px}
.wrap{max-width:560px;margin:0 auto}
.sel{margin:15px 0;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fafafa}
.card{width:260px;border:1px solid #ddd;border-radius:8px;padding:12px;margin:10px auto;text-align:center}
select,button{padding:8px;font-size:14px}
</style>
</head>
<body>
<div class="wrap">
<h2>QR للأجهزة</h2>
<form method="get" class="sel">
<label>اختار الروم / الجهاز:</label><br><br>
<select name="device_id" onchange="this.form.submit()">
<?php foreach($devices as $d){ ?>
<option value="<?php echo (int)$d['ID'];?>" <?php if((int)$d['ID']===$selected_device_id){echo 'selected';}?>><?php echo htmlspecialchars($d['Device Name']);?></option>
<?php } ?>
</select>
<noscript><button type="submit">عرض</button></noscript>
</form>

<?php if($selected_device_id > 0 && $selected_device_name != ''){
$qr_text = 'Device ID: '.$selected_device_id.' | Device Name: '.$selected_device_name;
$qr='https://quickchart.io/qr?size=260&text='.urlencode($qr_text);
?>
<div class="card">
    <b><?php echo htmlspecialchars($selected_device_name);?></b><br><br>
    <img src="<?php echo $qr;?>" width="220" height="220" alt="QR"><br>
    <small>ID: <?php echo (int)$selected_device_id;?></small>
</div>
<?php } else { ?>
<p>لا يوجد أجهزة متاحة.</p>
<?php } ?>
</div>
</body>
</html>
