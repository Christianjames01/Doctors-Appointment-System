<?php
ob_start();
date_default_timezone_set('Asia/Manila');
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    header("Location: ../login.php");
    exit();
}

// Import database
include("../connection.php"); 
include("includes/profile_helper.php");

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

// Get pre-selected service from URL parameter
$preselected_service = '';
if (isset($_GET['service']) && !empty($_GET['service'])) {
    $preselected_service = mysqli_real_escape_string($database, $_GET['service']);
    // Verify it's a valid service
    if (!array_key_exists($preselected_service, $service_prices)) {
        $preselected_service = '';
    }
}

// AJAX Slot Availability Check
if (isset($_GET['check_slots']) && isset($_GET['date'])) {
    $check_date = mysqli_real_escape_string($database, $_GET['date']);
    
    $slots_query = $database->query("
        SELECT TIME(appodate) as booked_time FROM appointment 
        WHERE DATE(appodate) = '$check_date'
    ");

    $booked_slots = [];
    while($row = $slots_query->fetch_assoc()) {
        $booked_slots[] = $row['booked_time'];
    }

    header('Content-Type: application/json');
    echo json_encode(['booked_slots' => $booked_slots]);
    exit();
}

// AJAX Month Booking Check
if (isset($_GET['check_month_slots']) && isset($_GET['year']) && isset($_GET['month'])) {
    $year = mysqli_real_escape_string($database, $_GET['year']);
    $month = mysqli_real_escape_string($database, $_GET['month']);
    $month_padded = str_pad($month, 2, '0', STR_PAD_LEFT);

    $month_query = $database->query("
        SELECT DATE(appodate) as appo_date, COUNT(*) as booking_count 
        FROM appointment 
        WHERE YEAR(appodate) = '$year' AND MONTH(appodate) = '$month_padded'
        GROUP BY appo_date
    ");

    $booked_dates = [];
    while($row = $month_query->fetch_assoc()) {
        $day = date('j', strtotime($row['appo_date']));
        $booked_dates[$day] = (int)$row['booking_count'];
    }

    header('Content-Type: application/json');
    echo json_encode(['booked_dates' => $booked_dates]);
    exit();
}

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date = mysqli_real_escape_string($database, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($database, $_POST['appointment_time']);
    $service_type = mysqli_real_escape_string($database, $_POST['service_type']);
    
    // DEBUG: Log received values
    error_log("DEBUG - Received Date: " . $appointment_date);
    error_log("DEBUG - Received Time: " . $appointment_time);
    error_log("DEBUG - Received Service: " . $service_type);
    
    // Ensure time is in HH:MM:SS format
    if (strlen($appointment_time) == 5) { // If format is HH:MM
        $appointment_time .= ':00'; // Add seconds
    }
    
    error_log("DEBUG - Time after processing: " . $appointment_time);
    
    // Validate time format
    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $appointment_time)) {
        $error_message = "Invalid time format. Received: " . $appointment_time;
        error_log("ERROR - Invalid time format: " . $appointment_time);
    } else {
        $time_hour = (int)substr($appointment_time, 0, 2);
        if ($time_hour < 8 || $time_hour >= 17) {
            $error_message = "Appointments can only be booked between 8:00 AM and 5:00 PM.";
        } elseif (empty($appointment_date) || empty($appointment_time) || empty($service_type)) {
            $error_message = "Please fill in all fields to book an appointment.";
        } else {
            if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
                $error_message = "Cannot book appointments in the past.";
            } else {
                // Create proper datetime string
                $appodate = $appointment_date . ' ' . $appointment_time;
                error_log("DEBUG - Final appodate being inserted: " . $appodate);
                
                // Verify the datetime is valid
                $datetime_check = DateTime::createFromFormat('Y-m-d H:i:s', $appodate);
                if (!$datetime_check || $datetime_check->format('Y-m-d H:i:s') !== $appodate) {
                    $error_message = "Invalid date/time format. Date: $appointment_date, Time: $appointment_time";
                    error_log("ERROR - DateTime validation failed: " . $appodate);
                } else {
                    $check_query = $database->query("
                        SELECT COUNT(*) as count FROM appointment 
                        WHERE DATE(appodate) = '$appointment_date' 
                        AND TIME(appodate) = '$appointment_time'
                    ");
                    
                    $check_result = $check_query->fetch_assoc();
                    
                    if ($check_result['count'] > 0) {
                        $error_message = "This time slot is already booked. Please choose a different time.";
                    } else {
                        $appointment_number = rand(100000, 999999);
                        $service_price = $service_prices[$service_type] ?? 1000;
                        
                        $insert_query = "INSERT INTO appointment (apponum, pid, appodate, service_type, status, amount, payment_status) 
                             VALUES ('$appointment_number', '$patient_id', '$appodate', '$service_type', 'pending', '$service_price', 'unpaid')";

                        error_log("DEBUG - SQL Query: " . $insert_query);

                        if ($database->query($insert_query)) {
                            $new_appointment_id = $database->insert_id;
                            error_log("DEBUG - Appointment created with ID: " . $new_appointment_id);
                            
                            ob_end_clean();
                            
                            if (!headers_sent()) {
                                header("Location: ../payment.php?appo_id=" . $new_appointment_id);
                                exit();
                            } else {
                                echo "<script>window.location.href='../payment.php?appo_id=" . $new_appointment_id . "';</script>";
                                exit();
                            }
                        } else {
                            $error_message = "Error booking appointment: " . $database->error;
                            error_log("ERROR - Database insert failed: " . $database->error);
                        }
                    }
                }
            }
        }
    }
}

// Get patient's appointments
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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/booking-styles.css">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
                        <div class="logo-container">
                <div class="logo">
                    <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>
        </div>

       
         <div class="user-profile-nav">
    <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
        <div class="user-avatar-nav">
            <img src="../<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile">
        </div>
    <?php else: ?>
        <div class="user-avatar-nav">
            <?php echo strtoupper(substr($patient_name, 0, 2)); ?>
        </div>
    <?php endif; ?>
    <div class="user-info-nav">
        <h4><?php echo htmlspecialchars($patient_name); ?></h4>
        <p>Patient</p>
    </div>
</div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-menu">
            <div class="nav-section-title">MAIN MENU</div>
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Available Sessions</span>
                </a>
            </div>
            
            <div class="nav-section-title">MY APPOINTMENTS</div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link active">
                    <i class="fas fa-calendar-check"></i>
                    <span>Book Appointment</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="appointment-history.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Appointment History</span>
                </a>
            </div>
            
            <div class="nav-section-title">ACCOUNT</div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>

        <div class="logout-section">
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-header-content">
                <h1>
                    <i class="fas fa-calendar-plus"></i>
                    Book Appointment
                </h1>
                <p>Schedule your dental appointment with our expert doctors</p>
            </div>
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

        <?php if (!empty($preselected_service)): ?>
            <div class="alert" style="background: #e0f2fe; color: #075985; border-left: 4px solid #0284c7;">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Service Selected:</strong> <?php echo htmlspecialchars($preselected_service); ?> 
                    (₱<?php echo number_format($service_prices[$preselected_service]); ?>)
                    <br><small>Please select your preferred date and time below.</small>
                </div>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Booking Form Card -->
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
                                <option value="<?php echo htmlspecialchars($service); ?>" 
                                        <?php echo ($preselected_service == $service) ? 'selected' : ''; ?>>
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
                                    <button type="button" onclick="changeMonth(-1)">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <h3 id="currentMonthYear"></h3>
                                    <button type="button" onclick="changeMonth(1)">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
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
                                    <div class="slots-list" id="timeSlotsList"></div>
                                </div>
                            </div>

                            <div class="form-row">
                                <input type="hidden" name="appointment_date" id="appointment_date" required>
                                <input type="hidden" name="appointment_time" id="appointment_time" required>
                                
                                <!-- Display fields for user to see -->
                                <input type="date" id="appointment_date_display" readonly style="background: #f5f5f5;">
                                <input type="text" id="appointment_time_display" readonly placeholder="Select time from slots" style="background: #f5f5f5;">
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

            <!-- Appointments List Card -->
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

                                <!-- PAY NOW BUTTON -->
                                <?php if ($payment_status == 'unpaid'): ?>
                                    <a href="../payment.php?appo_id=<?php echo htmlspecialchars($appointment['appoid']); ?>" class="pay-now-btn">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Appointments Yet</h3>
                            <p>Book your first appointment using the calendar above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>

// Replace the entire <script> section in your booking.php file with this:

// Replace the entire <script> section in your booking.php file with this:

// Global State
let currentCalendarDate = new Date();
const today = new Date();
let selectedDate = null;
let selectedTime = null;
let bookedDatesInMonth = {};

// Clinic Hours: 8:00 AM to 5:00 PM (17:00), in 30-minute steps
const timeSlots = [];
for (let h = 8; h < 17; h++) {
    for (let m = 0; m < 60; m += 30) {
        const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
        const date = new Date();
        date.setHours(h, m, 0);
        const displayStr = date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        timeSlots.push({ 
            time: timeStr,
            display: displayStr 
        });
    }
}

// DOM Elements
const dateInput = document.getElementById('appointment_date');
const timeInput = document.getElementById('appointment_time');
const dateDisplay = document.getElementById('appointment_date_display');
const timeDisplay = document.getElementById('appointment_time_display');
const serviceTypeInput = document.getElementById('service_type');
const submitBtn = document.getElementById('submitBtn');
const calendarGrid = document.getElementById('calendarGrid');
const timeSlotsList = document.getElementById('timeSlotsList');
const monthYearHeader = document.getElementById('currentMonthYear');
const indicator = document.getElementById('slot-indicator');
const message = document.getElementById('slot-message');
const bookingForm = document.getElementById('bookingForm');

// Helper Functions
function updateSubmitButton() {
    const hasDate = selectedDate !== null;
    const hasTime = selectedTime !== null;
    const hasService = serviceTypeInput.value !== '';
    const isBooked = indicator.classList.contains('booked');
    
    submitBtn.disabled = !(hasDate && hasTime && hasService && !isBooked);
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function isSunday(dateStr) {
    const date = new Date(dateStr);
    return date.getDay() === 0; // 0 = Sunday
}

function resetSlotSelection() {
    selectedTime = null;
    timeInput.value = '';
    timeDisplay.value = '';
    document.querySelectorAll('.slot-button').forEach(btn => {
        btn.classList.remove('selected-slot');
    });
    indicator.style.display = 'none';
}

// AJAX Functions
function fetchBookedDates(year, month) {
    const url = `booking.php?check_month_slots=1&year=${year}&month=${month + 1}`;
    
    return fetch(url)
        .then(response => response.json())
        .then(data => {
            bookedDatesInMonth = data.booked_dates || {};
            renderCalendar();
        })
        .catch(error => {
            console.error('Error fetching booked dates:', error);
            bookedDatesInMonth = {};
            renderCalendar();
        });
}

function fetchBookedSlots(date) {
    const url = `booking.php?check_slots=1&date=${date}`;
    
    return fetch(url)
        .then(response => response.json())
        .then(data => {
            renderTimeSlots(data.booked_slots || []);
        })
        .catch(error => {
            console.error('Error checking availability:', error);
            renderTimeSlots([]);
        });
}

// Rendering Functions
function renderCalendar() {
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const todayStr = formatDate(today);
    
    monthYearHeader.textContent = currentCalendarDate.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    
    calendarGrid.querySelectorAll('.calendar-day').forEach(d => d.remove());
    
    // Add padding days
    for (let i = 0; i < firstDayOfMonth; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.classList.add('calendar-day', 'disabled');
        calendarGrid.appendChild(emptyDay);
    }

    // Add day cells
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateStr = formatDate(date);
        const dayOfWeek = date.getDay();
        
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('calendar-day');
        dayDiv.textContent = day;
        
        // Disable past dates OR Sundays
        if (dateStr < todayStr || dayOfWeek === 0) {
            dayDiv.classList.add('disabled');
            if (dayOfWeek === 0) {
                dayDiv.style.color = '#e74c3c'; // Red color for Sundays
                dayDiv.title = 'Clinic closed on Sundays';
            }
        } else {
            dayDiv.dataset.date = dateStr;
            dayDiv.addEventListener('click', () => selectDate(dayDiv));
            
            if (selectedDate === dateStr) {
                dayDiv.classList.add('selected');
            }
        }
        
        // Add booking indicator (only for non-Sundays)
        if (bookedDatesInMonth[day] && dayOfWeek !== 0) {
            const indicatorSpan = document.createElement('span');
            indicatorSpan.classList.add('booking-indicator');
            dayDiv.appendChild(indicatorSpan);
        }
        
        calendarGrid.appendChild(dayDiv);
    }
}

function renderTimeSlots(bookedSlots) {
    timeSlotsList.innerHTML = '';
    
    if (!selectedDate) {
        timeSlotsList.innerHTML = '<p style="color:var(--gray-500); text-align:center; padding: 20px;">Please select a date from the calendar.</p>';
        return;
    }

    // Check if selected date is Sunday
    if (isSunday(selectedDate)) {
        timeSlotsList.innerHTML = '<p style="color:#e74c3c; text-align:center; padding: 20px; font-weight: bold;"><i class="fas fa-ban"></i> Clinic is closed on Sundays. Please select another date.</p>';
        return;
    }

    timeSlots.forEach(slot => {
        const button = document.createElement('button');
        button.classList.add('slot-button');
        button.textContent = slot.display;
        button.dataset.time = slot.time;
        button.dataset.display = slot.display;
        button.type = 'button';
        
        // Check against booked slots
        const slotWithSeconds = slot.time + ':00';
        if (bookedSlots.includes(slotWithSeconds)) {
            button.classList.add('booked');
            button.disabled = true;
        } else {
            button.addEventListener('click', () => selectTime(button));
        }
        
        timeSlotsList.appendChild(button);
    });
    
    if (selectedTime) {
        const prevSelectedButton = timeSlotsList.querySelector(`[data-time="${selectedTime}"]`);
        if (prevSelectedButton && !prevSelectedButton.classList.contains('booked')) {
            selectTime(prevSelectedButton);
        } else {
            resetSlotSelection();
        }
    }
}

// Event Handlers
function changeMonth(delta) {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
    
    // Prevent going back to months before current
    if (currentCalendarDate.getFullYear() < today.getFullYear() || 
        (currentCalendarDate.getFullYear() === today.getFullYear() && 
         currentCalendarDate.getMonth() < today.getMonth())) {
        currentCalendarDate = new Date(today.getFullYear(), today.getMonth(), 1);
    }
    
    selectedDate = null;
    dateInput.value = '';
    dateDisplay.value = '';
    resetSlotSelection();
    
    fetchBookedDates(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
}

function selectDate(dayElement) {
    const newDate = dayElement.dataset.date;
    
    if (selectedDate === newDate) return;
    
    // Check if trying to select a Sunday
    if (isSunday(newDate)) {
        alert('Clinic is closed on Sundays. Please select another date.');
        return;
    }
    
    document.querySelectorAll('.calendar-day.selected').forEach(d => d.classList.remove('selected'));
    
    selectedDate = newDate;
    dateInput.value = newDate;
    dateDisplay.value = newDate;
    dayElement.classList.add('selected');
    
    console.log('Date selected:', newDate);
    
    resetSlotSelection();
    fetchBookedSlots(newDate);
    updateSubmitButton();
}

function selectTime(button) {
    document.querySelectorAll('.slot-button.selected-slot').forEach(btn => btn.classList.remove('selected-slot'));
    
    selectedTime = button.dataset.time;
    const displayTime = button.dataset.display;
    
    // Set both hidden input and display
    timeInput.value = selectedTime;
    timeDisplay.value = displayTime;
    
    console.log('Time selected:', selectedTime);
    console.log('Hidden input value:', timeInput.value);
    
    button.classList.add('selected-slot');
    
    indicator.className = 'time-slot-indicator available';
    message.innerHTML = 'Slot selected. Ready to book.';
    indicator.style.display = 'flex';
    
    updateSubmitButton();
}

// Sidebar Toggle for Mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Form validation before submission
bookingForm.addEventListener('submit', function(e) {
    console.log('=== FORM SUBMISSION DEBUG ===');
    console.log('Selected Date:', selectedDate);
    console.log('Selected Time:', selectedTime);
    console.log('Date Input Value:', dateInput.value);
    console.log('Time Input Value:', timeInput.value);
    console.log('Service:', serviceTypeInput.value);
    
    // Check if trying to book on Sunday
    if (dateInput.value && isSunday(dateInput.value)) {
        e.preventDefault();
        alert('Cannot book appointments on Sundays. Clinic is closed.');
        return false;
    }
    
    if (!dateInput.value || !timeInput.value || !serviceTypeInput.value) {
        e.preventDefault();
        alert('Please fill in all fields: date, time, and service type');
        return false;
    }
    
    if (!/^\d{2}:\d{2}$/.test(timeInput.value)) {
        e.preventDefault();
        alert('Invalid time format. Please select a time slot again.');
        console.error('Invalid time format:', timeInput.value);
        return false;
    }
    
    console.log('Form validation passed, submitting...');
    return true;
});

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    currentCalendarDate.setDate(1); 
    fetchBookedDates(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth());
    
    serviceTypeInput.addEventListener('change', updateSubmitButton);
    
    renderTimeSlots([]); 
    updateSubmitButton(); 
});
    </script>
</body>
</html>