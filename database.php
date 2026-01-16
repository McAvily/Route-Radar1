<?php
$host = "sql12.freesqldatabase.com";
$user = "sql12814508";
$pass = "dETCcpjjvi";
$db = "sql12814508";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
