<?php
// connection.php - Database Connection File

$host = "localhost";
$username = "root";
$password = "";
$database = "edoc";

// Create connection
$database = new mysqli($host, $username, $password, $database);

// Check connection
if ($database->connect_error) {
    die("Connection failed: " . $database->connect_error);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>