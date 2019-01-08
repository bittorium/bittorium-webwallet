<html>
<head>
<title>Bittorium webwallet</title>
<link rel="shortcut icon" href="images/logo.png" >
</head>
<body>
<?php
setcookie("spendKey", "", time() - 3600);
setcookie("skip", 0, time() - 3600);
?>
You have been logged out!
<script>document.location='index.php';</script>
</body>
</html>
