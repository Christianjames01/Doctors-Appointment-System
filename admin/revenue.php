<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';

if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// Initialize variables
$totalRevenue = $paidRevenue = $unpaidRevenue = 0;
$revenueByService = $revenueByMonth = $recentPayments = [];
$chartLabels = $chartData = [];
$filterPeriod = $_GET['period'] ?? 'all';
$filterService = $_GET['service'] ?? 'all';

try {
    // Build WHERE clause for filters
    $whereClause = "WHERE amount IS NOT NULL";
    
    if ($filterPeriod !== 'all') {
        switch ($filterPeriod) {
            case 'today':
                $whereClause .= " AND DATE(appodate) = CURDATE()";
                break;
            case 'week':
                $whereClause .= " AND YEARWEEK(appodate) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $whereClause .= " AND MONTH(appodate) = MONTH(CURDATE()) AND YEAR(appodate) = YEAR(CURDATE())";
                break;
            case 'year':
                $whereClause .= " AND YEAR(appodate) = YEAR(CURDATE())";
                break;
        }
    }
    
    if ($filterService !== 'all') {
        $whereClause .= " AND service_type = '" . $database->real_escape_string($filterService) . "'";
    }

    // Total Revenue Statistics - NOW FILTERED
    $sql = "SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid,
        SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid,
        SUM(amount) AS total
        FROM appointment $whereClause";
    
    $result = $database->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $paidRevenue = $row['paid'] ?? 0;
        $unpaidRevenue = $row['unpaid'] ?? 0;
        $totalRevenue = $row['total'] ?? 0;
    }

    // Revenue by Service - NOW FILTERED
    $sql = "SELECT service_type, 
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
            SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid_amount,
            COUNT(*) AS appointment_count
            FROM appointment 
            $whereClause
            GROUP BY service_type 
            ORDER BY paid_amount DESC";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenueByService[] = $row;
        }
    }

    // Revenue by Month - Adjust based on filter period
    $monthWhereClause = "WHERE amount IS NOT NULL";
    $dateInterval = "12 MONTH"; // Default
    
    if ($filterPeriod === 'today') {
        $dateInterval = "1 DAY";
        $monthWhereClause .= " AND DATE(appodate) = CURDATE()";
    } elseif ($filterPeriod === 'week') {
        $dateInterval = "7 DAY";
        $monthWhereClause .= " AND appodate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filterPeriod === 'month') {
        $dateInterval = "30 DAY";
        $monthWhereClause .= " AND MONTH(appodate) = MONTH(CURDATE()) AND YEAR(appodate) = YEAR(CURDATE())";
    } elseif ($filterPeriod === 'year') {
        $dateInterval = "12 MONTH";
        $monthWhereClause .= " AND YEAR(appodate) = YEAR(CURDATE())";
    } else {
        $monthWhereClause .= " AND appodate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
    }
    
    if ($filterService !== 'all') {
        $monthWhereClause .= " AND service_type = '" . $database->real_escape_string($filterService) . "'";
    }

    $sql = "SELECT 
            DATE_FORMAT(appodate, '%b %Y') AS month_label,
            DATE_FORMAT(appodate, '%Y-%m') AS month_sort,
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS revenue,
            COUNT(*) AS appointments
            FROM appointment 
            $monthWhereClause
            GROUP BY DATE_FORMAT(appodate, '%Y-%m')
            ORDER BY month_sort ASC";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenueByMonth[] = $row;
            $chartLabels[] = $row['month_label'];
            $chartData[] = (float)$row['revenue'];
        }
    }

    // Recent Transactions - NOW FILTERED
    $sql = "SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, 
            a.amount, a.payment_status, a.status
            FROM appointment a 
            LEFT JOIN patient p ON a.pid = p.pid
            $whereClause
            ORDER BY 
                CASE 
                    WHEN a.status = 'completed' THEN 1
                    WHEN a.status = 'approved' THEN 2
                    WHEN a.status = 'pending' THEN 3
                    ELSE 4
                END,
                a.appodate DESC 
            LIMIT 50";
            
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentPayments[] = $row;
        }
    }
    
    // Get all service types for filter
    $services = [];
    $result = $database->query("SELECT DISTINCT service_type FROM appointment WHERE service_type IS NOT NULL ORDER BY service_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row['service_type'];
        }
    }

} catch (Exception $e) {
    error_log("Revenue Page Error: " . $e->getMessage());
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revenue Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="/dental-clinic-appointment-system/css/revenue.css">
</head>
<body>
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
<a href="index.php" class="nav-item"><i class="fas fa-home"></i><span>Home</span></a>
<a href="appointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
<a href="patient.php" class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
</div>
<div class="nav-section">
<div class="nav-section-title">Reports</div>
<a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
<a href="revenue.php" class="nav-item active"><i class="fas fa-dollar-sign"></i><span>Revenue</span></a>
</div>
<div class="nav-section">
<div class="nav-section-title">Settings</div>
<a href="settings.php" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
<a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
</div>
</nav>
<div class="sidebar-footer">
<button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
</div>
</aside>

<main class="main-content">
<div class="top-bar">
<div class="page-title">
<h1>Revenue Management</h1>
<p>Track and analyze clinic revenue</p>
</div>
<div class="top-actions">
<button class="btn btn-primary" onclick="window.print()">
<i class="fas fa-print"></i> Print Report
</button>
</div>
</div>

<div class="content-container">
<div class="stats-grid">
<div class="stat-card green">
<div class="stat-header">
<div>
<div class="stat-label">Total Revenue</div>
<div class="stat-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
</div>
<div class="stat-icon icon-green"><i class="fas fa-dollar-sign"></i></div>
</div>
</div>
<div class="stat-card blue">
<div class="stat-header">
<div>
<div class="stat-label">Paid Revenue</div>
<div class="stat-value">₱<?php echo number_format($paidRevenue, 2); ?></div>
</div>
<div class="stat-icon icon-blue"><i class="fas fa-check-circle"></i></div>
</div>
</div>
<div class="stat-card orange">
<div class="stat-header">
<div>
<div class="stat-label">Pending Revenue</div>
<div class="stat-value">₱<?php echo number_format($unpaidRevenue, 2); ?></div>
</div>
<div class="stat-icon icon-orange"><i class="fas fa-clock"></i></div>
</div>
</div>
</div>

<div class="filter-bar">
<div class="filter-group">
<label>Time Period</label>
<select id="periodFilter" onchange="applyFilters()">
<option value="all" <?php echo $filterPeriod === 'all' ? 'selected' : ''; ?>>All Time</option>
<option value="today" <?php echo $filterPeriod === 'today' ? 'selected' : ''; ?>>Today</option>
<option value="week" <?php echo $filterPeriod === 'week' ? 'selected' : ''; ?>>This Week</option>
<option value="month" <?php echo $filterPeriod === 'month' ? 'selected' : ''; ?>>This Month</option>
<option value="year" <?php echo $filterPeriod === 'year' ? 'selected' : ''; ?>>This Year</option>
</select>
</div>
<div class="filter-group">
<label>Service Type</label>
<select id="serviceFilter" onchange="applyFilters()">
<option value="all" <?php echo $filterService === 'all' ? 'selected' : ''; ?>>All Services</option>
<?php foreach ($services as $service): ?>
<option value="<?php echo e($service); ?>" <?php echo $filterService === $service ? 'selected' : ''; ?>>
<?php echo e($service); ?>
</option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="panels-grid">
<div class="panel">
<div class="panel-header">
<div class="panel-title"><i class="fas fa-chart-line"></i>Revenue Trend</div>
</div>
<div class="chart-container"><canvas id="revenueChart"></canvas></div>
</div>

<div class="panel">
<div class="panel-header">
<div class="panel-title"><i class="fas fa-tooth"></i>Revenue by Service</div>
</div>
<div class="table-container">
<table class="revenue-table">
<thead>
<tr>
<th>Service</th>
<th>Appointments</th>
<th>Paid</th>
<th>Unpaid</th>
</tr>
</thead>
<tbody>
<?php if (!empty($revenueByService)): ?>
<?php foreach ($revenueByService as $service): ?>
<tr>
<td class="service-name"><?php echo e($service['service_type']); ?></td>
<td><?php echo e($service['appointment_count']); ?></td>
<td class="amount-paid">₱<?php echo number_format($service['paid_amount'], 2); ?></td>
<td class="amount-unpaid">₱<?php echo number_format($service['unpaid_amount'], 2); ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="4" class="empty-message">No revenue data available for selected filters</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="panel">
<div class="panel-header">
<div class="panel-title">
<i class="fas fa-list"></i>Recent Transactions
<span style="font-size:12px;color:#64748b;font-weight:400;margin-left:10px;">
(Showing <?php echo count($recentPayments); ?> transactions - Completed shown first)
</span>
</div>
</div>
<div class="table-container">
<table class="revenue-table">
<thead>
<tr>
<th>Appointment #</th>
<th>Patient</th>
<th>Service</th>
<th>Date</th>
<th>Amount</th>
<th>Payment</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php if (!empty($recentPayments)): ?>
<?php foreach ($recentPayments as $payment): ?>
<tr class="<?php echo ($payment['status'] === 'completed') ? 'completed-row' : ''; ?>">
<td><strong>#<?php echo e($payment['apponum']); ?></strong></td>
<td><?php echo e($payment['pname']); ?></td>
<td><?php echo e($payment['service_type']); ?></td>
<td><?php echo date('M j, Y', strtotime($payment['appodate'])); ?></td>
<td class="<?php echo $payment['payment_status'] === 'paid' ? 'amount-paid' : 'amount-unpaid'; ?>">
₱<?php echo number_format($payment['amount'], 2); ?>
</td>
<td>
<span class="status-badge payment-<?php echo e($payment['payment_status']); ?>">
<?php echo ucfirst($payment['payment_status']); ?>
</span>
</td>
<td>
<span class="status-badge status-<?php echo e($payment['status']); ?>">
<?php 
if ($payment['status'] === 'completed') {
    echo '<i class="fas fa-check-circle"></i> ';
}
echo ucfirst($payment['status']); 
?>
</span>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7" class="empty-message">No transactions found for selected filters</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('mini');
}

function applyFilters() {
    const period = document.getElementById('periodFilter').value;
    const service = document.getElementById('serviceFilter').value;
    window.location.href = `revenue.php?period=${period}&service=${service}`;
}

const chartLabels = <?php echo json_encode($chartLabels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>;
const chartData = <?php echo json_encode($chartData ?: [0, 0, 0, 0, 0, 0]); ?>;

const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Revenue (₱)',
                data: chartData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.95)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#6b7280',
                        callback: value => '₱' + value.toLocaleString()
                    },
                    grid: { color: '#f3f4f6' }
                },
                x: {
                    ticks: { color: '#6b7280' },
                    grid: { display: false }
                }
            }
        }
    });
}
</script>
</body>
</html>