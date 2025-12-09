<?php
session_start();
include("../connection.php");

if(!isset($_SESSION["user"]) || $_SESSION["user"]=="" || $_SESSION['usertype']!='p'){
    header("location: ../login.php");
    exit();
}

$useremail = $_SESSION["user"];
$userrow = $database->query("select * from patient where pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$currentTime = date('H:i:s');

// Get statistics
$upcoming_query = $database->query("SELECT COUNT(*) as count FROM appointment WHERE pid='$userid' AND appodate >= NOW()");
$upcoming_count = $upcoming_query->fetch_assoc()['count'];

$completed_query = $database->query("SELECT COUNT(*) as count FROM appointment WHERE pid='$userid' AND status='completed'");
$completed_count = $completed_query->fetch_assoc()['count'];

$total_query = $database->query("SELECT COUNT(*) as count FROM appointment WHERE pid='$userid'");
$total_count = $total_query->fetch_assoc()['count'];

$pending_query = $database->query("SELECT COUNT(*) as count FROM appointment WHERE pid='$userid' AND payment_status='unpaid'");
$pending_payments = $pending_query->fetch_assoc()['count'];

// Get next appointment

$next_appointment = $database->query("SELECT a.*, s.title as session_title, d.docname 
    FROM appointment a 
    LEFT JOIN schedule s ON a.scheduleid = s.scheduleid 
    LEFT JOIN doctor d ON s.docid = d.doctor_id 
    WHERE a.pid='$userid' AND a.appodate >= NOW() 
    ORDER BY a.appodate ASC LIMIT 1");
$next_appt = $next_appointment->fetch_assoc();

// Lines 44-48 - Get recent appointments
$recent_appointments = $database->query("SELECT a.*, d.docname 
    FROM appointment a 
    LEFT JOIN schedule s ON a.scheduleid = s.scheduleid 
    LEFT JOIN doctor d ON s.docid = d.doctor_id 
    WHERE a.pid='$userid' 
    ORDER BY a.appodate DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dr. Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/styles.css">
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/chatbot.css">
    <style>
        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Chatbot Styles */
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .chatbot-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }

        .chatbot-window {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 380px;
            height: 550px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        .chatbot-window.active {
            display: flex;
        }

        .chatbot-window.minimized {
            height: 60px;
        }

        .chatbot-window.minimized .chatbot-messages,
        .chatbot-window.minimized .chatbot-input-container {
            display: none;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chatbot-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chatbot-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .chatbot-header-text h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .chatbot-header-text p {
            margin: 0;
            font-size: 12px;
            opacity: 0.9;
        }

        .chatbot-header-actions {
            display: flex;
            gap: 10px;
        }

        .chatbot-header-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .chatbot-header-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .chatbot-message {
            margin-bottom: 16px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-bot {
            display: flex;
            gap: 10px;
        }

        .message-user {
            display: flex;
            justify-content: flex-end;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .message-content {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
            white-space: pre-line;
        }

        .message-bot .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .message-user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            padding: 0 16px;
        }

        .typing-indicator {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
            animation: bounce 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }

        .chatbot-input-container {
            padding: 16px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .chatbot-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chatbot-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chatbot-input:focus {
            border-color: #667eea;
        }

        .chatbot-send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .chatbot-send-btn:hover {
            transform: scale(1.1);
        }

        .chatbot-send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .quick-reply-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-reply-btn:hover {
            background: #667eea;
            color: white;
        }

        @media (max-width: 480px) {
            .chatbot-window {
                width: calc(100vw - 40px);
                right: 20px;
                left: 20px;
                bottom: 90px;
            }

            .chatbot-toggle {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container">
                <div class="logo">
                    <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                </div>
                <span class="logo-text">Dr. Dental Clinic</span>
            </div>
        </div>
        <div class="user-profile-nav">
            <?php if (!empty($userfetch['profile_picture']) && file_exists('../' . $userfetch['profile_picture'])): ?>
                <div class="user-avatar-nav">
                    <img src="../<?php echo htmlspecialchars($userfetch['profile_picture']); ?>" alt="Profile">
                </div>
            <?php else: ?>
                <div class="user-avatar-nav">
                    <?php echo strtoupper(substr($username, 0, 2)); ?>
                </div>
            <?php endif; ?>
            <div class="user-info-nav">
                <h4><?php echo htmlspecialchars(substr($username, 0, 20)); ?></h4>
                <p>Patient</p>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-menu">
            <div class="nav-section-title">Main Menu</div>
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Available Sessions</span>
                </a>
            </div>
            
            <div class="nav-section-title">My Appointments</div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="appointment-history.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Appointment History</span>
                </a>
            </div>
            
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>
        
        <div class="logout-section">
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $username)[0]); ?>! 👋</h1>
                <p>Here's what's happening with your dental health today</p>
                <div class="welcome-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"><?php echo date('g:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $upcoming_count; ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $completed_count; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $total_count; ?></div>
                        <div class="stat-label">Total Visits</div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-hospital"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $pending_payments; ?></div>
                        <div class="stat-label">Pending Payment</div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="booking.php" class="action-card">
                <div class="action-icon-large blue">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3 class="action-title">Book Appointment</h3>
                <p class="action-desc">Schedule your next dental visit</p>
            </a>

            <a href="schedule.php" class="action-card">
                <div class="action-icon-large purple">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="action-title">View Sessions</h3>
                <p class="action-desc">Browse available time slots</p>
            </a>

            <a href="appointment-history.php" class="action-card">
                <div class="action-icon-large green">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="action-title">My History</h3>
                <p class="action-desc">View past appointments</p>
            </a>

            <a href="settings.php" class="action-card">
                <div class="action-icon-large orange">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3 class="action-title">Settings</h3>
                <p class="action-desc">Update your profile</p>
            </a>
        </div>

        <!-- Recent Appointments -->
        <div class="appointments-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Appointments
                </h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterAppointments('all')">All</button>
                    <button class="filter-tab" onclick="filterAppointments('week')">This Week</button>
                    <button class="filter-tab" onclick="filterAppointments('month')">This Month</button>
                </div>
            </div>

            <?php if($recent_appointments->num_rows > 0): ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Appt #</th>
                            <th>Service</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody">
                        <?php while($appt = $recent_appointments->fetch_assoc()): ?>
                        <tr data-date="<?php echo htmlspecialchars($appt['appodate']); ?>">
                            <td><span class="appoint-num">#<?php echo htmlspecialchars($appt['apponum']); ?></span></td>
                            <td><?php echo htmlspecialchars($appt['service_type'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($appt['docname'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo date('M j, Y - g:i A', strtotime($appt['appodate'])); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($appt['status']); ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($appt['payment_status']); ?>">
                                    <?php echo ucfirst($appt['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div id="noResultsMessage" style="display: none;">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>No Appointments Found</h3>
                        <p>No appointments found for the selected time period.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>No Appointments Yet</h3>
                    <p>You haven't booked any appointments. Start by scheduling your first visit!</p>
                    <a href="booking.php" class="action-btn">
                        <i class="fas fa-calendar-plus"></i>
                        Book Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

   <!-- Chatbot -->
 <div class="chatbot-container">
        <button class="chatbot-toggle" onclick="toggleChatbot()">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 .97 4.29L2 22l5.71-.97C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.38 0-2.68-.31-3.85-.85l-.27-.14-2.85.48.48-2.85-.14-.27C4.31 14.68 4 13.38 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>
                <circle cx="9" cy="12" r="1.5"/>
                <circle cx="15" cy="12" r="1.5"/>
                <path d="M12 2.5c-.28 0-.5.22-.5.5v2c0 .28.22.5.5.5s.5-.22.5-.5V3c0-.28-.22-.5-.5-.5z"/>
            </svg>
        </button>

        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <div class="chatbot-header-left">
                    <div class="chatbot-avatar">
                        <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                    </div>
                    <div class="chatbot-header-text">
                        <h3>Dr. Dental Clinic Care Assistant</h3>
                        <p>Online</p>
                    </div>
                </div>
                <div class="chatbot-header-actions">
                    <button class="chatbot-header-btn" onclick="minimizeChatbot()">
                        <span style="font-size: 20px;">−</span>
                    </button>
                    <button class="chatbot-header-btn" onclick="toggleChatbot()">
                        <span style="font-size: 18px;">×</span>
                    </button>
                </div>
            </div>

            <div class="chatbot-messages" id="chatbotMessages">
                <div class="chatbot-message message-bot">
                    <div class="message-avatar">
                        <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                    </div>
                    <div>
                        <div class="message-content">Good day! Welcome to Dr. Dental Clinic. I am your virtual assistant. How may I assist you today?</div>
                        <div class="quick-replies">
                            <button class="quick-reply-btn" onclick="sendQuickReply('Services')">Our Services</button>
                            <button class="quick-reply-btn" onclick="sendQuickReply('Location')">Location</button>
                            <button class="quick-reply-btn" onclick="sendQuickReply('Hours')">Clinic Hours</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chatbot-input-container">
                <div class="chatbot-input-wrapper">
                    <input 
                        type="text" 
                        class="chatbot-input" 
                        id="chatbotInput" 
                        placeholder="Type your message..."
                        onkeypress="handleKeyPress(event)"
                    >
                    <button class="chatbot-send-btn" onclick="sendMessage()">
                        ➤
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="chatbot-landing.js"></script>

        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <div class="chatbot-header-left">
                    <div class="chatbot-avatar">
                       <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                    </div>
                    <div class="chatbot-header-text">
                        <h3>Dr. Dental Assistant</h3>
                        <p>Online</p>
                    </div>
                </div>
                <div class="chatbot-header-actions">
                    <button class="chatbot-header-btn" onclick="minimizeChatbot()">
                        <span style="font-size: 20px;">−</span>
                    </button>
                    <button class="chatbot-header-btn" onclick="toggleChatbot()">
                        <span style="font-size: 18px;">×</span>
                    </button>
                </div>
            </div>

            <div class="chatbot-messages" id="chatbotMessages">
                <div class="chatbot-message message-bot">
                    <div class="message-avatar">
                    <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
                    </div>
                    <div>
                        <div class="message-content">Good day! Welcome to Dr. Dental Clinic. I am your virtual assistant. How may I assist you today?</div>
                        <div class="quick-replies">
                            <button class="quick-reply-btn" onclick="sendQuickReply('Services')">Our Services</button>
                            <button class="quick-reply-btn" onclick="sendQuickReply('Location')">Location</button>
                            <button class="quick-reply-btn" onclick="sendQuickReply('Hours')">Clinic Hours</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chatbot-input-container">
                <div class="chatbot-input-wrapper">
                    <input 
                        type="text" 
                        class="chatbot-input" 
                        id="chatbotInput" 
                        placeholder="Type your message..."
                        onkeypress="handleKeyPress(event)"
                    >
                    <button class="chatbot-send-btn" onclick="sendMessage()">
                        ➤
                    </button>
                </div>
            </div>
        </div>
    </div>


<script>
    // Update time every minute
    setInterval(() => {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeStr;
        }
    }, 60000);

    // Toggle sidebar for mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Filter appointments function
    function filterAppointments(filter) {
        const rows = document.querySelectorAll('#appointmentsTableBody tr');
        const tabs = document.querySelectorAll('.filter-tab');
        const table = document.querySelector('.appointments-table');
        const noResultsMsg = document.getElementById('noResultsMessage');
        
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        endOfWeek.setHours(23, 59, 59, 999);
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        endOfMonth.setHours(23, 59, 59, 999);
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            const dateStr = row.getAttribute('data-date');
            const appointmentDate = new Date(dateStr);
            let shouldShow = false;
            
            if (filter === 'all') {
                shouldShow = true;
            } else if (filter === 'week') {
                shouldShow = appointmentDate >= startOfWeek && appointmentDate <= endOfWeek;
            } else if (filter === 'month') {
                shouldShow = appointmentDate >= startOfMonth && appointmentDate <= endOfMonth;
            }
            
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        if (visibleCount === 0) {
            table.style.display = 'none';
            noResultsMsg.style.display = 'block';
        } else {
            table.style.display = 'table';
            noResultsMsg.style.display = 'none';
        }
    }

function toggleChatbot() {
    const chatbot = document.getElementById('chatbotWindow');
    chatbot.classList.toggle('active');
    chatbot.classList.remove('minimized');
    if (chatbot.classList.contains('active')) {
        document.getElementById('chatbotInput').focus();
    }
}

function minimizeChatbot() {
    const chatbot = document.getElementById('chatbotWindow');
    chatbot.classList.toggle('minimized');
}

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function sendQuickReply(text) {
    document.getElementById('chatbotInput').value = text;
    sendMessage();
}

function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    addMessage('user', message);
    input.value = '';
    
    showTypingIndicator();
    
    setTimeout(() => {
        hideTypingIndicator();
        const response = getBotResponse(message);
        addMessage('bot', response, true); // Add quick replies after bot response
    }, 1000);
}

function addMessage(type, text, showQuickReplies = false) {
    const messagesContainer = document.getElementById('chatbotMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message message-${type}`;
    
    if (type === 'bot') {
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
            </div>
            <div>
                <div class="message-content">${text}</div>
                ${showQuickReplies ? `
                <div class="quick-replies">
                    <button class="quick-reply-btn" onclick="sendQuickReply('Services')">Our Services</button>
                    <button class="quick-reply-btn" onclick="sendQuickReply('Location')">Location</button>
                    <button class="quick-reply-btn" onclick="sendQuickReply('Hours')">Clinic Hours</button>
                    <button class="quick-reply-btn" onclick="sendQuickReply('Book Appointment')">Book Appointment</button>
                    <button class="quick-reply-btn" onclick="sendQuickReply('Contact')">Contact Us</button>
                </div>
                ` : ''}
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-content">${text}</div>
        `;
    }
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatbotMessages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chatbot-message message-bot';
    typingDiv.id = 'typingIndicator';
    typingDiv.innerHTML = `
        <div class="message-avatar">
            <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo">
        </div>
        <div class="typing-indicator">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

function getBotResponse(message) {
    const lowerMessage = message.toLowerCase();
    
    // Greetings
    if (lowerMessage.match(/\b(hello|hi|hey|good morning|good afternoon|good evening|greetings)\b/)) {
        return "Good day! Welcome to Dr. Dental Clinic Care. How may I be of assistance to you today?";
    }
    
    // Services
    if (lowerMessage.match(/\b(service|services|treatment|treatments|procedure|procedures|what do you offer|what can you do)\b/)) {
        return "Dr. Dental Clinic Care offers a comprehensive range of dental services including:\n\n• General Dentistry (Check-ups, Cleanings)\n• Teeth Whitening\n• Dental Crowns and Bridges\n• Root Canal Treatment\n• Tooth Extraction\n• Orthodontics (Braces)\n• Dental Implants\n• Cosmetic Dentistry\n\nWould you like more information about any specific service?";
    }
    
    // Booking/Appointment
    if (lowerMessage.match(/\b(book|appointment|schedule|reservation|make appointment|set appointment)\b/)) {
        return "To book an appointment at Dr. Dental Clinic Care:\n\n1. Visit our website and create an account\n2. Log in to your patient portal\n3. Navigate to 'Book Appointment'\n4. Select your preferred service type\n5. Choose an available date and time slot\n6. Confirm your appointment details\n\nFor immediate assistance, please call our clinic during business hours.";
    }
    
    // Hours
    if (lowerMessage.match(/\b(hours|time|schedule|open|close|working hours|operating hours)\b/)) {
        return "Dr. Dental Clinic Care operates during the following hours:\n\nMonday - Saturday: 8:00 AM - 5:00 PM\n\nWe recommend booking an appointment in advance to ensure your preferred time slot is available.";
    }
    
    // Location
    if (lowerMessage.match(/\b(location|address|where|find|directions|situated)\b/)) {
        return "Dr. Dental Clinic Care is conveniently located at:\n\nPonciano Street, Davao City\nPhilippines\n\nWe are easily accessible and have parking facilities available for our patients. For detailed directions, please contact our reception desk or use your preferred navigation app.";
    }
    
    // Contact
    if (lowerMessage.match(/\b(contact|phone|call|email|reach)\b/)) {
        return "You may contact Dr. Dental Clinic Care through:\n\n• Visit our clinic during business hours\n• Call us for immediate assistance\n• Email us through our website contact form\n• Use this chatbot for general inquiries\n\nOur friendly staff will be happy to assist you with any questions or concerns.";
    }
    
    // Payment
    if (lowerMessage.match(/\b(payment|cost|price|fee|charge|insurance|accept)\b/)) {
        return "Dr. Dental Clinic Care accepts various payment methods:\n\n• Cash\n• Credit/Debit Cards\n• Gcash\n• Payment Plans (for major procedures)\n\nFor specific pricing information, please contact our reception desk. We believe in transparent pricing and will provide detailed cost estimates before any treatment.";
    }
    
    // Emergency
    if (lowerMessage.match(/\b(emergency|urgent|pain|toothache|bleeding|swelling)\b/)) {
        return "For dental emergencies:\n\n• During business hours: Visit our clinic immediately or call us\n• After hours: Please proceed to the nearest hospital emergency room\n\nCommon dental emergencies:\n• Severe toothache\n• Broken or knocked-out teeth\n• Uncontrolled bleeding\n• Jaw injuries\n• Severe swelling\n\nWe prioritize emergency cases and will accommodate you as soon as possible.";
    }
    
    // Doctors/Staff
    if (lowerMessage.match(/\b(doctor|doctors|dentist|dentists|staff|team)\b/)) {
        return "Dr. Dental Clinic Care has a team of experienced and qualified dental professionals dedicated to providing excellent care. Our team includes specialists in various fields of dentistry to ensure you receive comprehensive treatment.\n\nYou can learn more about our team on our website or by visiting our clinic.";
    }
    
    // Thank you
    if (lowerMessage.match(/\b(thank|thanks|appreciate)\b/)) {
        return "You are most welcome! It is our pleasure to assist you. Should you require any further assistance, please do not hesitate to reach out. Dr. Dental Clinic Care is committed to providing you with excellent dental care and service.";
    }
    
    // Goodbye
    if (lowerMessage.match(/\b(bye|goodbye|see you|take care)\b/)) {
        return "Thank you for contacting Dr. Dental Clinic Care. We wish you good health and look forward to serving you. Have a pleasant day!";
    }
    
    // Help
    if (lowerMessage.match(/\b(help|assist|support)\b/)) {
        return "I am here to assist you with information about:\n\n• Our dental services\n• Clinic hours and location\n• Booking appointments\n• Payment options\n• Emergency procedures\n• General inquiries\n\nPlease feel free to ask me any questions!";
    }
    
    // Default response
    return "Thank you for your inquiry. I apologize, but I may not have fully understood your question. Could you please rephrase it, or would you like to know about:\n\n• Our Services\n• Clinic Hours\n• Location\n• Booking Appointments\n• Payment Methods\n\nAlternatively, you may contact our clinic directly for more specific assistance.";
}
</script>
<script src="chatbot-landing.js"></script>
</body>
</html>