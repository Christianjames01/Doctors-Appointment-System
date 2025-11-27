<?php
// booking_confirmation.php
session_start();

// Check if booking was successful
if (!isset($_SESSION['booking_success']) || !$_SESSION['booking_success']) {
    header('Location: booking.php');
    exit;
}

$booking = $_SESSION['booking_details'];
unset($_SESSION['booking_success']);
unset($_SESSION['booking_details']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Dr. Dental Clinic Center</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirmation-container {
            max-width: 600px;
            width: 100%;
        }

        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .checkmark-circle {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .checkmark {
            color: white;
            font-size: 40px;
        }

        h1 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 30px;
        }

        .booking-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .booking-details h2 {
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.3em;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
            text-align: right;
        }

        .booking-id {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 1.1em;
        }

        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1em;
        }

        .info-box ul {
            margin-left: 20px;
            color: #856404;
        }

        .info-box li {
            margin: 5px 0;
        }

        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            color: #666;
        }

        .contact-info p {
            margin: 8px 0;
        }

        .contact-info strong {
            color: #333;
        }

        @media (max-width: 768px) {
            .confirmation-card {
                padding: 25px;
            }

            h1 {
                font-size: 1.5em;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media print {
            body {
                background: white;
            }

            .buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="checkmark-circle">
                <span class="checkmark">✓</span>
            </div>

            <h1>Appointment Confirmed!</h1>
            <p class="subtitle">Your appointment has been successfully booked</p>

            <?php if (isset($booking['booking_id'])): ?>
            <div class="booking-id">
                <strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?>
            </div>
            <?php endif; ?>

            <div class="booking-details">
                <h2>Appointment Details</h2>
                
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['fullname']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['service']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('l, F j, Y', strtotime($booking['date'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value"><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
                </div>
            </div>

            <div class="info-box">
                <h3>📋 What to Bring:</h3>
                <ul>
                    <li>Valid ID or Driver's License</li>
                    <li>Insurance card (if applicable)</li>
                    <li>Previous dental records (if available)</li>
                    <li>List of current medications</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>⏰ Important Reminders:</h3>
                <ul>
                    <li>Please arrive 10 minutes early</li>
                    <li>A confirmation email has been sent to your email address</li>
                    <li>To reschedule or cancel, contact us at least 24 hours in advance</li>
                </ul>
            </div>

            <div class="buttons">
                <a href="index.php" class="btn btn-primary">Back to Home</a>
                <button onclick="window.print()" class="btn btn-secondary">Print Details</button>
            </div>

            <div class="contact-info">
                <p><strong>Need to reschedule?</strong></p>
                <p>📞 Phone: (123) 456-7890</p>
                <p>📧 Email: info@dentalclinic.com</p>
                <p>⏰ Office Hours: Mon-Sat, 9:00 AM - 6:00 PM</p>
            </div>
        </div>
    </div>
</body>
</html>