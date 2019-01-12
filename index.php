<html>
<head>
<title>Bittorium webwallet</title>
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
  echo "<div id='wallet'>Address:&nbsp;", $address, "</div><br>";
  //
  $info = daemonrpc_get("/getinfo");
  $height = $info->height;
  //
  $WalletTransactionState = Array("Succeeded", "Failed", "Cancelled", "Created", "Deleted");
  $WalletTransferType = Array("Usual", "Donation", "Change");
  //
  if (array_key_exists('hash', $_GET)) {
    $hash = $_GET['hash'];
    if (!validate_txhash($hash)) {
      echo "<span class='error'>Invalid transaction hash!</span></div></body></html>";
      exit();
    }
    $params = Array();
    $params['transactionHash'] = $hash;
    $result = walletrpc_post('getTransaction', $params);
    if (!array_key_exists('transaction', $result)) {
      echo "<span class='error'>Transaction hash not found in blockchain!</span></div></body></html>";
      exit();
    }
    $tx = $result->transaction;
    echo "<h3>Transaction " . $hash . "</h3>";
    echo "<table id='transaction'>";
    echo "<tr><th>Amount</th><td>" . number_format($tx->amount / 100, 2) . " BTOR</td></tr>";
    echo "<tr><th>Block</th><td>" . $tx->blockIndex . "</td></tr>";
    echo "<tr><th>Fee</th><td>" . number_format($tx->fee / 100, 2) . " BTOR</td></tr>";
    echo "<tr><th>Type</th><td>" . ($tx->isBase ? "Coinbase" : "Key output") . "</td></tr>";
    if (strlen($tx->paymentId) > 0) {
      echo "<tr><th>Payment ID</th><td>" . $tx->paymentID . "</td></tr>";
    }
    echo "<tr><th>State</th><td>" . $WalletTransactionState[$tx->state] . "</td></tr>";
    echo "<tr><th>Time</th><td>" . date("D, d M y H:i:s", $tx->timestamp) . "</td></tr>";
    echo "</table>";
    echo "<h3>Transfers</h3>";
    echo "<div class='hscroll'>";
    echo "<table id='transfers'>";
    echo "<tr><th>Address</th><th>Amount</th><th>Type</th></tr>";
    foreach ($tx->transfers as $transfer) {
      echo "<tr>";
      echo "<td>" . $transfer->address . "</td>";
      echo "<td>" . number_format($transfer->amount / 100, 2) . " BTOR</td>";
      echo "<td>" . $WalletTransferType[$transfer->type] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    echo "<br><a href='index.php'>Back</a>";
  } else {
    $addresses = Array();
    $addresses[0] = $address;
    $txs_params = Array("addresses" => $addresses, "firstBlockIndex" => 0, "blockCount" => $height);
    $txs = walletrpc_post("getTransactions", $txs_params);
    $blocks = $txs->items;
    echo "<h2>Transactions</h2>";
    echo "<div class='hscroll'>";
    echo "<table id='transactions'>";
    echo "<tr><th>State</th><th>Hash</th><th>Time</th><th>Amount</th><th>Payment ID</th></tr>";
    $ntrans = 0;
    $skip = is_numeric($_POST["skip"]) ? $_POST["skip"] : 0;
    if ($skip < 0) {
      $skip = 0;
    }
    // List transactions in reverse order, from newest to oldest
    $blocks = array_reverse($blocks);
    foreach ($blocks as $block) {
      $transactions = array_reverse($block->transactions);
      foreach ($transactions as $transaction) {
        if ($transaction->amount != 0) {
          if ($ntrans >= $skip && $ntrans < $skip + 20) {
            echo "<tr>";
            echo "<td>" . $WalletTransactionState[$transaction->state] . "</td>";
            echo "<td><a href='?hash=" . $transaction->transactionHash . "'>" . $transaction->transactionHash . "</a></td>";
            echo "<td>" . date("D, d M y H:i:s", $transaction->timestamp) . "</td>";
            echo "<td>" . number_format($transaction->amount / 100, 2) . "</td>";
            echo "<td>" . $transaction->paymentId . "</td>";
            echo "</tr>";
          }
          $ntrans++;
        }
      }
    }
    echo "</table>";
    echo "</div>";
    echo "<table>";
    echo "<tr>";
    if ($skip > 0) {
      echo "<td><form action='index.php' method='post'>";
      echo "<input name='skip' type='hidden' value='0' />";
      echo "<input name='submit' type='submit' class='btn' value='First 20' />";
      echo "</form></td>";
      echo "<td><form action='index.php' method='post'>";
      echo "<input name='skip' type='hidden' value='" . ($skip - 20) . "' />";
      echo "<input name='submit' type='submit' class='btn' value='Previous 20' />";
      echo "</form></td>";
    }
    if ($ntrans > $skip + 20) {
      echo "<td><form action='index.php' method='post'>";
      echo "<input name='skip' type='hidden' value='" . ($skip + 20) . "' />";
      echo "<input name='submit' type='submit' class='btn' value='Next 20' />";
      echo "</form></td>";
      echo "<td><form action='index.php' method='post'>";
      echo "<input name='skip' type='hidden' value='" . ($ntrans - 20) . "' />";
      echo "<input name='submit' type='submit' class='btn' value='Last 20' />";
      echo "</form></td>";
    }
    echo "</tr>";
    echo "</table>";
  }
}
?>

</div>
</body>
</html>

