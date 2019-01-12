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
  $action = 'viewall';
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
  }
  if (isset($_GET['view'])) {
    $action = "view";
  }
  if ($action == 'viewall') {
    echo "<div id='wallet'>Address:&nbsp;", $address, "</div>";
    echo "<br>";
    //
    $contacts = get_contacts($spendKey);
    if ($contacts === false) {
      echo "<span class='error'>No contacts</span>";
    } else {
//    echo "Contacts: ";
//    var_dump($contacts);
      if (count($contacts) > 0) {
        echo "<div class='hscroll'>";
        echo "<table class='contacts'>";
        echo "<thead>";
        echo "<th>Name</th><th>Address</th><th>Payment ID</th>";
        echo "</thead>";
        echo "<tbody>";
        foreach ($contacts as $contact) {
          echo "<tr><td><a href='contacts.php?view=".htmlspecialchars($contact["name"])."'>".htmlspecialchars($contact["name"])."</a></td><td>".$contact["address"]."</td><td>".$contact["paymentID"]."</td></tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
      }
    }
    echo "<h3>Add contact</h3>";
    echo "<form action='contacts.php' method='post'>";
    echo "<input type='hidden' name='action' value='add'>";
    echo "<table class='contact'>";
    echo "<tr><th>Name:</th><td><input type='string' name='name' minlength='1' required size='64' value=''></td></tr>";
    echo "<tr><th>Address:</th><td><input type='string' name='address' maxlength='97' required pattern='bT[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{95}' size='97 value=''></td><tr>";
    echo "<tr><th>Payment ID:</th><td><input type='string' name='paymentID' maxlength='64' pattern='.{0}|[0-9a-fA-F]{64}' size='64' value=''></td></tr>";
    echo "<tr><td colspan='2' class='submit'><input type='submit' class='btn' name='submit' value='Add'></td></tr></table></form>";
  } else if ($action == 'add') {
    $cname = $_POST['name'];
    $caddress = $_POST['address'];
    $cpaymentID = $_POST['paymentID'];
    if (!validate_contact_name($cname)) {
      echo "<span class='error'>Contact name is invalid!</span></div></body></html>";
      exit();
    }
    if (!validate_address($caddress)) {
      echo "<span class='error'>Contact address is invalid!</span></div></body></html>";
      exit();
    }
    if (strlen($cpaymentID) > 0 && !validate_paymentid($cpaymentID)) {
      echo "<span class='error'>Contact&apos;s payment ID is invalid!</span></div></body></html>";
      exit();
    }
    if (has_contact($spendKey, $cname)) {
      echo "<span class='error'>Contact with name '".htmlspecialchars($cname)."' already exists!</span><br><br>";
      echo "<a href='contacts.php?view=".htmlspecialchars($cname)."' class='btn'>View</a>";
      echo "</div></body></html>";
      exit();
    }
    $result = create_contact($spendKey, $cname, $caddress, $cpaymentID);
    if ($result) {
      echo "Contact created.<br>";
    } else {
      echo "<span class='error'>Creating contact failed.</span><br>";
    }
    echo "<br><a href='contacts.php' class='btn'>Return</a>";
//    var_dump($result);
  } else if ($action == 'view') {
    $cname = $_GET['view'];
    if (!validate_contact_name($cname)) {
      echo "<span class='error'>Contact name is invalid!</span></div></body></html>";
      exit();
    }
    $contact=get_contact($spendKey, $cname);
//  var_dump($contact);
    echo "<h3>Contact &quot;", htmlspecialchars($cname), "&quot;</h3>";
    echo "<table class='contact'>";
    echo "<tr>";
    echo "<form action='contacts.php' method='post'>";
    echo "<input type='hidden' name='action' value='update'>";
    echo "<input type='hidden' name='name' value='".htmlspecialchars($cname)."'>";
    echo "<tr><th>Address:</th><td><input type='string' maxlength='97' required pattern='bT[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{95}' name='address' size='97' value='".$contact["address"]."'></td></tr>";
    echo "<tr><th>Payment ID:</th><td><input type='string' maxlength='64' pattern='.{0}|[0-9a-fA-F]{64}' name='paymentID' size='64' value='".$contact["paymentID"]."'></td></tr>";
    echo "<tr><td colspan='2' class='submit'><input type='submit' class='btn' name='submit' value='Update'></td></tr>";
    echo "</form>";
    echo "</table>";
    echo "<h3>Rename contact</h3>";
    echo "<table class='contact'>";
    echo "<form action='contacts.php' method='post'>";
    echo "<input type='hidden' name='action' value='rename'>";
    echo "<input type='hidden' name='oldName' value='".htmlspecialchars($cname)."'>";
    echo "<tr><th>New name:</th><td><input type='string' minlength='1' name='newName' size='64' value=''></td></tr>";
    echo "<tr><td colspan='2' class='submit'><input type='submit' class='btn' name='submit' value='Rename'></td></tr>";
    echo "</form>";
    echo "</table>";
    $maxAmount = $availableBalance - 1;
    $getFeeAddress = daemonrpc_get("/feeaddress");
    if (array_key_exists('fee_address', $getFeeAddress)) {
      $feeAddress = $getFeeAddress->fee_address;
      if (validate_address($feeAddress)) {
        $feeAmount = min(1, max(floatval($maxAmount) / 400001, 100));
        $maxAmount -= $feeAmount;
      }
    }
    if ($maxAmount > 0) {
      echo "<h3>Send BTOR to contact</h3>";
      echo "<form action='send.php' method='post'>";
      echo "<input type='hidden' name='recipient' value='".$contact['address']."'>";
      echo "<input type='hidden' name='paymentID' value='".$contact['paymentID']."'>";
      echo "<table class='send'>";
      echo "<tr><th>Amount:</th><td><input type='number' min='0.01' max='" . number_format($maxAmount / 100, 2) . "' step='0.01' name='amount' value='0.01'></td>";
      echo "<td rowspan='2'><input type='submit' class='btn' name='submit' value='Send'></td>";
      echo "</tr>";
      echo "<tr><th>Anonymity level:</th><td><input type='number' min='0' max='9' step='1' name='anonymity' value='0'></td></tr>";
      echo "</table>";
      echo "</form>";
    }
    echo "<h3>Delete contact</h3>";
    echo "<form action='contacts.php' method='post'>";
    echo "<input type='hidden' name='action' value='delete'>";
    echo "<input type='hidden' name='name' value='".htmlspecialchars($cname)."'>";
    echo "<input type='submit' name='submit' class='btn' value='Delete'>";
    echo "</form>";
   } else if ($action == 'rename') {
    $oldName=$_POST['oldName'];
    $newName=$_POST['newName'];
    if (!validate_contact_name($oldName) || !has_contact($spendKey, $oldName)) {
      echo "<span class='error'>Old contact name is not valid!</span></div></body></html>";
      exit();
    }
    if (!validate_contact_name($newName) || has_contact($spendKey, $newName)) {
      echo "<span class='error'>New contact name is not valid!</span></div></body></html>";
      exit();
    }
    if ($oldName == $newName) {
      echo "<span class='error'>Old and new names are the same!</span></div></body></html>";
      exit();
    }
    $result = rename_contact($spendKey, $oldName, $newName);
    if ($result) {
      echo "Contact renamed...<br><br>";
      echo "<a href='contacts.php?view=".htmlspecialchars($newName)."' class='btn'>View</a>";
    } else {
      echo "<span class='error'>Renaming failed.</span><br><br>";
      echo "<a href='contacts.php?view=".htmlspecialchars($oldName)."' class='btn'>View</a>";
    }
  } else if ($action == 'update') {
    $cname=$_POST['name'];
    $caddress=$_POST['address'];
    $cpaymentID=$_POST['paymentID'];
    if (!validate_contact_name($cname) || !has_contact($spendKey, $cname)) {
      echo "<span class='error'>Contact name is invalid!</span></div></body></html>";
      exit();
    }
    if (!validate_address($caddress)) {
      echo "<span class='error'>Contact address is invalid!</span></div></body></html>";
      exit();
    }
    if (strlen($cpaymentID) > 0 && !validate_paymentid($cpaymentID)) {
      echo "<span class='error'>Contact&apos;s payment ID is invalid!</span></div></body></html>";
      exit();
    }
    $result = update_contact($spendKey, $cname, $caddress, $cpaymentID);
    if ($result) {
      echo "Contact updated...<br>";
    } else {
      echo "<span class='error'>Updating of contact failed...</span><br>";
    }
    echo "<br><a href='contacts.php?view=".htmlspecialchars($cname)."' class='btn'>View</a>";
  } else if ($action == 'delete') {
    $cname=$_POST['name'];
    if (!validate_contact_name($cname)) {
      echo "<span class='error'>Contact name is invalid!</span></div></body></html>";
      exit();
    }
    if (!has_contact($spendKey, $cname)) {
      echo "<span class='error'>Contact does not exist!</span></div></body></html>";
      exit();
    }
    $result=delete_contact($spendKey, $cname);
    if ($result) {
      echo "Contact deleted...<br><br>";
      echo "<a href='contacts.php' class='btn'>Back</a>";
    } else {
      echo "<span class='error'>Deleting of contact failed...</span><br><br>";
      echo "<a href='contacts.php?view=".htmlspecialchars($cname)."' class='btn'>View</a>";
    }
  }
}
?>
</div>
</body>
</html>
