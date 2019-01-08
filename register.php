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
$address = "";
if (logged_in()) {
  $spendKey = $_COOKIE['spendKey'];
  if (validate_spendkey($spendKey)) {
    $address = get_address($spendKey);
    echo "<span class='error'>You are already logged in as ", $address, ", please log out before trying to register a new account.</span>";
    echo "</div></body></html>";
    exit();
  }
}
//
if (isset($_POST['email']) && !isset($_POST['authCode'])) {
  $email = $_POST['email'];
  if (!validate_email($email)) {
    echo "<span class='error'>Invalid e-mail!</span></div></body></html>";
    exit();
  }
  if (email_registered($email)) {
    echo "<span class='error'>User account with e-mail ", $email, " already exists!</span></div></body></html>";
    exit();
  }
  $authCode = generate_authcode_with_email($email);
  if ($authCode === false) {
    echo "<span class='error'>Database error!</span></div></body></html>";
    exit();
  }
  send_auth_email($email, $authCode);
  // ask user to enter auth code and verify it...
  echo "<form action='register.php' method='post'>";
  echo "<input type='hidden' value='", $_POST['email'], "' name='email'>";
  echo "Enter authentication code: <input type='number' pattern='[0-9]{6}' name='authCode' placeholder='000000' required size='6'><br>";
  echo "<input type='submit' name='submit' class='btn' value='Register'>";
  echo "</form><br>";
  echo "<font color='red'>WARNING: Reloading page will invalidate previously sent authentication code. ";
  echo "Browsing to another page, or closing the browser tab or window will lock out the e-mail address!</font>";
  echo "</div></body></html>";
  exit();
}
if (isset($_POST['email']) && isset($_POST['authCode'])) {
  $email = $_POST['email'];
  if (!validate_email($email)) {
    echo "<span class='error'>Invalid e-mail!</span></div></body></html>";
    exit();
  }
  if (!email_registered($email)) {
    echo "<span class='error'>User account with e-mail " . $email . " does not exist!</span></div></body></html>";
    exit();
  }
  $authCode1 = $_POST['authCode'];
  $authCode2 = get_authcode_with_email($email);
  if ($authCode1 != $authCode2) {
//    echo "User: ", $authCode1, "<br>";
//    echo "Database: ", $authCode2, "<br>";
    echo "<span class='error'>Invalid authentication code!</span></div></body></html>";
    exit();
  }
  echo "Generating wallet with email ", $email, "<br>";
  if (generate_wallet_with_email($email)) {
    echo "Registration successful!<br>";
    $spendKey = get_spendkey_with_email($email);
    send_key_email($email, $spendKey);
    echo "<a href='index.php'>Continue...</a></div></body></html>";
    exit();
  }
  echo "<span class='error'>Registration failed!</span></div></body></html>";
  exit();
}

echo "<form action='register.php' method='post'>";
echo "E-mail address: <input type='email' name='email' required placeholder='youremail@domain.com'><br>";
echo "<input type='submit' name='submit' class='btn' value='Validate'>";
?>
</div>
</body>
</html>
