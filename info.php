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
  if (isset($_POST['threshold'])) {
    $threshold = $_POST['threshold'];
    if (!validate_int($threshold) || intval($threshold) < 100) {
      echo "<span class='error'>Fusion threshold is invalid! Should be integer number and at least 100!</span></div></body></html>";
      exit();
    }
    $params = Array();
    $sourceAddresses = Array();
    $sourceAddresses[] = $address;
    $params['addresses'] = $sourceAddresses;
    $params['destinationAddress'] = $address;
    $params['threshold'] = intval($threshold);
    $params['anonymity'] = 0;
    $result = walletrpc_post('sendFusionTransaction', $params);
//  var_dump($result);
    if (array_key_exists('transactionHash', $result)) {
      echo "Fusion transaction sent with hash ", $result->transactionHash, ".<br>";
    } else {
      echo "<span class='error'>Sending fusion transaction failed!</span><br>";
    }
  } else if (isset($_POST['oldEmail']) && !isset($_POST['newEmail'])) {
    // Verify that oldEmail matches e-mail address associated with spendKey...
    $oldEmail = $_POST['oldEmail'];
    $email = get_email_with_spendkey($spendKey);
    if ($oldEmail != $email) {
      echo "<span class='error'>E-mail address doesn't match with address registered with account!</span></div></body></html>";
      exit();
    }
    if (isset($_POST['authCode'])) {
      $authCode = $_POST['authCode'];
      $authCode2 = get_authcode($spendKey);
      if ($authCode != $authCode2) {
        echo "<span class='error'>Invalid authentication code!</span></div></body></html>";
        exit();
      }
      echo "<form action='info.php' method='post'>";
      echo "<input type='hidden' name='oldEmail' value='".$email."'>";
      echo "Enter new e-mail address: <input type='email' name='newEmail' placeholder='@' required><br>";
      echo "<input type='submit' name='submit' class='btn' value='Verify'>";
      echo "</form></div></body></html>";
      exit();
    } else {
      $authCode = generate_authcode($spendKey);
      if ($authCode === false) {
        echo "<span class='error'>Database error!</span></div></body></html>";
        exit();
      }
      send_change_email_old($email, $authCode);
      echo "<form action='info.php' method='post'>";
      echo "<input type='hidden' name='oldEmail' value='".$email."'>";
      echo "Enter authentication code: <input type='text' name='authCode' pattern='[0-9]{6}' placeholder='000000' required><br>";
      echo "<input type='submit' name='submit' class='btn' value='Verify'>";
      echo "</form></div></body></html>";
      exit();
    }
  } else if (isset($_POST['oldEmail']) && isset($_POST['newEmail'])) {
    $oldEmail = $_POST['oldEmail'];
    $newEmail = $_POST['newEmail'];
    $email = get_email_with_spendkey($spendKey);
    if ($oldEmail != $email) {
      echo "<span class='error'>E-mail address doesn't match with address registered with account!</span></div></body></html>";
      exit();
    }
    if (!validate_email($newEmail)) {
      echo "<span class='error'>New e-mail address is not valid!</span></div></body></html>";
      exit();
    }
    if (isset($_POST['authCode'])) {
      $authCode = $_POST['authCode'];
      $authCode2 = get_authcode($spendKey);
      if ($authCode != $authCode2) {
        echo "<span class='error'>Invalid authentication code!</span></div></body></html>";
        exit();
      }
      $result = set_email_with_spendkey($spendKey, $newEmail);
      if ($result === false) {
        echo "<span class='error'>E-mail change failed!</span></div></body></html>";
        exit();
      }
      echo "E-mail address changed!<br>";
      echo "<a href='info.php'>Return</a></div></body></html>";
      exit();
    } else {
      $authCode = generate_authcode($spendKey);
      send_change_email_new($oldEmail, $newEmail, $authCode);
      echo "<form action='info.php' method='post'>";
      echo "<input type='hidden' name='oldEmail' value='".$oldEmail."'>";
      echo "<input type='hidden' name='newEmail' value='".$newEmail."'>";
      echo "Enter authentication code: <input type='text' name='authCode' pattern='[0-9]{6}' placeholder='000000' required><br>";
      echo "<input type='submit' name='submit' class='btn' value='Verify'>";
      echo "</form></div></body></html>";
      exit();
    }
  } else {
    echo "<h3>Wallet optimization</h3>";
    // Threshold should be largest multiple of 10 that is smaller than or equal to 1/12 of available balance
    $threshold = 100;
    while ($threshold < ($availableBalance / 120)) {
      $threshold *= 10;
    }
    $params = Array();
    $params['threshold'] = $threshold;
    $sourceAddresses = Array();
    $sourceAddresses[] = $address;
    $params['addresses'] = $sourceAddresses;
    $result = walletrpc_post('estimateFusion', $params);
    if (array_key_exists('fusionReadyCount', $result)) {
      echo $result->fusionReadyCount, " output(s) ready for fusion transaction.<br>";
    }
    if (array_key_exists('totalOutputCount', $result)) {
      echo $result->totalOutputCount, " output(s) found in wallet.<br>";
    }
    if ($result->fusionReadyCount > 0) {
      echo "<form action='info.php' method='post'>";
      echo "<input type='hidden' name='threshold' value='", $threshold, "'>";
      echo "<input type='submit' name='optimize' class='btn' value='Optimize wallet'>";
      echo "</form>";
      echo "<br>";
    }
  }
  echo "<br><h3>Wallet recovery</h3>";
  $params = Array();
  $params['address'] = $address;
  $result = walletrpc_post('getMnemonicSeed', $params);
  $getViewKey = walletrpc_post('getViewKey');
  $viewKey = $getViewKey->viewSecretKey;
  if ($result === NULL) {
    echo "<span class='error'>Internal server error, contact web wallet admin!</span></div></body></html>";
    exit();
  }
  echo "<table id='info'>";
  if (array_key_exists('mnemonicSeed', $result)) {
    echo "<tr><th>Mnemonic seed:</th><td>", $result->mnemonicSeed, "</td></tr>";
  }
  echo "<tr><th>View key:</th><td>", $viewKey, "</td></tr>";
  echo "<tr><th>Spend key:</th><td>", $spendKey, "</td></tr>";
  echo "</table>";
  echo "<br><h3>E-mail change</h3>";
  echo "<form action='info.php' method='post'>";
  echo "Enter old e-mail address: <input type='email' name='oldEmail' placeholder='youremail@domain.com' required><br>";
  echo "<input type='submit' name='submit' class='btn' value='Verify'>";
  echo "</form>";
}
?>
</div>
</body>
</html>
