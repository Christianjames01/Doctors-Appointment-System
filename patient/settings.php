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
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .settings-section {
            background: white;
            padding: 2.5rem;
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

        /* Profile Picture Section */
        .profile-picture-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            border-radius: 15px;
        }

        .profile-picture-preview {
            position: relative;
        }

        .profile-picture-preview img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-picture-preview .placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-picture-info {
            flex: 1;
        }

        .profile-picture-info h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .profile-picture-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .file-upload-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-name-display {
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.85rem;
            font-style: italic;
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

            .profile-picture-section {
                flex-direction: column;
                text-align: center;
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