<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';


if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    
    // Check status AND payment status BEFORE approving
    $check_query = $database->query("SELECT status, payment_status FROM appointment WHERE appoid = $appo_id");
    $data = $check_query->fetch_assoc();
    
    if (!$data) {
        $error_message = "❌ Appointment not found.";
    } else if ($data['status'] != 'pending') {  
        $error_message = "❌ Only pending appointments can be approved.";
    } else if ($data['payment_status'] != 'paid') {
        // CRITICAL: Block approval if payment is NOT paid
        $error_message = "❌ Cannot approve appointment. Payment must be completed first! Current payment status: " . strtoupper($data['payment_status']);
    } else {
        // Double-check payment status one more time before approving
        $verify_payment = $database->query("SELECT payment_status FROM appointment WHERE appoid = $appo_id AND payment_status = 'paid'");
        
        if ($verify_payment && $verify_payment->num_rows > 0) {
            // Payment verified as PAID - safe to approve
            $update_query = "UPDATE appointment SET status = 'approved' WHERE appoid = $appo_id AND payment_status = 'paid'";
            
            if ($database->query($update_query)) {
                $success_message = "✅ Appointment approved successfully!";
            } else {
                $error_message = "❌ Error approving appointment: " . $database->error;
            }
        } else {
            $error_message = "❌ Payment verification failed. Cannot approve unpaid appointment.";
        }
    }
}

// UPDATED REJECTION LOGIC - Can reject pending appointments regardless of payment status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    
    // Check current status
    $check = $database->query("SELECT status, payment_status FROM appointment WHERE appoid = $appo_id");
    $data = $check->fetch_assoc();
    
    if (!$data) {
        $error_message = "❌ Appointment not found.";
    } else if ($data['status'] != 'pending') {
        $error_message = "❌ Only pending appointments can be rejected.";
    } else {
        // Can reject even if unpaid - rejection doesn't require payment
        $update_query = "UPDATE appointment SET status = 'rejected' WHERE appoid = $appo_id";
        
        if ($database->query($update_query)) {
            $success_message = "✅ Appointment rejected.";
        } else {
            $error_message = "❌ Error rejecting appointment.";
        }
    }
}

// MARK AS PAID LOGIC - Admin can manually mark unpaid appointments as paid
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_paid'])) {
    $appo_id = (int)$_POST['appo_id'];
    
    // Check if appointment exists and is pending
    $check_appt = $database->query("SELECT status, payment_status FROM appointment WHERE appoid = $appo_id");
    $appt_data = $check_appt->fetch_assoc();
    
    if (!$appt_data) {
        $error_message = "❌ Appointment not found.";
    } else if ($appt_data['status'] != 'pending') {
        $error_message = "❌ Can only update payment for pending appointments.";
    } else if ($appt_data['payment_status'] == 'paid') {
        $error_message = "⚠️ Payment is already marked as PAID.";
    } else {
        // Mark as paid - appointment stays PENDING until admin approves it
        $update_query = "UPDATE appointment SET payment_status = 'paid' WHERE appoid = $appo_id AND status = 'pending'";
        
        if ($database->query($update_query)) {
            $success_message = "✅ Payment status updated to PAID! The appointment is still PENDING. You can now approve it.";
        } else {
            $error_message = "❌ Error updating payment status: " . $database->error;
        }
    }
}

// DELETION LOGIC - Admin can delete any appointment at any time
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    
    // Check if appointment exists before deleting
    $check = $database->query("SELECT appoid, status, payment_status FROM appointment WHERE appoid = $appo_id");
    $data = $check->fetch_assoc();
    
    if (!$data) {
        $error_message = "❌ Appointment not found.";
    } else {
        // WARNING: This performs a permanent delete (hard delete)
        // Admin can delete appointments at ANY status (pending/approved/completed/rejected)
        $delete_query = "DELETE FROM appointment WHERE appoid = $appo_id";
        
        if ($database->query($delete_query)) {
            $success_message = "✅ Appointment deleted permanently.";
        } else {
            $error_message = "❌ Error deleting appointment: " . $database->error;
        }
    }
}

// COMPLETION LOGIC - Can only complete APPROVED appointments with PAID status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_appointment'])) {
    $appo_id = (int)$_POST['appo_id'];
    
    // Check if appointment is approved and paid
    $check = $database->query("SELECT status, payment_status FROM appointment WHERE appoid = $appo_id");
    $data = $check->fetch_assoc();
    
    if (!$data) {
        $error_message = "❌ Appointment not found.";
    } else if ($data['status'] != 'approved') {
        $error_message = "❌ Only approved appointments can be marked as completed.";
    } else if ($data['payment_status'] != 'paid') {
        $error_message = "❌ Cannot complete appointment. Payment must be completed first!";
    } else {
        // Double-check both conditions before completing
        $update_query = "UPDATE appointment SET status = 'completed' WHERE appoid = $appo_id AND payment_status = 'paid' AND status = 'approved'";
        
        if ($database->query($update_query)) {
            $success_message = "✅ Appointment marked as completed!";
        } else {
            $error_message = "❌ Error updating appointment.";
        }
    }
}
// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];

// IMPORTANT: Exclude completed appointments from the default view
// They will only show if explicitly filtered
if ($filter_status == 'all') {
    $where_clauses[] = "a.status != 'completed'";
} else {
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

// Get statistics (keep all counts accurate)
$total_appointments = $database->query("SELECT COUNT(*) as count FROM appointment WHERE status != 'completed'")->fetch_assoc()['count'];
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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/appointment.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-tooth"></i></div>
            <div class="logo-text">
                <h2>Dr. Dental Clinic</h2>
                <p>Admin Portal</p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="appointment.php" class="nav-item active">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
                <a href="patient.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Patients</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Reports</div>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="revenue.php" class="nav-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Revenue</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </aside>
    
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleMobile()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Appointments Management</h1>
                <p>Welcome back, <?php echo $adminName; ?>!</p>
            </div>
        </div>
        
        <div class="content-container">
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
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-value"><?php echo $total_appointments; ?></div>
                        </div>
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Pending Approval</div>
                            <div class="stat-value"><?php echo $pending_appointments; ?></div>
                        </div>
                        <div class="stat-icon icon-green">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Approved</div>
                            <div class="stat-value"><?php echo $approved_appointments; ?></div>
                        </div>
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">₱<?php echo number_format($total_revenue); ?></div>
                        </div>
                        <div class="stat-icon icon-orange">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                    </div>
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
        <div class="panel">
            <div class="table-header">
                <h2>Appointments List</h2>
                <span style="color: var(--text-med);"><?php echo $appointments_query->num_rows; ?> appointments found</span>
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
                                    <div style="font-size: 12px; color: var(--text-med);"><?php echo e($appt['pemail']); ?></div>
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
            <?php if ($appt['payment_status'] == 'paid'): ?>
                <!-- Payment is PAID - Show Approve button -->
                <button 
                    class="btn-small btn-icon btn-approve ajax-action" 
                    title="Approve Appointment"
                    data-action="approve_appointment"
                    data-appo-id="<?php echo $appt['appoid']; ?>"
                    data-confirm="Approve appointment #<?php echo e($appt['apponum']); ?>?"
                >
                    <i class="fas fa-check"></i>
                </button>
            <?php else: ?>
                <!-- Payment is UNPAID - Show payment warning button -->
                <button 
                    class="btn-small btn-icon ajax-action" 
                    style="background: #ff9800; color: white;"
                    title="⚠️ Mark as PAID (For cash/manual payments)"
                    data-action="mark_paid"
                    data-appo-id="<?php echo $appt['appoid']; ?>"
                    data-confirm="Mark this appointment as PAID?\n\nOnly mark as paid if you've received payment in person or verified the transaction."
                >
                    <i class="fas fa-dollar-sign"></i>
                </button>
                
                <!-- Disabled Approve button with tooltip -->
                <button 
                    class="btn-small btn-icon" 
                    style="background: #ccc; color: #666; cursor: not-allowed; opacity: 0.6;"
                    title="❌ Cannot approve - Payment not completed yet"
                    disabled
                >
                    <i class="fas fa-check"></i>
                </button>
            <?php endif; ?>
            
            <!-- Reject button - always available for pending -->
            <button 
                class="btn-small btn-icon btn-reject ajax-action" 
                title="Reject Appointment"
                data-action="reject_appointment"
                data-appo-id="<?php echo $appt['appoid']; ?>"
                data-confirm="Reject appointment #<?php echo e($appt['apponum']); ?>?"
            >
                <i class="fas fa-times"></i>
            </button>
            
        <?php elseif ($appt['status'] == 'approved'): ?>
            <!-- For APPROVED appointments - Show Complete button -->
            <button 
                class="btn-small btn-icon btn-complete ajax-action" 
                title="Mark as Completed"
                data-action="complete_appointment"
                data-appo-id="<?php echo $appt['appoid']; ?>"
                data-confirm="Mark appointment #<?php echo e($appt['apponum']); ?> as completed?"
            >
                <i class="fas fa-check-double"></i>
            </button>
            
        <?php elseif ($appt['status'] == 'completed'): ?>
            <!-- Show completed status -->
            <span style="color: #28a745; font-weight: 600; font-size: 12px;">
                <i class="fas fa-check-circle"></i> Completed
            </span>
            
        <?php elseif ($appt['status'] == 'rejected'): ?>
            <!-- Show rejected status -->
            <span style="color: #dc3545; font-weight: 600; font-size: 12px;">
                <i class="fas fa-times-circle"></i> Rejected
            </span>
        <?php endif; ?>
        
        <!-- Delete button - always available -->
        <button 
            class="btn-small btn-icon btn-reject ajax-action" 
            title="Delete Appointment"
            data-action="delete_appointment"
            data-appo-id="<?php echo $appt['appoid']; ?>"
            data-confirm="⚠️ PERMANENTLY delete appointment #<?php echo e($appt['apponum']); ?>?\n\nThis action cannot be undone!"
        >
            <i class="fas fa-trash-alt"></i>
        </button>
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
</main>

<script>
    // Toggle sidebar function
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('mini');
    }
    
    // Toggle mobile sidebar
    function toggleMobile() {
        document.getElementById('sidebar').classList.toggle('mobile-open');
    }
    
    // Toast notification function
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <div>${message}</div>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                container.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Handle all AJAX action buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.ajax-action')) {
        e.preventDefault();
        const button = e.target.closest('.ajax-action');
        
        const confirmMessage = button.getAttribute('data-confirm');
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }
        
        const action = button.getAttribute('data-action');
        const appoId = button.getAttribute('data-appo-id');
        
        // Get the current row to update it
        const currentRow = button.closest('tr');
        
        button.disabled = true;
        button.style.opacity = '0.5';
        
        const formData = new FormData();
        formData.append(action, '1');
        formData.append('appo_id', appoId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update the specific row instead of the entire table
            const allRows = doc.querySelectorAll('tbody tr');
            let foundRow = null;
            
            allRows.forEach(row => {
                const rowAppoId = row.querySelector('.ajax-action')?.getAttribute('data-appo-id');
                if (rowAppoId === appoId) {
                    foundRow = row;
                }
            });
            
            if (foundRow && currentRow) {
                // Update status badge
                const newStatusBadge = foundRow.querySelector('.status-badge.status-pending, .status-badge.status-approved, .status-badge.status-completed, .status-badge.status-rejected');
                const currentStatusBadge = currentRow.querySelector('.status-badge.status-pending, .status-badge.status-approved, .status-badge.status-completed, .status-badge.status-rejected');
                if (newStatusBadge && currentStatusBadge) {
                    currentStatusBadge.className = newStatusBadge.className;
                    currentStatusBadge.textContent = newStatusBadge.textContent;
                }
                
                // Update payment badge
                const newPaymentBadge = foundRow.querySelector('.payment-paid, .payment-unpaid');
                const currentPaymentBadge = currentRow.querySelector('.payment-paid, .payment-unpaid');
                if (newPaymentBadge && currentPaymentBadge) {
                    currentPaymentBadge.className = newPaymentBadge.className;
                    currentPaymentBadge.textContent = newPaymentBadge.textContent;
                }
                
                // Update action buttons
                const newActions = foundRow.querySelector('.action-buttons');
                const currentActions = currentRow.querySelector('.action-buttons');
                if (newActions && currentActions) {
                    currentActions.innerHTML = newActions.innerHTML;
                }
            }
            
            // Update statistics
            const newStats = doc.querySelectorAll('.stat-card .stat-value');
            const currentStats = document.querySelectorAll('.stat-card .stat-value');
            newStats.forEach((stat, index) => {
                if (currentStats[index]) {
                    currentStats[index].textContent = stat.textContent;
                }
            });
            
            // Check for alerts and show toast
            const successAlert = doc.querySelector('.alert-success');
            const errorAlert = doc.querySelector('.alert-error');
            
            if (successAlert) {
                const message = successAlert.textContent.trim().replace(/\s+/g, ' ');
                showToast(message, 'success');
            } else if (errorAlert) {
                const message = errorAlert.textContent.trim().replace(/\s+/g, ' ');
                showToast(message, 'error');
            } else {
               const messages = {
                            'mark_paid': '✅ Payment status updated to PAID! You can now approve the appointment.',
                            'approve_appointment': '✅ Appointment approved successfully!',
                            'reject_appointment': '✅ Appointment rejected.',
                            'complete_appointment': '✅ Appointment marked as completed!',
                            'delete_appointment': '✅ Appointment deleted permanently.'
                        };
                        showToast(messages[action] || '✅ Action completed successfully!', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('❌ An error occurred. Please try again.', 'error');
            button.disabled = false;
            button.style.opacity = '1';
        });
    }
});
    
    // Show any initial alerts as toasts
    window.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.querySelector('.alert-success');
        const errorAlert = document.querySelector('.alert-error');
        
        if (successAlert) {
            const message = successAlert.textContent.trim().replace(/\s+/g, ' ');
            showToast(message, 'success');
            successAlert.remove();
        }
        
        if (errorAlert) {
            const message = errorAlert.textContent.trim().replace(/\s+/g, ' ');
            showToast(message, 'error');
            errorAlert.remove();
        }
    });
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });
</script>
</body>
</html>
