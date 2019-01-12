<html>
<head>
<title>Bittorium webwallet</title>
<link rel="shortcut icon" href="images/logo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
        <div class="logo"><img src="/images/logo.png"></div>
        <div class="pagetitle">Bittorium Web Wallet</div>
</div>

<div class="page">
<?php
require("config.php");
require("lib/daemon.php");
require("lib/database.php");
require("lib/validate.php");
require("lib/users.php");

try {
  open_database();
} catch (Exception $e) {
  echo '<span class="error">Caught exception while opening database: ', $e->getMessage(), "</span></div></body></html>";
  exit();
}
try {
  check_database();
} catch (Exception $e) {
  echo '<span class="error">Caught exception while reading database: ', $e->getMessage(), "</span></div></body></html>";
  exit();
}
// Check if user has logged in or not?
require("lib/login.php");
//
$address = "";
if (logged_in()) {
  $spendKey = $_COOKIE['spendKey'];
  $feeAddress = "";
  if (!validate_spendkey($spendKey)) {
    echo "<span class='error'>Invalid spend key!</span></div></body></html>";
    exit();
  }
  $address = get_address($spendKey);
  $params = Array();
  $params['address'] = $address;
  $getBalance = walletrpc_post("getBalance", $params);
  $availableBalance = $getBalance->availableBalance;
  $lockedBalance = $getBalance->lockedAmount;
  require("lib/menu.php");
  if (!isset($_POST['recipient']) || !isset($_POST['amount'])) {
    echo "<div id='wallet'>Address:&nbsp;", $address, "</div>";
    echo "<br>";
    //
    $maxAmount = $availableBalance - 1;
    $getFeeAddress = daemonrpc_get("/feeaddress");
    if (array_key_exists('fee_address', $getFeeAddress)) {
      $feeAddress = $getFeeAddress->fee_address;
      if (validate_address($feeAddress)) {
        $feeAmount = min(1, max(floatval($maxAmount) / 400001, 100));
        $maxAmount -= $feeAmount;
      }
    }
    if ($maxAmount < 1) {
      echo "<span class='error'>Not enough available balance to send transactions.</span></div></body></html>";
      exit();
    }
    //
    echo "<h2>Send BTOR</h2><br>";
    echo "<form action='send.php' method='post'>";
    echo "<table class='send'>";
    echo "<tr><th>Recipient address:</th><td><input type='text' maxlength='97' required pattern='bT[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{95}' name='recipient' size='97'></td></tr>";
    echo "<tr><th>Amount:</th><td><input type='number' min='0.01' max='" . number_format($maxAmount / 100, 2) . "' step='0.01' name='amount' value='0.01'></td></tr>";
    echo "<tr><th>Anonymity level:</th><td><input type='number' min='0' max='9' step='1' name='anonymity' value='0'></td></tr>";
    echo "<tr><th>Payment ID:</th><td><input type='text' maxlength='64' pattern='.{0}|[0-9a-fA-F]{64}' name='paymentID' size='64'></td></tr>";
    echo "<tr><td colspan=2 class='submit'><input type='submit' class='btn' name='send' value='Send'></td></tr>";
    echo "</form>";
  } else {
    $feeAmount = 0;
    $totalAmount = 1;
    $recipient = $_POST['recipient'];
    $amount = $_POST['amount'];
    $anonymity = $_POST['anonymity'];
    $paymentID = $_POST['paymentID'];
    $feeAddress = "";
    if (!validate_address($recipient)) {
      echo "<span class='error'>Recipient address is invalid.</span></div></body></html>";
      exit();
    }
    if (strlen($paymentID) > 0 && !validate_paymentid($paymentID)) {
      echo "<span class='error'>Payment ID '", htmlspecialchars($paymentID), "' is not valid!</span></div></body></html>";
      exit();
    }
    if (!validate_amount($amount)) {
      echo "<span class='error'>Amount is invalid, it should be either integer or with exactly 2 decimals!</span></div></body></html>";
      exit();
    }
    if (!validate_int($anonymity) || $anonymity < 0 || $anonymity > 9) {
      echo "<span class='error'>Anonymity level is invalid, should be integer between 0 and 9.</span></div></body></html>";
    }
    $totalAmount += $amount * 100;
    $params = Array();
    $sourceAddresses = Array();
    $sourceAddresses[] = $address;
    $params['sourceAddresses'] = $sourceAddresses;
    $params['changeAddress'] = $address;
    //
    $getFeeAddress = daemonrpc_get("/feeaddress");
    if (array_key_exists('fee_address', $getFeeAddress)) {
      $feeAddress = $getFeeAddress->fee_address;
    }
    if (validate_address($feeAddress)) {
      $feeAmount = min(0.01, max(floatval($amount) / 40000, 1.00));
      $totalAmount += $feeAmount * 100;
    }
    if ($totalAmount > $availableBalance) {
      echo "<span class='error'>Not enough available balance to send transaction, need ", number_format($totalAmount / 100, 2), " BTOR.</span></div></body></html>";
      exit();
    }
    //
    echo "Sending ", number_format($amount, 2), " BTOR";
    if (strlen($paymentID) > 0) {
      echo " using payment ID '", $paymentID, "'";
    }
    if ($feeAmount > 0) {
      echo " with node fee of ", number_format($feeAmount, 2), " BTOR and network fee of 0.01 BTOR";
    } else {
      echo " with network fee of 0.01 BTOR";
    }
    echo " from address ", $address, " to address ", $recipient, "<br>";
    //
    $transfers = Array();
    $transfers[] = Array("address" => $recipient, "amount" => intval($amount * 100));
    // Add transfer for node fee only if fee address is valid.
    if ($feeAmount > 0) {
      $transfers[] = Array("address" => $feeAddress, "amount" => intval($feeAmount * 100));
    }
    $params['transfers'] = $transfers;
    if (strlen($paymentID) > 0) {
      $params['paymentId'] = $paymentID;
    }
    $params['fee'] = 1;
    $params['anonymity'] = intval($anonymity);
    echo "<br>";
    $result = walletrpc_post("sendTransaction", (Object) $params);
//  echo "<br>Result:<br>";
//  var_dump($result);
    if ($result == NULL) {
      echo "<span class='error'>Internal error, contact webwallet admin!</span></div></body></html>";
      exit();
    }
    if (array_key_exists('error', $result)) {
      if (array_key_exists('message', $result->error)) {
        if ($result->error->message == 'Wrong amount') {
          echo "<span class='error'>Sending failed because there was not enough unlocked balance, available balance ", number_format($availableBalance / 100, 2), " BTOR!</span></div></body></html>";
          exit();
        } else if ($result->error->message == 'Transaction size is too big') {
          echo "<span class='error'>Sending failed because you don't have enough large inputs. Please <a href='info.php'>optimize</a> your wallet.</span></div></body></html>";
          exit();
        } else if ($result->error->message == 'Sum overflow') {
          echo "<span class='error'>Sending failed because the transfer amount is too large.</span></div></body></html>";
          exit();
        } else {
          echo "<span class='error'>Sending failed because of error '", $result->error->message, "'!</span></div></body></html>";
          exit();
        }
      }
    }
    if (array_key_exists('transactionHash', $result)) {
      echo "Transaction sent with hash ", $result->transactionHash, "<br>";
      echo "<a href='send.php'>Return to webwallet</a><br>";
    }
  }
}
?>
</div>
</body>
</html>
