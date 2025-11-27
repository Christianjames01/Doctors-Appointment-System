<?php
session_start();

// Import database first
include("connection.php");

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    // Clear any existing session data
    session_unset();
    session_destroy();
    
    // Start a new session for the redirect
    session_start();
    
    // Redirect to login page with return URL
    header("Location: login.php?redirect=booking.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get patient information
$email = $_SESSION['user'];
$patient_query = $database->query("SELECT * FROM patient WHERE pemail='$email'");

// Verify patient exists
if ($patient_query->num_rows == 0) {
    // Patient not found, clear session and redirect
    session_unset();
    session_destroy();
    header("Location: login.php?redirect=booking.php");
    exit();
}

$patient = $patient_query->fetch_assoc();
$patient_id = $patient['pid'];
$patient_name = $patient['pname'];

// Get available doctors
$doctors_query = $database->query("SELECT * FROM doctor ORDER BY docname");

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = mysqli_real_escape_string($database, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($database, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($database, $_POST['appointment_time']);
    $service_type = mysqli_real_escape_string($database, $_POST['service_type']);
    
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
        $error_message = "Please fill in all fields to book an appointment.";
    } else {
        // Generate unique appointment number
        $appointment_number = rand(100000, 999999);
        
        // Combine date and time
        $appodate = $appointment_date . ' ' . $appointment_time;
        
        // Check if the selected time slot is already booked for this doctor
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
            // Create or get schedule entry
            $schedule_title = $service_type; // Use service type as title
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
                // Create new schedule entry
                $nop = 1; // Number of patients for this slot
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
                // Insert appointment
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
date_default_timezone_set('Asia/Manila');
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
        
        .booking-header .welcome strong {
            color: #333;
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
        
        .alert i {
            font-size: 20px;
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

        .form-group select {
            cursor: pointer;
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
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .appointments-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .appointments-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .appointments-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .appointments-list::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
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
        
        .appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .appointment-card .appt-number {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .appointment-card .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .appointment-card .info-row i {
            color: #667eea;
            width: 18px;
            margin-top: 2px;
        }
        
        .appointment-card .info-row strong {
            color: #333;
            min-width: 80px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
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
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
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
        }
        
        .quick-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
        
        .quick-link i {
            font-size: 18px;
        }

        .info-box {
            background: linear-gradient(135deg, #e8ecff 0%, #f8f9ff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .info-box h4 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .time-slot {
            padding: 8px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
                top: 0;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .booking-section {
                padding: 25px;
            }
            
            .booking-header h2 {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
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

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Booking Information</h4>
                    <p>Choose your preferred doctor, service, date, and time for your dental appointment. Our clinic hours are Monday to Saturday, 8:00 AM to 5:00 PM.</p>
                    <div class="time-slots">
                        <div class="time-slot">8:00 AM - 10:00 AM</div>
                        <div class="time-slot">10:00 AM - 12:00 PM</div>
                        <div class="time-slot">1:00 PM - 3:00 PM</div>
                        <div class="time-slot">3:00 PM - 5:00 PM</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>
                        <i class="fas fa-calendar-check"></i>
                        Schedule New Appointment
                    </h3>
                    <form method="POST" action="">
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
                            <label for="doctor_id">
                                <i class="fas fa-user-md"></i> Select Doctor
                            </label>
                            <select name="doctor_id" id="doctor_id" required>
                                <option value="">-- Choose a Doctor --</option>
                                <?php 
                                if ($doctors_query->num_rows > 0) {
                                    while($doctor = $doctors_query->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $doctor['docid']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['docname']); ?> - 
                                        <?php echo htmlspecialchars($doctor['specialties']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                } else {
                                    echo '<option value="">No doctors available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="appointment_date">
                                    <i class="fas fa-calendar-day"></i> Appointment Date
                                </label>
                                <input 
                                    type="date" 
                                    name="appointment_date" 
                                    id="appointment_date" 
                                    min="<?php echo date('Y-m-d'); ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="appointment_time">
                                    <i class="fas fa-clock"></i> Appointment Time
                                </label>
                                <input 
                                    type="time" 
                                    name="appointment_time" 
                                    id="appointment_time" 
                                    min="08:00" 
                                    max="17:00"
                                    required
                                >
                            </div>
                        </div>
                        
                        <button type="submit" name="book_appointment" class="submit-btn">
                            <i class="fas fa-calendar-check"></i>
                            Book Appointment
                        </button>
                    </form>
                </div>
                
                <div class="form-section">
                    <h3>
                        <i class="fas fa-history"></i>
                        Your Recent Appointments
                    </h3>
                    
                    <div class="appointments-list">
                        <?php if ($appointments_query->num_rows > 0): ?>
                            <?php 
                            $count = 0;
                            while($appointment = $appointments_query->fetch_assoc()): 
                                if ($count >= 5) break;
                                $count++;
                            ?>
                                <div class="appointment-card">
                                    <div class="appt-number">
                                        <i class="fas fa-hashtag"></i>
                                        <?php echo htmlspecialchars($appointment['apponum']); ?>
                                    </div>
                                    
                                    <?php if (isset($appointment['title']) && $appointment['title']): ?>
                                        <div class="info-row">
                                            <i class="fas fa-tooth"></i>
                                            <div>
                                                <strong>Service:</strong>
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
                                    
                                    <?php if (isset($appointment['specialties']) && $appointment['specialties']): ?>
                                        <div class="info-row">
                                            <i class="fas fa-stethoscope"></i>
                                            <div>
                                                <strong>Specialty:</strong>
                                                <?php echo htmlspecialchars($appointment['specialties']); ?>
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
                                    <?php elseif (isset($appointment['appodate']) && $appointment['appodate']): ?>
                                        <div class="info-row">
                                            <i class="fas fa-calendar-day"></i>
                                            <div>
                                                <strong>Date:</strong>
                                                <?php echo date('F j, Y - g:i A', strtotime($appointment['appodate'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                            
                            <?php if ($appointments_query->num_rows > 5): ?>
                                <p style="text-align: center; color: #666; margin-top: 15px;">
                                    <a href="patient/appointment.php" style="color: #667eea; text-decoration: none;">
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
            
            <div class="sidebar">
                <h3>
                    <i class="fas fa-link"></i>
                    Quick Links
                </h3>
                <div class="quick-links">
                    <a href="patient/index.php" class="quick-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                    <a href="patient/doctors.php" class="quick-link">
                        <i class="fas fa-user-md"></i>
                        All Doctors
                    </a>
                    <a href="patient/schedule.php" class="quick-link">
                        <i class="fas fa-calendar-alt"></i>
                        View Schedules
                    </a>
                    <a href="patient/booking.php" class="quick-link">
                        <i class="fas fa-calendar-check"></i>
                        My Bookings
                    </a>
                    <a href="patient/settings.php" class="quick-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>