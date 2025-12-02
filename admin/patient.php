<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';

// Check if user is admin
if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// Get all patients with appointment counts
$sql = "SELECT p.*, 
        COUNT(DISTINCT a.appoid) as total_appointments,
        SUM(CASE WHEN a.payment_status = 'paid' THEN a.amount ELSE 0 END) as total_paid
        FROM patient p
        LEFT JOIN appointment a ON p.pid = a.pid
        GROUP BY p.pid
        ORDER BY p.pname ASC";

$patients = $database->query($sql);

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#10b981;--primary-dark:#059669;--sidebar-bg:#1e293b;--sidebar-hover:#334155;--text-dark:#0f172a;--text-med:#64748b;--bg-light:#f8fafc;--white:#fff;--sidebar-w:260px;--sidebar-mini:70px;--shadow:0 1px 3px rgba(0,0,0,0.1);--shadow-lg:0 10px 25px rgba(0,0,0,0.1)}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg-light);color:var(--text-dark);overflow-x:hidden}
        
        /* Sidebar Styles */
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
        
        /* Main Content */
        .main-content{margin-left:var(--sidebar-w);transition:margin-left .3s;min-height:100vh}
        .sidebar.mini~.main-content{margin-left:var(--sidebar-mini)}
        .top-bar{background:var(--white);padding:20px 30px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
        .page-title h1{font-size:24px;font-weight:700;color:var(--text-dark);margin-bottom:4px}
        .page-title p{font-size:14px;color:var(--text-med)}
        
        .content-container{padding:30px}
        
        .panel {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .panel h2 {
            color: var(--text-dark);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }

        .search-box {
            margin-bottom: 20px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-med);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .patients-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patients-table thead {
            background: #f9fafb;
        }

        .patients-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .patients-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text-dark);
            font-size: 14px;
        }

        .patients-table tbody tr {
            transition: background 0.2s;
        }

        .patients-table tbody tr:hover {
            background: #f9fafb;
        }

        .patient-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(16, 185, 129, 0.2);
        }

        .patient-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 600;
            border: 2px solid rgba(16, 185, 129, 0.2);
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .patient-email {
            font-size: 12px;
            color: var(--text-med);
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-dark);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
        }

        .btn-info:hover {
            background: #2563eb;
        }

        .empty-message {
            text-align: center;
            color: var(--text-med);
            padding: 40px;
            font-size: 14px;
        }

        .mobile-toggle{display:none}

        @media (max-width: 768px) {
            .sidebar{transform:translateX(-100%)}
            .sidebar.mobile-open{transform:translateX(0)}
            .main-content{margin-left:0}
            .top-bar{padding:15px 20px}
            .content-container{padding:20px 15px}
            .mobile-toggle{display:block!important;position:fixed;top:20px;left:20px;z-index:1001;background:var(--primary);color:white;border:none;padding:10px 15px;border-radius:8px;cursor:pointer}
            
            .patients-table {
                font-size: 13px;
            }

            .patients-table th,
            .patients-table td {
                padding: 10px 8px;
            }

            .patient-avatar,
            .patient-avatar-placeholder {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }
        }
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
                <a href="index.php" class="nav-item"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="appointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
                <a href="patient.php" class="nav-item active"><i class="fas fa-users"></i><span>Patients</span></a>
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
                <h1>Patient Management</h1>
                <p>Manage and view all patient records</p>
            </div>
        </div>

        <div class="content-container">
            <div class="panel">
                <h2>
                    <i class="fas fa-users"></i>
                    All Patients
                </h2>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search patients by name or email..." onkeyup="searchPatients()">
                </div>

                <?php if ($patients && $patients->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="patients-table" id="patientsTable">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Appointments</th>
                                    <th>Total Paid</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($patient = $patients->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="patient-profile">
                                                <?php if (!empty($patient['profile_picture']) && file_exists('../' . $patient['profile_picture'])): ?>
                                                    <img src="../<?php echo e($patient['profile_picture']); ?>" alt="Profile" class="patient-avatar">
                                                <?php else: ?>
                                                    <div class="patient-avatar-placeholder">
                                                        <?php echo strtoupper(substr($patient['pname'], 0, 2)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="patient-info">
                                                    <div class="patient-name"><?php echo e($patient['pname']); ?></div>
                                                    <div class="patient-email"><?php echo e($patient['pemail']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($patient['ptel'])): ?>
                                                <i class="fas fa-phone" style="color: var(--text-med);"></i>
                                                <?php echo e($patient['ptel']); ?>
                                            <?php else: ?>
                                                <span style="color: var(--text-med);">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo (int)$patient['total_appointments']; ?> appointments
                                            </span>
                                        </td>
                                        <td>
                                            <strong>₱<?php echo number_format((float)$patient['total_paid'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="patient-details.php?pid=<?php echo $patient['pid']; ?>" class="btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-message">
                        <i class="fas fa-user-slash" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                        No patients found in the system.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mini');
        }

        function toggleMobile() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        function searchPatients() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('patientsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const nameCell = tr[i].getElementsByClassName('patient-name')[0];
                const emailCell = tr[i].getElementsByClassName('patient-email')[0];
                
                if (nameCell && emailCell) {
                    const nameValue = nameCell.textContent || nameCell.innerText;
                    const emailValue = emailCell.textContent || emailCell.innerText;
                    
                    if (nameValue.toUpperCase().indexOf(filter) > -1 || 
                        emailValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>