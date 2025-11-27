<?php
// process_booking.php
session_start();

// Database configuration (UPDATE THESE WITH YOUR DATABASE DETAILS)
$servername = "localhost";
$username = "root";  // Your database username
$password = "";      // Your database password
$dbname = "dental_clinic";  // Your database name

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

// Sanitize and validate input
$fullname = trim(htmlspecialchars($_POST['fullname'] ?? ''));
$email = trim(htmlspecialchars($_POST['email'] ?? ''));
$service = trim(htmlspecialchars($_POST['service'] ?? ''));
$date = trim(htmlspecialchars($_POST['date'] ?? ''));
$time = trim(htmlspecialchars($_POST['time'] ?? ''));

// Validation
$errors = [];

if (empty($fullname)) {
    $errors[] = "Full name is required.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
}

if (empty($service)) {
    $errors[] = "Please select a service.";
}

if (empty($date)) {
    $errors[] = "Date is required.";
} else {
    // Check if date is not in the past
    $selected_date = strtotime($date);
    $today = strtotime(date('Y-m-d'));
    if ($selected_date < $today) {
        $errors[] = "Please select a future date.";
    }
}

if (empty($time)) {
    $errors[] = "Time is required.";
}

// If there are validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: booking.php');
    exit;
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If database connection fails, you can either:
    // 1. Save to a text file as backup
    // 2. Send email notification
    // 3. Show error to user
    
    // For now, we'll store in session and show success (backup method)
    $_SESSION['booking_success'] = true;
    $_SESSION['booking_details'] = [
        'fullname' => $fullname,
        'email' => $email,
        'service' => $service,
        'date' => $date,
        'time' => $time,
        'booking_id' => 'TEMP-' . strtoupper(substr(md5(time()), 0, 8))
    ];
    
    // Log to file as backup
    $log_data = date('Y-m-d H:i:s') . " - Booking: $fullname, $email, $service, $date, $time\n";
    file_put_contents('bookings_backup.txt', $log_data, FILE_APPEND);
    
    header('Location: booking_confirmation.php');
    exit;
}

// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO appointments (fullname, email, service, appointment_date, appointment_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

$status = 'pending'; // Default status
$stmt->bind_param("ssssss", $fullname, $email, $service, $date, $time, $status);

// Execute the statement
if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;
    
    // Store success data in session
    $_SESSION['booking_success'] = true;
    $_SESSION['booking_details'] = [
        'booking_id' => $booking_id,
        'fullname' => $fullname,
        'email' => $email,
        'service' => $service,
        'date' => $date,
        'time' => $time
    ];
    
    // Optional: Send confirmation email
    sendConfirmationEmail($email, $fullname, $service, $date, $time, $booking_id);
    
    // Redirect to confirmation page
    header('Location: booking_confirmation.php');
    exit;
    
} else {
    // Error inserting into database
    $_SESSION['errors'] = ["An error occurred while booking your appointment. Please try again."];
    $_SESSION['form_data'] = $_POST;
    header('Location: booking.php');
    exit;
}

$stmt->close();
$conn->close();

// Email function (optional)
function sendConfirmationEmail($to_email, $name, $service, $date, $time, $booking_id) {
    $subject = "Appointment Confirmation - Dr. Dental Clinic Center";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #667eea; }
            .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Appointment Confirmed!</h1>
            </div>
            <div class='content'>
                <p>Dear $name,</p>
                <p>Thank you for booking an appointment with Dr. Dental Clinic Center.</p>
                
                <div class='details'>
                    <h3>Appointment Details:</h3>
                    <p><strong>Booking ID:</strong> #$booking_id</p>
                    <p><strong>Service:</strong> $service</p>
                    <p><strong>Date:</strong> " . date('l, F j, Y', strtotime($date)) . "</p>
                    <p><strong>Time:</strong> " . date('g:i A', strtotime($time)) . "</p>
                </div>
                
                <p><strong>What to bring:</strong></p>
                <ul>
                    <li>Valid ID</li>
                    <li>Insurance card (if applicable)</li>
                    <li>Previous dental records (if any)</li>
                </ul>
                
                <p>Please arrive 10 minutes early to complete any necessary paperwork.</p>
                <p>If you need to reschedule or cancel, please contact us at least 24 hours in advance.</p>
                
                <p><strong>Contact us:</strong> (123) 456-7890</p>
            </div>
            <div class='footer'>
                <p>© 2024 Dr. Dental Clinic Center. All rights reserved.</p>
                <p>Your Smile is Our Priority 😊</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Dr. Dental Clinic <noreply@dentalclinic.com>" . "\r\n";
    
    // Send email (make sure your server has mail configured)
    @mail($to_email, $subject, $message, $headers);
}
?>