<?php
ob_start();
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    header("Location: ../login.php");
    exit();
}

// Import database
// Assuming connection.php contains the $database mysqli connection object
include("../connection.php"); 

$success_message = '';
$error_message = '';

// Get patient information
$useremail = $_SESSION['user'];
$patient_query = $database->query("SELECT * FROM patient WHERE pemail='$useremail'");

// Verify patient exists
if ($patient_query->num_rows == 0) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$patient = $patient_query->fetch_assoc();
$patient_id = $patient['pid'];
$patient_name = $patient['pname'];

// Set timezone for date validation and display
date_default_timezone_set('Asia/Manila'); 

// Service prices (as defined in the original prompt)
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

// --- AJAX Slot Availability Check (Original Endpoint) ---
if (isset($_GET['check_slots']) && isset($_GET['date'])) {
    $check_date = mysqli_real_escape_string($database, $_GET['date']);
    
    // Query to fetch all booked times for the given date
    $slots_query = $database->query("
        SELECT TIME(appodate) as booked_time FROM appointment 
        WHERE DATE(appodate) = '$check_date'
    ");

    $booked_slots = [];
    while($row = $slots_query->fetch_assoc()) {
        $booked_slots[] = $row['booked_time'];
    }

    // Set JSON header and output the booked slots
    header('Content-Type: application/json');
    echo json_encode(['booked_slots' => $booked_slots]);
    exit(); // Stop further PHP execution for AJAX request
}

// --- AJAX Month Booking Check (New Endpoint for Calendar UI) ---
if (isset($_GET['check_month_slots']) && isset($_GET['year']) && isset($_GET['month'])) {
    $year = mysqli_real_escape_string($database, $_GET['year']);
    $month = mysqli_real_escape_string($database, $_GET['month']);

    // Ensure month is two digits (e.g., '01' instead of '1')
    $month_padded = str_pad($month, 2, '0', STR_PAD_LEFT);

    // Query to fetch all dates with at least one booking in the given month/year
    $month_query = $database->query("
        SELECT DATE(appodate) as appo_date, COUNT(*) as booking_count 
        FROM appointment 
        WHERE YEAR(appodate) = '$year' AND MONTH(appodate) = '$month_padded'
        GROUP BY appo_date
    ");

    $booked_dates = [];
    while($row = $month_query->fetch_assoc()) {
        // Use the day part of the date as the key
        $day = date('j', strtotime($row['appo_date'])); // 'j' is day of month without leading zeros
        $booked_dates[$day] = (int)$row['booking_count'];
    }

    header('Content-Type: application/json');
    echo json_encode(['booked_dates' => $booked_dates]);
    exit();
}
// --- END AJAX Checks ---


// Handle appointment booking (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date = mysqli_real_escape_string($database, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($database, $_POST['appointment_time']);
    $service_type = mysqli_real_escape_string($database, $_POST['service_type']);
    
    // Simple time slot constraint check (8:00 to 17:00)
    $time_hour = (int)substr($appointment_time, 0, 2);
    if ($time_hour < 8 || $time_hour >= 17) {
        $error_message = "Appointments can only be booked between 8:00 AM and 5:00 PM.";
    } elseif (empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
        $error_message = "Please fill in all fields to book an appointment.";
    } else {
        // Validate date is not in the past
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            $error_message = "Cannot book appointments in the past.";
        } else {
            // Check if the selected time slot is already booked (server-side validation for security)
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
                    
                    // Clear any output buffer and redirect to payment
                    ob_end_clean();
                    
                    // Redirect to payment
                    if (!headers_sent()) {
                        header("Location: ../payment.php?appo_id=" . $new_appointment_id);
                        exit();
                    } else {
                        echo "<script>window.location.href='../payment.php?appo_id=" . $new_appointment_id . "';</script>";
                        exit();
                    }
                } else {
                    $error_message = "Error booking appointment: " . $database->error;
                }
            }
        }
    }
}

// Get patient's appointments with payment status
$appointments_query = $database->query("
    SELECT * FROM appointment 
    WHERE pid = '$patient_id' 
    ORDER BY 
        CASE WHEN appodate >= NOW() THEN 0 ELSE 1 END, 
        appodate ASC
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
        /* Base Reset and Variables */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Sidebar (Styles truncated for brevity - same as original) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: var(--white);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--bg-light);
            border-radius: 12px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .user-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .user-info p {
            font-size: 13px;
            color: var(--text-light);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 150px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            margin: 5px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 15px;
        }

        .nav-link:hover {
            background: var(--bg-light);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-link i {
            font-size: 18px;
            width: 20px;
        }

        .logout-btn {
            margin: 20px 15px;
            padding: 14px 20px;
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: calc(100% - 30px);
            transition: transform 0.3s ease;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
        }
        /* End Sidebar Styles */

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 15px;
        }

        /* Alerts (Styles truncated for brevity - same as original) */
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
        /* End Alert Styles */

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }

        .card h2 {
            color: var(--primary-color);
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Styling (Styles truncated for brevity - same as original) */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group select,
        .form-group input[type="date"],
        .form-group input[type="time"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
            color: var(--text-dark);
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .submit-btn {
            width: 100%;
            background: var(--primary-gradient);
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
            margin-top: 30px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        /* End Form Styles */


        /* --- New Calendar Styles --- */
        .calendar-container {
            flex-grow: 1; /* Make the calendar section fill the card */
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .calendar-header button {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
        }

        .calendar-header button:hover {
            background: #e2e8f0;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
        }

        .day-name {
            font-weight: 700;
            color: var(--primary-color);
            padding: 10px 0;
            font-size: 13px;
        }

        .calendar-day {
            padding: 10px 5px;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.2s, transform 0.1s;
            font-size: 14px;
            position: relative;
            line-height: 1.2;
        }

        .calendar-day.disabled {
            color: var(--text-light);
            cursor: not-allowed;
            opacity: 0.5;
            background: none;
        }

        .calendar-day:not(.disabled):hover {
            background: var(--bg-light);
            transform: scale(1.05);
        }

        .calendar-day.selected {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow);
            font-weight: 600;
        }
        
        /* Indicator for booked days */
        .booking-indicator {
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #f6ad55; /* Orange/Yellow */
            border-radius: 50%;
            display: block;
        }

        .calendar-day.selected .booking-indicator {
            background-color: white;
        }

        /* Time Slots */
        .time-slots-grid {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .time-slots-grid h3 {
            font-size: 16px;
            color: var(--text-dark);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .slots-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }

        .slot-button {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid #e2e8f0;
            padding: 10px 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
        }

        .slot-button:hover:not(.booked):not(.selected-slot) {
            background: #e2e8f0;
        }

        .slot-button.booked {
            background: #fed7d7; /* Light Red */
            color: #c53030; /* Dark Red */
            cursor: not-allowed;
            border-color: #feb2b2;
            text-decoration: line-through;
            opacity: 0.7;
        }

        .slot-button.selected-slot {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
            box-shadow: var(--shadow);
        }
        
        /* Hiding original date/time inputs */
        .form-row input[type="date"],
        .form-row input[type="time"] {
            display: none !important;
        }
        /* --- End New Calendar Styles --- */
        
        /* Appointments List (Styles truncated for brevity - same as original) */
        .appointments-section {
            grid-column: 2 / 3;
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .appointment-card {
            background: var(--bg-light);
            padding: 24px;
            border-radius: 12px;
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s;
        }

        .appointment-card:hover {
            box-shadow: var(--shadow);
        }

        .appointment-card .appt-number {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 15px;
            border-bottom: 1px dashed #cbd5e0;
            padding-bottom: 10px;
        }

        .appointment-card .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--text-dark);
        }

        .appointment-card .info-row i {
            color: var(--primary-color);
            width: 18px;
            margin-top: 2px;
            text-align: center;
        }

        .appointment-card .info-row strong {
            color: var(--text-dark);
            font-weight: 600;
            min-width: 80px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            margin-right: 8px;
            text-transform: capitalize;
        }

        .status-approved {
            background: #e6ffed;
            color: #276749;
        }

        .status-pending {
            background: #fffbe5;
            color: #744210;
        }
        
        .status-completed {
            background: #ebf8ff;
            color: #2c5282;
        }

        .payment-unpaid {
            background: #fee2e2;
            color: #9b2c2c;
        }

        .payment-paid {
            background: #e6ffed;
            color: #276749;
        }

        .pay-now-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            padding: 8px 16px;
            background: #38a169;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .pay-now-btn:hover {
            background: #2f855a;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(56, 161, 105, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
            background: var(--bg-light);
            border-radius: 12px;
            margin-top: 10px;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Indicator moved to new slot selection area */
        .time-slot-indicator {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            display: none;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .time-slot-indicator i {
            font-size: 16px;
        }

        .time-slot-indicator.available {
            background: #e8f5e9;
            color: #2e7d32;
            display: flex;
        }

        .time-slot-indicator.booked {
            background: #ffebee;
            color: #c62828;
            display: flex;
        }
        /* End Indicator Styles */


        /* Responsive Adjustments (Styles truncated for brevity - same as original) */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .appointments-section {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
            
            /* Remove form-row grid since inputs are now hidden */
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-tooth"></i>
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>

            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($patient_name, 0, 2)); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($patient_name); ?></h3>
                    <p><?php echo htmlspecialchars($useremail); ?></p>
                </div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Home</span></a></div>
            <div class="nav-item"><a href="doctors.php" class="nav-link"><i class="fas fa-user-md"></i><span>All Doctors</span></a></div>
            <div class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Scheduled Sessions</span></a></div>
            <div class="nav-item"><a href="booking.php" class="nav-link active"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a></div>
            <div class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a></div>
        </nav>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-calendar-plus"></i>
                Book Appointment
            </h1>
            <p>Schedule your dental appointment with our expert doctors</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success_message; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h2>
                    <i class="fas fa-calendar-check"></i>
                    Schedule New Appointment
                </h2>
                
                <form method="POST" action="booking.php" id="bookingForm">
                    <div class="form-group">
                        <label for="service_type">
                            <i class="fas fa-tooth"></i> Select Service
                        </label>
                        <select name="service_type" id="service_type" required>
                            <option value="">-- Choose a Service --</option>
                            <?php foreach ($service_prices as $service => $price): ?>
                            <option value="<?php echo htmlspecialchars($service); ?>">
                                <?php echo htmlspecialchars($service); ?> - ₱<?php echo number_format($price); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-day"></i> Select Date & Time
                        </label>
                        
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button type="button" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <h3 id="currentMonthYear"></h3>
                                <button type="button" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>

                            <div class="calendar-grid" id="calendarGrid">
                                <div class="day-name">Sun</div>
                                <div class="day-name">Mon</div>
                                <div class="day-name">Tue</div>
                                <div class="day-name">Wed</div>
                                <div class="day-name">Thu</div>
                                <div class="day-name">Fri</div>
                                <div class="day-name">Sat</div>
                                </div>
                            
                            <div class="time-slots-grid">
                                <h3>Available Time Slots (8:00 AM - 5:00 PM)</h3>
                                <div class="slots-list" id="timeSlotsList">
                                    </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <input type="date" name="appointment_date" id="appointment_date" required>
                            <input type="time" name="appointment_time" id="appointment_time" required>
                        </div>

                    </div>
                    
                    <div id="slot-indicator" class="time-slot-indicator">
                        <i class="fas fa-info-circle"></i> <span id="slot-message"></span>
                    </div>

                    <button type="submit" name="book_appointment" class="submit-btn" id="submitBtn" disabled>
                        <i class="fas fa-calendar-check"></i>
                        Book Appointment & Proceed to Payment
                    </button>
                </form>
            </div>

            <div class="card appointments-section">
                <h2>
                    <i class="fas fa-history"></i>
                    Your Recent Appointments
                </h2>

                <div class="appointments-list">
                    <?php if ($appointments_query->num_rows > 0): ?>
                        <?php while($appointment = $appointments_query->fetch_assoc()): ?>
                            <div class="appointment-card">
                                <div class="appt-number">
                                    Appointment #<?php echo htmlspecialchars($appointment['apponum']); ?>
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
                                    </div>
                                </div>

                                <?php if (isset($appointment['amount'])): ?>
                                    <div class="info-row">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <div>
                                            <strong>Amount:</strong>
                                            ₱<?php echo number_format($appointment['amount'], 2); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="margin-top: 10px;">
                                    <?php 
                                    $status = strtolower($appointment['status'] ?? '');
                                    $payment_status = strtolower($appointment['payment_status'] ?? '');
                                    ?>
                                    <?php if (isset($appointment['status'])): ?>
                                        <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                                            <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (isset($appointment['payment_status'])): ?>
                                        <span class="status-badge payment-<?php echo htmlspecialchars($payment_status); ?>">
                                            <?php echo htmlspecialchars(ucfirst($appointment['payment_status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php 
                                // Show Pay Now button if approved and unpaid
                                if ($payment_status == 'unpaid' && $status == 'approved'): 
                                ?>
                                    <a href="../payment.php?appo_id=<?php echo htmlspecialchars($appointment['appoid']); ?>" class="pay-now-btn">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Appointments Yet</h3>
                            <p>Book your first appointment using the calendar above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- Global State ---
        let currentCalendarDate = new Date();
        const today = new Date();
        let selectedDate = null;
        let bookedDatesInMonth = {};

        // Clinic Hours: 8:00 AM to 5:00 PM (17:00), in 30-minute steps
        const timeSlots = [];
        for (let h = 8; h < 17; h++) {
            // 8:00, 8:30, 9:00, 9:30, ..., 16:30
            for (let m = 0; m < 60; m += 30) {
                const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`;
                const displayStr = new Date(today.toDateString() + ' ' + timeStr).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                timeSlots.push({ time: timeStr.substring(0, 5), display: displayStr }); // Use HH:MM for comparison
            }
        }
        
        // --- DOM Elements ---
        const dateInput = document.getElementById('appointment_date');
        const timeInput = document.getElementById('appointment_time');
        const serviceTypeInput = document.getElementById('service_type');
        const submitBtn = document.getElementById('submitBtn');
        const calendarGrid = document.getElementById('calendarGrid');
        const timeSlotsList = document.getElementById('timeSlotsList');
        const monthYearHeader = document.getElementById('currentMonthYear');
        const indicator = document.getElementById('slot-indicator');
        const message = document.getElementById('slot-message');


        // --- Helper Functions ---

        /** Updates the submit button state based on selected inputs. */
        function updateSubmitButton() {
            const hasDate = dateInput.value !== '';
            const hasTime = timeInput.value !== '';
            const hasService = serviceTypeInput.value !== '';
            
            // Disable if any required field is missing or if the current slot is marked as booked
            const isBooked = indicator.classList.contains('booked');
            
            submitBtn.disabled = !(hasDate && hasTime && hasService && !isBooked);
        }

        /** Formats a Date object to YYYY-MM-DD string. */
        function formatDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
        
        /** Clears slot selection and indicator. */
        function resetSlotSelection() {
            timeInput.value = '';
            // Clear slot styling
            document.querySelectorAll('.slot-button').forEach(btn => {
                btn.classList.remove('selected-slot');
            });
            // Clear indicator
            indicator.style.display = 'none';
        }


        // --- AJAX Functions ---

        /** Fetches all dates in the current month that have at least one booking. */
        function fetchBookedDates(year, month) {
            // month is 0-indexed in JS, but 1-indexed in PHP/SQL
            const url = `booking.php?check_month_slots=1&year=${year}&month=${month + 1}`;
            
            return fetch(url)
                .then(response => response.json())
                .then(data => {
                    bookedDatesInMonth = data.booked_dates || {};
                    renderCalendar(); // Re-render the calendar with indicators
                })
                .catch(error => {
                    console.error('Error fetching booked dates:', error);
                    bookedDatesInMonth = {}; // Fallback
                    renderCalendar();
                });
        }
        
        /** Fetches booked time slots for the selected date. */
        function fetchBookedSlots(date) {
            const url = `booking.php?check_slots=1&date=${date}`;
            
            return fetch(url)
                .then(response => response.json())
                .then(data => {
                    renderTimeSlots(data.booked_slots || []);
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                    renderTimeSlots([]); // Fallback
                });
        }


        // --- Rendering Functions ---

        /** Renders the calendar grid for the currentCalendarDate. */
        function renderCalendar() {
            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth();
            const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 (Sun) - 6 (Sat)
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const todayStr = formatDate(today);
            
            monthYearHeader.textContent = currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            calendarGrid.querySelectorAll('.calendar-day').forEach(d => d.remove());
            
            // Add padding (blank) days
            for (let i = 0; i < firstDayOfMonth; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.classList.add('calendar-day', 'disabled');
                calendarGrid.appendChild(emptyDay);
            }

            // Add day cells
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = formatDate(date);
                
                const dayDiv = document.createElement('div');
                dayDiv.classList.add('calendar-day');
                dayDiv.textContent = day;
                
                // Disable past dates
                if (dateStr < todayStr) {
                    dayDiv.classList.add('disabled');
                } else {
                    dayDiv.dataset.date = dateStr;
                    dayDiv.addEventListener('click', () => selectDate(dayDiv));
                    
                    // Add selected class if this day is currently selected
                    if (selectedDate === dateStr) {
                        dayDiv.classList.add('selected');
                    }
                }
                
                // Add booking indicator if the day has bookings
                if (bookedDatesInMonth[day]) {
                    const indicatorSpan = document.createElement('span');
                    indicatorSpan.classList.add('booking-indicator');
                    dayDiv.appendChild(indicatorSpan);
                }
                
                calendarGrid.appendChild(dayDiv);
            }
        }

        /** Renders the time slots, marking booked ones. */
        function renderTimeSlots(bookedSlots) {
            timeSlotsList.innerHTML = '';
            
            if (!selectedDate) {
                timeSlotsList.innerHTML = '<p style="color:var(--text-light); text-align:center; padding: 20px;">Please select a date from the calendar.</p>';
                return;
            }

            // Get the day of the week (0=Sun, 6=Sat). Assuming clinic is open all days.
            const selectedDayOfWeek = new Date(selectedDate).getDay(); 
            // Optional: If you wanted to disable slots for weekends, you'd check here:
            // if (selectedDayOfWeek === 0 || selectedDayOfWeek === 6) { /* Show message: Weekend Closed */ }


            timeSlots.forEach(slot => {
                const button = document.createElement('button');
                button.classList.add('slot-button');
                button.textContent = slot.display;
                button.dataset.time = slot.time;
                
                // Check if the slot is booked
                if (bookedSlots.includes(slot.time + ':00')) { // Compare HH:MM to HH:MM:SS
                    button.classList.add('booked');
                    button.disabled = true;
                } else {
                    button.addEventListener('click', () => selectTime(button));
                }
                
                timeSlotsList.appendChild(button);
            });
            
            // Re-select time if a date was changed but a time was already selected
            const previouslySelectedTime = timeInput.value;
            if (previouslySelectedTime) {
                 const prevSelectedButton = timeSlotsList.querySelector(`[data-time="${previouslySelectedTime.substring(0, 5)}"]`);
                 if (prevSelectedButton && !prevSelectedButton.classList.contains('booked')) {
                     selectTime(prevSelectedButton);
                 } else {
                     resetSlotSelection();
                 }
            } else {
                resetSlotSelection();
            }
        }


        // --- Event Handlers ---

        /** Changes the month displayed in the calendar. */
        function changeMonth(delta) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
            
            // Prevent going back to months before the current one (today)
            if (currentCalendarDate.getFullYear() < today.getFullYear() || 
                (currentCalendarDate.getFullYear() === today.getFullYear() && currentCalendarDate.getMonth() < today.getMonth())) {
                currentCalendarDate = new Date(today.getFullYear(), today.getMonth(), 1); // Reset to current month
            }
            
            // Deselect date/time when changing month
            selectedDate = null;
            dateInput.value = '';
            resetSlotSelection();
            
            // Fetch booked dates for the new month and render
            fetchBookedDates(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
        }

        /** Handles clicking on a calendar day. */
        function selectDate(dayElement) {
            const newDate = dayElement.dataset.date;
            
            if (selectedDate === newDate) return; // Already selected
            
            // Remove 'selected' class from previously selected day
            document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList.remove('selected'));
            
            // Set new selected date
            selectedDate = newDate;
            dateInput.value = newDate;
            dayElement.classList.add('selected');
            
            // Clear time selection
            resetSlotSelection();
            
            // Fetch and render time slots for the new date
            fetchBookedSlots(newDate);
            updateSubmitButton();
        }

        /** Handles clicking on a time slot button. */
        function selectTime(button) {
            // Remove 'selected-slot' class from previously selected time
            document.querySelectorAll('.slot-button.selected-slot').forEach(btn => btn.classList.remove('selected-slot'));
            
            // Set new selected time
            const newTime = button.dataset.time;
            
            // The database expects HH:MM:SS (step=1800 in original input was 30 mins)
            timeInput.value = newTime + ':00'; 
            button.classList.add('selected-slot');
            
            // Display slot availability indicator (always available if you clicked it)
            indicator.className = 'time-slot-indicator available';
            message.innerHTML = '<i class="fas fa-check-circle"></i> Slot selected. Ready to book.';
            indicator.style.display = 'flex';
            
            updateSubmitButton();
        }


        // --- Initialisation ---
        document.addEventListener('DOMContentLoaded', () => {
            // Initial call to set up the calendar for the current month
            // We set the date to the first of the month to ensure calendar starts correctly
            currentCalendarDate.setDate(1); 
            fetchBookedDates(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
            
            // Listen for service type change
            serviceTypeInput.addEventListener('change', updateSubmitButton);

            // Initial render of time slots with no date selected
            renderTimeSlots([]); 
            
            // Initial button state
            updateSubmitButton(); 
        });
        
    </script>
</body>
</html>