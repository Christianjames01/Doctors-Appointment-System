<?php
session_start();
require_once '../connection.php';

// Check if user is admin
if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

// Get patient ID from URL
$patient_id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;

if ($patient_id <= 0) {
    header("Location: patient.php");
    exit();
}

// Get patient details
$stmt = $database->prepare("SELECT * FROM patient WHERE pid = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: patient.php");
    exit();
}

// Get patient statistics
$stmt = $database->prepare("SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(amount) as total_amount
FROM appointment WHERE pid = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get appointment history
$stmt = $database->prepare("SELECT * FROM appointment WHERE pid = ? ORDER BY appodate DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-primary: #10b981;
            --color-primary-dark: #059669;
            --color-primary-light: #d1fae5;
            --color-text-dark: #1f2937;
            --color-text-medium: #6b7280;
            --color-bg-light: #f9fafb;
            --color-white: #ffffff;
            --border-radius-large: 16px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--color-bg-light);
            color: var(--color-text-dark);
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .header {
            background-color: var(--color-white);
            padding: 25px 30px;
            border-radius: var(--border-radius-large);
            margin-bottom: 30px;
            box-shadow: var(--shadow-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--color-text-dark);
        }

        .back-btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            background-color: var(--color-primary);
            color: var(--color-white);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-1px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .patient-card {
            background: var(--color-white);
            border-radius: var(--border-radius-large);
            padding: 30px;
            box-shadow: var(--shadow-subtle);
            text-align: center;
        }

        .profile-picture-container {
            margin-bottom: 20px;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--color-primary-light);
            box-shadow: var(--shadow-hover);
            margin: 0 auto;
        }

        .profile-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
            margin: 0 auto;
            border: 5px solid var(--color-primary-light);
            box-shadow: var(--shadow-hover);
        }

        .patient-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 5px;
        }

        .patient-email {
            color: var(--color-text-medium);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info-section {
            text-align: left;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
            margin-top: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--color-text-medium);
            font-size: 14px;
        }

        .info-value {
            font-weight: 500;
            color: var(--color-text-dark);
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--color-white);
            padding: 25px;
            border-radius: var(--border-radius-large);
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 22px;
            color: white;
        }

        .icon-total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .icon-completed { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .icon-approved { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .icon-pending { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .icon-paid { background: linear-gradient(135deg, #fccb90, #d57eeb); }
        .icon-amount { background: linear-gradient(135deg, #fa709a, #fee140); }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--color-text-medium);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .panel {
            background: var(--color-white);
            border-radius: var(--border-radius-large);
            padding: 30px;
            box-shadow: var(--shadow-subtle);
        }

        .panel h2 {
            color: var(--color-text-dark);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table thead {
            background: #f9fafb;
        }

        .appointments-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: var(--color-text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .appointments-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--color-text-dark);
            font-size: 14px;
        }

        .appointments-table tbody tr {
            transition: background 0.2s;
        }

        .appointments-table tbody tr:hover {
            background: #f9fafb;
        }

        .appt-number {
            font-weight: 700;
            color: var(--color-primary);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .payment-unpaid { background: #fee2e2; color: #991b1b; }
        .payment-paid { background: #d1fae5; color: #065f46; }

        .empty-message {
            text-align: center;
            color: var(--color-text-medium);
            padding: 40px;
            font-size: 14px;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .container {
                padding: 15px;
            }

            .appointments-table {
                font-size: 13px;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-user-injured"></i>
                Patient Details
            </h1>
            <a href="patient.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Patients
            </a>
        </div>

        <div class="content-grid">
            <div class="patient-card">
                <div class="profile-picture-container">
                    <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                        <img src="../<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <div class="profile-placeholder">
                            <?php echo strtoupper(substr($patient['pname'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="patient-name"><?php echo htmlspecialchars($patient['pname']); ?></div>
                <div class="patient-email"><?php echo htmlspecialchars($patient['pemail']); ?></div>

                <div class="info-section">
                    <div class="info-row">
                        <span class="info-label">Patient ID</span>
                        <span class="info-value">#<?php echo htmlspecialchars($patient['pid']); ?></span>
                    </div>
                    <?php if (!empty($patient['ptel'])): ?>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['ptel']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['paddress'])): ?>
                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['paddress']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['pdob'])): ?>
                    <div class="info-row">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value"><?php echo date('M j, Y', strtotime($patient['pdob'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['pnic'])): ?>
                    <div class="info-row">
                        <span class="info-label">NIC/ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['pnic']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon icon-total">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3><?php echo $stats['total_appointments']; ?></h3>
                        <p>Total Appointments</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon icon-completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>Completed</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon icon-approved">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p>Approved</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon icon-pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>

                    <div class="stat-card">
                        <div class="icon icon-paid">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <h3>₱<?php echo number_format($stats['total_paid'], 2); ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>
                <i class="fas fa-history"></i>
                Appointment History
            </h2>

            <?php if ($appointments->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Appt. No.</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($appt = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="appt-number">#<?php echo htmlspecialchars($appt['apponum']); ?></span></td>
                                    <td><?php echo htmlspecialchars($appt['service_type']); ?></td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($appt['appodate'])); ?><br>
                                        <small style="color: #6b7280;"><?php echo date('g:i A', strtotime($appt['appodate'])); ?></small>
                                    </td>
                                    <td><strong>₱<?php echo number_format($appt['amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($appt['status']); ?>">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge payment-<?php echo strtolower($appt['payment_status']); ?>">
                                            <?php echo ucfirst($appt['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-message">No appointments found for this patient.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>