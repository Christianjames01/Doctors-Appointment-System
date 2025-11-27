<?php
ob_start();
date_default_timezone_set('Asia/Manila');
session_start();
include("connection.php");

 if (!isset($_SESSION['user']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'p') {
    header("Location: login.php");
    exit();
}

// Helper function to escape output - DECLARED ONLY ONCE
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$success_message = '';
$error_message = '';

// Get patient information
$email = $_SESSION['user'];
$patient_query = $database->query("SELECT * FROM patient WHERE pemail='$email'");

if ($patient_query->num_rows == 0) {
    header("Location: login.php");
    exit();
}

$patient = $patient_query->fetch_assoc();
$patient_id = $patient['pid'];
$patient_name = $patient['pname'];

// Get appointment ID from URL
if (!isset($_GET['appo_id']) || empty($_GET['appo_id'])) {
    header("Location: booking.php");
    exit();
}

$appointment_id = (int)$_GET['appo_id'];

// Get appointment details
$appointment_query = $database->query("
    SELECT a.*, p.pname, p.pemail, p.ptel 
    FROM appointment a
    LEFT JOIN patient p ON a.pid = p.pid
    WHERE a.appoid = $appointment_id AND a.pid = $patient_id
");

if ($appointment_query->num_rows == 0) {
    header("Location: booking.php");
    exit();
}

$appointment = $appointment_query->fetch_assoc();

// Check if already paid
if ($appointment['payment_status'] == 'paid') {
    $success_message = "This appointment has already been paid!";
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $payment_method = mysqli_real_escape_string($database, $_POST['payment_method']);
    $reference_number = mysqli_real_escape_string($database, $_POST['reference_number'] ?? '');
    
    if (empty($payment_method)) {
        $error_message = "Please select a payment method.";
    } else {
        // Update appointment payment status
        $update_query = "UPDATE appointment SET payment_status = 'paid' WHERE appoid = $appointment_id";
        
        if ($database->query($update_query)) {
            $success_message = "Payment successful! Your appointment is confirmed.";
            
            // Refresh appointment data
            $appointment_query = $database->query("
                SELECT a.*, p.pname, p.pemail, p.ptel 
                FROM appointment a
                LEFT JOIN patient p ON a.pid = p.pid
                WHERE a.appoid = $appointment_id AND a.pid = $patient_id
            ");
            $appointment = $appointment_query->fetch_assoc();
        } else {
            $error_message = "Error processing payment: " . $database->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Dr. Dental Care Center</title>
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
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
        
        .back-btn {
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
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .payment-section {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .payment-header h2 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .payment-header p {
            font-size: 16px;
            color: #666;
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
        
        .appointment-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        
        .appointment-details h3 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        
        .amount-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
        }
        
        .amount-row .detail-label,
        .amount-row .detail-value {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .payment-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 16px;
        }
        
        .payment-form h3 {
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
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            position: relative;
        }
        
        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .payment-method label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            min-height: 120px;
        }
        
        .payment-method label i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .payment-method label span {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .payment-method input[type="radio"]:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .payment-method label:hover {
            border-color: #667eea;
            transform: translateY(-3px);
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
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .payment-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .payment-info h4 {
            color: #1565c0;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-info p {
            color: #0d47a1;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .payment-section {
                padding: 25px;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .logo-text h1 {
                font-size: 22px;
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
                    <p>Secure Payment Gateway</p>
                </div>
            </div>
            <a href="patient/index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
        
        <div class="payment-section">
            <div class="payment-header">
                <h2>
                    <i class="fas fa-credit-card"></i>
                    Payment Details
                </h2>
                <p>Complete your payment for appointment <strong>#<?php echo e($appointment['apponum']); ?></strong></p>
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
            
            <div class="appointment-details">
                <h3>
                    <i class="fas fa-file-invoice"></i>
                    Appointment Summary
                </h3>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-user"></i>
                        Patient Name
                    </div>
                    <div class="detail-value"><?php echo e($appointment['pname']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-hashtag"></i>
                        Appointment Number
                    </div>
                    <div class="detail-value">#<?php echo e($appointment['apponum']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-tooth"></i>
                        Service Type
                    </div>
                    <div class="detail-value"><?php echo e($appointment['service_type']); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-day"></i>
                        Appointment Date
                    </div>
                    <div class="detail-value"><?php echo date('F j, Y - g:i A', strtotime($appointment['appodate'])); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-info-circle"></i>
                        Payment Status
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $appointment['payment_status']; ?>">
                            <?php echo ucfirst($appointment['payment_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="amount-row">
                    <div class="detail-row" style="border: none; padding: 0;">
                        <div class="detail-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Total Amount
                        </div>
                        <div class="detail-value">₱<?php echo number_format($appointment['amount']); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($appointment['payment_status'] == 'unpaid'): ?>
                <div class="payment-info">
                    <h4>
                        <i class="fas fa-info-circle"></i>
                        Payment Instructions
                    </h4>
                    <p>
                        Please select your preferred payment method below. After successful payment, 
                        your appointment will be confirmed. For GCash and bank transfers, please keep 
                        your reference number for verification.
                    </p>
                </div>
                
                <div class="payment-form">
                    <h3>
                        <i class="fas fa-wallet"></i>
                        Select Payment Method
                    </h3>
                    
                    <form method="POST" action="">
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="cash" value="Cash" required>
                                <label for="cash">
                                    <i class="fas fa-money-bill"></i>
                                    <span>Cash</span>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="gcash" value="GCash" required>
                                <label for="gcash">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>GCash</span>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="card" value="Credit/Debit Card" required>
                                <label for="card">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Card</span>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="bank" value="Bank Transfer" required>
                                <label for="bank">
                                    <i class="fas fa-university"></i>
                                    <span>Bank Transfer</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reference_number">
                                <i class="fas fa-receipt"></i> Reference Number (Optional)
                            </label>
                            <input 
                                type="text" 
                                name="reference_number" 
                                id="reference_number"
                                placeholder="Enter your transaction reference number"
                            >
                        </div>
                        
                        <button type="submit" name="submit_payment" class="submit-btn">
                            <i class="fas fa-check-circle"></i>
                            Confirm Payment
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="payment-info">
                    <h4>
                        <i class="fas fa-check-circle"></i>
                        Payment Completed
                    </h4>
                    <p>
                        Your payment has been successfully processed. Your appointment is confirmed. 
                        Please arrive 10 minutes before your scheduled time. Thank you for choosing 
                        Dr. Dental Care Center!
                    </p>
                </div>
                
                <a href="booking.php" class="submit-btn" style="text-decoration: none;">
                    <i class="fas fa-calendar-check"></i>
                    View My Appointments
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>