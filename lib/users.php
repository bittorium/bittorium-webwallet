<?php
//echo "Entering lib/users.php...<br>";

function logged_in() {
  if(!isset($_COOKIE["spendKey"])) {
    return false;
  }
  $spendKey = $_COOKIE["spendKey"];
  if (!validate_spendkey($spendKey)) {
    return false;
  }
  if (!check_spendkey($spendKey)) {
    return false;
  }
  return true;
}
function log_in($spendKey, $authCode) {
  if(isset($_COOKIE["spendKey"])) {
    echo "Already logged in!<br>";
    return false;
  }
  if(!validate_spendkey($spendKey)) {
    echo "Invalid wallet key!<br>";
    return false;
  }
  if(!check_spendkey($spendKey)) {
    echo "Wallet doesn't exist!<br>";
    return false;
  }
  $email = get_email_with_spendkey($spendKey);
  $authCode2 = get_authcode($spendKey);
  if (strlen($email) > 0 && $authCode !== $authCode2) {
    return false;
  }
  setcookie("spendKey", $spendKey, 0);
  echo "Logging in...<br>";
  return true;
}

function send_auth_email($email, $authCode) {
  global $walletEmail;
  $msg = "You have asked for a registration on Bittorium webwallet using e-mail address ".$email.".\nTo finish registration, enter authentication code ".$authCode." in the registration page.\n";
  $result = mail($email, "Registration for Bittorium webwallet", $msg, "From: " . $walletEmail, "-f" . $walletEmail);
//  echo "Sent e-mail from ", $walletEmail, " to ", $email, " with authentication code ", $authCode, "<br>";
}

function send_login_email($email, $authCode) {
  global $walletEmail;
  $msg = "You have tried logging in on Bittorium webwallet using e-mail address ".$email.".\nTo finish logging in, enter authentication code ".$authCode." in the login page.\n";
  $result = mail($email, "Login for Bittorium webwallet", $msg, "From: " . $walletEmail, "-f" . $walletEmail);
//  echo "Sent e-mail from ", $walletEmail, " to ", $email, " with authentication code ", $authCode, "<br>";
}

function send_key_email($email, $spendKey) {
  global $walletEmail;
  $msg = "You have registered on Bittorium webwallet using e-mail address ".$email.". To login, use key ".$spendKey." and one-time authentication code will be e-mailed to you.";
  $result = mail($email, "Registration for Bittorium webwallet", $msg, "From: " . $walletEmail, "-f" . $walletEmail);
}

function send_change_email_old($email, $authCode) {
  global $walletEmail;
  $msg = "You have requested change of e-mail address on Bittorium webwallet. To proceed with change, enter authentication code ".$authCode." in the e-mail verification page.";
  $result = mail($email, "Change of e-mail address for Bittorium webwallet", $msg, "From: ". $walletEmail, "-f" . $walletEmail);
}

function send_change_email_new($oldEmail, $newEmail, $authCode) {
  global $walletEmail;
  $msg = "You have requested change of e-mail address on Bittorium webwallet from " . $oldEmail . " to " . $newEmail . ". To confirm, enter authentication code " . $authCode . " in the e-mail verification page.";
  $result = mail($newEmail, "Change of e-mail address for Bittorium webwallet", $msg, "From: ". $walletEmail, "-f" . $walletEmail); 
}

//echo "Leaving lib/users.php...<br>";
?>
