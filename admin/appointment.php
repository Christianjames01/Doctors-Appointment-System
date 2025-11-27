<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Dr. Dental Clinic</title>
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
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --danger-color: #f56565;
            --info-color: #4299e1;
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

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f6f8ff 0%, #f0e6ff 100%);
            border-radius: 12px;
            border: 2px solid #e9d8fd;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .admin-info h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .admin-badge {
            display: inline-block;
            background: var(--primary-gradient);
            color: white;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
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
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .back-btn {
            padding: 10px 20px;
            background: var(--bg-light);
            color: var(--primary-color);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(-3px);
        }

        .date-display {
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

        /* Alert Message */
        .alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert i {
            font-size: 20px;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .appointment-count {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Table Section */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: var(--bg-light);
        }

        .data-table th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-dark);
            font-size: 14px;
        }

        .data-table tbody tr {
            transition: background 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Booking ID Badge */
        .booking-id {
            background: var(--primary-gradient);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            display: inline-block;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-update {
            background: #4299e1;
            color: white;
        }

        .btn-update:hover {
            background: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
        }

        .btn-view {
            background: #48bb78;
            color: white;
        }

        .btn-view:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.4);
        }

        .btn-delete {
            background: #f56565;
            color: white;
        }

        .btn-delete:hover {
            background: #e53e3e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
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
            font-weight: 700;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 25px;
            font-size: 16px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Popup/Modal */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .popup {
            background: var(--white);
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .popup-header {
            padding: 30px;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }

        .popup-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .popup-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        .close {
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
            font-weight: 300;
            transition: all 0.3s ease;
        }

        .close:hover {
            background: #e2e8f0;
            transform: rotate(90deg);
        }

        .popup-content {
            padding: 30px;
        }

        .detail-row {
            margin-bottom: 25px;
        }

        .detail-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .popup-actions {
            padding: 20px 30px;
            background: var(--bg-light);
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: var(--text-dark);
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.3);
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

            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .date-display {
                text-align: left;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }
    }else{
        header("location: ../login.php");
    }

    include("../connection.php");

    // Handle status update
    if(isset($_POST['update_status'])) {
        $appoid = (int)$_POST['appoid'];
        $new_status = $_POST['status'];
        
        $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt = $database->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $appoid);
        $stmt->execute();
        
        $_SESSION['message'] = "Appointment status updated successfully!";
        header("Location: appointment.php");
        exit;
    }

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

            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="admin-info">
                    <h3>Administrator</h3>
                    <span class="admin-badge">ADMIN</span>
                </div>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="doctors.php" class="nav-link">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="appointment.php" class="nav-link active">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="patient.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Patients</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="billing.php" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Payment</span>
                </a>
            </div>
        </nav>

        <button class="logout-btn" onclick="window.location.href='../index.html'">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
                <h1>Appointment Manager</h1>
            </div>

            <div class="date-display">
                <div class="date-label">Today's Date</div>
                <div class="date-value">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
        <div class="alert">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h2>Filter Appointments</h2>
                <?php 
                $list110 = $database->query("SELECT * FROM appointments");
                ?>
                <span class="appointment-count">
                    <i class="fas fa-calendar-check"></i> <?php echo $list110->num_rows; ?> Total
                </span>
            </div>

            <form method="POST" action="" class="filter-form">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="appointment_date" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" name="filter" class="filter-btn">
                        <i class="fas fa-filter"></i>
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <?php
        $sqlmain = "SELECT * FROM appointments WHERE 1=1";
        
        if($_POST){
            if(!empty($_POST["appointment_date"])){
                $appointment_date = $_POST["appointment_date"];
                $sqlmain .= " AND appointment_date='$appointment_date'";
            }

            if(!empty($_POST["status"])){
                $status = $_POST["status"];
                $sqlmain .= " AND status='$status'";
            }
        }
        
        $sqlmain .= " ORDER BY appointment_date DESC, appointment_time DESC";
        $result = $database->query($sqlmain);
        ?>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-wrapper">
                <?php if($result->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointments Found</h3>
                    <p>We couldn't find any appointments matching your criteria.</p>
                    <a href="appointment.php" class="btn-primary">
                        <i class="fas fa-sync-alt"></i>
                        Show All Appointments
                    </a>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="booking-id">#<?php echo $row["id"]; ?></span>
                            </td>
                            <td style="font-weight:600;">
                                <?php echo substr($row["fullname"], 0, 30); ?>
                            </td>
                            <td><?php echo substr($row["email"], 0, 25); ?></td>
                            <td><?php echo substr($row["service"], 0, 20); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row["appointment_date"])); ?></td>
                            <td><?php echo date('g:i A', strtotime($row["appointment_time"])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row["status"]; ?>">
                                    <?php echo ucfirst($row["status"]); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row["created_at"])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=update&id=<?php echo $row["id"]; ?>&status=<?php echo $row["status"]; ?>" class="btn btn-update">
                                        <i class="fas fa-edit"></i>
                                        Update
                                    </a>
                                    <a href="?action=view&id=<?php echo $row["id"]; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                    <a href="?action=drop&id=<?php echo $row["id"]; ?>&name=<?php echo $row["fullname"]; ?>" class="btn btn-delete">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php
    if($_GET){
        $id = $_GET["id"];
        $action = $_GET["action"];
        
        if($action=='update'){
            $current_status = $_GET["status"];
            echo '
            <div class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2>Update Appointment Status</h2>
                        <p>Change the status of this appointment</p>
                        <a class="close" href="appointment.php">&times;</a>
                    </div>
                    <form method="POST" action="">
                        <div class="popup-content">
                            <input type="hidden" name="appoid" value="'.$id.'">
                            <div class="detail-row">
                                <div class="detail-label">Select New Status</div>
                                <select name="status" class="form-select" required>
                                    <option value="pending" '.($current_status=='pending'?'selected':'').'>Pending</option>
                                    <option value="confirmed" '.($current_status=='confirmed'?'selected':'').'>Confirmed</option>
                                    <option value="completed" '.($current_status=='completed'?'selected':'').'>Completed</option>
                                    <option value="cancelled" '.($current_status=='cancelled'?'selected':'').'>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="popup-actions">
                            <a href="appointment.php" class="btn-secondary">Cancel</a>
                            <button type="submit" name="update_status" class="btn-primary">
                                <i class="fas fa-save"></i>
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>';
            
        }elseif($action=='view'){
            $sqlmain = "SELECT * FROM appointments WHERE id='$id'";
            $result = $database->query($sqlmain);
            $row = $result->fetch_assoc();
            
            echo '
            <div class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2>Appointment Details</h2>
                        <p>Booking #'.$id.'</p>
                        <a class="close" href="appointment.php">&times;</a>
                    </div>
                    <div class="popup-content">
                        <div class="detail-row">
                            <div class="detail-label">Patient Name</div>
                            <div class="detail-value">'.$row["fullname"].'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Email Address</div>
                            <div class="detail-value">'.$row["email"].'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Service</div>
                            <div class="detail-value">'.$row["service"].'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Appointment Date</div>
                            <div class="detail-value">'.date('l, F j, Y', strtotime($row["appointment_date"])).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Appointment Time</div>
                            <div class="detail-value">'.date('g:i A', strtotime($row["appointment_time"])).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge status-'.$row["status"].'">'.ucfirst($row["status"]).'</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Booked On</div>
                            <div class="detail-value">'.date('F j, Y g:i A', strtotime($row["created_at"])).'</div>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <a href="appointment.php" class="btn-primary">
                            <i class="fas fa-check"></i>
                            OK
                        </a>
                    </div>
                </div>
            </div>';
            
        }elseif($action=='drop'){
            $nameget = $_GET["name"];
            echo '
            <div class="overlay">
                <div class="popup">
                    <div class="popup-header">
                        <h2>Confirm Deletion</h2>
                        <p>This action cannot be undone</p>
                        <a class="close" href="appointment.php">&times;</a>
                    </div>
                    <div class="popup-content">
                        <div class="detail-row">
                            <p style="font-size: 16px; line-height: 1.6;">
                                Are you sure you want to delete this appointment?
                            </p>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Patient Name</div>
                            <div class="detail-value" style="font-weight: 700;">'.substr($nameget,0,40).'</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Booking ID</div>
                            <div class="detail-value"><span class="booking-id">#'.$id.'</span></div>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <a href="appointment.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            No, Cancel
                        </a>
                        <a href="delete-appointment.php?id='.$id.'" class="btn-danger">
                            <i class="fas fa-trash"></i>
                            Yes, Delete
                        </a>
                    </div>
                </div>
            </div>';
        }
    }
    ?>
</body>
</html>