<?php
/**
 * Revenue Management Page
 * Displays revenue statistics, charts, and transaction history
 */

session_start();
date_default_timezone_set('Asia/Manila');
require_once '../connection.php';

// ==========================================
// AUTHENTICATION & SECURITY
// ==========================================

if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Escape output for safe HTML display
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Build WHERE clause based on filters
 */
function buildWhereClause($database, $filterPeriod, $filterService) {
    $whereClause = "WHERE amount IS NOT NULL";
    
    // Add time period filter
    if ($filterPeriod !== 'all') {
        switch ($filterPeriod) {
            case 'today':
                $whereClause .= " AND DATE(appodate) = CURDATE()";
                break;
            case 'week':
                $whereClause .= " AND YEARWEEK(appodate, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $whereClause .= " AND MONTH(appodate) = MONTH(CURDATE()) AND YEAR(appodate) = YEAR(CURDATE())";
                break;
            case 'year':
                $whereClause .= " AND YEAR(appodate) = YEAR(CURDATE())";
                break;
        }
    }
    
    // Add service filter
    if ($filterService !== 'all') {
        $whereClause .= " AND service_type = '" . $database->real_escape_string($filterService) . "'";
    }
    
    return $whereClause;
}

/**
 * Get chart configuration based on filter period
 */
function getChartConfig($filterPeriod) {
    $configs = [
        'today' => [
            'interval' => '1 DAY',
            'groupBy' => '%H:00',
            'sortBy' => '%Y-%m-%d %H',
            'additionalWhere' => ' AND DATE(appodate) = CURDATE()'
        ],
        'week' => [
            'interval' => '7 DAY',
            'groupBy' => '%W',
            'sortBy' => '%Y-%m-%d',
            'additionalWhere' => ' AND YEARWEEK(appodate, 1) = YEARWEEK(CURDATE(), 1)'
        ],
        'month' => [
            'interval' => '30 DAY',
            'groupBy' => '%b %d',
            'sortBy' => '%Y-%m-%d',
            'additionalWhere' => ' AND MONTH(appodate) = MONTH(CURDATE()) AND YEAR(appodate) = YEAR(CURDATE())'
        ],
        'year' => [
            'interval' => '12 MONTH',
            'groupBy' => '%b %Y',
            'sortBy' => '%Y-%m',
            'additionalWhere' => ' AND YEAR(appodate) = YEAR(CURDATE())'
        ],
        'all' => [
            'interval' => '12 MONTH',
            'groupBy' => '%b %Y',
            'sortBy' => '%Y-%m',
            'additionalWhere' => ' AND appodate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)'
        ]
    ];
    
    return $configs[$filterPeriod] ?? $configs['all'];
}

// ==========================================
// INITIALIZE VARIABLES
// ==========================================

$totalRevenue = 0;
$paidRevenue = 0;
$unpaidRevenue = 0;
$revenueByService = [];
$revenueByMonth = [];
$recentPayments = [];
$services = [];
$chartLabels = [];
$chartData = [];

// Get filter parameters
$filterPeriod = $_GET['period'] ?? 'all';
$filterService = $_GET['service'] ?? 'all';

// ==========================================
// DATABASE QUERIES
// ==========================================

try {
    $whereClause = buildWhereClause($database, $filterPeriod, $filterService);
    
    // -------------------------------------------
    // 1. TOTAL REVENUE STATISTICS
    // -------------------------------------------
    $sql = "SELECT 
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid,
                SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid,
                SUM(amount) AS total
            FROM appointment 
            $whereClause";
    
    $result = $database->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $paidRevenue = $row['paid'] ?? 0;
        $unpaidRevenue = $row['unpaid'] ?? 0;
        $totalRevenue = $row['total'] ?? 0;
    }
    
    // -------------------------------------------
    // 2. REVENUE BY SERVICE TYPE
    // -------------------------------------------
    $sql = "SELECT 
                service_type, 
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
                SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) AS unpaid_amount,
                COUNT(*) AS appointment_count
            FROM appointment 
            $whereClause
            GROUP BY service_type 
            ORDER BY paid_amount DESC";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenueByService[] = $row;
        }
    }
    
    // -------------------------------------------
    // 3. REVENUE BY TIME PERIOD (CHART DATA)
    // -------------------------------------------
    $chartConfig = getChartConfig($filterPeriod);
    $monthWhereClause = "WHERE amount IS NOT NULL" . $chartConfig['additionalWhere'];
    
    if ($filterService !== 'all') {
        $monthWhereClause .= " AND service_type = '" . $database->real_escape_string($filterService) . "'";
    }
    
    $sql = "SELECT 
                DATE_FORMAT(appodate, '{$chartConfig['groupBy']}') AS month_label,
                DATE_FORMAT(appodate, '{$chartConfig['sortBy']}') AS month_sort,
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS revenue,
                COUNT(*) AS appointments
            FROM appointment 
            $monthWhereClause
            GROUP BY DATE_FORMAT(appodate, '{$chartConfig['sortBy']}')
            ORDER BY month_sort ASC";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $revenueByMonth[] = $row;
            $chartLabels[] = $row['month_label'];
            $chartData[] = (float)$row['revenue'];
        }
    }
    
    // -------------------------------------------
    // 4. RECENT TRANSACTIONS
    // -------------------------------------------
    $sql = "SELECT 
                a.appoid, 
                a.apponum, 
                p.pname, 
                a.service_type, 
                a.appodate, 
                a.amount, 
                a.payment_status, 
                a.status
            FROM appointment a 
            LEFT JOIN patient p ON a.pid = p.pid
            $whereClause
            ORDER BY 
                CASE 
                    WHEN a.status = 'completed' THEN 1
                    WHEN a.status = 'approved' THEN 2
                    WHEN a.status = 'pending' THEN 3
                    ELSE 4
                END,
                a.appodate DESC 
            LIMIT 50";
    
    $result = $database->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentPayments[] = $row;
        }
    }
    
    // -------------------------------------------
    // 5. GET ALL SERVICE TYPES FOR FILTER
    // -------------------------------------------
    $result = $database->query("SELECT DISTINCT service_type FROM appointment WHERE service_type IS NOT NULL ORDER BY service_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row['service_type'];
        }
    }
    
} catch (Exception $e) {
    error_log("Revenue Page Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Management - Dr. Dental Clinic Care</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/revenue.css">
</head>
<body>

<!-- ==========================================
     SIDEBAR NAVIGATION
     ========================================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-icon">
            <i class="fas fa-tooth"></i>
        </div>
        <div class="logo-text">
            <h2>Dr. Dental Clinic Care</h2>
            <p>Admin Portal</p>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="appointment.php" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
            <a href="patient.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Patients</span>
            </a>
        </div>
        
        <!-- Reports Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Reports</div>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
            <a href="revenue.php" class="nav-item active">
                <i class="fas fa-dollar-sign"></i>
                <span>Revenue</span>
            </a>
        </div>
        
        <!-- Settings Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Settings</div>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</aside>

<!-- ==========================================
     MAIN CONTENT AREA
     ========================================== -->
<main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="page-title">
            <h1>Revenue Management</h1>
            <p>Track and analyze clinic revenue</p>
        </div>
        <div class="top-actions">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="content-container">
        <!-- ==========================================
             REVENUE STATISTICS CARDS
             ========================================== -->
        <div class="stats-grid">
            <!-- Total Revenue -->
            <div class="stat-card green">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                    </div>
                    <div class="stat-icon icon-green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            
            <!-- Paid Revenue -->
            <div class="stat-card blue">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Paid Revenue</div>
                        <div class="stat-value">₱<?php echo number_format($paidRevenue, 2); ?></div>
                    </div>
                    <div class="stat-icon icon-blue">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <!-- Pending Revenue -->
            <div class="stat-card orange">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Pending Revenue</div>
                        <div class="stat-value">₱<?php echo number_format($unpaidRevenue, 2); ?></div>
                    </div>
                    <div class="stat-icon icon-orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             FILTER BAR
             ========================================== -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Time Period</label>
                <select id="periodFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $filterPeriod === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $filterPeriod === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $filterPeriod === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $filterPeriod === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="year" <?php echo $filterPeriod === 'year' ? 'selected' : ''; ?>>This Year</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Service Type</label>
                <select id="serviceFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $filterService === 'all' ? 'selected' : ''; ?>>All Services</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo e($service); ?>" <?php echo $filterService === $service ? 'selected' : ''; ?>>
                            <?php echo e($service); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- ==========================================
             CHARTS AND TABLES
             ========================================== -->
        <div class="panels-grid">
            <!-- Revenue Trend Chart -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-chart-line"></i>
                        Revenue Trend
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Revenue by Service Table -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fas fa-tooth"></i>
                        Revenue by Service
                    </div>
                </div>
                <div class="table-container">
                    <table class="revenue-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Appointments</th>
                                <th>Paid</th>
                                <th>Unpaid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($revenueByService)): ?>
                                <?php foreach ($revenueByService as $service): ?>
                                    <tr>
                                        <td class="service-name"><?php echo e($service['service_type']); ?></td>
                                        <td><?php echo e($service['appointment_count']); ?></td>
                                        <td class="amount-paid">₱<?php echo number_format($service['paid_amount'], 2); ?></td>
                                        <td class="amount-unpaid">₱<?php echo number_format($service['unpaid_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-message">No revenue data available for selected filters</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==========================================
             RECENT TRANSACTIONS TABLE
             ========================================== -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fas fa-list"></i>
                    Recent Transactions
                    <span style="font-size:12px;color:#64748b;font-weight:400;margin-left:10px;">
                        (Showing <?php echo count($recentPayments); ?> transactions - Completed shown first)
                    </span>
                </div>
            </div>
            <div class="table-container">
                <table class="revenue-table">
                    <thead>
                        <tr>
                            <th>Appointment #</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentPayments)): ?>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr class="<?php echo ($payment['status'] === 'completed') ? 'completed-row' : ''; ?>">
                                    <td><strong>#<?php echo e($payment['apponum']); ?></strong></td>
                                    <td><?php echo e($payment['pname']); ?></td>
                                    <td><?php echo e($payment['service_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($payment['appodate'])); ?></td>
                                    <td class="<?php echo $payment['payment_status'] === 'paid' ? 'amount-paid' : 'amount-unpaid'; ?>">
                                        ₱<?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge payment-<?php echo e($payment['payment_status']); ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo e($payment['status']); ?>">
                                            <?php if ($payment['status'] === 'completed'): ?>
                                                <i class="fas fa-check-circle"></i> 
                                            <?php endif; ?>
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-message">No transactions found for selected filters</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ==========================================
     JAVASCRIPT
     ========================================== -->
<script>
/**
 * Toggle sidebar mini/full view
 */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('mini');
}

/**
 * Apply selected filters and reload page
 */
function applyFilters() {
    const period = document.getElementById('periodFilter').value;
    const service = document.getElementById('serviceFilter').value;
    window.location.href = `revenue.php?period=${period}&service=${service}`;
}

/**
 * Initialize Revenue Chart
 */
const chartLabels = <?php echo json_encode($chartLabels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>;
const chartData = <?php echo json_encode($chartData ?: [0, 0, 0, 0, 0, 0]); ?>;

const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Revenue (₱)',
                data: chartData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: false 
                },
                tooltip: {
                    backgroundColor: 'rgba(31, 41, 55, 0.95)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#6b7280',
                        callback: value => '₱' + value.toLocaleString()
                    },
                    grid: { 
                        color: '#f3f4f6' 
                    }
                },
                x: {
                    ticks: { 
                        color: '#6b7280' 
                    },
                    grid: { 
                        display: false 
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>