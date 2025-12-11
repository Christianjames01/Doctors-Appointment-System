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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/details.css">
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