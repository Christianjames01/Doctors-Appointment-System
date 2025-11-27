<?php
session_start();

// Set timezone
date_default_timezone_set('Asia/Manila');
$date = date('Y-m-d');
$_SESSION["date"] = $date;

// Import database (this file contains sanitize_input function)
include("connection.php");

$error = '';

// Check if user wants to book appointment
$redirect_to_booking = isset($_GET['redirect']) && $_GET['redirect'] === 'booking.php';

// If already logged in, handle redirect
if (isset($_SESSION['user']) && isset($_SESSION['usertype'])) {
    // If coming from booking page, redirect to booking
    if ($redirect_to_booking) {
        if ($_SESSION['usertype'] == 'p') {
            header('Location: booking.php');
            exit();
        } else {
            // Only patients can book, redirect others to their dashboard
            if ($_SESSION['usertype'] == 'a') {
                header('Location: admin/index.php');
            } elseif ($_SESSION['usertype'] == 'd') {
                header('Location: doctor/index.php');
            }
            exit();
        }
    }
    
    // If NOT coming from booking, redirect to respective dashboard
    if ($_SESSION['usertype'] == 'p') {
        header('Location: patient/index.php');
    } elseif ($_SESSION['usertype'] == 'a') {
        header('Location: admin/index.php');
    } elseif ($_SESSION['usertype'] == 'd') {
        header('Location: doctor/index.php');
    }
    exit();
}

if ($_POST) {
    $email = sanitize_input($_POST['useremail']);
    $password = $_POST['userpassword'];
    
    // Prepared statement to prevent SQL injection
    $stmt = $database->prepare("SELECT * FROM webuser WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $utype = $user['usertype'];
        
        if ($utype == 'p') {
            // Patient login
            $stmt = $database->prepare("SELECT * FROM patient WHERE pemail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $checker = $stmt->get_result();
            
            if ($checker->num_rows == 1) {
                $patient = $checker->fetch_assoc();
                // Verify password (use password_verify if passwords are hashed)
                if ($patient['ppassword'] === $password) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'p';
                    $_SESSION['username'] = $patient['pname'];
                    
                    // Redirect to booking if they came from booking page
                    if ($redirect_to_booking) {
                        header('Location: booking.php');
                    } else {
                        header('Location: patient/index.php');
                    }
                    exit();
                } else {
                    $error = 'Wrong credentials: Invalid email or password';
                }
            }
            
        } elseif ($utype == 'a') {
            // Admin login
            $stmt = $database->prepare("SELECT * FROM admin WHERE aemail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $checker = $stmt->get_result();
            
            if ($checker->num_rows == 1) {
                $admin = $checker->fetch_assoc();
                if ($admin['apassword'] === $password) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'a';
                    $_SESSION['username'] = $admin['aname'];
                    header('Location: admin/index.php');
                    exit();
                } else {
                    $error = 'Wrong credentials: Invalid email or password';
                }
            }
            
        } elseif ($utype == 'd') {
            // Doctor login
            $stmt = $database->prepare("SELECT * FROM doctor WHERE docemail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $checker = $stmt->get_result();
            
            if ($checker->num_rows == 1) {
                $doctor = $checker->fetch_assoc();
                if ($doctor['docpassword'] === $password) {
                    $_SESSION['user'] = $email;
                    $_SESSION['usertype'] = 'd';
                    $_SESSION['username'] = $doctor['docname'];
                    header('Location: doctor/index.php');
                    exit();
                } else {
                    $error = 'Wrong credentials: Invalid email or password';
                }
            }
        }
    } else {
        $error = 'We cannot find any account with this email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dr. Dental Clinic</title>
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
            max-width: 450px;
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

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 1rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .header-text {
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .sub-text {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .booking-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .input-text {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .input-text:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .error {
            color: #dc2626;
            text-align: center;
            padding: 1rem;
            background: #fee2e2;
            border: 2px solid #dc2626;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.95rem;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #764ba2;
        }

        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-home a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-home a:hover {
            color: #764ba2;
        }

        .user-type-info {
            background: #f8f9ff;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }

        .user-type-info p {
            color: #667eea;
            font-size: 0.85rem;
            margin: 0;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .container {
                padding: 2rem 1.5rem;
            }

            .header-text {
                font-size: 1.7rem;
            }

            .logo-section img {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <img src="img/images.png" alt="Dr. Dental Clinic Logo">
        </div>

        <h1 class="header-text">Welcome Back!</h1>
        <p class="sub-text">Login with your details to continue</p>
        
        <?php if ($redirect_to_booking): ?>
            <div class="booking-notice">
                📅 Please login to book your appointment
            </div>
        <?php endif; ?>

        <div class="user-type-info">
            <p>💡 Login as Patient, Doctor, or Admin</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="<?php echo $redirect_to_booking ? '?redirect=booking.php' : ''; ?>" method="POST">
            <label for="useremail" class="form-label">Email Address</label>
            <input type="email" id="useremail" name="useremail" class="input-text" placeholder="your@email.com" required>
            
            <label for="userpassword" class="form-label">Password</label>
            <input type="password" id="userpassword" name="userpassword" class="input-text" placeholder="Enter your password" required>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php<?php echo $redirect_to_booking ? '?redirect=booking.php' : ''; ?>">Sign Up</a>
        </div>
        
        <div class="back-home">
            <a href="index.html">← Back to Home</a>
        </div>
    </div>

    <script>
        // Auto-hide error message after 5 seconds
        <?php if (!empty($error)): ?>
        setTimeout(() => {
            const errorDiv = document.querySelector('.error');
            if (errorDiv) {
                errorDiv.style.animation = 'slideUp 0.5s ease reverse';
                setTimeout(() => errorDiv.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>