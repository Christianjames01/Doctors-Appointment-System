<?php
session_start();
include("../connection.php");

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['usertype'] != 'p') {
    header('Location: ../login.php');
    exit;
}

$useremail = $_SESSION['user'];

// Get patient info
$stmt = $database->prepare("SELECT * FROM patient WHERE pemail = ?");
$stmt->bind_param("s", $useremail);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    $pname = sanitize_input($_POST['pname']);
    $paddress = sanitize_input($_POST['paddress']);
    $pnic = sanitize_input($_POST['pnic']);
    $pdob = sanitize_input($_POST['pdob']);
    $ptel = sanitize_input($_POST['ptel']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($pname)) {
        $error = 'Name is required.';
    } else {
        // Update patient info
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                // Update with new password
                $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=?, ppassword=? WHERE pemail=?");
                $stmt->bind_param("sssssss", $pname, $paddress, $pnic, $pdob, $ptel, $new_password, $useremail);
            }
        } else {
            // Update without password change
            $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=? WHERE pemail=?");
            $stmt->bind_param("ssssss", $pname, $paddress, $pnic, $pdob, $ptel, $useremail);
        }
        
        if (!$error && $stmt->execute()) {
            $success = 'Profile updated successfully!';
            $_SESSION['username'] = $pname;
            // Refresh patient data
            $stmt = $database->prepare("SELECT * FROM patient WHERE pemail = ?");
            $stmt->bind_param("s", $useremail);
            $stmt->execute();
            $patient = $stmt->get_result()->fetch_assoc();
        } else if (!$error) {
            $error = 'Failed to update profile.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Dr. Dental Clinic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .user-info {
            color: white;
            font-weight: 500;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-text {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-text:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-text:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }

        .section-divider {
            border-top: 2px solid #e0e0e0;
            margin: 2rem 0;
            padding-top: 2rem;
        }

        .update-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">Dr. Dental Clinic</div>
        <div class="nav-right">
            <span class="user-info">👤 <?php echo htmlspecialchars($patient['pname']); ?></span>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="settings-section">
            <h2 class="section-title">⚙️ Account Settings</h2>
            <p class="subtitle">Update your personal information</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="input-text" value="<?php echo htmlspecialchars($patient['pemail']); ?>" disabled>
                    <small style="color: #666;">Email cannot be changed</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="pname" class="input-text" value="<?php echo htmlspecialchars($patient['pname']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="paddress" class="input-text" value="<?php echo htmlspecialchars($patient['paddress']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="pnic" class="input-text" value="<?php echo htmlspecialchars($patient['pnic']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="pdob" class="input-text" value="<?php echo htmlspecialchars($patient['pdob']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="ptel" class="input-text" value="<?php echo htmlspecialchars($patient['ptel']); ?>">
                </div>

                <div class="section-divider">
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Change Password</h3>
                    <div class="note">
                        ℹ️ Leave password fields empty if you don't want to change your password
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="input-text" placeholder="Leave blank to keep current">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="input-text" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="update-btn">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>