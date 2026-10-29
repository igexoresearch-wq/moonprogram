<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection parameters
$servername = "localhost"; // Replace with your database server name
$username = "victorywebproductions";       // Replace with your database username
$password = "CHANGE_ME";           // Replace with your database password
$dbname = "moonland"; // Replace with your database name

// Establish connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>
