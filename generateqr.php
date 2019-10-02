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
$paymentID = '';
$amount = '0';
if (isset($_POST['address'])) {
  $address = $_POST['address'];
  if (isset($_POST['paymentID'])) {
    $paymentID = $_POST['paymentID'];
  }
  if (isset($_POST['amount'])) {
    $amount = round($_POST['amount'] * 100);
  }
}
if (isset($_GET['address'])) {
  $address = $_GET['address'];
  if (isset($_GET['paymentID'])) {
    $paymentID = $_GET['paymentID'];
  }
  if (isset($_GET['amount'])) {
    $amount = round($_GET['amount'] * 100);
  }
}
if (validate_address($address)) {
    if (strlen($paymentID) == 0 || validate_paymentid($paymentID)) {
      $address .= ";" . $paymentID;
      if ($amount != 0 && validate_amount(strval($amount))) {
        $address .= ";" . $amount;
      }
    }
    QRcode::png($address);
} else {
  echo "<html>";
  echo "<head>";
  echo "<link rel='stylesheet' href='style.css'>";
  echo "</head>";
  echo "<body>";
  echo "<form action='generateqr.php' method='post'>";
  echo "<table class='generate'>";
  echo "<tr><th>Wallet address:</th><td><input type='string' name='address' maxlength='97' required pattern='bT[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{95}' size='97' value=''></td></tr>";
  echo "<tr><th>Payment ID:</th><td><input type='string' name='paymentID' maxlength='64' pattern='[0123456789ABCDEFabcdef]{64}' size='64' value=''></td></tr>";
  echo "<tr><th>Amount:</th><td><input type='number' name='amount' min='0.00' step='0.01' value='0.00'></td></tr>";
  echo "<tr><td colspan='2' class='submit'><input type='submit' value='Submit' class='btn'></td></tr>";
  echo "</table>";
  echo "</form>";
  echo "</body>";
  echo "</html>";
}
?>
