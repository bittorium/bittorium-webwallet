<?php
  echo "<div style='clear: left;'></div>";
  echo "<div class='menu'>";
  echo "<p class='balance'>";
  echo "<b>Available balance:</b><br>";
  echo number_format($availableBalance / 100, 2), " BTOR<br>";
  echo "<b>Locked balance:</b><br>";
  echo number_format($lockedBalance / 100, 2), " BTOR<br>";
  echo "</p><br>";
  echo "<a href='index.php'>Transactions</a><br>";
  echo "<a href='send.php'>Send BTOR</a><br>";
  echo "<a href='contacts.php'>Contacts</a><br>";
  echo "<a href='info.php'>Wallet info</a><br>";
  echo "<a href='logout.php'>Logout</a><br>";
  $dt = date("Y");
  echo "<p class='footer'>&copy; ", dt != "2018" ? "2018&ndash;" : "", date("Y"), " Bittorium Project</p>";
  echo "</div>";
?>
