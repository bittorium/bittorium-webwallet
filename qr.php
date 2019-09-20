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
// Check if user has logged in or not?
require("lib/login.php");
// Load QR Code library
require("qr/lib/full/qrlib.php");
//
$address = "";
if (logged_in()) {
  $spendKey = $_COOKIE['spendKey'];
  if (!validate_spendkey($spendKey)) {
    exit();
  }
  $address = get_address($spendKey);
  QRcode::png($address);
}
?>
