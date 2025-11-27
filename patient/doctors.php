<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Doctors - Dr. Dental Clinic</title>
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
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
            padding: 25px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .welcome-section p {
            color: var(--text-light);
            font-size: 15px;
        }

        .date-section {
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

        /* Search Section */
        .search-section {
            background: var(--white);
            padding: 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .search-section h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .search-section p {
            color: var(--text-light);
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            max-width: 800px;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            padding: 15px 35px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Doctors Grid */
        .doctors-section {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .section-header {
            margin-bottom: 25px;
        }

        .section-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .doctor-card {
            background: var(--white);
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .doctor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .doctor-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .doctor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .doctor-info h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .doctor-info p {
            font-size: 14px;
            color: var(--text-light);
        }

        .specialty-badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .doctor-actions {
            display: flex;
            gap: 10px;
        }

        .action-button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-view {
            background: var(--bg-light);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-view:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-sessions {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-sessions:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 80px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .action-btn {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .action-btn:hover {
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
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .popup {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .popup .close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .popup .close:hover {
            background: var(--primary-color);
            color: white;
            transform: rotate(90deg);
        }

        .popup h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .popup-content {
            margin-bottom: 25px;
        }

        .detail-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-dark);
            width: 150px;
            flex-shrink: 0;
        }

        .detail-value {
            color: var(--text-light);
        }

        .popup-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .doctors-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-form {
                flex-direction: column;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .popup {
                padding: 30px 20px;
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
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="doctors.php" class="nav-link active">
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
                <h1>Our Medical Experts 👨‍⚕️</h1>
                <p>Browse through our qualified doctors and book your appointment</p>
            </div>
            <div class="date-section">
                <div class="date-label">Today's Date</div>
                <div class="date-value">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <h2>Find a Doctor</h2>
            <p>Search by doctor name or email address</p>
            <form action="" method="post" class="search-form">
                <input 
                    type="search" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search doctor name or email..."
                    list="doctors"
                >
                <datalist id="doctors">
                    <?php
                    $list11 = $database->query("select docname, docemail from doctor;");
                    while($row00 = $list11->fetch_assoc()){
                        echo "<option value='".$row00["docname"]."'>";
                        echo "<option value='".$row00["docemail"]."'>";
                    }
                    ?>
                </datalist>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Doctors Section -->
        <div class="doctors-section">
            <div class="section-header">
                <?php
                if($_POST){
                    $keyword=$_POST["search"];
                    $sqlmain= "select * from doctor where docemail='$keyword' or docname='$keyword' or docname like '$keyword%' or docname like '%$keyword' or docname like '%$keyword%'";
                }else{
                    $sqlmain= "select * from doctor order by docid desc";
                }
                
                $result= $database->query($sqlmain);
                ?>
                <h2>All Doctors (<?php echo $result->num_rows; ?>)</h2>
            </div>

            <?php
            if($result->num_rows == 0){
                echo '
                <div class="empty-state">
                    <i class="fas fa-user-md-slash"></i>
                    <h3>No Doctors Found</h3>
                    <p>We couldn\'t find any doctors matching your search criteria.</p>
                    <a href="doctors.php" class="action-btn">
                        <i class="fas fa-redo"></i> Show All Doctors
                    </a>
                </div>';
            } else {
                echo '<div class="doctors-grid">';
                
                while($row = $result->fetch_assoc()){
                    $docid = $row["docid"];
                    $name = $row["docname"];
                    $email = $row["docemail"];
                    $spe = $row["specialties"];
                    
                    $spcil_res = $database->query("select sname from specialties where id='$spe'");
                    $spcil_array = $spcil_res->fetch_assoc();
                    $spcil_name = $spcil_array["sname"];
                    
                    $initials = strtoupper(substr($name, 0, 2));
                    
                    echo '
                    <div class="doctor-card">
                        <div class="doctor-header">
                            <div class="doctor-avatar">'.$initials.'</div>
                            <div class="doctor-info">
                                <h3>'.htmlspecialchars(substr($name, 0, 30)).'</h3>
                                <p><i class="fas fa-envelope"></i> '.htmlspecialchars(substr($email, 0, 25)).'</p>
                            </div>
                        </div>
                        <div class="specialty-badge">
                            <i class="fas fa-stethoscope"></i> '.htmlspecialchars(substr($spcil_name, 0, 20)).'
                        </div>
                        <div class="doctor-actions">
                            <a href="?action=view&id='.$docid.'" class="action-button btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="?action=session&id='.$docid.'&name='.urlencode($name).'" class="action-button btn-sessions">
                                <i class="fas fa-calendar"></i> Sessions
                            </a>
                        </div>
                    </div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
    </main>

    <?php
    if($_GET){
        $id = $_GET["id"];
        $action = $_GET["action"];
        
        if($action == 'view'){
            $sqlmain = "select * from doctor where docid='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            $name = $row["docname"];
            $email = $row["docemail"];
            $spe = $row["specialties"];
            
            $spcil_res = $database->query("select sname from specialties where id='$spe'");
            $spcil_array = $spcil_res->fetch_assoc();
            $spcil_name = $spcil_array["sname"];
            $nic = $row['docnic'];
            $tele = $row['doctel'];
            
            echo '
            <div class="overlay">
                <div class="popup">
                    <a class="close" href="doctors.php">&times;</a>
                    <h2><i class="fas fa-user-md" style="color: var(--primary-color);"></i> Doctor Details</h2>
                    <div class="popup-content">
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-user"></i> Name:</div>
                            <div class="detail-value">'.htmlspecialchars($name).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-envelope"></i> Email:</div>
                            <div class="detail-value">'.htmlspecialchars($email).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-id-card"></i> NIC:</div>
                            <div class="detail-value">'.htmlspecialchars($nic).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-phone"></i> Telephone:</div>
                            <div class="detail-value">'.htmlspecialchars($tele).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label"><i class="fas fa-stethoscope"></i> Specialty:</div>
                            <div class="detail-value">'.htmlspecialchars($spcil_name).'</div>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <a href="doctors.php" class="action-btn">
                            <i class="fas fa-check"></i> OK
                        </a>
                    </div>
                </div>
            </div>';
            
        } elseif($action == 'session'){
            $name = $_GET["name"];
            echo '
            <div class="overlay">
                <div class="popup">
                    <a class="close" href="doctors.php">&times;</a>
                    <h2><i class="fas fa-calendar-alt" style="color: var(--primary-color);"></i> View Doctor Sessions?</h2>
                    <div class="popup-content">
                        <p style="font-size: 16px; color: var(--text-light); line-height: 1.6;">
                            You want to view all sessions by<br>
                            <strong style="color: var(--text-dark);">'.htmlspecialchars(substr($name, 0, 40)).'</strong>
                        </p>
                    </div>
                    <form action="schedule.php" method="post">
                        <input type="hidden" name="search" value="'.htmlspecialchars($name).'">
                        <div class="popup-actions">
                            <a href="doctors.php" class="action-button btn-view" style="text-decoration: none;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="action-btn">
                                <i class="fas fa-check"></i> Yes, Continue
                            </button>
                        </div>
                    </form>
                </div>
            </div>';
        }
    }
    ?>
</body>
</html>