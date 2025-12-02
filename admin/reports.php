<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';

if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'Asia/Manila');

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$totalAppointments = 0;
$completedAppointments = 0;
$cancelledAppointments = 0;
$pendingAppointments = 0;
$avgAppointmentsPerDay = 0;
$totalPatients = 0;
$newPatients = 0;
$serviceDistribution = [];
$statusDistribution = [];
$dailyAppointments = [];
$topPatients = [];

try {
    // Total appointments in date range
    $stmt = $database->prepare("SELECT COUNT(*) AS c FROM appointment WHERE DATE(appodate) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $totalAppointments = (int)$result->fetch_assoc()['c'];
    }
    
    // Status distribution
    $stmt = $database->prepare("SELECT status, COUNT(*) AS c FROM appointment WHERE DATE(appodate) BETWEEN ? AND ? GROUP BY status");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $statusDistribution[] = $row;
            if ($row['status'] === 'completed') {
                $completedAppointments = (int)$row['c'];
            } else if ($row['status'] === 'cancelled' || $row['status'] === 'rejected') {
                $cancelledAppointments += (int)$row['c'];
            } else if ($row['status'] === 'pending') {
                $pendingAppointments = (int)$row['c'];
            }
        }
    }
    
    // Average appointments per day
    $stmt = $database->prepare("SELECT COUNT(DISTINCT DATE(appodate)) AS days FROM appointment WHERE DATE(appodate) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $days = (int)$result->fetch_assoc()['days'];
        $avgAppointmentsPerDay = $days > 0 ? round($totalAppointments / $days, 1) : 0;
    }
    
    // Total patients
    $result = $database->query("SELECT COUNT(*) AS c FROM patient");
    if ($result) {
        $totalPatients = (int)$result->fetch_assoc()['c'];
    }
    
    // New patients in date range
    $stmt = $database->prepare("SELECT COUNT(*) AS c FROM patient WHERE DATE(registered_date) BETWEEN ? AND ?");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $newPatients = (int)$result->fetch_assoc()['c'];
    }
    
    // Service distribution - Fixed to get all services regardless of date range for better visibility
    $serviceQuery = "SELECT service_type, COUNT(*) AS cnt 
                     FROM appointment 
                     WHERE service_type IS NOT NULL 
                     AND service_type != '' 
                     AND TRIM(service_type) != ''
                     GROUP BY service_type 
                     ORDER BY cnt DESC 
                     LIMIT 10";
    
    $result = $database->query($serviceQuery);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $serviceDistribution[] = $row;
        }
    }
    
    // Daily appointments for last 30 days (fixed query)
    $dailyQuery = "SELECT DATE(appodate) as appt_date, DATE_FORMAT(appodate, '%b %d') AS day, COUNT(*) AS cnt 
                   FROM appointment 
                   WHERE DATE(appodate) >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) 
                   AND DATE(appodate) <= CURDATE() 
                   GROUP BY DATE(appodate) 
                   ORDER BY DATE(appodate) ASC";
    
    $result = $database->query($dailyQuery);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dailyAppointments[] = ['day' => $row['day'], 'cnt' => (int)$row['cnt']];
        }
    }
    
    // If no data, create placeholder
    if (empty($dailyAppointments)) {
        for ($i = 29; $i >= 0; $i--) {
            $date = date('M d', strtotime("-$i days"));
            $dailyAppointments[] = ['day' => $date, 'cnt' => 0];
        }
    }
    
    // Top patients - Shows filtered data when available, falls back to all-time
$topPatientsFiltered = [];
$topPatientsQuery = "SELECT p.pid, p.pname, p.pemail, p.profile_picture, 
                     COUNT(a.appoid) AS appt_count 
                     FROM patient p
                     INNER JOIN appointment a ON p.pid = a.pid 
                     WHERE DATE(a.appodate) BETWEEN ? AND ?
                     GROUP BY p.pid, p.pname, p.pemail, p.profile_picture
                     HAVING appt_count > 0 
                     ORDER BY appt_count DESC 
                     LIMIT 5";

$stmt = $database->prepare($topPatientsQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $topPatientsFiltered[] = [
            'pid' => (int)$row['pid'],
            'pname' => $row['pname'], 
            'pemail' => $row['pemail'], 
            'profile_picture' => $row['profile_picture'],
            'appt_count' => (int)$row['appt_count']
        ];
    }
}

// Fallback: Top patients all-time (if no data in selected period)
$topPatientsAllTime = [];
$topPatientsAllTimeQuery = "SELECT p.pid, p.pname, p.pemail, p.profile_picture, 
                            COUNT(a.appoid) AS appt_count 
                            FROM patient p
                            INNER JOIN appointment a ON p.pid = a.pid 
                            GROUP BY p.pid, p.pname, p.pemail, p.profile_picture
                            HAVING appt_count > 0 
                            ORDER BY appt_count DESC 
                            LIMIT 5";

$result = $database->query($topPatientsAllTimeQuery);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $topPatientsAllTime[] = [
            'pid' => (int)$row['pid'],
            'pname' => $row['pname'], 
            'pemail' => $row['pemail'], 
            'profile_picture' => $row['profile_picture'],
            'appt_count' => (int)$row['appt_count']
        ];
    }
}

// Use filtered if it has data, otherwise show all-time
$topPatients = !empty($topPatientsFiltered) ? $topPatientsFiltered : $topPatientsAllTime;
$showingAllTime = empty($topPatientsFiltered); 
} catch (Exception $e) {
    error_log("Analytics Error: " . $e->getMessage());
}

// Initialize variable to prevent undefined errors
$showingAllTime = false;

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics - Admin Dashboard</title>
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
.top-bar{background:var(--white);padding:20px 30px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;flex-wrap:wrap;gap:15px}
.page-title h1{font-size:24px;font-weight:700;color:var(--text-dark);margin-bottom:4px}
.page-title p{font-size:14px;color:var(--text-med)}
.top-actions{display:flex;gap:12px;align-items:center}
.filter-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.form-group{display:flex;flex-direction:column;gap:4px}
.form-group label{font-size:12px;color:var(--text-med);font-weight:600}
.form-group input{padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px}
.btn{padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:8px;font-size:14px;border:none;cursor:pointer}
.btn-primary{background:var(--primary);color:white}
.btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px)}
.content-container{padding:30px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:30px}
.stat-card{background:var(--white);padding:24px;border-radius:12px;box-shadow:var(--shadow);transition:all .3s;border-left:4px solid transparent}
.stat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.stat-card.blue{border-left-color:#3b82f6}
.stat-card.green{border-left-color:var(--primary)}
.stat-card.purple{border-left-color:#8b5cf6}
.stat-card.orange{border-left-color:#f59e0b}
.stat-card.red{border-left-color:#ef4444}
.stat-card.cyan{border-left-color:#06b6d4}
.stat-label{color:var(--text-med);font-size:13px;font-weight:500;margin-bottom:8px}
.stat-value{font-size:28px;font-weight:700;color:var(--text-dark)}
.stat-change{font-size:12px;margin-top:8px}
.panels-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:25px;margin-bottom:30px}
.panel{background:var(--white);border-radius:12px;padding:25px;box-shadow:var(--shadow)}
.panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #f3f4f6}
.panel-title{font-size:18px;font-weight:600;color:var(--text-dark);display:flex;align-items:center;gap:10px}
.panel-title i{color:var(--primary)}
.chart-container{height:300px;margin-top:10px;position:relative}
.table{width:100%;border-collapse:collapse}
.table th{text-align:left;padding:12px;font-size:12px;font-weight:600;color:var(--text-med);text-transform:uppercase;border-bottom:2px solid #f3f4f6}
.table td{padding:14px 12px;border-bottom:1px solid #f3f4f6;font-size:14px}
.table tr:hover{background:#f9fafb}
.progress-bar{width:100%;height:8px;background:#f3f4f6;border-radius:4px;overflow:hidden;margin-top:8px}
.progress-fill{height:100%;background:var(--primary);transition:width .3s}
.empty-state{text-align:center;padding:40px 20px;color:var(--text-med)}
.empty-state i{font-size:48px;margin-bottom:12px;opacity:0.3}
.empty-state p{font-size:14px}
.mobile-toggle{display:none}
@media(max-width:768px){
.sidebar{transform:translateX(-100%)}
.sidebar.mobile-open{transform:translateX(0)}
.main-content{margin-left:0}
.top-bar{padding:15px 20px}
.filter-form{flex-direction:column;align-items:stretch;width:100%}
.content-container{padding:20px 15px}
.stats-grid{grid-template-columns:1fr}
.panels-grid{grid-template-columns:1fr}
.mobile-toggle{display:block!important;position:fixed;top:20px;left:20px;z-index:1001;background:var(--primary);color:white;border:none;padding:10px 15px;border-radius:8px;cursor:pointer}
}
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
<a href="reports.php" class="nav-item active"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
<a href="revenue.php" class="nav-item"><i class="fas fa-dollar-sign"></i><span>Revenue</span></a>
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

<button class="mobile-toggle" onclick="toggleMobile()"><i class="fas fa-bars"></i></button>

<main class="main-content">
<div class="top-bar">
<div class="page-title">
<h1>Analytics & Reports</h1>
<p><?php echo date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate)); ?></p>
</div>
<div class="top-actions">
<form method="GET" class="filter-form">
<div class="form-group">
<label>Start Date</label>
<input type="date" name="start_date" value="<?php echo e($startDate); ?>">
</div>
<div class="form-group">
<label>End Date</label>
<input type="date" name="end_date" value="<?php echo e($endDate); ?>">
</div>
<button type="submit" class="btn btn-primary">
<i class="fas fa-filter"></i> Filter
</button>
<a href="reports.php" class="btn" style="background:#6b7280;color:white">
<i class="fas fa-redo"></i> Reset
</a>
</form>
</div>
</div>

<div class="content-container">

<?php if ($totalAppointments == 0): ?>
<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:15px;margin-bottom:20px;border-radius:8px">
    <strong style="color:#92400e"><i class="fas fa-exclamation-triangle"></i> No appointments in this date range</strong>
    <p style="color:#92400e;font-size:13px;margin-top:5px">
        Try selecting dates where you have data, or click "Reset" for current month.
    </p>
</div>
<?php endif; ?>

<div class="stats-grid">
<div class="stat-card blue">
<div class="stat-label">Total Appointments</div>
<div class="stat-value"><?php echo number_format($totalAppointments); ?></div>
<div class="stat-change">In selected period</div>
</div>
<div class="stat-card green">
<div class="stat-label">Completed</div>
<div class="stat-value"><?php echo number_format($completedAppointments); ?></div>
<div class="stat-change"><?php echo $totalAppointments > 0 ? round(($completedAppointments/$totalAppointments)*100,1) : 0; ?>% success</div>
</div>
<div class="stat-card orange">
<div class="stat-label">Pending</div>
<div class="stat-value"><?php echo number_format($pendingAppointments); ?></div>
<div class="stat-change">Awaiting approval</div>
</div>
<div class="stat-card red">
<div class="stat-label">Cancelled/Rejected</div>
<div class="stat-value"><?php echo number_format($cancelledAppointments); ?></div>
<div class="stat-change"><?php echo $totalAppointments > 0 ? round(($cancelledAppointments/$totalAppointments)*100,1) : 0; ?>%</div>
</div>
<div class="stat-card purple">
<div class="stat-label">Avg. Per Day</div>
<div class="stat-value"><?php echo $avgAppointmentsPerDay; ?></div>
<div class="stat-change">Daily average</div>
</div>
<div class="stat-card cyan">
<div class="stat-label">New Patients</div>
<div class="stat-value"><?php echo number_format($newPatients); ?></div>
<div class="stat-change">In this period</div>
</div>
</div>

<div class="panels-grid">
<div class="panel">
<div class="panel-header">
<div class="panel-title"><i class="fas fa-chart-line"></i>Daily Appointments (Last 30 Days)</div>
</div>
<div class="chart-container">
<canvas id="dailyChart"></canvas>
</div>
</div>

<div class="panel">
<div class="panel-header">
<div class="panel-title"><i class="fas fa-chart-pie"></i>Status Distribution</div>
</div>
<?php if (!empty($statusDistribution)): ?>
<div class="chart-container">
<canvas id="statusChart"></canvas>
</div>
<?php else: ?>
<div class="empty-state">
<i class="fas fa-chart-pie"></i>
<p>No data for selected period</p>
</div>
<?php endif; ?>
</div>
</div>

<div class="panels-grid">
    <!-- Service Distribution Panel -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas fa-tooth"></i>Service Distribution
                <span style="font-size:11px;font-weight:400;color:#10b981;margin-left:8px;background:rgba(16,185,129,0.1);padding:4px 10px;border-radius:12px">
                    All Time Data
                </span>
            </div>
        </div>
        <?php if (!empty($serviceDistribution)): ?>
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Calculate total from service distribution for accurate percentages
                    $totalServices = array_sum(array_column($serviceDistribution, 'cnt'));
                    foreach($serviceDistribution as $service): 
                        $percentage = $totalServices > 0 ? round(($service['cnt']/$totalServices)*100,1) : 0;
                    ?>
                    <tr>
                        <td><?php echo e($service['service_type']); ?></td>
                        <td><strong><?php echo number_format($service['cnt']); ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="min-width:45px"><?php echo $percentage; ?>%</span>
                                <div class="progress-bar" style="flex:1;max-width:150px">
                                    <div class="progress-fill" style="width:<?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tooth"></i>
            <p>No services recorded yet</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Patients Panel -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fas fa-user-friends"></i>Top Patients
                <?php if ($showingAllTime): ?>
                    <span style="font-size:12px;font-weight:400;color:#f59e0b;margin-left:8px;background:rgba(245,158,11,0.1);padding:4px 10px;border-radius:12px">
                        <i class="fas fa-info-circle"></i> Showing All-Time (No data in selected period)
                    </span>
                <?php else: ?>
                    <span style="font-size:12px;font-weight:400;color:#10b981;margin-left:8px;background:rgba(16,185,129,0.1);padding:4px 10px;border-radius:12px">
                        <i class="fas fa-calendar-check"></i> Selected Period
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($topPatients)): ?>
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Email</th>
                        <th>Total Appointments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach($topPatients as $patient): 
                    ?>
                    <tr>
                        <td>
                            <div style="width:30px;height:30px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px">
                                <?php echo $rank; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                                <img src="../<?php echo e($patient['profile_picture']); ?>" style="width:35px;height:35px;border-radius:50%;object-fit:cover;border:2px solid rgba(16,185,129,0.2)">
                                <?php else: ?>
                                <div style="width:35px;height:35px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:14px;border:2px solid rgba(16,185,129,0.2)">
                                    <?php echo strtoupper(substr($patient['pname'], 0, 2)); ?>
                                </div>
                                <?php endif; ?>
                                <strong><?php echo e($patient['pname']); ?></strong>
                            </div>
                        </td>
                        <td style="color:var(--text-med)"><?php echo e($patient['pemail']); ?></td>
                        <td>
                            <span style="background:rgba(16,185,129,0.1);color:var(--primary-dark);padding:6px 12px;border-radius:12px;font-weight:600;font-size:13px">
                                <i class="fas fa-calendar-check"></i> <?php echo number_format($patient['appt_count']); ?> appointments
                            </span>
                        </td>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-friends"></i>
            <p>No patient data available</p>
        </div>
        <?php endif; ?>
    </div>
</div>


</div>
</main>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('mini')}
function toggleMobile(){document.getElementById('sidebar').classList.toggle('mobile-open')}

// Daily Appointments Chart
var dailyData = <?php echo json_encode($dailyAppointments); ?>;
console.log('Daily Data:', dailyData); // Debug

if (dailyData && dailyData.length > 0) {
    var ctx = document.getElementById('dailyChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(function(d) { return d.day; }),
                datasets: [{
                    label: 'Appointments',
                    data: dailyData.map(function(d) { return parseInt(d.cnt); }),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(31,41,55,0.95)',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { precision: 0, color: '#6b7280' },
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
}

// Status Distribution Chart
<?php if (!empty($statusDistribution)): ?>
var statusData = <?php echo json_encode($statusDistribution); ?>;
console.log('Status Data:', statusData); // Debug

if (statusData && statusData.length > 0) {
    var ctx2 = document.getElementById('statusChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: statusData.map(function(s) { return s.status.charAt(0).toUpperCase() + s.status.slice(1); }),
                datasets: [{
                    data: statusData.map(function(s) { return parseInt(s.c); }),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(31,41,55,0.95)',
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
    }
}
<?php endif; ?>
</script>
</body>
</html>