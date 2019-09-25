<?php
require("config.php");
require("lib/daemon.php");
require("lib/database.php");
require("lib/validate.php");
require("lib/users.php");

try {
  open_database();
} catch (Exception $e) {
  exit();
}
try {
  check_database();
} catch (Exception $e) {
  exit();
}
// Load QR Code library
require("qr/lib/full/qrlib.php");
//
$address = '';
if (isset($_POST['address'])) {
  $address = $_POST['address'];
}
if (isset($_GET['address'])) {
  $address = $_GET['address'];
}
if (validate_address($address)) {
    QRcode::png($address);
} else {
  echo "<form action='generateqr.php' method='post'>";
  echo "Wallet address: <input type='string' name='address' maxlength='97' required pattern='bT[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{95}' size='97' value=''><br>";
  echo "<input type='submit' value='Submit' class='btn'>";
  echo "</form>";
}
?>
