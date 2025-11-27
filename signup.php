<?php
session_start();
include("connection.php");

$error = '';
$success = '';

if ($_POST) {
    $fname = sanitize_input($_POST['fname']);
    $lname = sanitize_input($_POST['lname']);
    $email = sanitize_input($_POST['email']);
    $nic = sanitize_input($_POST['nic']);
    $dob = sanitize_input($_POST['dob']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $address = sanitize_input($_POST['address']);
    $tel = sanitize_input($_POST['tel']);
    
    // Validate inputs
    if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $cpassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if email already exists
        $stmt = $database->prepare("SELECT * FROM webuser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            // Insert into webuser table
            $stmt = $database->prepare("INSERT INTO webuser (email, usertype) VALUES (?, 'p')");
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                // Insert into patient table
                $name = $fname . " " . $lname;
                $stmt = $database->prepare("INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $email, $name, $password, $address, $nic, $dob, $tel);
                
                if ($stmt->execute()) {
                    $success = 'Registration successful! Redirecting to login...';
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Sign Up - Dr. Dental Clinic</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-text {
            font-size: 2rem;
            color: #667eea;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .sub-text {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 500;
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

        .signup-btn {
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

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .error {
            color: #e74c3c;
            text-align: center;
            padding: 0.5rem;
            background: #ffebee;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .success {
            color: #27ae60;
            text-align: center;
            padding: 0.5rem;
            background: #e8f8f5;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1rem;
        }

        .back-home a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-home a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header-text">Create Account</h1>
        <p class="sub-text">Sign up to book your appointments</p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="fname" class="form-label">First Name *</label>
                    <input type="text" id="fname" name="fname" class="input-text" required>
                </div>
                <div class="form-group">
                    <label for="lname" class="form-label">Last Name *</label>
                    <input type="text" id="lname" name="lname" class="input-text" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" class="input-text" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nic" class="form-label">NIC/ID Number</label>
                    <input type="text" id="nic" name="nic" class="input-text">
                </div>
                <div class="form-group">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="input-text">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address" class="form-label">Address</label>
                <input type="text" id="address" name="address" class="input-text">
            </div>
            
            <div class="form-group">
                <label for="tel" class="form-label">Phone Number</label>
                <input type="tel" id="tel" name="tel" class="input-text">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" id="password" name="password" class="input-text" required>
                </div>
                <div class="form-group">
                    <label for="cpassword" class="form-label">Confirm Password *</label>
                    <input type="password" id="cpassword" name="cpassword" class="input-text" required>
                </div>
            </div>
            
            <button type="submit" class="signup-btn">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
        
        <div class="back-home">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>
</body>
</html>