<?php
session_start();
require_once '../connection.php';


if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

$success_message = '';
$error_message = '';

// Handle appointment approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    $update_query = "UPDATE appointment SET status = 'approved' WHERE appoid = $appo_id";
    
    if ($database->query($update_query)) {
        $success_message = "Appointment approved successfully!";
    } else {
        $error_message = "Error approving appointment.";
    }
}

// Handle appointment rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    $update_query = "UPDATE appointment SET status = 'rejected' WHERE appoid = $appo_id";
    
    if ($database->query($update_query)) {
        $success_message = "Appointment rejected.";
    } else {
        $error_message = "Error rejecting appointment.";
    }
}

// Handle appointment completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    $update_query = "UPDATE appointment SET status = 'completed' WHERE appoid = $appo_id";
    
    if ($database->query($update_query)) {
        $success_message = "Appointment marked as completed!";
    } else {
        $error_message = "Error updating appointment.";
    }
}

// Handle appointment deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    // WARNING: This performs a permanent delete (hard delete).
    $delete_query = "DELETE FROM appointment WHERE appoid = $appo_id";
    
    if ($database->query($delete_query)) {
        $success_message = "Appointment deleted permanently.";
    } else {
        $error_message = "Error deleting appointment: " . $database->error;
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
if ($filter_status != 'all') {
    $where_clauses[] = "a.status = '" . mysqli_real_escape_string($database, $filter_status) . "'";
}
if ($filter_payment != 'all') {
    $where_clauses[] = "a.payment_status = '" . mysqli_real_escape_string($database, $filter_payment) . "'";
}
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($database, $search);
    $where_clauses[] = "(p.pname LIKE '%$search_escaped%' OR a.apponum LIKE '%$search_escaped%' OR a.service_type LIKE '%$search_escaped%')";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get appointments
$appointments_query = $database->query("
    SELECT a.*, p.pname, p.pemail, p.ptel
    FROM appointment a
    LEFT JOIN patient p ON a.pid = p.pid
    $where_sql
    ORDER BY a.appodate DESC
");

// Get statistics
$total_appointments = $database->query("SELECT COUNT(*) as count FROM appointment")->fetch_assoc()['count'];
$pending_appointments = $database->query("SELECT COUNT(*) as count FROM appointment WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_appointments = $database->query("SELECT COUNT(*) as count FROM appointment WHERE status = 'approved'")->fetch_assoc()['count'];
$total_revenue = $database->query("SELECT SUM(amount) as total FROM appointment WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Use dashboard background gradient */
            background: linear-gradient(135deg, #f6f7fb 0%, #e9ecef 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            /* Use dashboard header gradient and style */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px 35px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header h1 {
            font-size: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Added pulse animation for the icon from index.php */
        .header h1 i {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        /* End added pulse animation */
        
        .header p {
            margin-top: 8px;
            opacity: 0.95;
            font-size: 15px;
            color: white !important; /* Ensure secondary text is white */
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            /* Use dashboard button style */
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            /* Dashboard transparent button style */
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* Adjusted for 4 cards */
            gap: 20px;
            margin-bottom: 30px;
            animation: fadeIn 0.6s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            font-size: 28px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        
        /* Dashboard Card Colors */
        .stat-card:nth-child(1) {
            --card-color: #0284c7;
            --card-color-light: #38bdf8;
        }
        
        .stat-card:nth-child(2) {
            --card-color: #16a34a;
            --card-color-light: #4ade80;
        }
        
        .stat-card:nth-child(3) {
            --card-color: #9333ea;
            --card-color-light: #c084fc;
        }

        .stat-card:nth-child(4) {
            --card-color: #ea580c; /* Orange for revenue */
            --card-color-light: #f97316;
        }
        
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-orange { background: #fff7ed; color: #ea580c; } /* New color */
        
        /* Panel style for filters and table to match dashboard's look */
        .panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .panel:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .filters {
            /* Inherit panel style, adjust padding/margin */
            padding: 30px;
            margin-bottom: 25px;
        }
        
        .filters form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .appointments-table {
            /* Inherit panel style, adjust padding */
            padding: 30px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-header h2 {
            color: #333;
            font-size: 22px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-complete {
            background: #17a2b8;
            color: white;
        }
        
        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters form {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .appointments-table {
                overflow-x: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-calendar-check"></i> Appointments Management</h1>
                <p>Manage and monitor all patient appointments</p> 
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../logout.php" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon icon-blue">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="label">Total Appointments</div>
                <div class="value"><?php echo $total_appointments; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon icon-green">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="label">Pending Approval</div>
                <div class="value"><?php echo $pending_appointments; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon icon-purple">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="label">Approved</div>
                <div class="value"><?php echo $approved_appointments; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon icon-orange">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="label">Total Revenue</div>
                <div class="value">₱<?php echo number_format($total_revenue); ?></div>
            </div>
        </div>
        
        <div class="filters panel">
            <form method="GET" action="">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search by patient name, appointment #, or service" value="<?php echo e($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Payment</label>
                    <select name="payment">
                        <option value="all" <?php echo $filter_payment == 'all' ? 'selected' : ''; ?>>All Payments</option>
                        <option value="paid" <?php echo $filter_payment == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="unpaid" <?php echo $filter_payment == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>
        
        <div class="appointments-table panel">
            <div class="table-header">
                <h2>Appointments List</h2>
                <span style="color: #666;"><?php echo $appointments_query->num_rows; ?> appointments found</span>
            </div>
            
            <?php if ($appointments_query->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Appt #</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($appt = $appointments_query->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo e($appt['apponum']); ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo e($appt['pname']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo e($appt['pemail']); ?></div>
                                </td>
                                <td><?php echo e($appt['service_type']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($appt['appodate'])); ?></td>
                                <td><strong>₱<?php echo number_format($appt['amount']); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appt['status']; ?>">
                                        <?php echo ucfirst($appt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge payment-<?php echo $appt['payment_status']; ?>">
                                        <?php echo ucfirst($appt['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($appt['status'] == 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appo_id" value="<?php echo $appt['appoid']; ?>">
                                                <button type="submit" name="approve_appointment" class="btn-small btn-approve" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appo_id" value="<?php echo $appt['appoid']; ?>">
                                                <button type="submit" name="reject_appointment" class="btn-small btn-reject" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($appt['status'] == 'approved'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appo_id" value="<?php echo $appt['appoid']; ?>">
                                                <button type="submit" name="complete_appointment" class="btn-small btn-complete" title="Mark as Completed">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete appointment #<?php echo e($appt['apponum']); ?>? This action cannot be undone.');">
                                            <input type="hidden" name="appo_id" value="<?php echo $appt['appoid']; ?>">
                                            <button type="submit" name="delete_appointment" class="btn-small btn-reject" title="Delete Appointment">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointments Found</h3>
                    <p>There are no appointments matching your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>