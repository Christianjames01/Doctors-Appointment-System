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
    LEFT JOIN doctor d ON s.docid = d.docid 
    WHERE a.pid='$userid' AND a.appodate >= NOW() 
    ORDER BY a.appodate ASC LIMIT 1");
$next_appt = $next_appointment->fetch_assoc();

// Get ALL appointments (we'll filter on client-side)
$recent_appointments = $database->query("SELECT a.*, d.docname 
    FROM appointment a 
    LEFT JOIN schedule s ON a.scheduleid = s.scheduleid 
    LEFT JOIN doctor d ON s.docid = d.docid 
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
    <style>
        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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

    <script>
        // Update time every minute
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            document.getElementById('current-time').textContent = timeStr;
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
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            
            // Calculate start of week (Sunday)
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            
            // Calculate end of week (Saturday)
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            endOfWeek.setHours(23, 59, 59, 999);
            
            // Calculate start of month
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            
            // Calculate end of month
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
            
            // Show/hide table and no results message
            if (visibleCount === 0) {
                table.style.display = 'none';
                noResultsMsg.style.display = 'block';
            } else {
                table.style.display = 'table';
                noResultsMsg.style.display = 'none';
            }
        }
    </script>
</body>
</html>