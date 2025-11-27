<?php
// Start session and output buffering to prevent header errors
ob_start();
session_start();
include("connection.php");

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    session_unset();
    session_destroy();
    session_start();
    header("Location: login.php?redirect=booking.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get patient information
$email = $_SESSION['user'];
$patient_query = $database->query("SELECT * FROM patient WHERE pemail='$email'");

if ($patient_query->num_rows == 0) {
    session_unset();
    session_destroy();
    header("Location: login.php?redirect=booking.php");
    exit();
}

$patient = $patient_query->fetch_assoc();
$patient_id = $patient['pid'];
$patient_name = $patient['pname'];

// Service prices
$service_prices = [
    'General Checkup' => 500,
    'Teeth Cleaning' => 800,
    'Teeth Whitening' => 3000,
    'Tooth Extraction' => 1500,
    'Dental Filling' => 1200,
    'Root Canal Treatment' => 5000,
    'Braces Consultation' => 1000,
    'Dental Crown' => 4000,
    'Dental Bridge' => 6000,
    'Dental Implant' => 15000,
    'Gum Treatment' => 2000,
    'Emergency Dental Care' => 2500,
    'Pediatric Dentistry' => 800,
    'Orthodontics' => 3500,
    'Cosmetic Dentistry' => 4500
];

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date = mysqli_real_escape_string($database, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($database, $_POST['appointment_time']);
    $service_type = mysqli_real_escape_string($database, $_POST['service_type']);
    
    if (empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
        $error_message = "Please fill in all fields to book an appointment.";
    } else {
        // Validate date is not in the past
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            $error_message = "Cannot book appointments in the past.";
        } else {
            // Check if the selected time slot is already booked
            $appodate = $appointment_date . ' ' . $appointment_time;
            
            $check_query = $database->query("
                SELECT COUNT(*) as count FROM appointment 
                WHERE DATE(appodate) = '$appointment_date' 
                AND TIME(appodate) = '$appointment_time'
            ");
            
            $check_result = $check_query->fetch_assoc();
            
            if ($check_result['count'] > 0) {
                $error_message = "This time slot is already booked. Please choose a different time.";
            } else {
                // Generate unique appointment number
                $appointment_number = rand(100000, 999999);
                
                // Get service price
                $service_price = $service_prices[$service_type] ?? 1000;
                
                // Insert appointment with approved status and unpaid payment status
                $insert_query = "INSERT INTO appointment (apponum, pid, appodate, service_type, status, amount, payment_status) 
                                VALUES ('$appointment_number', '$patient_id', '$appodate', '$service_type', 'approved', '$service_price', 'unpaid')";
                
                if ($database->query($insert_query)) {
                    // Get the newly created appointment ID
                    $new_appointment_id = $database->insert_id;
                    
                    // Clear any output buffer and redirect
                    ob_end_clean();
                    
                    // Try PHP header redirect first
                    if (!headers_sent()) {
                        header("Location: payment.php?appo_id=" . $new_appointment_id);
                        exit();
                    } else {
                        // Fallback to JavaScript redirect if headers already sent
                        echo "<script>window.location.href='payment.php?appo_id=" . $new_appointment_id . "';</script>";
                        exit();
                    }
                } else {
                    $error_message = "Error booking appointment: " . $database->error;
                }
            }
        }
    }
}

// Check available time slots for a specific date via AJAX
if (isset($_GET['check_slots']) && isset($_GET['date'])) {
    $check_date = mysqli_real_escape_string($database, $_GET['date']);
    
    $booked_slots = [];
    $booked_query = $database->query("
        SELECT TIME_FORMAT(TIME(appodate), '%H:%i') as time_slot 
        FROM appointment 
        WHERE DATE(appodate) = '$check_date'
    ");
    
    while ($row = $booked_query->fetch_assoc()) {
        $booked_slots[] = $row['time_slot'];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['booked_slots' => $booked_slots]);
    exit();
}

// Add endpoint to fetch booked dates for entire month
if (isset($_GET['check_month_slots']) && isset($_GET['year']) && isset($_GET['month'])) {
    $year = (int)$_GET['year'];
    $month = (int)$_GET['month'];
    
    $booked_dates = [];
    $query = $database->query("
        SELECT DATE(appodate) as date, COUNT(*) as booking_count
        FROM appointment 
        WHERE YEAR(appodate) = $year 
        AND MONTH(appodate) = $month
        GROUP BY DATE(appodate)
    ");
    
    while ($row = $query->fetch_assoc()) {
        $booked_dates[$row['date']] = (int)$row['booking_count'];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['booked_dates' => $booked_dates]);
    exit();
}

// Get patient's appointments - UPCOMING FIRST (sorted by date ascending for future, then past descending)
date_default_timezone_set('Asia/Manila');
$appointments_query = $database->query("
    SELECT * FROM appointment 
    WHERE pid = '$patient_id' 
    ORDER BY 
        CASE 
            WHEN appodate >= NOW() THEN 0 
            ELSE 1 
        END,
        CASE 
            WHEN appodate >= NOW() THEN appodate 
        END ASC,
        CASE 
            WHEN appodate < NOW() THEN appodate 
        END DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Dr. Dental Care Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }
        
        .logo-icon {
            font-size: 40px;
        }
        
        .logo-text h1 {
            font-size: 28px;
            font-weight: 700;
        }
        
        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .booking-section {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-header h2 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .booking-header .welcome {
            font-size: 18px;
            color: #666;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Calendar Styles */
        .calendar-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-header h4 {
            color: #667eea;
            font-size: 18px;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-nav button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calendar-nav button:hover {
            background: #5568d3;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            color: #667eea;
            padding: 10px;
            font-size: 12px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            background: white;
        }
        
        .calendar-day:hover:not(.empty):not(.past) {
            border-color: #667eea;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .calendar-day.empty {
            cursor: default;
            opacity: 0.3;
            background: transparent;
        }
        
        .calendar-day.empty:hover {
            border-color: #e0e0e0;
            transform: none;
            box-shadow: none;
        }
        
        .calendar-day.past {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .calendar-day.past:hover {
            border-color: #e0e0e0;
            transform: none;
            box-shadow: none;
        }
        
        .calendar-day.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .calendar-day.has-booking {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
            border-color: #ffc107;
        }
        
        .calendar-day.has-booking::after {
            content: '';
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: #ff6b6b;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(255, 107, 107, 0.5);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        .calendar-day.has-booking.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .calendar-day.has-booking.selected::after {
            background: white;
            box-shadow: 0 0 4px rgba(255, 255, 255, 0.8);
        }
        
        .booking-count {
            position: absolute;
            bottom: 2px;
            font-size: 9px;
            color: #666;
            font-weight: 700;
        }
        
        .calendar-day.selected .booking-count {
            color: white;
        }
        
        .time-slots-container {
            margin-top: 20px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .time-slots-container.show {
            display: block;
        }
        
        .time-slots-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .time-slots-header h4 {
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .slots-info {
            font-size: 13px;
            color: #666;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .time-slot {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 13px;
            background: white;
            position: relative;
        }
        
        .time-slot:hover:not(.booked) {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
        }
        
        .time-slot.booked {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border-color: #ef5350;
            color: #c62828;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .time-slot.booked::before {
            content: '\f00d';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 2px;
            right: 4px;
            font-size: 10px;
            color: #ef5350;
        }
        
        .time-slot.booked:hover {
            border-color: #ef5350;
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            transform: none;
            box-shadow: none;
        }
        
        .time-slot.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .time-slot.selected::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 2px;
            right: 4px;
            font-size: 10px;
            color: white;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .legend-box {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 2px solid;
        }
        
        .legend-available {
            background: white;
            border-color: #e0e0e0;
        }
        
        .legend-booked {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
            border-color: #ffc107;
        }
        
        .legend-selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .appointment-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 16px;
            border-left: 5px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .appointment-card.upcoming {
            border-left-color: #28a745;
            background: linear-gradient(to right, #f0fff4 0%, white 100%);
        }
        
        .appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .appointment-card .appt-number {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 12px;
        }
        
        .appointment-card.upcoming .appt-number {
            color: #28a745;
        }
        
        .appointment-card .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .time-slot-indicator {
            margin-top: 10px;
            padding: 12px;
            background: #e3f2fd;
            border-radius: 8px;
            font-size: 13px;
            color: #1565c0;
            display: none;
        }
        
        .time-slot-indicator.available {
            background: #e8f5e9;
            color: #2e7d32;
            display: block;
        }
        
        .time-slot-indicator.booked {
            background: #ffebee;
            color: #c62828;
            display: block;
        }
        
        .sidebar {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .sidebar h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .quick-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
        
        .pay-now-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .pay-now-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .empty-appointments {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-appointments i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .booking-section {
                padding: 25px;
            }
            
            .logo-text h1 {
                font-size: 22px;
            }
            
            .time-slots-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .calendar-grid {
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                <i class="fas fa-tooth logo-icon"></i>
                <div class="logo-text">
                    <h1>Dr. Dental Care Center</h1>
                    <p>Your smile is our priority</p>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
        
        <div class="main-content">
            <div class="booking-section">
                <div class="booking-header">
                    <h2>
                        <i class="fas fa-calendar-plus"></i>
                        Book Your Appointment
                    </h2>
                    <p class="welcome">Welcome back, <strong><?php echo htmlspecialchars($patient_name); ?></strong>!</p>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo $success_message; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo $error_message; ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="form-section">
                    <h3>
                        <i class="fas fa-calendar-check"></i>
                        Schedule New Appointment
                    </h3>
                    
                    <!-- Calendar View -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h4 id="currentMonthYear"></h4>
                            <div class="calendar-nav">
                                <button onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <button onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                        
                        <div class="calendar-grid" id="calendarGrid">
                            <!-- Calendar will be generated by JavaScript -->
                        </div>
                        
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-box legend-available"></div>
                                <span>Available</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box legend-booked"></div>
                                <span>Has Bookings</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box legend-selected"></div>
                                <span>Selected</span>
                                </div>
                        </div>
                    </div>
                    <!-- Time Slots -->
                <div class="time-slots-container" id="timeSlotsContainer">
                    <div class="time-slots-header">
                        <h4>
                            <i class="fas fa-clock"></i> Available Time Slots
                        </h4>
                        <div class="slots-info">
                            <span id="availableSlotsCount">0</span> slots available
                        </div>
                    </div>
                    <div class="time-slots-grid" id="timeSlotsGrid">
                        <!-- Time slots will be generated by JavaScript -->
                    </div>
                </div>
                <!-- Time Slots -->
                <div class="time-slots-container" id="timeSlotsContainer">
                    <div class="time-slots-header">
                        <h4>
                            <i class="fas fa-clock"></i> Available Time Slots
                        </h4>
                        <div class="slots-info">
                            <span id="availableSlotsCount">0</span> slots available
                        </div>
                    </div>
                    <div class="time-slots-grid" id="timeSlotsGrid">
                        <!-- Time slots will be generated by JavaScript -->
                    </div>
                </div>
                
                <form method="POST" action="" id="bookingForm">
                    <div class="form-group">
                        <label for="service_type">
                            <i class="fas fa-tooth"></i> Select Service
                        </label>
                        <select name="service_type" id="service_type" required>
                            <option value="">-- Choose a Service --</option>
                            <?php foreach ($service_prices as $service => $price): ?>
                            <option value="<?php echo $service; ?>">
                                <?php echo $service; ?> - ₱<?php echo number_format($price); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="appointment_date" id="appointment_date" required>
                    <input type="hidden" name="appointment_time" id="appointment_time" required>
                    
                    <div id="slot-indicator" class="time-slot-indicator">
                        <i class="fas fa-info-circle"></i> <span id="slot-message"></span>
                    </div>
                    
                    <button type="submit" name="book_appointment" class="submit-btn" id="submitBtn" disabled>
                        <i class="fas fa-calendar-check"></i>
                        Book Appointment & Proceed to Payment
                    </button>
                </form>
            </div>
            
            <div class="form-section">
                <h3>
                    <i class="fas fa-history"></i>
                    Your Appointments
                </h3>
                
                <?php if ($appointments_query->num_rows > 0): ?>
                    <?php 
                    $now = new DateTime();
                    while($appointment = $appointments_query->fetch_assoc()): 
                        $apptDate = new DateTime($appointment['appodate']);
                        $isUpcoming = $apptDate >= $now;
                    ?>
                        <div class="appointment-card <?php echo $isUpcoming ? 'upcoming' : ''; ?>">
                            <div class="appt-number">
                                <?php if ($isUpcoming): ?>
                                    <i class="fas fa-clock" style="font-size: 16px; margin-right: 5px;"></i>
                                <?php endif; ?>
                                #<?php echo htmlspecialchars($appointment['apponum']); ?>
                            </div>
                            
                            <?php if (isset($appointment['service_type'])): ?>
                            <div class="info-row">
                                <i class="fas fa-tooth"></i>
                                <div>
                                    <strong>Service:</strong>
                                    <?php echo htmlspecialchars($appointment['service_type']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-row">
                                <i class="fas fa-calendar-day"></i>
                                <div>
                                    <strong>Date & Time:</strong>
                                    <?php echo date('F j, Y - g:i A', strtotime($appointment['appodate'])); ?>
                                    <?php if ($isUpcoming): ?>
                                        <span style="color: #28a745; font-weight: 600; margin-left: 10px;">
                                            <i class="fas fa-arrow-right"></i> Upcoming
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($appointment['amount'])): ?>
                            <div class="info-row">
                                <i class="fas fa-money-bill"></i>
                                <div>
                                    <strong>Amount:</strong>
                                    ₱<?php echo number_format($appointment['amount']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                <?php if (isset($appointment['status'])): ?>
                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (isset($appointment['payment_status'])): ?>
                                <span class="status-badge payment-<?php echo $appointment['payment_status']; ?>">
                                    Payment: <?php echo ucfirst($appointment['payment_status']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($appointment['payment_status']) && $appointment['payment_status'] == 'unpaid' && isset($appointment['status']) && $appointment['status'] == 'approved'): ?>
                                <a href="payment.php?appo_id=<?php echo $appointment['appoid']; ?>" class="pay-now-btn">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-appointments">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments Yet</h3>
                        <p>Book your first appointment using the calendar above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>
                <i class="fas fa-link"></i>
                Quick Links
            </h3>
            <a href="patient/index.php" class="quick-link">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="booking.php" class="quick-link">
                <i class="fas fa-calendar-check"></i>
                My Appointments
            </a>
            <a href="patient/settings.php" class="quick-link">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>
    </div>
</div>

<script>
    let currentDate = new Date();
    let selectedDate = null;
    let selectedTime = null;
    let bookedDates = {};
    
    // Business hours: 8 AM to 8 PM
    const timeSlots = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '12:00', '12:30', '1:00', '2:30',
        '3:00', '3:30', '4:00', '4:30', '5:00', '5:30',
        '6:00', '6:30', '7:00', '7:30', '8:00'
       
    ];
    
    // Initialize calendar
    function initCalendar() {
        renderCalendar();
        fetchBookedDates();
    }
    
    // Fetch all booked dates for the current month
    function fetchBookedDates() {
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        
        fetch(`?check_month_slots=1&year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                bookedDates = data.booked_dates || {};
                renderCalendar();
            })
            .catch(error => {
                console.error('Error fetching booked dates:', error);
                bookedDates = {};
                renderCalendar();
            });
    }
    
    // Render calendar
    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        // Update header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        document.getElementById('currentMonthYear').textContent = `${monthNames[month]} ${year}`;
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Create calendar grid
        let calendarHTML = '';
        
        // Day headers
        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayHeaders.forEach(day => {
            calendarHTML += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            calendarHTML += '<div class="calendar-day empty"></div>';
        }
        
        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateObj = new Date(year, month, day);
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isPast = dateObj < today;
            const isSelected = selectedDate === dateStr;
            const bookingCount = bookedDates[dateStr] || 0;
            const hasBooking = bookingCount > 0;
            
            let classes = 'calendar-day';
            if (isPast) classes += ' past';
            if (isSelected) classes += ' selected';
            if (hasBooking && !isPast) classes += ' has-booking';
            
            const bookingCountHtml = hasBooking ? `<span class="booking-count">${bookingCount} booked</span>` : '';
            
            calendarHTML += `
                <div class="${classes}" onclick="selectDate('${dateStr}', ${isPast})">
                    ${day}
                    ${bookingCountHtml}
                </div>
            `;
        }
        
        document.getElementById('calendarGrid').innerHTML = calendarHTML;
    }
    
    // Change month
    function changeMonth(direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        selectedDate = null;
        selectedTime = null;
        document.getElementById('timeSlotsContainer').classList.remove('show');
        updateSubmitButton();
        renderCalendar();
        fetchBookedDates();
    }
    
    // Select date
    function selectDate(dateStr, isPast) {
        if (isPast) return;
        
        selectedDate = dateStr;
        selectedTime = null;
        renderCalendar();
        loadTimeSlots(dateStr);
        updateSubmitButton();
    }
    
    // Load time slots for selected date
    function loadTimeSlots(dateStr) {
        fetch(`?check_slots=1&date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                const bookedSlots = data.booked_slots || [];
                let slotsHTML = '';
                let availableCount = 0;
                
                timeSlots.forEach(time => {
                    const isBooked = bookedSlots.includes(time);
                    const classes = isBooked ? 'time-slot booked' : 'time-slot';
                    const onclick = isBooked ? '' : `onclick="selectTime('${time}')"`;
                    
                    if (!isBooked) availableCount++;
                    
                    slotsHTML += `<div class="${classes}" ${onclick}>${time}</div>`;
                });
                
                document.getElementById('timeSlotsGrid').innerHTML = slotsHTML;
                document.getElementById('availableSlotsCount').textContent = availableCount;
                document.getElementById('timeSlotsContainer').classList.add('show');
                
                // Show message if no slots available
                if (availableCount === 0) {
                    const indicator = document.getElementById('slot-indicator');
                    const message = document.getElementById('slot-message');
                    indicator.className = 'time-slot-indicator booked';
                    message.textContent = 'All time slots are booked for this date. Please select another date.';
                }
            })
            .catch(error => {
                console.error('Error loading time slots:', error);
                document.getElementById('timeSlotsGrid').innerHTML = '<p style="text-align:center;color:#999;">Error loading time slots. Please try again.</p>';
            });
    }
    
    // Select time
    function selectTime(time) {
        selectedTime = time;
        
        // Update UI
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        event.target.classList.add('selected');
        
        // Update hidden form fields
        document.getElementById('appointment_date').value = selectedDate;
        document.getElementById('appointment_time').value = time;
        
        // Show confirmation
        const indicator = document.getElementById('slot-indicator');
        const message = document.getElementById('slot-message');
        indicator.className = 'time-slot-indicator available';
        
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        message.textContent = `Selected: ${formattedDate} at ${time}`;
        
        updateSubmitButton();
    }
    
    // Update submit button state
    function updateSubmitButton() {
        const serviceSelected = document.getElementById('service_type').value !== '';
        const submitBtn = document.getElementById('submitBtn');
        
        if (selectedDate && selectedTime && serviceSelected) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }
    
    // Listen for service type change
    document.getElementById('service_type').addEventListener('change', updateSubmitButton);
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initCalendar);
</script>