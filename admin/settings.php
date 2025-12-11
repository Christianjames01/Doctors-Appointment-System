<?php
session_start();
require_once '../connection.php';
if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}
$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($_SESSION['useremail'] ?? '', ENT_QUOTES, 'UTF-8');

$success = $error = '';

// Create clinic_settings table if it doesn't exist
try {
    $database->query("CREATE TABLE IF NOT EXISTS clinic_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        clinic_name VARCHAR(255) NOT NULL,
        clinic_email VARCHAR(255) NOT NULL,
        clinic_phone VARCHAR(50),
        clinic_address TEXT,
        opening_hours VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default settings if table is empty
    $check = $database->query("SELECT COUNT(*) as count FROM clinic_settings");
    $row = $check->fetch_assoc();
    if ($row['count'] == 0) {
        $database->query("INSERT INTO clinic_settings (clinic_name, clinic_email, clinic_phone, clinic_address, opening_hours) 
                         VALUES ('Dr. Dental Clinic Center', 'info@dentalcare.com', '+63 123 456 7890', '123 Main Street, City, Philippines', 'Open 8:00 AM - 5:00 PM')");
    }
} catch (Exception $e) {
    // Table creation failed, but continue
}

// Create appointment_settings table if it doesn't exist
try {
    $database->query("CREATE TABLE IF NOT EXISTS appointment_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        appointment_duration INT DEFAULT 30,
        working_hours_start TIME DEFAULT '09:00:00',
        working_hours_end TIME DEFAULT '18:00:00',
        advance_booking_days INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default appointment settings if table is empty
    $check = $database->query("SELECT COUNT(*) as count FROM appointment_settings");
    $row = $check->fetch_assoc();
    if ($row['count'] == 0) {
        $database->query("INSERT INTO appointment_settings (appointment_duration, working_hours_start, working_hours_end, advance_booking_days) 
                         VALUES (30, '09:00:00', '18:00:00', 30)");
    }
} catch (Exception $e) {
    // Table creation failed, but continue
}

// Fetch current clinic settings
$clinicSettings = [
    'clinic_name' => 'Dr. Dental Clinic Center',
    'clinic_email' => 'info@dentalcare.com',
    'clinic_phone' => '+63 123 456 7890',
    'clinic_address' => '123 Main Street, City, Philippines',
    'opening_hours' => 'Open 8:00 AM - 5:00 PM'
];

try {
    $result = $database->query("SELECT * FROM clinic_settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $clinicSettings = $row;
    }
} catch (Exception $e) {
    // Use defaults
}

// Fetch current appointment settings
$appointmentSettings = [
    'appointment_duration' => 30,
    'working_hours_start' => '09:00',
    'working_hours_end' => '18:00',
    'advance_booking_days' => 30
];

try {
    $result = $database->query("SELECT * FROM appointment_settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $appointmentSettings = [
            'appointment_duration' => $row['appointment_duration'],
            'working_hours_start' => substr($row['working_hours_start'], 0, 5), // Convert to HH:MM format
            'working_hours_end' => substr($row['working_hours_end'], 0, 5),
            'advance_booking_days' => $row['advance_booking_days']
        ];
    }
} catch (Exception $e) {
    // Use defaults
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $newName = trim($_POST['admin_name'] ?? '');
        $newEmail = trim($_POST['admin_email'] ?? '');
        
        if (!empty($newName) && !empty($newEmail) && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $database->prepare("UPDATE admin SET aemail = ?, aname = ? WHERE aemail = ?");
                $stmt->bind_param("sss", $newEmail, $newName, $adminEmail);
                
                if ($stmt->execute()) {
                    $_SESSION['username'] = $newName;
                    $_SESSION['useremail'] = $newEmail;
                    $adminName = $newName;
                    $adminEmail = $newEmail;
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile.";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please provide valid name and email.";
        }
    }
    
    // Handle password change
    if ($_POST['action'] === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        
        if (!empty($currentPass) && !empty($newPass) && !empty($confirmPass)) {
            if ($newPass === $confirmPass) {
                if (strlen($newPass) >= 8) {
                    try {
                        $stmt = $database->prepare("SELECT apassword FROM admin WHERE aemail = ?");
                        $stmt->bind_param("s", $adminEmail);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        
                        if ($result && password_verify($currentPass, $result['apassword'])) {
                            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
                            $stmt = $database->prepare("UPDATE admin SET apassword = ? WHERE aemail = ?");
                            $stmt->bind_param("ss", $hashedPass, $adminEmail);
                            
                            if ($stmt->execute()) {
                                $success = "Password changed successfully!";
                            } else {
                                $error = "Failed to update password.";
                            }
                        } else {
                            $error = "Current password is incorrect.";
                        }
                    } catch (Exception $e) {
                        $error = "Error: " . $e->getMessage();
                    }
                } else {
                    $error = "Password must be at least 8 characters long.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "All password fields are required.";
        }
    }
    
    // Handle system settings
    if ($_POST['action'] === 'update_system') {
        $clinicName = trim($_POST['clinic_name'] ?? '');
        $clinicEmail = trim($_POST['clinic_email'] ?? '');
        $clinicPhone = trim($_POST['clinic_phone'] ?? '');
        $clinicAddress = trim($_POST['clinic_address'] ?? '');
        $openingHours = trim($_POST['opening_hours'] ?? '');
        
        if (!empty($clinicName) && !empty($clinicEmail) && filter_var($clinicEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $check = $database->query("SELECT id FROM clinic_settings LIMIT 1");
                
                if ($check && $check->num_rows > 0) {
                    $stmt = $database->prepare("UPDATE clinic_settings SET clinic_name = ?, clinic_email = ?, clinic_phone = ?, clinic_address = ?, opening_hours = ? WHERE id = (SELECT id FROM (SELECT id FROM clinic_settings LIMIT 1) as temp)");
                    $stmt->bind_param("sssss", $clinicName, $clinicEmail, $clinicPhone, $clinicAddress, $openingHours);
                } else {
                    $stmt = $database->prepare("INSERT INTO clinic_settings (clinic_name, clinic_email, clinic_phone, clinic_address, opening_hours) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $clinicName, $clinicEmail, $clinicPhone, $clinicAddress, $openingHours);
                }
                
                if ($stmt->execute()) {
                    $success = "System settings updated successfully! Changes will appear on the homepage.";
                    $clinicSettings['clinic_name'] = $clinicName;
                    $clinicSettings['clinic_email'] = $clinicEmail;
                    $clinicSettings['clinic_phone'] = $clinicPhone;
                    $clinicSettings['clinic_address'] = $clinicAddress;
                    $clinicSettings['opening_hours'] = $openingHours;
                } else {
                    $error = "Failed to update system settings.";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please provide valid clinic name and email.";
        }
    }
    
    // Handle appointment settings
    if ($_POST['action'] === 'update_appointments') {
        $duration = intval($_POST['appointment_duration'] ?? 30);
        $startTime = trim($_POST['working_hours_start'] ?? '09:00');
        $endTime = trim($_POST['working_hours_end'] ?? '18:00');
        $advanceBooking = intval($_POST['advance_booking'] ?? 30);
        
        // Validate times
        if (!empty($startTime) && !empty($endTime)) {
            try {
                // Convert times to comparable format
                $start = strtotime($startTime);
                $end = strtotime($endTime);
                
                if ($end > $start) {
                    $check = $database->query("SELECT id FROM appointment_settings LIMIT 1");
                    
                    if ($check && $check->num_rows > 0) {
                        $stmt = $database->prepare("UPDATE appointment_settings SET appointment_duration = ?, working_hours_start = ?, working_hours_end = ?, advance_booking_days = ? WHERE id = (SELECT id FROM (SELECT id FROM appointment_settings LIMIT 1) as temp)");
                        $stmt->bind_param("issi", $duration, $startTime, $endTime, $advanceBooking);
                    } else {
                        $stmt = $database->prepare("INSERT INTO appointment_settings (appointment_duration, working_hours_start, working_hours_end, advance_booking_days) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("issi", $duration, $startTime, $endTime, $advanceBooking);
                    }
                    
                    if ($stmt->execute()) {
                        $success = "Appointment settings updated successfully!";
                        $appointmentSettings['appointment_duration'] = $duration;
                        $appointmentSettings['working_hours_start'] = $startTime;
                        $appointmentSettings['working_hours_end'] = $endTime;
                        $appointmentSettings['advance_booking_days'] = $advanceBooking;
                    } else {
                        $error = "Failed to update appointment settings.";
                    }
                } else {
                    $error = "End time must be after start time.";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please provide valid working hours.";
        }
    }
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/dental-clinic-appointment-system/css/asettings.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
<div class="sidebar-header">
<div class="logo-icon"><i class="fas fa-tooth"></i></div>
<div class="logo-text">
<h2>Dr. Dental Clinic</h2>
<p>Admin Portal</p>
</div>
</div>
<nav class="sidebar-nav">
<div class="nav-section">
<div class="nav-section-title">Main</div>
<a href="index.php" class="nav-item"><i class="fas fa-home"></i><span>Home</span></a>
<a href="appointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
<a href="patient.php" class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
</div>
<div class="nav-section">
<div class="nav-section-title">Reports</div>
<a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
<a href="revenue.php" class="nav-item"><i class="fas fa-dollar-sign"></i><span>Revenue</span></a>
</div>
<div class="nav-section">
<div class="nav-section-title">Settings</div>
<a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i><span>Settings</span></a>
<a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
</div>
</nav>
<div class="sidebar-footer">
<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
</div>
</aside>

<main class="main-content">
<div class="top-bar">
<div class="page-title">
<h1>Settings</h1>
<p>Manage your account and system preferences</p>
</div>
</div>

<div class="content-container">
<?php if ($success): ?>
<div class="alert alert-success">
<i class="fas fa-check-circle"></i>
<span><?php echo e($success); ?></span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error">
<i class="fas fa-exclamation-circle"></i>
<span><?php echo e($error); ?></span>
</div>
<?php endif; ?>

<div class="settings-grid">
<!-- Profile Settings -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-user-circle"></i>
<div>
<h2>Profile Information</h2>
<p>Update your personal account details</p>
</div>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="update_profile">
<div class="form-group">
<label for="admin_name">Full Name</label>
<input type="text" id="admin_name" name="admin_name" value="<?php echo e($adminName); ?>" required>
</div>
<div class="form-group">
<label for="admin_email">Email Address</label>
<input type="email" id="admin_email" name="admin_email" value="<?php echo e($adminEmail); ?>" required>
<small>This will be used for login and notifications</small>
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">
<i class="fas fa-save"></i> Save Changes
</button>
</div>
</form>
</div>

<!-- Security Settings -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-lock"></i>
<div>
<h2>Security Settings</h2>
<p>Change your password and security preferences</p>
</div>
</div>
<div class="info-box">
<p><i class="fas fa-info-circle"></i> For your security, use a strong password with at least 8 characters including letters, numbers, and symbols.</p>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="change_password">
<div class="form-group">
<label for="current_password">Current Password</label>
<input type="password" id="current_password" name="current_password" required>
</div>
<div class="form-group">
<label for="new_password">New Password</label>
<input type="password" id="new_password" name="new_password" minlength="8" required>
<small>Minimum 8 characters</small>
</div>
<div class="form-group">
<label for="confirm_password">Confirm New Password</label>
<input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">
<i class="fas fa-key"></i> Change Password
</button>
</div>
</form>
</div>

<!-- System Settings -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-cogs"></i>
<div>
<h2>System Settings</h2>
<p>Configure clinic information and preferences (displays on homepage)</p>
</div>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="update_system">
<div class="form-group">
<label for="clinic_name">Clinic Name</label>
<input type="text" id="clinic_name" name="clinic_name" value="<?php echo e($clinicSettings['clinic_name']); ?>" required>
</div>
<div class="form-group">
<label for="clinic_email">Clinic Email</label>
<input type="email" id="clinic_email" name="clinic_email" value="<?php echo e($clinicSettings['clinic_email']); ?>" required>
</div>
<div class="form-group">
<label for="clinic_phone">Clinic Phone</label>
<input type="tel" id="clinic_phone" name="clinic_phone" value="<?php echo e($clinicSettings['clinic_phone']); ?>">
</div>
<div class="form-group">
<label for="clinic_address">Clinic Address</label>
<textarea id="clinic_address" name="clinic_address"><?php echo e($clinicSettings['clinic_address']); ?></textarea>
</div>
<div class="form-group">
<label for="opening_hours">Opening Hours</label>
<input type="text" id="opening_hours" name="opening_hours" value="<?php echo e($clinicSettings['opening_hours']); ?>" placeholder="e.g., Open 8:00 AM - 5:00 PM">
<small>This will be displayed in the footer of the homepage</small>
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">
<i class="fas fa-save"></i> Save Settings
</button>
</div>
</form>
</div>

<!-- Appointment Settings -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-calendar-alt"></i>
<div>
<h2>Appointment Settings</h2>
<p>Configure appointment scheduling options</p>
</div>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="update_appointments">
<div class="form-group">
<label for="appointment_duration">Default Appointment Duration (minutes)</label>
<select id="appointment_duration" name="appointment_duration">
<option value="30" <?php echo $appointmentSettings['appointment_duration'] == 30 ? 'selected' : ''; ?>>30 minutes</option>
<option value="45" <?php echo $appointmentSettings['appointment_duration'] == 45 ? 'selected' : ''; ?>>45 minutes</option>
<option value="60" <?php echo $appointmentSettings['appointment_duration'] == 60 ? 'selected' : ''; ?>>60 minutes</option>
<option value="90" <?php echo $appointmentSettings['appointment_duration'] == 90 ? 'selected' : ''; ?>>90 minutes</option>
</select>
</div>
<div class="form-group">
<label for="working_hours_start">Working Hours Start</label>
<input type="time" id="working_hours_start" name="working_hours_start" value="<?php echo e($appointmentSettings['working_hours_start']); ?>" required>
</div>
<div class="form-group">
<label for="working_hours_end">Working Hours End</label>
<input type="time" id="working_hours_end" name="working_hours_end" value="<?php echo e($appointmentSettings['working_hours_end']); ?>" required>
</div>
<div class="form-group">
<label for="advance_booking">Maximum Advance Booking (days)</label>
<input type="number" id="advance_booking" name="advance_booking" value="<?php echo e($appointmentSettings['advance_booking_days']); ?>" min="1" max="365" required>
<small>Patients can book appointments up to this many days in advance</small>
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">
<i class="fas fa-save"></i> Save Settings
</button>
</div>
</form>
</div>

<!-- Notification Settings -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-bell"></i>
<div>
<h2>Notification Preferences</h2>
<p>Manage email and system notifications</p>
</div>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="update_notifications">
<div class="form-group">
<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<input type="checkbox" name="notify_new_appointment" checked style="width:auto">
<span>Email me when new appointments are booked</span>
</label>
</div>
<div class="form-group">
<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<input type="checkbox" name="notify_cancellation" checked style="width:auto">
<span>Email me when appointments are cancelled</span>
</label>
</div>
<div class="form-group">
<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<input type="checkbox" name="notify_payment" checked style="width:auto">
<span>Email me about payment updates</span>
</label>
</div>
<div class="form-group">
<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
<input type="checkbox" name="notify_daily_summary" style="width:auto">
<span>Send daily appointment summary</span>
</label>
</div>
<div class="form-actions">
<button type="submit" class="btn btn-primary">
<i class="fas fa-save"></i> Save Preferences
</button>
</div>
</form>
</div>

<!-- Database Management -->
<div class="settings-card">
<div class="settings-header">
<i class="fas fa-database"></i>
<div>
<h2>Database Management</h2>
<p>Backup and maintenance tools</p>
</div>
</div>
<div class="info-box">
<p><i class="fas fa-exclamation-triangle"></i> Database operations should be performed with caution. Always ensure you have a recent backup before making changes.</p>
</div>
<div class="form-actions">
<button type="button" class="btn btn-primary" onclick="alert('Backup functionality would be implemented here')">
<i class="fas fa-download"></i> Download Backup
</button>
<button type="button" class="btn btn-secondary" onclick="if(confirm('This will clear old records. Continue?')) alert('Cleanup functionality would be implemented here')">
<i class="fas fa-broom"></i> Cleanup Old Data
</button>
</div>
</div>
</div>
</div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('mini');
}

// Password confirmation validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = this.value;
    
    if (confirmPass && newPass !== confirmPass) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Validate working hours
document.getElementById('working_hours_end')?.addEventListener('change', function() {
    const startTime = document.getElementById('working_hours_start').value;
    const endTime = this.value;
    
    if (startTime && endTime && endTime <= startTime) {
        this.setCustomValidity('End time must be after start time');
    } else {
        this.setCustomValidity('');
    }
}); 

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>
</body>
</html>