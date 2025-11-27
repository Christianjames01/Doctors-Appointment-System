<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Dr. Dental Clinic</title>
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
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Sidebar */
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
            transition: transform 0.3s ease;
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }

        .user-avatar {
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
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
            transition: all 0.3s ease;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.4);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 30px;
        }

        /* Top Bar */
        .top-bar {
            background: var(--white);
            padding: 30px 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
        }

        .welcome-section h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-section p {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.6;
        }

        .date-section {
            text-align: right;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 15px 20px;
            border-radius: 12px;
        }

        .date-label {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .date-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }

        .date-value i {
            color: var(--primary-color);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: var(--primary-gradient);
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-icon.doctors { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.patients { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.bookings { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.sessions { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            border: 2px solid #e2e8f0;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .action-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .action-icon.find { background: var(--primary-gradient); }
        .action-icon.schedule { background: var(--info-gradient); }
        .action-icon.bookings { background: var(--warning-gradient); }
        .action-icon.settings { background: var(--success-gradient); }

        .action-text h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .action-text p {
            font-size: 13px;
            color: var(--text-light);
        }

        /* Appointments Section */
        .appointments-section {
            background: var(--white);
            padding: 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: var(--primary-color);
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table thead {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        }

        .appointments-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .appointments-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-dark);
            font-size: 14px;
        }

        .appointments-table tbody tr {
            transition: all 0.3s ease;
        }

        .appointments-table tbody tr:hover {
            background: linear-gradient(135deg, #f7fafc50 0%, #edf2f750 100%);
            transform: scale(1.01);
        }

        .appoint-num {
            font-size: 18px;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #43e97b15 0%, #38f9d715 100%);
            color: #27ae60;
            border: 1px solid #27ae60;
        }

        .status-pending {
            background: linear-gradient(135deg, #f093fb15 0%, #f5576c15 100%);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 100px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 15px;
            line-height: 1.6;
        }

        .action-btn {
            padding: 15px 40px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .date-section {
                width: 100%;
            }

            .welcome-section h1 {
                font-size: 24px;
            }

            .appointments-table {
                font-size: 13px;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 12px 8px;
            }

            .stat-info h3 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='p'){
            header("location: ../login.php");
        }else{
            $useremail=$_SESSION["user"];
        }
    }else{
        header("location: ../login.php");
    }

    include("../connection.php");
    $userrow = $database->query("select * from patient where pemail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];

    date_default_timezone_set('Asia/Manila');
    $today = date('Y-m-d');

    $patientrow = $database->query("select * from patient;");
    $doctorrow = $database->query("select * from doctor;");
    $appointmentrow = $database->query("select * from appointment where appodate>='$today';");
    $schedulerow = $database->query("select * from schedule where scheduledate='$today';");
    ?>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-tooth"></i>
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>

            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($username, 0, 2)); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo substr($username, 0, 15); ?></h3>
                    <p><?php echo substr($useremail, 0, 20); ?></p>
                </div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="doctors.php" class="nav-link">
                    <i class="fas fa-user-md"></i>
                    <span>All Doctors</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Scheduled Sessions</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Bookings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h1>
                <p>Here's an overview of your dental appointments and clinic statistics.</p>
            </div>
            <div class="date-section">
                <div class="date-label">Today's Date</div>
                <div class="date-value">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon doctors">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $doctorrow->num_rows; ?></h3>
                    <p>All Doctors</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon patients">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $patientrow->num_rows; ?></h3>
                    <p>All Patients</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bookings">
                    <i class="fas fa-book-medical"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $appointmentrow->num_rows; ?></h3>
                    <p>New Bookings</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon sessions">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $schedulerow->num_rows; ?></h3>
                    <p>Today's Sessions</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="doctors.php" class="action-card">
                <div class="action-icon find">
                    <i class="fas fa-search"></i>
                </div>
                <div class="action-text">
                    <h3>Find a Doctor</h3>
                    <p>Browse our specialists</p>
                </div>
            </a>

            <a href="schedule.php" class="action-card">
                <div class="action-icon schedule">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="action-text">
                    <h3>View Sessions</h3>
                    <p>See available appointments</p>
                </div>
            </a>

            <a href="booking.php" class="action-card">
                <div class="action-icon bookings">
                    <i class="fas fa-list-check"></i>
                </div>
                <div class="action-text">
                    <h3>My Bookings</h3>
                    <p>Manage your appointments</p>
                </div>
            </a>

            <a href="settings.php" class="action-card">
                <div class="action-icon settings">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="action-text">
                    <h3>Settings</h3>
                    <p>Update your profile</p>
                </div>
            </a>
        </div>

        <!-- Upcoming Appointments -->
        <div class="appointments-section">
            <div class="section-header">
                <h2><i class="fas fa-calendar-check"></i> Your Upcoming Appointments</h2>
            </div>

            <?php
            $sqlmain = "select * from schedule 
                       inner join appointment on schedule.scheduleid=appointment.scheduleid 
                       inner join patient on patient.pid=appointment.pid 
                       inner join doctor on schedule.docid=doctor.docid  
                       where patient.pid=$userid and schedule.scheduledate>='$today' 
                       order by schedule.scheduledate asc";
            
            $result = $database->query($sqlmain);

            if($result->num_rows == 0){
                echo '
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Upcoming Appointments</h3>
                    <p>You don\'t have any scheduled appointments at the moment.<br>Book an appointment with our qualified doctors today!</p>
                    <a href="schedule.php" class="action-btn">
                        <i class="fas fa-plus"></i> Schedule an Appointment
                    </a>
                </div>';
            } else {
                echo '
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Appt. No.</th>
                            <th><i class="fas fa-file-medical"></i> Session Title</th>
                            <th><i class="fas fa-user-md"></i> Doctor</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-clock"></i> Time</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                while($row = $result->fetch_assoc()){
                    $apponum = $row["apponum"];
                    $title = $row["title"];
                    $docname = $row["docname"];
                    $scheduledate = $row["scheduledate"];
                    $scheduletime = $row["scheduletime"];
                    
                    echo '<tr>
                        <td><span class="appoint-num">#'.htmlspecialchars($apponum).'</span></td>
                        <td>'.htmlspecialchars(substr($title, 0, 40)).'</td>
                        <td><i class="fas fa-user-circle" style="color: var(--primary-color); margin-right: 8px;"></i>'.htmlspecialchars(substr($docname, 0, 25)).'</td>
                        <td><i class="fas fa-calendar-day" style="color: var(--text-light); margin-right: 8px;"></i>'.date('M j, Y', strtotime($scheduledate)).'</td>
                        <td><i class="fas fa-clock" style="color: var(--text-light); margin-right: 8px;"></i>'.date('g:i A', strtotime($scheduletime)).'</td>
                    </tr>';
                }
                
                echo '
                    </tbody>
                </table>';
            }
            ?>
        </div>
    </main>
</body>
</html>