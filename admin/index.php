    <?php
    session_start();
    require_once '../connection.php';
    if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
        header("Location: ../login.php");
        exit();
    }
    $adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
    $totalPatients = $totalAppointments = $todayAppointments = 0;
    $totalRevenue = 0;
    $recentAppointments = $upcomingAppointments = $topServices = [];
    $chartMonths = $appointmentChartData = $revenueChartData = [];
    try {
        $r = $database->query("SELECT COUNT(*) AS c FROM patient");
        if ($r) $totalPatients = (int)$r->fetch_assoc()['c'];
        $r = $database->query("SELECT COUNT(*) AS c FROM appointment");
        if ($r) $totalAppointments = (int)$r->fetch_assoc()['c'];
        $r = $database->query("SELECT COUNT(*) AS c FROM appointment WHERE DATE(appodate) = CURDATE()");
        if ($r) $todayAppointments = (int)$r->fetch_assoc()['c'];
        $r = $database->query("SELECT SUM(amount) AS total FROM appointment WHERE payment_status = 'paid'");
        if ($r) {
            $result = $r->fetch_assoc();
            $totalRevenue = $result['total'] ?? 0;
        }
        $sql = "SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, a.status, a.payment_status
                FROM appointment a LEFT JOIN patient p ON a.pid = p.pid
                WHERE DATE(a.appodate) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                ORDER BY a.appodate ASC LIMIT 8";
        $res = $database->query($sql);
        if ($res) while ($row = $res->fetch_assoc()) $recentAppointments[] = $row;
        $sql = "SELECT a.appoid, a.apponum, p.pname, a.service_type, a.appodate, a.status, a.payment_status
                FROM appointment a LEFT JOIN patient p ON a.pid = p.pid
                WHERE a.appodate BETWEEN DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY a.appodate ASC LIMIT 6";
        $res = $database->query($sql);
        if ($res) while ($row = $res->fetch_assoc()) $upcomingAppointments[] = $row;
        $r = $database->query("SELECT service_type, COUNT(*) AS cnt FROM appointment WHERE service_type IS NOT NULL GROUP BY service_type ORDER BY cnt DESC LIMIT 5");
        if ($r) while ($row = $r->fetch_assoc()) $topServices[] = $row;
        $r = $database->query("SELECT DATE_FORMAT(appodate, '%b %Y') AS mon, COUNT(*) AS total_appointments, SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS total_revenue FROM appointment WHERE appodate >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY DATE_FORMAT(appodate, '%Y-%m') ORDER BY MIN(appodate) ASC");
        if ($r) while ($row = $r->fetch_assoc()) {
            $chartMonths[] = $row['mon'];
            $appointmentChartData[] = (int)$row['total_appointments'];
            $revenueChartData[] = (float)$row['total_revenue'];
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/aindex.css">
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
    <a href="index.php" class="nav-item active"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="appointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
    <a href="patient.php" class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
    </div>
    <div class="nav-section">
    <div class="nav-section-title">Reports</div>
    <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
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
    <h1>Dashboard Overview</h1>
    <p>Welcome back, <?php echo $adminName; ?>!</p>
    </div>
    <div class="top-actions">
    </div>
    </div>
    <div class="content-container">
    <div class="stats-grid">
    <div class="stat-card blue">
    <div class="stat-header">
    <div>
    <div class="stat-label">Total Patients</div>
    <div class="stat-value"><?php echo e($totalPatients); ?></div>
    </div>
    <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
    </div>
    </div>
    <div class="stat-card green">
    <div class="stat-header">
    <div>
    <div class="stat-label">Total Appointments</div>
    <div class="stat-value"><?php echo e($totalAppointments); ?></div>
    </div>
    <div class="stat-icon icon-green"><i class="fas fa-calendar-alt"></i></div>
    </div>
    </div>
    <div class="stat-card orange">
    <div class="stat-header">
    <div>
    <div class="stat-label">Today's Appointments</div>
    <div class="stat-value"><?php echo e($todayAppointments); ?></div>
    </div>
    <div class="stat-icon icon-orange"><i class="fas fa-calendar-day"></i></div>
    </div>
    </div>
    <div class="stat-card purple">
    <div class="stat-header">
    <div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">₱<?php echo number_format($totalRevenue,0); ?></div>
    </div>
    <div class="stat-icon icon-purple"><i class="fas fa-dollar-sign"></i></div>
    </div>
    </div>
    </div>
    <div class="panels-grid">
    <div class="panel">
    <div class="panel-header">
    <div class="panel-title"><i class="fas fa-chart-line"></i>Appointment Trends</div>
    </div>
    <div class="chart-container"><canvas id="appointmentsChart"></canvas></div>
    </div>
    <div class="panel">
    <div class="panel-header">
    <div class="panel-title"><i class="fas fa-chart-bar"></i>Revenue Overview</div>
    </div>
    <div class="chart-container"><canvas id="revenueChart"></canvas></div>
    </div>
    </div>
    <div class="panels-grid">
    <div class="panel">
    <div class="panel-header">
    <div class="panel-title"><i class="fas fa-calendar-day"></i>Recent Appointments</div>
    </div>
    <div class="appointment-list">
    <?php if(!empty($recentAppointments)):foreach($recentAppointments as $appt):?>
    <div class="appointment-item">
    <div class="appointment-info">
    <h4><?php echo e($appt['pname']); ?> - #<?php echo e($appt['apponum']); ?></h4>
    <p><?php echo e($appt['service_type']); ?> • <?php echo date('M j, Y g:i A',strtotime($appt['appodate'])); ?></p>
    </div>
    <div>
    <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo ucfirst($appt['status']); ?></span>
    <span class="status-badge payment-<?php echo $appt['payment_status']; ?>"><?php echo ucfirst($appt['payment_status']); ?></span>
    </div>
    </div>
    <?php endforeach;else:?>
    <p class="empty-message">No recent appointments</p>
    <?php endif; ?>
    </div>
    </div>
    <div class="panel">
    <div class="panel-header">
    <div class="panel-title"><i class="fas fa-tooth"></i>Top Services</div>
    </div>
    <div class="service-list">
    <?php if(!empty($topServices)):foreach($topServices as $service):?>
    <div class="service-item">
    <span class="service-name"><?php echo e($service['service_type']); ?></span>
    <span class="service-count"><?php echo e($service['cnt']); ?></span>
    </div>
    <?php endforeach;else:?>
    <p class="empty-message">No service data</p>
    <?php endif; ?>
    </div>
    </div>
    </div>
    <div class="panel">
    <div class="panel-header">
    <div class="panel-title"><i class="fas fa-calendar-alt"></i>Upcoming This Week</div>
    </div>
    <div class="appointment-list" style="max-height:300px">
    <?php if(!empty($upcomingAppointments)):foreach($upcomingAppointments as $appt):?>
    <div class="appointment-item">
    <div class="appointment-info">
    <h4><?php echo e($appt['pname']); ?></h4>
    <p><?php echo e($appt['service_type']); ?> • <?php echo date('M j, Y',strtotime($appt['appodate'])); ?></p>
    </div>
    <div>
    <span class="status-badge payment-<?php echo $appt['payment_status']; ?>"><?php echo ucfirst($appt['payment_status']); ?></span>
    </div>
    </div>
    <?php endforeach;else:?>
    <p class="empty-message">No upcoming appointments</p>
    <?php endif; ?>
    </div>
    </div>
    </div>
    </main>
    <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('mini')}
    function toggleMobile(){document.getElementById('sidebar').classList.toggle('mobile-open')}
    const chartMonths=<?php echo json_encode($chartMonths?:['Jan','Feb','Mar','Apr','May','Jun']); ?>;
    const appointmentChartData=<?php echo json_encode($appointmentChartData?:[0,0,0,0,0,0]); ?>;
    const revenueChartData=<?php echo json_encode($revenueChartData?:[0,0,0,0,0,0]); ?>;
    const apptCtx=document.getElementById('appointmentsChart');
    if(apptCtx){
    new Chart(apptCtx.getContext('2d'),{
    type:'line',
    data:{
    labels:chartMonths,
    datasets:[{
    label:'Appointments',
    data:appointmentChartData,
    borderColor:'#3b82f6',
    backgroundColor:'rgba(59,130,246,0.1)',
    tension:0.4,
    fill:true,
    pointBackgroundColor:'#3b82f6',
    pointBorderColor:'#fff',
    pointBorderWidth:2,
    pointRadius:4,
    pointHoverRadius:6
    }]
    },
    options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
    legend:{display:false},
    tooltip:{
    backgroundColor:'rgba(31,41,55,0.95)',
    padding:12,
    cornerRadius:8
    }
    },
    scales:{
    y:{
    beginAtZero:true,
    ticks:{precision:0,color:'#6b7280'},
    grid:{color:'#f3f4f6'}
    },
    x:{
    ticks:{color:'#6b7280'},
    grid:{display:false}
    }
    }
    }
    });
    }
    const revenueCtx=document.getElementById('revenueChart');
    if(revenueCtx){
    new Chart(revenueCtx.getContext('2d'),{
    type:'bar',
    data:{
    labels:chartMonths,
    datasets:[{
    label:'Revenue (₱)',
    data:revenueChartData,
    backgroundColor:'#10b981',
    borderRadius:6,
    hoverBackgroundColor:'#059669'
    }]
    },
    options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
    legend:{display:false},
    tooltip:{
    backgroundColor:'rgba(31,41,55,0.95)',
    padding:12,
    cornerRadius:8,
    callbacks:{
    label:function(context){
    return '₱'+context.parsed.y.toLocaleString('en-US',{
    minimumFractionDigits:2,
    maximumFractionDigits:2
    });
    }
    }
    }
    },
    scales:{
    y:{
    beginAtZero:true,
    ticks:{
    color:'#6b7280',
    callback:value=>'₱'+value.toLocaleString()
    },
    grid:{color:'#f3f4f6'}
    },
    x:{
    ticks:{color:'#6b7280'},
    grid:{display:false}
    }
    }
    }
    });
    }
    </script>
    </body>
    </html>