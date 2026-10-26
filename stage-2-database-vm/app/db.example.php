<?php
// Connection to the REDEEMALL database on the DB VM.
$servername = "10.0.2.4";              // private IP of the DB VM
$username   = "redeemuser";
$password   = "CHANGE_ME";            // the password you used in CREATE USER (never commit the real one)
$dbname     = "REDEEMALL";

if (!class_exists('mysqli')) {
    die("PHP MySQL driver missing. Run: sudo apt install -y php-mysql && sudo systemctl restart apache2");
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
