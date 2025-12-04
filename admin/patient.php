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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/patient.css">
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