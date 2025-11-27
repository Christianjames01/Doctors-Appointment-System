<?php
// admin/delete-appointment.php
session_start();

// Check if admin is logged in
if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit;
    }
}else{
    header("location: ../login.php");
    exit;
}

// Import database connection
include("../connection.php");

// Check if ID is provided
if(isset($_GET["id"])){
    $id = (int)$_GET["id"];
    
    // Delete the appointment
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()){
        $_SESSION['message'] = "Appointment deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting appointment!";
    }
    
    $stmt->close();
} else {
    $_SESSION['message'] = "Invalid appointment ID!";
}

// Redirect back to appointment page
header("Location: appointment.php");
exit;
?>