<?php
session_start();
include("../connection.php");

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || $_SESSION['usertype'] != 'p') {
    header("Location: ../login.php");
    exit();
}

$useremail = $_SESSION['user'];

// Get patient information
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: var(--white);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--bg-light);
            border-radius: 12px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .user-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .user-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .user-info p {
            font-size: 13px;
            color: var(--text-light);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 150px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            margin: 5px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 20px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 15px;
        }

        .nav-link:hover {
            background: var(--bg-light);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-link i {
            font-size: 18px;
            width: 20px;
        }

        .logout-btn {
            margin: 20px 15px;
            padding: 14px 20px;
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: calc(100% - 30px);
            transition: transform 0.3s ease;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-card .icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card .icon.completed { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card .icon.approved { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card .icon.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card .icon.paid { background: linear-gradient(135deg, #fccb90 0%, #d57eeb 100%); }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filters-section {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters-section label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .filters-section select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s;
        }

        .filters-section select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .filter-btn {
            padding: 10px 25px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .appointments-table {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-light);
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 20px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .appt-number {
            font-weight: 700;
            color: var(--primary-color);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #e6ffed;
            color: #276749;
        }

        .status-approved {
            background: #ebf8ff;
            color: #2c5282;
        }

        .status-pending {
            background: #fffbe5;
            color: #744210;
        }

        .payment-paid {
            background: #e6ffed;
            color: #276749;
        }

        .payment-unpaid {
            background: #fee2e2;
            color: #9b2c2c;
        }

        .pay-now-btn {
            padding: 6px 15px;
            background: #38a169;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .pay-now-btn:hover {
            background: #2f855a;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background: var(--white);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: var(--shadow);
        }

        .pagination a:hover {
            background: var(--primary-gradient);
            color: white;
        }

        .pagination .active {
            background: var(--primary-gradient);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-tooth"></i>
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>

            <div class="user-profile">
                <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                    <img src="../<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?php echo strtoupper(substr($patient_name, 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($patient_name); ?></h3>
                    <p><?php echo htmlspecialchars($useremail); ?></p>
                </div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Home</span></a></div>
            <div class="nav-item"><a href="schedule.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Scheduled Sessions</span></a></div>
            <div class="nav-item"><a href="booking.php" class="nav-link"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a></div>
            <div class="nav-item"><a href="appointment-history.php" class="nav-link active"><i class="fas fa-history"></i><span>Appointment History</span></a></div>
            <div class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a></div>
        </nav>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-history"></i>
                Appointment History
            </h1>
            <p>View all your past and upcoming dental appointments</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon total">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Appointments</p>
            </div>

            <div class="stat-card">
                <div class="icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?php echo $stats['completed']; ?></h3>
                <p>Completed</p>
            </div>

            <div class="stat-card">
                <div class="icon approved">
                    <i class="fas fa-thumbs-up"></i>
                </div>
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Approved</p>
            </div>

            <div class="stat-card">
                <div class="icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
            </div>

            <div class="stat-card">
                <div class="icon paid">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>₱<?php echo number_format($stats['total_paid'], 2); ?></h3>
                <p>Total Paid</p>
            </div>
        </div>

        <form method="GET" class="filters-section">
            <label>Filter by Status:</label>
            <select name="status">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>

            <label>Filter by Payment:</label>
            <select name="payment">
                <option value="all" <?php echo $filter_payment == 'all' ? 'selected' : ''; ?>>All Payment</option>
                <option value="unpaid" <?php echo $filter_payment == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                <option value="paid" <?php echo $filter_payment == 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </form>

        <div class="appointments-table">
            <div class="table-responsive">
                <?php if ($appointments->num_rows > 0): ?>
                    <table>
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
                                    <td><span class="appt-number">#<?php echo htmlspecialchars($appt['apponum']); ?></span></td>
                                    <td><?php echo htmlspecialchars($appt['service_type']); ?></td>
                                    <td>
                                        <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($appt['appodate'])); ?><br>
                                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($appt['appodate'])); ?>
                                    </td>
                                    <td>₱<?php echo number_format($appt['amount'], 2); ?></td>
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
                                    <td>
                                        <?php if ($appt['payment_status'] == 'unpaid' && $appt['status'] == 'approved'): ?>
                                            <a href="../payment.php?appo_id=<?php echo $appt['appoid']; ?>" class="pay-now-btn">
                                                <i class="fas fa-credit-card"></i> Pay Now
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&payment=<?php echo $filter_payment; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Appointments Found</h3>
                        <p>You don't have any appointments matching the selected filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>