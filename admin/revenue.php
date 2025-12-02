<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';

if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// Initialize variables$
$totalRevenue = $paidRevenue = $unpaidRevenue = 0;
$revenueByService = $revenueByMonth = $recentPayments = [];
$chartLabels = $chartData = [];
$filterPeriod = $_GET['period'] ?? 'all';
$filterService = $_GET['service'] ?? 'all';

try {
    // Total Revenue Statistics
    $result = $database->query("SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid,
        SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid,
        SUM(amount) AS total
        FROM appointment WHERE amount IS NOT NULL");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $paidRevenue = $row['paid'] ?? 0;
        $unpaidRevenue = $row['unpaid'] ?? 0;
        $totalRevenue = $row['total'] ?? 0;
    }

    // Build WHERE clause for filters
    $whereClause = "WHERE a.amount IS NOT NULL";
    
    if ($filterPeriod !== 'all') {
        switch ($filterPeriod) {
            case 'today':
                $whereClause .= " AND DATE(a.appodate) = CURDATE()";
                break;
            case 'week':
                $whereClause .= " AND YEARWEEK(a.appodate) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $whereClause .= " AND MONTH(a.appodate) = MONTH(CURDATE()) AND YEAR(a.appodate) = YEAR(CURDATE())";
                break;
            case 'year':
                $whereClause .= " AND YEAR(a.appodate) = YEAR(CURDATE())";
                break;
        }
    }
    
    if ($filterService !== 'all') {
        $whereClause .= " AND a.service_type = '" . $database->real_escape_string($filterService) . "'";
    }

    // Revenue by Service
    $sql = "SELECT service_type, 
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
            SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid_amount,
            COUNT(*) AS appointment_count
            FROM appointment 
            WHERE service_type IS NOT NULL AND amount IS NOT NULL
            GROUP BY service_type 
            ORDER BY paid_amount DESC";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenueByService[] = $row;
        }
    }

    // Revenue by Month (Last 12 months)
    $sql = "SELECT 
            DATE_FORMAT(appodate, '%b %Y') AS month_label,
            DATE_FORMAT(appodate, '%Y-%m') AS month_sort,
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS revenue,
            COUNT(*) AS appointments
            FROM appointment 
            WHERE appodate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND amount IS NOT NULL
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

    // Recent Transactions - Shows ALL appointments with amounts
    $sql = "SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, 
            a.amount, a.payment_status, a.status
            FROM appointment a 
            LEFT JOIN patient p ON a.pid = p.pid
            WHERE a.amount IS NOT NULL
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
<style>
:root{--primary:#10b981;--primary-dark:#059669;--sidebar-bg:#1e293b;--sidebar-hover:#334155;--text-dark:#0f172a;--text-med:#64748b;--bg-light:#f8fafc;--white:#fff;--sidebar-w:260px;--sidebar-mini:70px;--shadow:0 1px 3px rgba(0,0,0,0.1);--shadow-lg:0 10px 25px rgba(0,0,0,0.1)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg-light);color:var(--text-dark);overflow-x:hidden}
.sidebar{position:fixed;left:0;top:0;height:100vh;width:var(--sidebar-w);background:var(--sidebar-bg);transition:width .3s;z-index:1000;display:flex;flex-direction:column;overflow:hidden}
.sidebar.mini{width:var(--sidebar-mini)}
.sidebar-header{padding:20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,0.1);min-height:70px}
.logo-icon{width:40px;height:40px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;flex-shrink:0}
.logo-text{white-space:nowrap;opacity:1;transition:opacity .2s}
.sidebar.mini .logo-text{opacity:0}
.logo-text h2{color:white;font-size:16px;font-weight:700}
.logo-text p{color:rgba(255,255,255,0.6);font-size:12px}
.sidebar-nav{flex:1;padding:20px 10px;overflow-y:auto}
.sidebar-nav::-webkit-scrollbar{width:4px}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.2);border-radius:4px}
.nav-section{margin-bottom:20px}
.nav-section-title{padding:0 15px;font-size:11px;font-weight:600;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;transition:opacity .2s}
.sidebar.mini .nav-section-title{opacity:0;height:0;margin:0}
.nav-item{display:flex;align-items:center;padding:12px 15px;color:rgba(255,255,255,0.8);text-decoration:none;border-radius:8px;margin-bottom:4px;transition:all .2s;position:relative}
.nav-item:hover{background:var(--sidebar-hover);color:white}
.nav-item.active{background:var(--primary);color:white}
.nav-item i{width:20px;font-size:18px;margin-right:12px;text-align:center;flex-shrink:0}
.nav-item span{white-space:nowrap;font-size:14px;font-weight:500;opacity:1;transition:opacity .2s}
.sidebar.mini .nav-item span{opacity:0}
.sidebar-footer{padding:15px;border-top:1px solid rgba(255,255,255,0.1)}
.toggle-btn{width:100%;padding:10px;background:rgba(255,255,255,0.1);border:none;border-radius:8px;color:white;cursor:pointer;transition:all .2s;font-size:16px}
.toggle-btn:hover{background:rgba(255,255,255,0.2)}
.main-content{margin-left:var(--sidebar-w);transition:margin-left .3s;min-height:100vh}
.sidebar.mini~.main-content{margin-left:var(--sidebar-mini)}
.top-bar{background:var(--white);padding:20px 30px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.page-title h1{font-size:24px;font-weight:700;color:var(--text-dark);margin-bottom:4px}
.page-title p{font-size:14px;color:var(--text-med)}
.top-actions{display:flex;gap:12px;align-items:center}
.btn{padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;font-size:14px;border:none;cursor:pointer}
.btn-primary{background:var(--primary);color:white}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px)}
.content-container{padding:30px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
.stat-card{background:var(--white);padding:24px;border-radius:12px;box-shadow:var(--shadow);transition:all .3s;border-left:4px solid transparent}
.stat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.stat-card.green{border-left-color:var(--primary)}
.stat-card.blue{border-left-color:#3b82f6}
.stat-card.orange{border-left-color:#f59e0b}
.stat-header{display:flex;justify-content:space-between;align-items:start;margin-bottom:12px}
.stat-icon{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
.icon-green{background:#d1fae5;color:var(--primary)}
.icon-blue{background:#dbeafe;color:#3b82f6}
.icon-orange{background:#fed7aa;color:#f59e0b}
.stat-label{color:var(--text-med);font-size:13px;font-weight:500;margin-bottom:8px}
.stat-value{font-size:28px;font-weight:700;color:var(--text-dark)}
.filter-bar{background:var(--white);padding:20px;border-radius:12px;margin-bottom:25px;box-shadow:var(--shadow);display:flex;gap:15px;flex-wrap:wrap;align-items:center}
.filter-group{display:flex;flex-direction:column;gap:5px}
.filter-group label{font-size:12px;font-weight:600;color:var(--text-med);text-transform:uppercase;letter-spacing:.5px}
.filter-group select{padding:10px 15px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;color:var(--text-dark);background:white;cursor:pointer;min-width:150px}
.filter-group select:focus{outline:none;border-color:var(--primary)}
.panels-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:25px;margin-bottom:30px}
.panel{background:var(--white);border-radius:12px;padding:25px;box-shadow:var(--shadow)}
.panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #f3f4f6}
.panel-title{font-size:18px;font-weight:600;color:var(--text-dark);display:flex;align-items:center;gap:10px}
.panel-title i{color:var(--primary)}
.chart-container{height:300px;margin-top:10px}
.table-container{overflow-x:auto}
.revenue-table{width:100%;border-collapse:collapse}
.revenue-table th{text-align:left;padding:12px;font-size:12px;font-weight:600;color:var(--text-med);text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f3f4f6}
.revenue-table td{padding:14px 12px;border-bottom:1px solid #f3f4f6;font-size:14px;color:var(--text-dark)}
.revenue-table tr:hover{background:#f9fafb}
.revenue-table tr.completed-row{background:#f0fdf4!important}
.revenue-table tr.completed-row:hover{background:#dcfce7!important}
.service-name{font-weight:600;color:var(--text-dark)}
.amount-paid{color:var(--primary);font-weight:600}
.amount-unpaid{color:#f59e0b;font-weight:600}
.status-badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;display:inline-block}
.payment-paid{background:#d1fae5;color:#065f46}
.payment-unpaid{background:#fee2e2;color:#991b1b}
.status-pending{background:#fef3c7;color:#92400e}
.status-approved{background:#dbeafe;color:#1e40af}
.status-completed{background:#d1fae5;color:#065f46;font-weight:700;border:2px solid #059669}
.status-rejected{background:#fee2e2;color:#991b1b}
.empty-message{text-align:center;color:var(--text-med);padding:40px;font-size:14px}
@media(max-width:1024px){.panels-grid{grid-template-columns:1fr}}
@media(max-width:768px){.sidebar{transform:translateX(-100%)}
.sidebar.mobile-open{transform:translateX(0)}
.main-content{margin-left:0}
.filter-bar{flex-direction:column;align-items:stretch}
.filter-group select{width:100%}
.stats-grid{grid-template-columns:1fr}}
</style>
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
<div class="panel-title"><i class="fas fa-chart-line"></i>Revenue Trend (Last 12 Months)</div>
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
<tr><td colspan="4" class="empty-message">No revenue data available</td></tr>
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
<tr><td colspan="7" class="empty-message">No transactions found</td></tr>
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