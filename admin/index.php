<?php
session_start();
require_once '../connection.php';

// Check if user is admin
if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// Initialize variables
$totalPatients = $totalAppointments = $todayAppointments = 0;
$totalRevenue = 0;
$recentAppointments = [];
$upcomingAppointments = [];
$topServices = [];
$chartMonths = [];
$appointmentChartData = [];
$revenueChartData = []; // New variable for revenue chart

try {
    // Get total patients
    $r = $database->query("SELECT COUNT(*) AS c FROM patient");
    if ($r) $totalPatients = (int)$r->fetch_assoc()['c'];

    // Get total appointments
    $r = $database->query("SELECT COUNT(*) AS c FROM appointment");
    if ($r) $totalAppointments = (int)$r->fetch_assoc()['c'];

    // Get today's appointments
    $r = $database->query("SELECT COUNT(*) AS c FROM appointment WHERE DATE(appodate) = CURDATE()");
    if ($r) $todayAppointments = (int)$r->fetch_assoc()['c'];

    // Get total revenue (using sum of paid appointments for dashboard total)
    $r = $database->query("SELECT SUM(amount) AS total FROM appointment WHERE payment_status = 'paid'");
    if ($r) {
        $result = $r->fetch_assoc();
        $totalRevenue = $result['total'] ?? 0;
    }

    // Get recent appointments (today and tomorrow)
    $sql = "
        SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, a.status, a.payment_status
        FROM appointment a
        LEFT JOIN patient p ON a.pid = p.pid
        WHERE DATE(a.appodate) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ORDER BY a.appodate ASC
        LIMIT 8
    ";
    $res = $database->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $recentAppointments[] = $row;
    }

    // Get upcoming appointments (next 7 days, excluding today)
    $sql = "
        SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, a.status, a.payment_status
        FROM appointment a
        LEFT JOIN patient p ON a.pid = p.pid
        WHERE a.appodate BETWEEN DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY a.appodate ASC
        LIMIT 6
    ";
    $res = $database->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $upcomingAppointments[] = $row;
    }

    // Get top services
    $r = $database->query("
        SELECT service_type, COUNT(*) AS cnt
        FROM appointment
        WHERE service_type IS NOT NULL
        GROUP BY service_type
        ORDER BY cnt DESC
        LIMIT 5
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $topServices[] = $row;
        }
    }

    // Get monthly appointment and revenue data for charts (last 6 months)
    $r = $database->query("
        SELECT DATE_FORMAT(appodate, '%b %Y') AS mon,
               COUNT(*) AS total_appointments,
               SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS total_revenue
        FROM appointment
        WHERE appodate >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(appodate, '%Y-%m')
        ORDER BY MIN(appodate) ASC
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $chartMonths[] = $row['mon'];
            $appointmentChartData[] = (int)$row['total_appointments'];
            $revenueChartData[] = (float)$row['total_revenue']; // Revenue data
        }
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dr. Dental Care Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modern 2025 Design System Variables */
        :root {
            --color-primary: #10b981; /* Modern Green/Teal */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        
        /* Header - Minimalist, Focus on CTA */
        .header {
            background-color: var(--color-white);
            padding: 25px 30px;
            border-radius: var(--border-radius-large);
            margin-bottom: 30px;
            box-shadow: var(--shadow-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 6px solid var(--color-primary);
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--color-text-dark);
        }
        
        .header h1 i {
            color: var(--color-primary);
        }
        
        .header p {
            margin-top: 4px;
            font-size: 14px;
            color: var(--color-text-medium);
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        
        .btn-primary {
            background-color: var(--color-primary);
            color: var(--color-white);
        }
        
        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-subtle);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--color-text-medium);
            border-color: #e5e7eb;
        }
        
        .btn-secondary:hover {
            background-color: #f3f4f6;
            color: var(--color-text-dark);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--color-white);
            padding: 28px;
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
            opacity: 0.9;
        }
        
        .stat-card .label {
            color: var(--color-text-medium);
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-text-dark);
        }
        
        /* Specific Stat Colors (Modernized) */
        .icon-blue { background-color: #eff6ff; color: #2563eb; } /* Patients */
        .icon-green { background-color: #d1fae5; color: #059669; } /* Appointments */
        .icon-purple { background-color: #ede9fe; color: #7c3aed; } /* Revenue */

        /* Content Grid & Panels */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .full-width-panel { 
            grid-column: 1 / -1; 
        }

        .panel {
            background: var(--color-white);
            border-radius: var(--border-radius-large);
            padding: 30px;
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s;
        }
        
        .panel:hover {
            box-shadow: var(--shadow-hover);
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
        
        .panel h2 i {
            color: var(--color-primary-dark);
        }
        
        .chart-container {
            height: 350px; /* Slightly taller for visual impact */
            margin-top: 10px;
        }
        
        /* Lists */
        .appointment-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .appointment-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .appointment-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
        
        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        
        .appointment-item:hover {
            background: #f9fafb;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
        }
        
        .appointment-info h4 {
            font-size: 15px;
            color: var(--color-text-dark);
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .appointment-info p {
            font-size: 12px;
            color: var(--color-text-medium);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
            text-transform: capitalize;
        }
        
        /* Status & Payment Badges (Modernized) */
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        
        .payment-unpaid { background: #fee2e2; color: #991b1b; }
        .payment-paid { background: #d1fae5; color: #065f46; }
        
        /* Top Services List */
        .service-list {
            padding-top: 5px;
        }
        
        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #f3f4f6;
        }
        
        .service-item:last-child {
            border-bottom: none;
        }
        
        .service-name {
            font-weight: 500;
            color: var(--color-text-dark);
            font-size: 14px;
        }
        
        .service-count {
            background-color: var(--color-primary);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-card {
            padding: 25px;
            border-radius: var(--border-radius-large);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .action-green { 
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
        }
        
        .action-card i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        
        .action-card div:first-of-type {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 3px;
        }
        
        .action-card div:last-of-type {
            font-size: 11px;
            opacity: 0.8;
        }
        
        .empty-message {
            text-align: center;
            color: var(--color-text-medium);
            padding: 40px;
            font-size: 14px;
        }
        
        /* Responsive Overrides (Maintainability) */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }

            .full-width-panel {
                grid-column: 1 / -1;
            }
        }
        
        @media (max-width: 768px) {
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

            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-tooth"></i> Dr. Dental Care Center</h1>
                <p>Welcome back, <?php echo $adminName; ?>! Ready to review your practice performance.</p>
            </div>
            <div class="header-actions">
                <a href="appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> View Appointments
                </a>
                <a href="../logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="label">Total Patients</div>
                <div class="value"><?php echo e($totalPatients); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon icon-green">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="label">Total Appointments</div>
                <div class="value"><?php echo e($totalAppointments); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon icon-purple">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="label">Total Revenue (Paid)</div>
                <div class="value">₱<?php echo number_format($totalRevenue, 2); ?></div>
            </div>
        </div>
        
        <div class="content-grid">
             <div class="panel full-width-panel">
                <h2>
                    <i class="fas fa-chart-bar"></i>
                    Monthly Revenue Trend (Last 6 Months)
                </h2>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div>
                <div class="panel">
                    <h2>
                        <i class="fas fa-chart-line"></i>
                        Appointment Trends
                    </h2>
                    <div class="chart-container">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                </div>
                
                <div class="panel" style="margin-top: 25px;">
                    <h2>
                        <i class="fas fa-calendar-day"></i>
                        Recent Appointments (Today & Tomorrow)
                    </h2>
                    <div class="appointment-list">
                        <?php if (!empty($recentAppointments)): ?>
                            <?php foreach ($recentAppointments as $appt): ?>
                                <div class="appointment-item">
                                    <div class="appointment-info">
                                        <h4><?php echo e($appt['pname']); ?> - #<?php echo e($appt['apponum']); ?></h4>
                                        <p><?php echo e($appt['service_type']); ?> • <?php echo date('M j, Y g:i A', strtotime($appt['appodate'])); ?></p>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo $appt['status']; ?>">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </span>
                                        <span class="status-badge payment-<?php echo $appt['payment_status']; ?>">
                                            <?php echo ucfirst($appt['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-message">No recent appointments</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="panel">
                    <h2>
                        <i class="fas fa-tooth"></i>
                        Top Services
                    </h2>
                    <div class="service-list">
                        <?php if (!empty($topServices)): ?>
                            <?php foreach ($topServices as $service): ?>
                                <div class="service-item">
                                    <span class="service-name"><?php echo e($service['service_type']); ?></span>
                                    <span class="service-count"><?php echo e($service['cnt']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-message">No service data</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="panel" style="margin-top: 25px;">
                    <h2>
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h2>
                    <div class="quick-actions">
                        <a href="patient.php" class="action-card action-green">
                            <i class="fas fa-user-plus"></i>
                            <div>Manage Patients</div>
                            <div><?php echo $totalPatients; ?> total patients</div>
                        </a>
                    </div>
                </div>
                
                <div class="panel" style="margin-top: 25px;">
                    <h2>
                        <i class="fas fa-calendar-alt"></i>
                        Upcoming This Week
                    </h2>
                    <div class="appointment-list" style="max-height: 280px;">
                        <?php if (!empty($upcomingAppointments)): ?>
                            <?php foreach ($upcomingAppointments as $appt): ?>
                                <div class="appointment-item">
                                    <div class="appointment-info">
                                        <h4><?php echo e($appt['pname']); ?></h4>
                                        <p><?php echo e($appt['service_type']); ?></p>
                                        <p style="font-size: 12px; color: #3b82f6; margin-top: 3px;">
                                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($appt['appodate'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="status-badge payment-<?php echo $appt['payment_status']; ?>">
                                            <?php echo ucfirst($appt['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-message">No upcoming appointments</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const chartMonths = <?php echo json_encode($chartMonths ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>;
        const appointmentChartData = <?php echo json_encode($appointmentChartData ?: [0, 0, 0, 0, 0, 0]); ?>;
        const revenueChartData = <?php echo json_encode($revenueChartData ?: [0, 0, 0, 0, 0, 0]); ?>;

        // Appointment Chart (Line)
        const apptCtx = document.getElementById('appointmentsChart');
        if (apptCtx) {
            new Chart(apptCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartMonths,
                    datasets: [{
                        label: 'Total Appointments',
                        data: appointmentChartData,
                        borderColor: '#3b82f6', /* Modern Blue */
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#3b82f6',
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
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(31, 41, 55, 0.9)', /* Darker tooltip */
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#e5e7eb',
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6b7280'
                            },
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Revenue Chart (Bar)
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartMonths,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: revenueChartData,
                        backgroundColor: '#10b981', /* Primary Green */
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 5,
                        hoverBackgroundColor: '#059669'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#1f2937'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(31, 41, 55, 0.9)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#e5e7eb',
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        // Format the revenue amount with Peso sign and commas
                                        label += '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#6b7280',
                                callback: function(value) {
                                    // Format the y-axis ticks with Peso sign and commas
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>