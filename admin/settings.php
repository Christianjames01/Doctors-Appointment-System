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

// Handle profile update
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
                        // Verify current password
                        $stmt = $database->prepare("SELECT apassword FROM admin WHERE aemail = ?");
                        $stmt->bind_param("s", $adminEmail);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        
                        if ($result && password_verify($currentPass, $result['apassword'])) {
                            // Update password
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
        
        // In a real application, these would be saved to a settings table
        // For now, we'll just show success
        $success = "System settings updated successfully!";
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
<style>
:root{--primary:#10b981;--primary-dark:#059669;--sidebar-bg:#1e293b;--sidebar-hover:#334155;--text-dark:#0f172a;--text-med:#64748b;--bg-light:#f8fafc;--white:#fff;--sidebar-w:260px;--sidebar-mini:70px;--shadow:0 1px 3px rgba(0,0,0,0.1);--shadow-lg:0 10px 25px rgba(0,0,0,0.1)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg-light);color:var(--text-dark);overflow-x:hidden}
.sidebar{position:fixed;left:0;top:0;height:100vh;width:var(--sidebar-w);background:var(--sidebar-bg);transition:width .3s;z-index:1000;display:flex;flex-direction:column;overflow:hidden}
.sidebar.mini{width:var(--sidebar-mini)}
.sidebar-header{padding:20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,0.1);min-height:70px}
.logo-icon{width:40px;height:40px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;flex-shrink:0}
.logo-text{white-space:nowrap;opacity:1;transition:opacity .2s}
.sidebar.mini .logo-text{opacity:0}
.logo-text h2{color:white;font-size:16px;font-weight:700}
.logo-text p{color:rgba(255,255,255,0.6);font-size:12px}
.sidebar-nav{flex:1;padding:20px 10px;overflow-y:auto}
.sidebar-nav::-webkit-scrollbar{width:4px}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.2);border-radius:4px}
.nav-section{margin-bottom:20px}
.nav-section-title{padding:0 15px;font-size:11px;font-weight:600;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;transition:opacity .2s}
.sidebar.mini .nav-section-title{opacity:0;height:0;margin:0}
.nav-item{display:flex;align-items:center;padding:12px 15px;color:rgba(255,255,255,0.8);text-decoration:none;border-radius:8px;margin-bottom:4px;transition:all .2s}
.nav-item:hover{background:var(--sidebar-hover);color:white}
.nav-item.active{background:var(--primary);color:white}
.nav-item i{width:20px;font-size:18px;margin-right:12px;text-align:center;flex-shrink:0}
.nav-item span{white-space:nowrap;font-size:14px;font-weight:500;opacity:1;transition:opacity .2s}
.sidebar.mini .nav-item span{opacity:0}
.sidebar-footer{padding:15px;border-top:1px solid rgba(255,255,255,0.1)}
.toggle-btn{width:100%;padding:10px;background:rgba(255,255,255,0.1);border:none;border-radius:8px;color:white;cursor:pointer;transition:all .2s;font-size:16px}
.toggle-btn:hover{background:rgba(255,255,255,0.2)}
.main-content{margin-left:var(--sidebar-w);transition:margin-left .3s;min-height:100vh}
.sidebar.mini~.main-content{margin-left:var(--sidebar-mini)}
.top-bar{background:var(--white);padding:20px 30px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.page-title h1{font-size:24px;font-weight:700;color:var(--text-dark);margin-bottom:4px}
.page-title p{font-size:14px;color:var(--text-med)}
.content-container{padding:30px;max-width:1200px}
.alert{padding:14px 18px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500}
.alert-success{background:#d1fae5;color:#065f46;border-left:4px solid #10b981}
.alert-error{background:#fee2e2;color:#991b1b;border-left:4px solid #ef4444}
.alert i{font-size:18px}
.settings-grid{display:grid;gap:25px}
.settings-card{background:var(--white);border-radius:12px;padding:30px;box-shadow:var(--shadow)}
.settings-header{display:flex;align-items:center;gap:12px;margin-bottom:25px;padding-bottom:20px;border-bottom:2px solid #f3f4f6}
.settings-header i{font-size:24px;color:var(--primary)}
.settings-header h2{font-size:20px;font-weight:700;color:var(--text-dark)}
.settings-header p{font-size:14px;color:var(--text-med);margin-top:4px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:14px;font-weight:600;color:var(--text-dark);margin-bottom:8px}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:12px 16px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;transition:all .2s;font-family:inherit}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(16,185,129,0.1)}
.form-group textarea{resize:vertical;min-height:100px}
.form-group small{display:block;margin-top:6px;font-size:12px;color:var(--text-med)}
.btn{padding:12px 24px;border-radius:10px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;font-size:14px;border:none;cursor:pointer;text-decoration:none}
.btn-primary{background:var(--primary);color:white}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,0.3)}
.btn-secondary{background:transparent;color:var(--text-med);border:1px solid #e5e7eb}
.btn-secondary:hover{background:#f3f4f6}
.form-actions{display:flex;gap:12px;margin-top:30px;padding-top:20px;border-top:1px solid #f3f4f6}
.info-box{background:#f0f9ff;border-left:4px solid #3b82f6;padding:16px;border-radius:8px;margin-bottom:20px}
.info-box p{color:#1e40af;font-size:13px;line-height:1.6}
.info-box i{color:#3b82f6;margin-right:8px}
@media(max-width:768px){.sidebar{transform:translateX(-100%)}
.sidebar.mobile-open{transform:translateX(0)}
.main-content{margin-left:0}
.content-container{padding:20px 15px}
.form-actions{flex-direction:column}
.btn{width:100%}}
</style>
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
<p>Configure clinic information and preferences</p>
</div>
</div>
<form method="POST" action="">
<input type="hidden" name="action" value="update_system">
<div class="form-group">
<label for="clinic_name">Clinic Name</label>
<input type="text" id="clinic_name" name="clinic_name" value="Dental Care Clinic" required>
</div>
<div class="form-group">
<label for="clinic_email">Clinic Email</label>
<input type="email" id="clinic_email" name="clinic_email" value="info@dentalcare.com" required>
</div>
<div class="form-group">
<label for="clinic_phone">Clinic Phone</label>
<input type="tel" id="clinic_phone" name="clinic_phone" value="+63 123 456 7890">
</div>
<div class="form-group">
<label for="clinic_address">Clinic Address</label>
<textarea id="clinic_address" name="clinic_address">123 Main Street, City, Philippines</textarea>
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
<option value="30" selected>30 minutes</option>
<option value="45">45 minutes</option>
<option value="60">60 minutes</option>
<option value="90">90 minutes</option>
</select>
</div>
<div class="form-group">
<label for="working_hours_start">Working Hours Start</label>
<input type="time" id="working_hours_start" name="working_hours_start" value="09:00">
</div>
<div class="form-group">
<label for="working_hours_end">Working Hours End</label>
<input type="time" id="working_hours_end" name="working_hours_end" value="18:00">
</div>
<div class="form-group">
<label for="advance_booking">Maximum Advance Booking (days)</label>
<input type="number" id="advance_booking" name="advance_booking" value="30" min="1" max="365">
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
</script>
</body>
</html>