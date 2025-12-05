<?php
// MySQL connection configuration
$host = 'localhost';
$username = 'hounty';
$password = 'thebat1939';
$database = 'myapp';

// Establishing the connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check if the connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
