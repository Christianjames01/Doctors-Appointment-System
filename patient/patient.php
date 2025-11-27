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

$sqlmain= "select appointment.appoid,schedule.scheduleid,schedule.title,doctor.docname,patient.pname,schedule.scheduledate,schedule.scheduletime,appointment.apponum,appointment.appodate from schedule inner join appointment on schedule.scheduleid=appointment.scheduleid inner join patient on patient.pid=appointment.pid inner join doctor on schedule.docid=doctor.docid  where  patient.pid=$userid ";

if($_POST){
    if(!empty($_POST["sheduledate"])){
        $sheduledate=$_POST["sheduledate"];
        $sqlmain.=" and schedule.scheduledate='$sheduledate' ";
    }
}

$sqlmain.="order by appointment.appodate desc";
$result= $database->query($sqlmain);

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Dr. Dental Care Center</title>
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
            background: var(--bg-light);
            color: var(--text-dark);
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
            background: var(--bg-light);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white;
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

        /* Main Content */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-left h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-left p {
            color: var(--text-light);
            font-size: 15px;
        }

        .page-header-right {
            text-align: right;
        }

        .date-label {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .date-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            padding: 25px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Appointments Grid */
        .appointments-container {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .appointments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .appointment-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            padding: 25px;
            border-radius: 16px;
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s;
            position: relative;
        }

        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .appointment-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .reference-number {
            font-size: 12px;
            color: var(--text-light);
            background: white;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .appointment-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .appointment-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--text-dark);
        }

        .appointment-detail i {
            color: var(--primary-color);
            width: 18px;
        }

        .appointment-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }

        .cancel-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 80px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 25px;
        }

        .book-btn {
            padding: 14px 35px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Modal */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .popup {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .popup h2 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 28px;
        }

        .popup .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s;
        }

        .popup .close:hover {
            color: var(--text-dark);
            transform: rotate(90deg);
        }

        .popup .content {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .popup-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-primary {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .appointments-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .filter-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
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
                <a href="index.php" class="nav-link">
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
                <a href="appointment.php" class="nav-link active">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Bookings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
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
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </h1>
                <p>View and manage your dental appointments</p>
            </div>
            <div class="page-header-right">
                <div class="date-label">Today's Date</div>
                <div class="date-value">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <form method="POST" action="" class="filter-form">
                <div class="form-group">
                    <label for="sheduledate">
                        <i class="fas fa-filter"></i> Filter by Date
                    </label>
                    <input type="date" name="sheduledate" id="sheduledate">
                </div>
                <button type="submit" name="filter" class="filter-btn">
                    <i class="fas fa-search"></i>
                    Filter
                </button>
            </form>
        </div>

        <div class="appointments-container">
            <div class="section-title">
                <i class="fas fa-list"></i>
                Your Appointments (<?php echo $result->num_rows; ?>)
            </div>

            <?php if($result->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointments Found</h3>
                    <p>You don't have any appointments yet. Book your first appointment now!</p>
                    <a href="booking.php" class="book-btn">
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </a>
                </div>
            <?php else: ?>
                <div class="appointments-grid">
                    <?php while($row = $result->fetch_assoc()): 
                        $scheduleid = $row["scheduleid"];
                        $title = $row["title"];
                        $docname = $row["docname"];
                        $scheduledate = $row["scheduledate"];
                        $scheduletime = $row["scheduletime"];
                        $apponum = $row["apponum"];
                        $appodate = $row["appodate"];
                        $appoid = $row["appoid"];
                    ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <div class="appointment-number">#<?php echo $apponum; ?></div>
                                <div class="reference-number">OC-000-<?php echo $appoid; ?></div>
                            </div>

                            <div class="appointment-title">
                                <?php echo htmlspecialchars(substr($title, 0, 40)); ?>
                            </div>

                            <div class="appointment-detail">
                                <i class="fas fa-user-md"></i>
                                <span><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($docname); ?></span>
                            </div>

                            <div class="appointment-detail">
                                <i class="fas fa-calendar-day"></i>
                                <span><strong>Date:</strong> <?php echo date('M j, Y', strtotime($scheduledate)); ?></span>
                            </div>

                            <div class="appointment-detail">
                                <i class="fas fa-clock"></i>
                                <span><strong>Time:</strong> <?php echo date('g:i A', strtotime($scheduletime)); ?></span>
                            </div>

                            <div class="appointment-detail">
                                <i class="fas fa-bookmark"></i>
                                <span><strong>Booked:</strong> <?php echo date('M j, Y', strtotime($appodate)); ?></span>
                            </div>

                            <div class="appointment-actions">
                                <a href="?action=drop&id=<?php echo $appoid; ?>&title=<?php echo urlencode($title); ?>&doc=<?php echo urlencode($docname); ?>">
                                    <button class="cancel-btn">
                                        <i class="fas fa-times-circle"></i>
                                        Cancel Booking
                                    </button>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php
    if($_GET){
        $id = $_GET["id"];
        $action = $_GET["action"];
        
        if($action == 'booking-added'){
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <h2><i class="fas fa-check-circle" style="color: #28a745;"></i> Booking Successful</h2>
                    <a class="close" href="appointment.php">&times;</a>
                    <div class="content">
                        Your appointment has been booked successfully!<br>
                        Your appointment number is: <strong>#'.$id.'</strong>
                    </div>
                    <div class="popup-actions">
                        <a href="appointment.php" class="btn-primary">OK</a>
                    </div>
                </div>
            </div>';
            
        } elseif($action == 'drop'){
            $title = $_GET["title"];
            $docname = $_GET["doc"];
            
            echo '
            <div id="popup1" class="overlay">
                <div class="popup">
                    <h2><i class="fas fa-exclamation-triangle" style="color: #ff6b6b;"></i> Confirm Cancellation</h2>
                    <a class="close" href="appointment.php">&times;</a>
                    <div class="content">
                        Are you sure you want to cancel this appointment?<br><br>
                        <strong>Session:</strong> '.htmlspecialchars(substr($title, 0, 40)).'<br>
                        <strong>Doctor:</strong> Dr. '.htmlspecialchars(substr($docname, 0, 40)).'<br>
                    </div>
                    <div class="popup-actions">
                        <a href="delete-appointment.php?id='.$id.'" class="btn-primary">Yes, Cancel</a>
                        <a href="appointment.php" class="btn-primary" style="background: #6c757d;">No, Keep It</a>
                    </div>
                </div>
            </div>';
        }
    }
    ?>
</body>
</html>