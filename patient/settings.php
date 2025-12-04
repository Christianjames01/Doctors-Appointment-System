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

// Check if profile_picture column exists, if not set default
if (!isset($patient['profile_picture'])) {
    $patient['profile_picture'] = null;
}

$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    $pname = trim($_POST['pname']);
    $paddress = trim($_POST['paddress']);
    $pnic = trim($_POST['pnic']);
    $pdob = $_POST['pdob'];
    $ptel = trim($_POST['ptel']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($pname)) {
        $error = 'Name is required.';
    } else {
        // Handle profile picture upload
        $profile_picture = $patient['profile_picture'] ?? null; // Keep existing if no new upload
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_picture']['type'];
            $file_size = $_FILES['profile_picture']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } elseif ($file_size > $max_size) {
                $error = 'File size must be less than 5MB.';
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = 'patient_' . $patient['pid'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
                    if ($patient['profile_picture'] && file_exists('../' . $patient['profile_picture'])) {
                        unlink('../' . $patient['profile_picture']);
                    }
                    $profile_picture = 'uploads/profiles/' . $new_filename;
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            }
        }
        
        // Update patient info
        if (!$error) {
            // Check if profile_picture column exists in database
            $check_column = $database->query("SHOW COLUMNS FROM patient LIKE 'profile_picture'");
            $column_exists = $check_column->num_rows > 0;
            
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters.';
                } else {
                    // Update with new password
                    if ($column_exists) {
                        $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=?, ppassword=?, profile_picture=? WHERE pemail=?");
                        $stmt->bind_param("ssssssss", $pname, $paddress, $pnic, $pdob, $ptel, $new_password, $profile_picture, $useremail);
                    } else {
                        $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=?, ppassword=? WHERE pemail=?");
                        $stmt->bind_param("sssssss", $pname, $paddress, $pnic, $pdob, $ptel, $new_password, $useremail);
                    }
                }
            } else {
                // Update without password change
                if ($column_exists) {
                    $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=?, profile_picture=? WHERE pemail=?");
                    $stmt->bind_param("sssssss", $pname, $paddress, $pnic, $pdob, $ptel, $profile_picture, $useremail);
                } else {
                    $stmt = $database->prepare("UPDATE patient SET pname=?, paddress=?, pnic=?, pdob=?, ptel=? WHERE pemail=?");
                    $stmt->bind_param("ssssss", $pname, $paddress, $pnic, $pdob, $ptel, $useremail);
                }
            }
            
            if (!$error && $stmt->execute()) {
                if (!$column_exists) {
                    $success = 'Profile updated successfully! Note: Profile picture feature requires database update.';
                } else {
                    $success = 'Profile updated successfully!';
                }
                $_SESSION['username'] = $pname;
                // Refresh patient data
                $stmt = $database->prepare("SELECT * FROM patient WHERE pemail = ?");
                $stmt->bind_param("s", $useremail);
                $stmt->execute();
                $patient = $stmt->get_result()->fetch_assoc();
                if (!isset($patient['profile_picture'])) {
                    $patient['profile_picture'] = null;
                }
            } else if (!$error) {
                $error = 'Failed to update profile.';
            }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/dental-clinic-appointment-system/css/settings.css">

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
            <p class="subtitle">Update your personal information and profile picture</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="profile-picture-section">
                    <div class="profile-picture-preview">
                        <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                            <img src="../<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile Picture" id="preview-image">
                        <?php else: ?>
                            <div class="placeholder" id="preview-placeholder">
                                <?php echo strtoupper(substr($patient['pname'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-picture-info">
                        <h3>Profile Picture</h3>
                        <p>Upload a profile picture. Accepted formats: JPG, PNG, GIF (Max 5MB)</p>
                        <div class="file-upload-wrapper">
                            <label for="profile_picture" class="file-upload-label">
                                <i class="fas fa-camera"></i>
                                Choose Photo
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" class="file-upload-input" accept="image/*">
                        </div>
                        <div id="file-name-display" class="file-name-display"></div>
                    </div>
                </div>

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
                        <label class="form-label">NIC/ID Number</label>
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

                <button type="submit" class="update-btn">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        const fileInput = document.getElementById('profile_picture');
        const fileNameDisplay = document.getElementById('file-name-display');
        const previewImage = document.getElementById('preview-image');
        const previewPlaceholder = document.getElementById('preview-placeholder');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = `Selected: ${file.name}`;
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImage) {
                        previewImage.src = e.target.result;
                    } else if (previewPlaceholder) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.id = 'preview-image';
                        img.style.width = '150px';
                        img.style.height = '150px';
                        img.style.borderRadius = '50%';
                        img.style.objectFit = 'cover';
                        img.style.border = '5px solid white';
                        img.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
                        previewPlaceholder.parentNode.replaceChild(img, previewPlaceholder);
                    }
                }
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    </script>
</body>
</html>