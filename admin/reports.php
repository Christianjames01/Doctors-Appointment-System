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

    $adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    $totalAppointments = 0;
    $completedAppointments = 0;
    $cancelledAppointments = 0;
    $pendingAppointments = 0;
    $avgAppointmentsPerDay = 0;
    $totalPatients = 0;
    $newPatients = 0;
    $statusDistribution = [];
    $dailyAppointments = [];
    $showingAllTime = false;


    $dateRange = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dateRange[$date] = date('M d', strtotime("-$i days"));
    }

    $dailyQuery = "SELECT DATE(appodate) as appt_date, COUNT(*) AS cnt 
                FROM appointment 
                WHERE DATE(appodate) >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) 
                AND DATE(appodate) <= CURDATE() 
                GROUP BY DATE(appodate)
                ORDER BY DATE(appodate) ASC";

    $result = $database->query($dailyQuery);

    // Create a map of dates to counts
    $appointmentCounts = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointmentCounts[$row['appt_date']] = (int)$row['cnt'];
        }
    }

    // Merge the data - ensure all 30 days are present
    foreach ($dateRange as $date => $label) {
        $dailyAppointments[] = [
            'day' => $label,
            'cnt' => isset($appointmentCounts[$date]) ? $appointmentCounts[$date] : 0
        ];
    }

    // Debug logging
    error_log("Daily Appointments Count: " . count($dailyAppointments));
    error_log("Daily Appointments Data: " . json_encode($dailyAppointments));

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
        
    $serviceQuery = "
        SELECT service_type, COUNT(*) AS cnt
        FROM appointment
        WHERE service_type IS NOT NULL 
        AND service_type != ''
        AND TRIM(service_type) != ''
        GROUP BY service_type
        ORDER BY cnt DESC
        LIMIT 10
    ";

    $result = $database->query($serviceQuery);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['service_type']) && trim($row['service_type']) !== '') {
                $serviceDistribution[] = [
                    'service_type' => trim($row['service_type']),
                    'cnt' => (int)$row['cnt']
                ];
            }
        }
    }
        
    error_log("Service Distribution Count: " . count($serviceDistribution));
        error_log("Service Distribution Data: " . json_encode($serviceDistribution));
        
        // Daily appointments for last 30 days (fixed query - removed stray $)
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
    $topPatientsQuery = "
        SELECT p.pid, p.pname, p.pemail, p.profile_picture, 
        COUNT(a.appoid) AS appt_count 
        FROM patient p
        INNER JOIN appointment a ON p.pid = a.pid 
        GROUP BY p.pid, p.pname, p.pemail, p.profile_picture
        HAVING appt_count > 0 
        ORDER BY appt_count DESC 
        LIMIT 5
    ";

    $result = $database->query($topPatientsQuery);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $topPatients[] = [
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
        
        error_log("Top Patients Filtered Count: " . count($topPatientsFiltered));
        
    } catch (Exception $e) {
        error_log("Analytics Error: " . $e->getMessage());
    }

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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/reports.css">
    </head>
    <body>
    <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
    <div class="logo-icon"><i class="fas fa-tooth"></i></div>
    <div class="logo-text">
    <h2>Dr. Dental Clinic Care</h2>
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
    </div>

    <div class="panels-grid">
    <!-- Daily Chart Panel -->
    <!-- Replace the Daily Appointments Chart Panel in your reports.php -->

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title"><i class="fas fa-chart-line"></i>Daily Appointments (Last 30 Days)</div>
        </div>
        
        <?php if (!empty($dailyAppointments) && count($dailyAppointments) > 0): ?>
            <div class="chart-container" style="height: 300px; position: relative;">
                <canvas id="dailyChart" style="max-height: 300px;"></canvas>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                <i class="fas fa-chart-line" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-size: 15px; margin: 0;">No appointment data for last 30 days</p>
                <p style="color: #94a3b8; font-size: 13px; margin-top: 8px;">Data will appear here once appointments are scheduled.</p>
            </div>
        <?php endif; ?>
    </div>
    </div>

        <!-- Status Distribution Chart -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="fas fa-chart-pie"></i>Status Distribution</div>
            </div>
            
            <?php if (!empty($statusDistribution) && count($statusDistribution) > 0): ?>
                <div class="chart-container" style="height: 300px; position: relative;">
                    <canvas id="statusChart" style="max-height: 300px;"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                    <i class="fas fa-chart-pie" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b; font-size: 15px; margin: 0;">No status data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </main>

    <script>
    // Complete fixed JavaScript for charts - Replace entire <script> section in reports.php

    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('mini')}
    function toggleMobile(){document.getElementById('sidebar').classList.toggle('mobile-open')}

    // Initialize charts when everything is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }

    function initCharts() {
        console.log('=== Initializing Charts ===');
        
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        console.log('Chart.js loaded successfully');
        
        // Daily Appointments Chart
        const dailyData = <?php echo json_encode($dailyAppointments); ?>;
        console.log('Daily Data:', dailyData);
        console.log('Daily Data length:', dailyData ? dailyData.length : 0);

        const dailyCanvas = document.getElementById('dailyChart');
        console.log('Daily Canvas element:', dailyCanvas);
        
        if (dailyCanvas) {
            if (dailyData && dailyData.length > 0) {
                console.log('Creating daily appointments chart...');
                try {
                    const ctx = dailyCanvas.getContext('2d');
                    
                    const dailyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dailyData.map(d => d.day),
                            datasets: [{
                                label: 'Appointments',
                                data: dailyData.map(d => parseInt(d.cnt) || 0),
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
                                    ticks: { 
                                        precision: 0, 
                                        color: '#6b7280',
                                        callback: function(value) {
                                            return Number.isInteger(value) ? value : '';
                                        }
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
                    console.log('✓ Daily chart created successfully');
                } catch (error) {
                    console.error('Error creating daily chart:', error);
                }
            } else {
                console.warn('No daily data available - showing empty state');
                dailyCanvas.parentElement.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><p>No appointment data for last 30 days</p></div>';
            }
        } else {
            console.error('Daily chart canvas element not found!');
        }

        // Status Distribution Chart
        <?php if (!empty($statusDistribution)): ?>
        const statusData = <?php echo json_encode($statusDistribution); ?>;
        console.log('Status Data:', statusData);
        console.log('Status Data length:', statusData ? statusData.length : 0);

        const statusCanvas = document.getElementById('statusChart');
        console.log('Status Canvas element:', statusCanvas);
        
        if (statusCanvas) {
            if (statusData && statusData.length > 0) {
                console.log('Creating status distribution chart...');
                try {
                    const ctx2 = statusCanvas.getContext('2d');
                    
                    const statusChart = new Chart(ctx2, {
                        type: 'doughnut',
                        data: {
                            labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
                            datasets: [{
                                data: statusData.map(s => parseInt(s.c) || 0),
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
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                    console.log('✓ Status chart created successfully');
                } catch (error) {
                    console.error('Error creating status chart:', error);
                }
            } else {
                console.warn('No status data available');
            }
        } else {
            console.error('Status chart canvas element not found!');
        }
        <?php else: ?>
        console.log('Status distribution data is empty - showing empty state');
        <?php endif; ?>
        
        console.log('=== Chart Initialization Complete ===');
    }
    </script>
    </body>
    </html>