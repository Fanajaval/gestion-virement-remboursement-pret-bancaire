<?php
$host = "sql304.infinityfree.com";
$user = "if0_42438965";
$password = "Evon2004Banks";
$dbname = "if0_42438965_bankonline";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Echec de connexion :" . $conn->connect_error);
}
?>