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
    .btn-secondary{background:transparent;color:var(--text-med);border:1px solid #e5e7eb}
    .btn-secondary:hover{background:#f3f4f6}
    .content-container{padding:30px}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
    .stat-card{background:var(--white);padding:24px;border-radius:12px;box-shadow:var(--shadow);transition:all .3s;border-left:4px solid transparent}
    .stat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
    .stat-card.blue{border-left-color:#3b82f6}
    .stat-card.green{border-left-color:var(--primary)}
    .stat-card.purple{border-left-color:#8b5cf6}
    .stat-card.orange{border-left-color:#f59e0b}
    .stat-header{display:flex;justify-content:space-between;align-items:start;margin-bottom:12px}
    .stat-icon{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
    .icon-blue{background:#dbeafe;color:#3b82f6}
    .icon-green{background:#d1fae5;color:var(--primary)}
    .icon-purple{background:#ede9fe;color:#8b5cf6}
    .icon-orange{background:#fed7aa;color:#f59e0b}
    .stat-label{color:var(--text-med);font-size:13px;font-weight:500;margin-bottom:8px}
    .stat-value{font-size:28px;font-weight:700;color:var(--text-dark)}
    .panels-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:25px;margin-bottom:30px}
    .panel{background:var(--white);border-radius:12px;padding:25px;box-shadow:var(--shadow)}
    .panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #f3f4f6}
    .panel-title{font-size:18px;font-weight:600;color:var(--text-dark);display:flex;align-items:center;gap:10px}
    .panel-title i{color:var(--primary)}
    .chart-container{height:300px;margin-top:10px}
    .appointment-list{max-height:400px;overflow-y:auto}
    .appointment-list::-webkit-scrollbar{width:4px}
    .appointment-list::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:4px}
    .appointment-item{display:flex;justify-content:space-between;align-items:center;padding:14px;border-bottom:1px solid #f3f4f6;transition:all .2s;border-radius:8px;margin-bottom:4px}
    .appointment-item:hover{background:#f9fafb}
    .appointment-info h4{font-size:14px;color:var(--text-dark);margin-bottom:4px;font-weight:600}
    .appointment-info p{font-size:12px;color:var(--text-med)}
    .status-badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;margin-left:5px}
    .status-pending{background:#fef3c7;color:#92400e}
    .status-approved{background:#d1fae5;color:#065f46}
    .status-completed{background:#dbeafe;color:#1e40af}
    .payment-unpaid{background:#fee2e2;color:#991b1b}
    .payment-paid{background:#d1fae5;color:#065f46}
    .service-list{padding-top:5px}
    .service-item{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px dashed #f3f4f6}
    .service-item:last-child{border-bottom:none}
    .service-name{font-weight:500;color:var(--text-dark);font-size:14px}
    .service-count{background:var(--primary);color:white;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px}
    .empty-message{text-align:center;color:var(--text-med);padding:40px;font-size:14px}
    @media(max-width:1024px){.panels-grid{grid-template-columns:1fr}}
    @media(max-width:768px){.sidebar{transform:translateX(-100%)}
    .sidebar.mobile-open{transform:translateX(0)}
    .main-content{margin-left:0}
    .top-bar{padding:15px 20px}
    .content-container{padding:20px 15px}
    .stats-grid{grid-template-columns:1fr}
    .mobile-toggle{display:block!important;position:fixed;top:20px;left:20px;z-index:1001;background:var(--primary);color:white;border:none;padding:10px 15px;border-radius:8px;cursor:pointer}}
    .mobile-toggle{display:none}
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