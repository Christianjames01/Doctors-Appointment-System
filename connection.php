<?php
// connection.php - Database Connection File

$host = "localhost";        
$username = "root";
$password = "";
$database_name = "edoc";


$database = new mysqli($host, $username, $password, $database_name);

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
