<?php
//echo "Entering lib/validate.php...<br>";

function validate_address($address) {
  $valid = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
  if (strlen($address) != 97) {
    return false;
  }
  if (substr($address, 0, 2) != "bT") {
    return false;
  }
  for ($i = 2; $i < 97; $i++) {
    if (strpos($valid, $address{$i}) === false) {
      return false;
    }
  }
  return true;
}

function validate_hex($str, $len) {
  $valid = "0123456789abcdef";
  if (strlen($str) != $len) {
    return false;
  }
  for ($i = 0; $i < $len; $i++) {
    if (stripos($valid, $str{$i}) === false) {
      return false;
    }
  }
  return true;
}

function validate_spendkey($spendKey) {
  return validate_hex($spendKey, 64);
}

function validate_paymentid($paymentId) {
  return validate_hex($paymentId, 64);
}

function validate_txhash($txhash) {
  return validate_hex($txhash, 64);
}

function validate_email($email) {
  $valid1 = "0123456789abcdefghijklmnopqrstuvwxyz.-+_";
  $valid2 = "0123456789abcdefghijklmnopqrstuvwxyz.";
  $at = strpos($email, '@');
  if ($at === false) {
    return false;
  }
  if ($at === 0) {
    return false;
  }
  if ($at == (strlen($email) - 1)) {
    return false;
  }
  $user = substr($email, 0, $at);
  $domain = substr($email, $at + 1);
  for ($i = 0; $i < strlen($user); $i++) {
    if (strpos($valid1, $user{$i}) === false) {
      return false;
    }
  }
  for ($i = 0; $i < strlen($domain); $i++) {
    if (strpos($valid2, $domain{$i}) === false) {
      return false;
    }
  }
  return true;
}

function validate_int($amount) {
  $valid = "0123456789";
  for ($i = 0; $i < strlen($amount); $i++) {
    if (strpos($valid, $amount{$i}) === false) {
      return false;
    }
  }
  return true;
}

function validate_amount($amount) {
  $valid = "0123456789";
  $dot = strpos($amount, ".");
  if ($dot === false) {
    return validate_int($amount);
  }
  if ($dot === 0) {
    return false;
  }
  if ($dot != (strlen($amount) - 3)) {
    return false;
  }
  for ($i = 0; $i < $dot; $i++) {
    if (strpos($valid, $amount{$i}) === false) {
      return false;
    }
  }
  for ($i = $dot + 1; $i < strlen($amount); $i++) {
    if (strpos($valid, $amount{$i}) === false) {
      return false;
    }
  }
  return true;
}

function validate_contact_name($name) {
  if (strlen($name) < 1 || strlen($name) > 64) {
    return false;
  }
  if (strpos($name, "'") !== false) {
    return false;
  }
  if (strpos($name, '"') !== false) {
    return false;
  }
  if (strpos($name, "\\") !== false) {
    return false;
  }
  for ($i = 0; $i < strlen($name); $i++) {
    if (ord($name{$i}) < 32) {
      return false;
    }
  }
  return true;
}
//echo "Leaving lib/validate.php...<br>";
?>
