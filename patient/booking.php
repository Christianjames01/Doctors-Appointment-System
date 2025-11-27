// Get available doctors
$doctors_query = $database->query("SELECT * FROM doctor ORDER BY docname");<?php
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    header("Location: ../login.php");
    exit();
}

// Import database
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

// Get available schedules (sessions)
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$schedules_query = $database->query("
    SELECT s.*, d.docname, d.specialties 
    FROM schedule s 
    INNER JOIN doctor d ON s.docid = d.docid 
    WHERE s.scheduledate >= '$today' 
    ORDER BY s.scheduledate, s.scheduletime
");

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $schedule_id = mysqli_real_escape_string($database, $_POST['schedule_id']);
    
    if (empty($schedule_id)) {
        $error_message = "Please select a session to book an appointment.";
    } else {
        $appointment_number = rand(100000, 999999);
        
        // Get schedule details
        $schedule_query = $database->query("SELECT * FROM schedule WHERE scheduleid = '$schedule_id'");
        
        if ($schedule_query->num_rows > 0) {
            $schedule = $schedule_query->fetch_assoc();
            $appodate = $schedule['scheduledate'] . ' ' . $schedule['scheduletime'];
            
            // Check if patient already has an appointment for this schedule
            $check_existing = $database->query("SELECT * FROM appointment WHERE pid = '$patient_id' AND scheduleid = '$schedule_id'");
            
            if ($check_existing->num_rows > 0) {
                $error_message = "You already have an appointment for this session.";
            } else {
                // Insert appointment
                $insert_query = "INSERT INTO appointment (apponum, pid, appodate, scheduleid) 
                                 VALUES ('$appointment_number', '$patient_id', '$appodate', '$schedule_id')";
                
                if ($database->query($insert_query)) {
                    $success_message = "Appointment booked successfully! Your appointment number is <strong>#$appointment_number</strong>";
                } else {
                    $error_message = "Error booking appointment. Please try again.";
                }
            }
        } else {
            $error_message = "Invalid schedule selected.";
        }
    }
}

// Alternative booking with custom date/time and service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_custom_appointment'])) {
    $doctor_id = mysqli_real_escape_string($database, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($database, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($database, $_POST['appointment_time']);
    $service_type = mysqli_real_escape_string($database, $_POST['service_type']);
    
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
        $error_message = "Please fill in all fields to book an appointment.";
    } else {
        $appointment_number = rand(100000, 999999);
        
        // Combine date and time
        $appodate = $appointment_date . ' ' . $appointment_time;
        
        // Check if the selected time slot is already booked
        $check_query = $database->query("
            SELECT * FROM appointment 
            WHERE appodate = '$appodate' 
            AND scheduleid IN (
                SELECT scheduleid FROM schedule WHERE docid = '$doctor_id'
            )
        ");
        
        if ($check_query && $check_query->num_rows > 0) {
            $error_message = "This time slot is already booked. Please choose a different time.";
        } else {
            // Create schedule entry with service type as title
            $schedule_title = $service_type;
            $schedule_check = $database->query("
                SELECT scheduleid FROM schedule 
                WHERE docid = '$doctor_id' 
                AND scheduledate = '$appointment_date' 
                AND scheduletime = '$appointment_time'
            ");
            
            if ($schedule_check && $schedule_check->num_rows > 0) {
                $schedule_row = $schedule_check->fetch_assoc();
                $schedule_id = $schedule_row['scheduleid'];
            } else {
                $nop = 1;
                $insert_schedule = "INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) 
                                   VALUES ('$doctor_id', '$schedule_title', '$appointment_date', '$appointment_time', '$nop')";
                
                if ($database->query($insert_schedule)) {
                    $schedule_id = $database->insert_id;
                } else {
                    $error_message = "Error creating schedule. Please try again.";
                    $schedule_id = null;
                }
            }
            
            if ($schedule_id) {
                $insert_query = "INSERT INTO appointment (apponum, pid, appodate, scheduleid) 
                                VALUES ('$appointment_number', '$patient_id', '$appodate', '$schedule_id')";
                
                if ($database->query($insert_query)) {
                    $success_message = "Appointment booked successfully! Your appointment number is <strong>#$appointment_number</strong>";
                } else {
                    $error_message = "Error booking appointment. Please try again.";
                }
            }
        }
    }
}

// Get patient's appointments
$appointments_query = $database->query("
    SELECT a.*, s.title, d.docname, d.specialties, s.scheduledate, s.scheduletime
    FROM appointment a 
    LEFT JOIN schedule s ON a.scheduleid = s.scheduleid
    LEFT JOIN doctor d ON s.docid = d.docid 
    WHERE a.pid = '$patient_id' 
    ORDER BY a.appodate DESC
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
        }

        /* Sidebar */
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
        }

        .card h2 {
            color: var(--primary-color);
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
            cursor: pointer;
            color: var(--text-dark);
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .appointments-section {
            grid-column: 1 / -1;
        }

        .appointments-list {
            display: grid;
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
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .appointment-card .appt-number {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
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
        }

        .appointment-card .info-row strong {
            color: var(--text-dark);
            min-width: 100px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
                    <h3><?php echo substr($patient_name, 0, 15); ?></h3>
                    <p><?php echo substr($useremail, 0, 20); ?></p>
                </div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="doctors.php" class="nav-link">
                    <i class="fas fa-user-md"></i>
                    <span>All Doctors</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Scheduled Sessions</span>
                </a>
            </div>
            </div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link active">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-calendar-plus"></i>
                Book Appointment
            </h1>
            <p>Schedule your dental appointment with our expert doctors</p>
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

        <div class="content-grid">
            <div class="card">
                <h2>
                    <i class="fas fa-calendar-check"></i>
                    Schedule New Appointment
                </h2>
                
                <!-- Tab Selection -->
                <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
                    <button onclick="showTab('custom')" id="customTab" style="flex: 1; padding: 12px; background: var(--primary-gradient); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Custom Booking
                    </button>
                    <button onclick="showTab('scheduled')" id="scheduledTab" style="flex: 1; padding: 12px; background: #f8f9fa; color: var(--text-dark); border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Pre-Scheduled Sessions
                    </button>
                </div>

                <!-- Custom Booking Form -->
                <form method="POST" action="" id="customForm">
                    <div class="form-group">
                        <label for="service_type">
                            <i class="fas fa-tooth"></i> Select Service
                        </label>
                        <select name="service_type" id="service_type" required>
                            <option value="">-- Choose a Service --</option>
                            <option value="General Checkup">🔍 General Checkup</option>
                            <option value="Teeth Cleaning">✨ Teeth Cleaning</option>
                            <option value="Teeth Whitening">💎 Teeth Whitening</option>
                            <option value="Tooth Extraction">🦷 Tooth Extraction</option>
                            <option value="Dental Filling">🔧 Dental Filling</option>
                            <option value="Root Canal Treatment">🏥 Root Canal Treatment</option>
                            <option value="Braces Consultation">📏 Braces Consultation</option>
                            <option value="Dental Crown">👑 Dental Crown</option>
                            <option value="Dental Bridge">🌉 Dental Bridge</option>
                            <option value="Dental Implant">⚙️ Dental Implant</option>
                            <option value="Gum Treatment">🌸 Gum Treatment</option>
                            <option value="Emergency Dental Care">🚨 Emergency Dental Care</option>
                            <option value="Pediatric Dentistry">👶 Pediatric Dentistry</option>
                            <option value="Orthodontics">😁 Orthodontics</option>
                            <option value="Cosmetic Dentistry">💄 Cosmetic Dentistry</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="doctor_id_custom">
                            <i class="fas fa-user-md"></i> Select Doctor
                        </label>
                        <select name="doctor_id" id="doctor_id_custom" required>
                            <option value="">-- Choose a Doctor --</option>
                            <?php 
                            $doctors_query_reset = $database->query("SELECT * FROM doctor ORDER BY docname");
                            while($doctor = $doctors_query_reset->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $doctor['docid']; ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['docname']); ?> - 
                                    <?php echo htmlspecialchars($doctor['specialties']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="appointment_date">
                            <i class="fas fa-calendar-day"></i> Appointment Date
                        </label>
                        <input type="date" name="appointment_date" id="appointment_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">
                            <i class="fas fa-clock"></i> Appointment Time
                        </label>
                        <input type="time" name="appointment_time" id="appointment_time" 
                               min="08:00" max="17:00" required>
                    </div>

                    <button type="submit" name="book_custom_appointment" class="submit-btn">
                        <i class="fas fa-calendar-check"></i>
                        Book Appointment
                    </button>
                </form>

                <!-- Scheduled Sessions Form -->
                <form method="POST" action="" id="scheduledForm" style="display: none;">
                    <div class="form-group">
                        <label for="schedule_id">
                            <i class="fas fa-list"></i> Select Available Session
                        </label>
                        <select name="schedule_id" id="schedule_id" required>
                            <option value="">-- Choose a Session --</option>
                            <?php 
                            if ($schedules_query->num_rows > 0) {
                                while($schedule = $schedules_query->fetch_assoc()): 
                                    $session_date = date('M j, Y', strtotime($schedule['scheduledate']));
                                    $session_time = date('g:i A', strtotime($schedule['scheduletime']));
                            ?>
                                <option value="<?php echo $schedule['scheduleid']; ?>">
                                    <?php echo htmlspecialchars($schedule['title']); ?> - 
                                    Dr. <?php echo htmlspecialchars($schedule['docname']); ?> - 
                                    <?php echo $session_date; ?> at <?php echo $session_time; ?>
                                </option>
                            <?php 
                                endwhile;
                            } else {
                                echo '<option value="">No available sessions</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="book_appointment" class="submit-btn">
                        <i class="fas fa-calendar-check"></i>
                        Book Appointment
                    </button>
                </form>

                <script>
                function showTab(tab) {
                    const customForm = document.getElementById('customForm');
                    const scheduledForm = document.getElementById('scheduledForm');
                    const customTab = document.getElementById('customTab');
                    const scheduledTab = document.getElementById('scheduledTab');
                    
                    if (tab === 'custom') {
                        customForm.style.display = 'block';
                        scheduledForm.style.display = 'none';
                        customTab.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        customTab.style.color = 'white';
                        scheduledTab.style.background = '#f8f9fa';
                        scheduledTab.style.color = 'var(--text-dark)';
                    } else {
                        customForm.style.display = 'none';
                        scheduledForm.style.display = 'block';
                        scheduledTab.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        scheduledTab.style.color = 'white';
                        customTab.style.background = '#f8f9fa';
                        customTab.style.color = 'var(--text-dark)';
                    }
                }
                </script>
            </div>

            <div class="card appointments-section">
                <h2>
                    <i class="fas fa-history"></i>
                    Your Recent Appointments
                </h2>

                <div class="appointments-list">
                    <?php if ($appointments_query->num_rows > 0): ?>
                        <?php 
                        $count = 0;
                        while($appointment = $appointments_query->fetch_assoc()): 
                            if ($count >= 5) break; // Show only 5 recent appointments
                            $count++;
                        ?>
                            <div class="appointment-card">
                                <div class="appt-number">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo htmlspecialchars($appointment['apponum']); ?>
                                </div>

                                <?php if (isset($appointment['title']) && $appointment['title']): ?>
                                    <div class="info-row">
                                        <i class="fas fa-clipboard"></i>
                                        <div>
                                            <strong>Session:</strong>
                                            <?php echo htmlspecialchars($appointment['title']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($appointment['docname']) && $appointment['docname']): ?>
                                    <div class="info-row">
                                        <i class="fas fa-user-md"></i>
                                        <div>
                                            <strong>Doctor:</strong>
                                            Dr. <?php echo htmlspecialchars($appointment['docname']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($appointment['scheduledate']) && isset($appointment['scheduletime'])): ?>
                                    <div class="info-row">
                                        <i class="fas fa-calendar-day"></i>
                                        <div>
                                            <strong>Date & Time:</strong>
                                            <?php 
                                            echo date('F j, Y', strtotime($appointment['scheduledate'])); 
                                            echo ' at ';
                                            echo date('g:i A', strtotime($appointment['scheduletime'])); 
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($appointments_query->num_rows > 5): ?>
                            <p style="text-align: center; color: var(--text-light); margin-top: 15px;">
                                <a href="appointment.php" style="color: var(--primary-color); text-decoration: none;">
                                    View all appointments →
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Appointments Yet</h3>
                            <p>Book your first appointment using the form above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>