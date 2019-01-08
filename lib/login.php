<?php
// Check if user has logged in or not?
if (isset($_POST['spendKey']) && !isset($_POST['authCode'])) {
  // User is logging in...
  $spendKey = $_POST['spendKey'];
  if (!validate_spendkey($spendKey)) {
    echo "<span class='error'>Invalid spend key!</span></div></body></html>";
    exit();
  }
  $authCode = generate_authcode($spendKey);
  $email = get_email_with_spendkey($spendKey);
  if (strlen($email) > 0) {
    send_login_email($email, $authCode);
    echo "<form action='index.php' method='post'>";
    echo "<input type='hidden' name='spendKey' value='", $spendKey, "'>";
    echo "Authentication code: <input type='number' pattern='[0-9]{6}' name='authCode' placeholder='000000' required size='6'><br>";
    echo "<input type='submit' name='Verify' class='btn' value='Verify'>";
    echo "</form></body></html>";
    exit();
  }
  if (log_in($spendKey, $authCode)) {
    echo "Wallet ", get_address($spendKey), " logged in!<br>";
    echo "<script>document.location='index.php';</script>";
  } else {
    echo "<span class='error'>Authentication code not verified!</span></div></body></html>";
    exit();
  }
}
if (isset($_POST['spendKey']) && isset($_POST['authCode'])) {
  $spendKey = $_POST['spendKey'];
  if (!validate_spendkey($spendKey)) {
    echo "<span class='error'>Invalid spend key!</span></div></body></html>";
    exit();
  }
  $authCode = $_POST['authCode'];
  if (log_in($spendKey, $authCode)) {
    echo "Wallet ", get_address($spendKey), " logged in!<br>";
    echo "<script>document.location='index.php';</script>";
  } else {
    echo "<span class='error'>Authentication code not verified!</span></div></body></html>";
    exit();
  }
}
if (!logged_in() && !isset($_POST['spendKey'])) {
  echo "Please log in to access Bittorium web wallet!<br><br>";
  echo "<form action='index.php' method='post'>";
  echo "Wallet key: <input type='text' maxlength=64 name='spendKey' pattern='[0-9a-f]{64}' required size='64'><br>";
  echo "<input type='submit' name='submit' class='btn' value='Log in'><br>";
  echo "</form><hr>";
  echo "If you don't have an account yet, you can register using your e-mail address.<br>";
  echo "<span class='error'>NOTE: Some e-mail providers, including Google Mail and Hotmail/Outlook/Live might block, or mark as spam, e-mails sent from websites.</span><br><br>";
  echo "<a href='register.php' class='btn'>Register an account</a><br>";
}
?>
