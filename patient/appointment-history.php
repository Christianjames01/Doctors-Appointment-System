<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['user']) || $_SESSION['usertype'] != 'p') {
    header("Location: ../login.php");
    exit();
}

$useremail = $_SESSION['user'];

$stmt = $database->prepare("SELECT * FROM patient WHERE pemail = ?");
$stmt->bind_param("s", $useremail);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: ../logout.php");
    exit();
}

$patient_id = $patient['pid'];
$patient_name = $patient['pname'];

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter options
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_payment = isset($_GET['payment']) ? $_GET['payment'] : 'all';

// Build query with filters
$where_clauses = ["pid = ?"];
$params = [$patient_id];
$param_types = "i";

if ($filter_status != 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if ($filter_payment != 'all') {
    $where_clauses[] = "payment_status = ?";
    $params[] = $filter_payment;
    $param_types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM appointment WHERE $where_sql";
$stmt = $database->prepare($count_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get appointments
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$sql = "SELECT * FROM appointment WHERE $where_sql ORDER BY appodate DESC LIMIT ? OFFSET ?";
$stmt = $database->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();

// Get statistics
$stmt = $database->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_paid
FROM appointment WHERE pid = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History - Dr. Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/styles.css">
    <style>
        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container">
                <div class="logo">
                    <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>
        </div>
            <div class="user-profile-nav">
                <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                    <div class="user-avatar-nav">
                        <img src="../<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile">
                    </div>
                <?php else: ?>
                    <div class="user-avatar-nav">
                        <?php echo strtoupper(substr($patient_name, 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <div class="user-info-nav">
                    <h4><?php echo htmlspecialchars(substr($patient_name, 0, 20)); ?></h4>
                    <p>Patient</p>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-menu">
            <div class="nav-section-title">Main Menu</div>
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Available Sessions</span>
                </a>
            </div>
            
            <div class="nav-section-title">My Appointments</div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="appointment-history.php" class="nav-link active">
                    <i class="fas fa-history"></i>
                    <span>Appointment History</span>
                </a>
            </div>
            
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>
        
        <div class="logout-section">
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h1>Appointment History 📋</h1>
                <p>View and manage all your past and upcoming dental appointments</p>
                <div class="welcome-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-check"></i>
                        <span><?php echo $stats['total']; ?> Total Appointments</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>₱<?php echo number_format($stats['total_paid'], 2); ?> Paid</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="appointments-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-filter"></i>
                    Filter Appointments
                </h2>
            </div>

            <form method="GET" style="display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
                <select name="status" style="padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 12px; background: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <select name="payment" style="padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 12px; background: white; font-size: 14px; font-weight: 500; cursor: pointer;">
                    <option value="all" <?php echo $filter_payment == 'all' ? 'selected' : ''; ?>>All Payment</option>
                    <option value="unpaid" <?php echo $filter_payment == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="paid" <?php echo $filter_payment == 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>

                <button type="submit" class="action-btn" style="padding: 12px 30px;">
                    <i class="fas fa-filter"></i>
                    Apply Filters
                </button>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="appointments-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Your Appointments
                </h2>
                <div class="filter-tabs">
                    <button class="filter-tab active">All (<?php echo $total_records; ?>)</button>
                </div>
            </div>

            <?php if ($appointments->num_rows > 0): ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Appt. No.</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($appt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><span class="appoint-num">#<?php echo htmlspecialchars($appt['apponum']); ?></span></td>
                                <td><?php echo htmlspecialchars($appt['service_type']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($appt['appodate'])); ?><br>
                                    <small style="color: var(--gray-500);"><?php echo date('g:i A', strtotime($appt['appodate'])); ?></small>
                                </td>
                                <td><strong>₱<?php echo number_format($appt['amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($appt['status']); ?>">
                                        <?php echo ucfirst($appt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($appt['payment_status']); ?>">
                                        <?php echo ucfirst($appt['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appt['payment_status'] == 'unpaid'): ?>
                                        <a href="../payment.php?appo_id=<?php echo $appt['appoid']; ?>" class="action-btn" style="padding: 8px 16px; font-size: 13px;">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 13px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 10px; margin-top: 30px; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>" 
                               style="padding: 10px 15px; background: white; color: var(--gray-800); text-decoration: none; border-radius: 8px; font-weight: 600; border: 2px solid var(--gray-200); transition: all 0.3s;">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span style="padding: 10px 15px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; border-radius: 8px; font-weight: 600;">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>" 
                                   style="padding: 10px 15px; background: white; color: var(--gray-800); text-decoration: none; border-radius: 8px; font-weight: 600; border: 2px solid var(--gray-200); transition: all 0.3s;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>" 
                               style="padding: 10px 15px; background: white; color: var(--gray-800); text-decoration: none; border-radius: 8px; font-weight: 600; border: 2px solid var(--gray-200); transition: all 0.3s;">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>No Appointments Found</h3>
                    <p>You don't have any appointments matching the selected filters.</p>
                    <a href="booking.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>