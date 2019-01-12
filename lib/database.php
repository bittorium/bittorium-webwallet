<?php
//echo "Entering lib/database.php<br>";
$db = NULL;

function open_database() {
  global $db;
  if ($db === NULL) {
    $db = new SQLite3('/webwallet/data/db/database.db', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
  }
}

function add_user($emailAddress, $spendKey, $address, $authCode) {
  global $db;
  open_database();
  return $db->exec("INSERT INTO users (emailAddress, spendKey, address, authCode) VALUES ('" . $emailAddress . "', '" . $spendKey . "', '" . $address . "', '" . $authCode . "');");
}

function check_spendkey($spendKey) {
  global $db;
  open_database();
  $result = $db->query("SELECT count(*) FROM users WHERE spendKey = '" . $spendKey . "';");
  $arr = $result->fetchArray();
  return ($arr["count(*)"] != 0);
}

function get_address($spendKey) {
  global $db;
  open_database();
  $result = $db->query("SELECT address FROM users WHERE spendKey = '" . $spendKey . "';");
  $arr = $result->fetchArray();
  return $arr["address"];
}

function get_authcode($spendKey) {
  global $db;
  open_database();
  $result = $db->query("SELECT authCode FROM users WHERE spendKey = '" . $spendKey . "';");
  $arr = $result->fetchArray();
  return $arr["authCode"];
}

function get_authcode_with_email($email) {
  global $db;
  open_database();
  $result = $db->query("SELECT authCode FROM users WHERE emailAddress = '" . $email . "';");
  $arr = $result->fetchArray();
  return $arr["authCode"];
}

function generate_authcode($spendKey) {
  global $db;
  open_database();
  $code = random_int(0, 999999);
  $authCode = sprintf("%06d", $code);
  if ($db->exec("UPDATE users SET authCode = '".$authCode."' WHERE spendKey='".$spendKey."';"))
  {
    return $authCode;
  }
  return false;
}

function email_registered($email) {
  global $db;
  open_database();
  $result = $db->query("SELECT count(*) FROM users WHERE emailAddress='".$email."';");
  $arr = $result->fetchArray();
  if ($arr["count(*)"] != 0) {
    return true;
  }
  return false;
}

function generate_authcode_with_email($email) {
  global $db;
  open_database();
  $code = random_int(0, 999999);
  $authCode = sprintf("%06d", $code);
  if ($db->exec("INSERT INTO users (emailAddress, authCode) VALUES ('".$email."','".$authCode."');"))
  {
    return $authCode;
  }
  return false;
}

function get_spendkey_with_address($address) {
  $params = Array();
  $params["address"] = $address;
  $getSpendKeys = walletrpc_post("getSpendKeys", $params);
  $spendKey = $getSpendKeys->spendSecretKey;
  return $spendKey;
}

function check_database() {
  global $db;
  open_database();
  $result = $db->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='users';");
  $arr = $result->fetchArray();
  if ($arr["count(*)"] == 0) {
     echo "Creating users table...<br>";
      if ($db->exec("CREATE TABLE users (emailAddress TEXT, spendKey TEXT, address TEXT, authCode TEXT);")) {
        echo "Users table created...<br>";
      } else {
        echo "Creating users table failed.<br>";
        return false;
      }
  }
// echo "Reading users table...<br>";
  $result = $db->query("SELECT count(*) FROM users;");
  $arr = $result->fetchArray();
  if ($arr["count(*)"] == 0) {
//  echo "Importing users from wallet daemon...<br>";
    $getViewKey = walletrpc_post("getViewKey");
    $viewKey = $getViewKey->viewSecretKey;
//  echo "View key: ", $viewKey, "<br>";
    $getAddresses = walletrpc_post("getAddresses");
    $addresses = $getAddresses->addresses;
//  echo "Found ", count($addresses), " wallets...<br>";
    foreach ($addresses as $address) {
//    echo "Importing ", $address, "...<br>";
      $spendKey = get_spendkey_with_address($address);
//    echo "Spend key: ", $spendKey, "<br>";
      if (add_user('', $spendKey, $address, '')) {
//	echo "Added wallet.<br>";
      } else {
//      echo "Adding wallet failed: ", $db->lastErrorMsg(), "<br>";
      }
    }
  }
  $result = $db->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='contacts';");
  $arr = $result->fetchArray();
  if ($arr["count(*)"] == 0) {
    echo "Creating contacts table...<br>";
    if ($db->exec("CREATE TABLE contacts (spendKey TEXT, name TEXT, address TEXT, paymentID TEXT);")) {
      echo "Contacts table created...<br>";
    } else {
      echo "Creating contacts table failed.<br>";
      return false;
    }
  }
  return true;
}

function generate_wallet_with_email($email) {
  global $db;
  $generateWallet = walletrpc_post("createAddress");
  $address = $generateWallet->address;
  $spendKey = get_spendkey_with_address($address);
//echo "Generated wallet with spend key ", $spendKey, " and address ", $address, "<br>";
  $result = $db->query("UPDATE users SET spendKey = '".$spendKey."', address = '".$address."' WHERE emailAddress='".$email."';");
  return $result;
}

function get_spendkey_with_email($email) {
  global $db;
  $result = $db->query("SELECT spendKey FROM users WHERE emailAddress='".$email."';");
  $arr = $result->fetchArray();
  return $arr['spendKey'];
}

function get_email_with_spendkey($spendKey) {
  global $db;
  $result = $db->query("SELECT emailAddress FROM users WHERE spendKey='".$spendKey."';");
  $arr = $result->fetchArray();
  return $arr['emailAddress'];
}

function set_email_with_spendkey($spendKey, $email) {
  global $db;
  $result = $db->query("UPDATE users SET emailAddress = '".$email."' WHERE spendKey='".$spendKey."';");
  return $result;
}

function create_contact($spendKey, $name, $address, $paymentID) {
  global $db;
  $result = $db->query("INSERT INTO contacts (spendKey, name, address, paymentID) VALUES ('".$spendKey."', '".$name."', '".$address."', '".$paymendID."');");
  return $result;
}

function delete_contact($spendKey, $name) {
  global $db;
  $result = $db->query("DELETE FROM contacts WHERE spendKey = '".$spendKey."' AND name = '".$name."';");
  return $result;
}

function rename_contact($spendKey, $oldName, $newName) {
  global $db;
  $result = $db->query("UPDATE contacts SET name = '".$newName."' WHERE spendKey = '".$spendKey."' AND name = '".$oldName."';");
  return $result;
}

function update_contact($spendKey, $name, $address, $paymentID) {
  global $db;
  $result = $db->query("UPDATE contacts SET address = '".$address."', paymentID = '".$paymentID."' WHERE spendKey = '".$spendKey."' AND name = '".$name."';");
  return $result;
}

function has_contact($spendKey, $name) {
  global $db;
  $result = $db->query("SELECT count(*) FROM contacts WHERE spendKey ='".$spendKey."' AND name = '".$name."';");
  $arr = $result->fetchArray();
  return ($arr["count(*)"] != 0);
}

function get_contact($spendKey, $name) {
  global $db;
  $result = $db->query("SELECT address, paymentID FROM contacts WHERE spendKey = '".$spendKey."' AND name = '".$name."';");
  $arr = $result->fetchArray();
  return $arr;
}

function get_contacts($spendKey) {
  global $db;
  $result = $db->query("SELECT name, address, paymentID FROM contacts WHERE spendKey = '".$spendKey."';");
  if ($result->numColumns() == 0) {
    return false;
  }
  $contacts = Array();
  $arr = Array();
  while ($arr = $result->fetchArray()) {
    $contacts[] = $arr;
  }
  return $contacts;
}
//echo "Leaving lib/database.php<br>";
?>
