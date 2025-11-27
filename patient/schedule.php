<?php
// Start session at the very beginning before any output
session_start();

// Check authentication
if(!isset($_SESSION["user"]) || $_SESSION["user"]=="" || $_SESSION['usertype']!='p'){
    header("location: ../login.php");
    exit();
}

$useremail = $_SESSION["user"];

// Import database
include("../connection.php");

// Get user information
$userrow = $database->query("select * from patient where pemail='$useremail'");
if($userrow && $userrow->num_rows > 0){
    $userfetch = $userrow->fetch_assoc();
    $userid = $userfetch["pid"];
    $username = $userfetch["pname"];
} else {
    header("location: ../login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Initialize variables
$sqlmain = "select * from schedule inner join doctor on schedule.docid=doctor.docid where schedule.scheduledate>='$today' order by schedule.scheduledate asc";
$insertkey = "";
$searchtype = "All Available";
$selectedService = "";

// Handle search and filter
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $conditions = array("schedule.scheduledate>='$today'");
    
    // Handle service filter
    if(!empty($_POST["service"]) && $_POST["service"] != "all"){
        $service = $database->real_escape_string($_POST["service"]);
        $conditions[] = "schedule.title='$service'";
        $selectedService = $service;
        $searchtype = "Filtered by Service";
    }
    
    // Handle search
    if(!empty($_POST["search"])){
        $keyword = $database->real_escape_string($_POST["search"]);
        $conditions[] = "(doctor.docname='$keyword' or doctor.docname like '$keyword%' or doctor.docname like '%$keyword' or doctor.docname like '%$keyword%' or schedule.title='$keyword' or schedule.title like '$keyword%' or schedule.title like '%$keyword' or schedule.title like '%$keyword%' or schedule.scheduledate like '$keyword%' or schedule.scheduledate like '%$keyword' or schedule.scheduledate like '%$keyword%' or schedule.scheduledate='$keyword')";
        $insertkey = $keyword;
        $searchtype = "Search Results for";
    }
    
    $sqlmain = "select * from schedule inner join doctor on schedule.docid=doctor.docid where " . implode(" and ", $conditions) . " order by schedule.scheduledate asc";
}

$result = $database->query($sqlmain);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Sessions - Dr. Dental Clinic</title>
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
            line-height: 1.6;
        }

        .search-form {
            display: flex;
            gap: 15px;
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
            white-space: nowrap;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .service-icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .service-card {
            background: var(--white);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100px;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .service-card.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-color);
        }

        /* Sessions Section */
        .sessions-section {
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .results-count {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .keyword-display {
            background: var(--bg-light);
            padding: 12px 20px;
            border-radius: 10px;
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .keyword-display strong {
            color: var(--text-dark);
        }

        /* Sessions Grid */
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .session-card {
            background: var(--white);
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .session-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .session-card:hover::before {
            transform: scaleY(1);
        }

        .session-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg-light);
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .doctor-info i {
            color: var(--primary-color);
            font-size: 18px;
        }

        .doctor-info span {
            color: var(--text-dark);
            font-weight: 500;
            font-size: 15px;
        }

        .session-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-light);
            font-size: 14px;
        }

        .detail-item i {
            color: var(--primary-color);
            width: 20px;
            font-size: 16px;
        }

        .book-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
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

            .sessions-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .date-section {
                text-align: left;
            }

            .date-value {
                justify-content: flex-start;
            }

            .search-form {
                flex-direction: column;
            }

            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .sessions-grid {
                grid-template-columns: 1fr;
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
                    <?php echo htmlspecialchars(strtoupper(substr($username, 0, 2))); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars(substr($username, 0, 15)); ?></h3>
                    <p><?php echo htmlspecialchars(substr($useremail, 0, 20)); ?></p>
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
                <a href="schedule.php" class="nav-link active">
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
                <h1>Available Sessions 📅</h1>
                <p>Browse and book your medical appointments</p>
            </div>
            <div class="date-section">
                <div class="date-label">Today's Date</div>
                <div class="date-value">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="search-section">
            <h2>Choose a Service</h2>
            <p>Select a dental service to view available sessions</p>
            
            <div class="services-grid">
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="General Checkup" class="service-card">
                        <span class="service-icon">🔍</span>
                        <span>General Checkup</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Teeth Cleaning" class="service-card">
                        <span class="service-icon">✨</span>
                        <span>Teeth Cleaning</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Teeth Whitening" class="service-card">
                        <span class="service-icon">💎</span>
                        <span>Teeth Whitening</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Tooth Extraction" class="service-card">
                        <span class="service-icon">🦷</span>
                        <span>Tooth Extraction</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Dental Filling" class="service-card">
                        <span class="service-icon">🔧</span>
                        <span>Dental Filling</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Root Canal Treatment" class="service-card">
                        <span class="service-icon">🏥</span>
                        <span>Root Canal Treatment</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Braces Consultation" class="service-card">
                        <span class="service-icon">🔩</span>
                        <span>Braces Consultation</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Dental Crown" class="service-card">
                        <span class="service-icon">👑</span>
                        <span>Dental Crown</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Dental Bridge" class="service-card">
                        <span class="service-icon">🌉</span>
                        <span>Dental Bridge</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Dental Implant" class="service-card">
                        <span class="service-icon">⚙️</span>
                        <span>Dental Implant</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Gum Treatment" class="service-card">
                        <span class="service-icon">🌸</span>
                        <span>Gum Treatment</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Emergency Dental Care" class="service-card">
                        <span class="service-icon">🚨</span>
                        <span>Emergency Dental Care</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Pediatric Dentistry" class="service-card">
                        <span class="service-icon">👶</span>
                        <span>Pediatric Dentistry</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Orthodontics" class="service-card">
                        <span class="service-icon">😁</span>
                        <span>Orthodontics</span>
                    </button>
                </form>
                
                <form action="" method="post" style="display: contents;">
                    <button type="submit" name="service" value="Cosmetic Dentistry" class="service-card">
                        <span class="service-icon">💄</span>
                        <span>Cosmetic Dentistry</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <h2>Find a Session</h2>
            <p>Search for a doctor, session title, or date to find available appointments</p>
            <form action="" method="post" class="search-form">
                <div class="search-row">
                    <input 
                        type="search" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search by doctor name, session title, or date (YYYY-MM-DD)..."
                        list="doctors"
                        value="<?php echo htmlspecialchars($insertkey); ?>"
                    >
                    <datalist id="doctors">
                        <?php
                        $list11 = $database->query("select DISTINCT docname from doctor;");
                        $list12 = $database->query("select DISTINCT title from schedule GROUP BY title;");

                        if($list11){
                            while($row00 = $list11->fetch_assoc()){
                                echo "<option value='".htmlspecialchars($row00["docname"])."'>";
                            }
                        }

                        if($list12){
                            while($row00 = $list12->fetch_assoc()){
                                echo "<option value='".htmlspecialchars($row00["title"])."'>";
                            }
                        }
                        ?>
                    </datalist>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Sessions Section -->
        <div class="sessions-section">
            <div class="section-header">
                <h2><?php echo htmlspecialchars($searchtype); ?> Sessions</h2>
                <span class="results-count"><?php echo $result->num_rows; ?> found</span>
            </div>

            <?php if($insertkey != "" || $selectedService != ""): ?>
                <div class="keyword-display">
                    <?php if($selectedService != ""): ?>
                        <i class="fas fa-filter"></i> Filtered by Service: <strong>"<?php echo htmlspecialchars($selectedService); ?>"</strong>
                    <?php endif; ?>
                    <?php if($insertkey != ""): ?>
                        <?php if($selectedService != ""): ?> | <?php endif; ?>
                        <i class="fas fa-search"></i> Search: <strong>"<?php echo htmlspecialchars($insertkey); ?>"</strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($result->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Sessions Found</h3>
                    <p>We couldn't find any sessions matching your search criteria.</p>
                    <a href="schedule.php" class="action-btn">
                        <i class="fas fa-list"></i> Show All Sessions
                    </a>
                </div>
            <?php else: ?>
                <div class="sessions-grid">
                    <?php
                    while($row = $result->fetch_assoc()){
                        $scheduleid = htmlspecialchars($row["scheduleid"]);
                        $title = htmlspecialchars($row["title"]);
                        $docname = htmlspecialchars($row["docname"]);
                        $scheduledate = htmlspecialchars($row["scheduledate"]);
                        $scheduletime = htmlspecialchars($row["scheduletime"]);
                    ?>
                        <div class="session-card">
                            <h3 class="session-title"><?php echo substr($title, 0, 50); ?></h3>
                            
                            <div class="doctor-info">
                                <i class="fas fa-user-md"></i>
                                <span><?php echo substr($docname, 0, 30); ?></span>
                            </div>

                            <div class="session-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('F j, Y', strtotime($scheduledate)); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Starts at <strong><?php echo date('g:i A', strtotime($scheduletime)); ?></strong></span>
                                </div>
                            </div>

                            <a href="booking.php?id=<?php echo $scheduleid; ?>" style="text-decoration: none;">
                                <button class="book-btn">
                                    <i class="fas fa-calendar-plus"></i>
                                    Book Appointment
                                </button>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>