<?php
// admin/index.php
session_start();
require_once '../connection.php'; // adjust path if needed

// --- AUTH ---
if (!isset($_SESSION['user']) || ($_SESSION['usertype'] ?? '') !== 'a') {
    header("Location: ../login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');

// initialize
$totalDoctors = $totalPatients = $newBookings = $todaySessions = 0;
$todayAppointments = $upcomingAppointments = [];
$nextPatient = null;
$doctorsAtWork = [];
$approvalRequests = [];
$chartMonths = [];
$chartConsult = [];
$chartProcedure = [];
$donutLabels = [];
$donutData = [];

try {
    // totals
    $r = $database->query("SELECT COUNT(*) AS c FROM doctor");
    if ($r) $totalDoctors = (int)$r->fetch_assoc()['c'];

    $r = $database->query("SELECT COUNT(*) AS c FROM patient");
    if ($r) $totalPatients = (int)$r->fetch_assoc()['c'];

    $r = $database->query("SELECT COUNT(*) AS c FROM appointment WHERE appodate >= CURDATE()");
    if ($r) $newBookings = (int)$r->fetch_assoc()['c'];

    $r = $database->query("SELECT COUNT(*) AS c FROM appointment WHERE DATE(appodate) = CURDATE()");
    if ($r) $todaySessions = (int)$r->fetch_assoc()['c'];

    // Today's appointments (limit 8)
    $sql = "
        SELECT a.appoid, p.pname, d.docname, s.title, a.appotime, a.appodate
        FROM appointment a
        LEFT JOIN patient p ON a.pid=p.pid
        LEFT JOIN doctor d ON a.docid=d.docid
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        WHERE DATE(a.appodate) = CURDATE()
        ORDER BY a.appotime ASC
        LIMIT 8
    ";
    $res = $database->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $todayAppointments[] = $row;
    }

    // Upcoming (next 7 days)
    $sql = "
        SELECT a.appoid, p.pname, d.docname, s.title, a.appodate
        FROM appointment a
        LEFT JOIN patient p ON a.pid=p.pid
        LEFT JOIN doctor d ON a.docid=d.docid
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        WHERE a.appodate BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY a.appodate ASC, a.appotime ASC
        LIMIT 6
    ";
    $res = $database->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $upcomingAppointments[] = $row;
    }

    // Next patient details (most recent upcoming or today)
    $r = $database->query("
        SELECT p.*, a.appodate, a.appotime, s.title
        FROM appointment a
        LEFT JOIN patient p ON a.pid=p.pid
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        WHERE a.appodate >= CURDATE()
        ORDER BY a.appodate ASC, a.appotime ASC
        LIMIT 1
    ");
    if ($r && $r->num_rows) $nextPatient = $r->fetch_assoc();

    // Doctors at work - sample: list doctors with count of today's patients
    $r = $database->query("
        SELECT d.docname, COUNT(a.appoid) AS patients, MIN(s.starttime) AS starttime, MAX(s.endtime) AS endtime
        FROM doctor d
        LEFT JOIN appointment a ON a.docid = d.docid AND DATE(a.appodate) = CURDATE()
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        GROUP BY d.docid
        ORDER BY patients DESC
        LIMIT 6
    ");
    if ($r) while ($row = $r->fetch_assoc()) $doctorsAtWork[] = $row;

    // Approval requests - sample: appointment requests with some status column - fallback to upcoming appointments if none
    // Adjust this query to your actual approval/status columns if present
    $r = $database->query("
        SELECT a.appoid, p.pname, s.title, a.appodate
        FROM appointment a
        LEFT JOIN patient p ON a.pid=p.pid
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        WHERE a.appodate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY a.appodate ASC
        LIMIT 6
    ");
    if ($r) while ($row = $r->fetch_assoc()) $approvalRequests[] = $row;

    // Chart data: get monthly counts for last 12 months split by a heuristic: if schedule.title contains 'Consult' -> consultation else procedure
    // This is a heuristic — adjust to your schema if you have explicit type column.
    $r = $database->query("
        SELECT DATE_FORMAT(appodate, '%b %Y') AS mon,
               SUM(CASE WHEN LOWER(s.title) LIKE '%consult%' THEN 1 ELSE 0 END) AS consultation,
               SUM(CASE WHEN LOWER(s.title) LIKE '%proc%' OR LOWER(s.title) LIKE '%scal%' OR LOWER(s.title) LIKE '%root%' THEN 1 ELSE 0 END) AS procedure
        FROM appointment a
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        WHERE a.appodate >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(appodate, '%Y-%m')
        ORDER BY MIN(appodate) ASC
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $chartMonths[] = $row['mon'];
            $chartConsult[] = (int)$row['consultation'];
            $chartProcedure[] = (int)$row['procedure'];
        }
    }

    // Donut: top treatments based on schedule.title grouping (top 4)
    $r = $database->query("
        SELECT s.title, COUNT(*) AS cnt
        FROM appointment a
        LEFT JOIN schedule s ON a.scheduleid=s.scheduleid
        GROUP BY s.title
        ORDER BY cnt DESC
        LIMIT 4
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $donutLabels[] = $row['title'];
            $donutData[] = (int)$row['cnt'];
        }
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

// helpers for safe outputs
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function fmtDate($d){ if(!$d) return ''; return date('Y.m.d', strtotime($d)); }
function fmtTime($t){ if(!$t) return ''; return date('g:i A', strtotime($t)); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dental Admin Dashboard</title>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Styles: replicate dark UI -->
<style>
:root{
    --bg:#0f1720;
    --card:#111317;
    --muted:#94a3b8;
    --accent:#2b6ef6;
    --panel:#16181a;
    --soft:#1f2326;
    --glass: rgba(255,255,255,0.03);
}
*{box-sizing:border-box}
body{
    margin:0;
    font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    background: #f6f7fb;
    color:#e6eef8;
}
.app-wrap{
    max-width:1250px;
    margin:34px auto;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 20px 40px rgba(2,6,23,0.35);
    background: linear-gradient(180deg,#0e1113 0%, #0b0c0d 100%);
    display:flex;
    min-height:650px;
}

/* SIDEBAR */
.sidebar{
    width:220px;
    background:linear-gradient(180deg, #0a0b0d, #0e1113);
    padding:28px 18px;
    display:flex;
    flex-direction:column;
    gap:18px;
    border-right: 1px solid rgba(255,255,255,0.02);
}
.brand{
    display:flex;
    align-items:center;
    gap:12px;
    color:#7dd3fc;
    font-weight:600;
    font-size:18px;
}
.brand .logo{
    width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:18px;
}
.menu{margin-top:10px;flex:1;display:flex;flex-direction:column;gap:6px}
.menu a{
    display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;color:var(--muted);text-decoration:none;font-weight:500;font-size:14px;
    transition:all .18s ease;
}
.menu a.active{background:#2f3a48;color:#fff;padding-left:14px;box-shadow: inset 4px 0px 0px rgba(59,130,246,.9);}
.menu a:hover{transform:translateX(6px);color:#fff}
.menu i{width:18px;text-align:center;font-size:16px}

/* bottom controls */
.sidebar .bottom{margin-top:auto;display:flex;flex-direction:column;gap:12px}
.small-link{display:flex;align-items:center;gap:12px;color:var(--muted);padding:10px;border-radius:8px;text-decoration:none}
.small-link:hover{color:#fff;transform:translateX(6px)}

/* MAIN PANEL */
.main {
    flex:1;padding:22px 30px;overflow:auto;
}

/* topbar */
.topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}
.search{
    width:520px;background:var(--soft);padding:10px 14px;border-radius:999px;display:flex;align-items:center;gap:12px;border:1px solid rgba(255,255,255,0.02)
}
.search input{background:transparent;border:0;outline:0;color:#cbd5e1;width:100%}
.top-actions{display:flex;align-items:center;gap:14px;font-size:14px;color:#cbd5e1}
.avatar{width:36px;height:36px;border-radius:999px;border:2px solid rgba(255,255,255,0.06);overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}

/* greeting + cards */
.greet{font-size:20px;font-weight:600;color:#e6eef8;margin-bottom:10px}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.card{
    background:linear-gradient(180deg, #15181a, #0f1315);
    padding:18px;border-radius:10px;border:1px solid rgba(255,255,255,0.02);
}
.card .k{font-size:12px;color:var(--muted);margin-bottom:8px}
.card .v{font-size:22px;font-weight:700;color:#fff}

/* content area layout */
.content-grid{display:grid;grid-template-columns:1fr 380px;gap:16px}

/* big panels */
.panel{background:linear-gradient(180deg,#0f1315,#0c0e10);padding:18px;border-radius:10px;border:1px solid rgba(255,255,255,0.02)}
.panel h3{margin:0 0 12px 0;color:#e6eef8}

/* chart area */
.charts{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.chart-box{height:260px;padding:12px;background:linear-gradient(180deg,#101214,#0b0d0f);border-radius:8px;}

/* bottom small panels */
.bottom-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px}

/* patient card */
.patient-card{display:flex;gap:12px;align-items:flex-start}
.patient-card .img{width:56px;height:56px;border-radius:8px;overflow:hidden}
.patient-card .meta{flex:1}
.meta h4{margin:0;font-size:15px}
.meta p{margin:4px 0 0;color:var(--muted);font-size:13px}

/* doctors list */
.doctors-list .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.02)}
.doctors-list .row:last-child{border-bottom:0}
.doctors-list .status{font-weight:600;font-size:13px;color:#10b981}

/* approvals */
.approval-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.02)}
.approval-row:last-child{border-bottom:0}
.icon-btn{width:34px;height:34px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;margin-left:6px}
.icon-btn.ok{background:rgba(16,185,129,0.12);color:#10b981}
.icon-btn.no{background:rgba(239,68,68,0.1);color:#ef4444}
.icon-btn.msg{background:rgba(99,102,241,0.06);color:#6366f1}

/* responsive */
@media (max-width:1100px){
    .search{width:320px}
    .content-grid{grid-template-columns:1fr}
    .charts{grid-template-columns:1fr}
    .cards{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:720px){
    .sidebar{display:none}
    .app-wrap{margin:10px}
}
</style>
</head>
<body>

<div class="app-wrap" role="application">
    <!-- SIDEBAR -->
    <aside class="sidebar" aria-label="Sidebar">
        <div class="brand">
            <div class="logo">🦷</div>
            <div>Smile360</div>
        </div>

        <nav class="menu" aria-label="Main navigation">
            <a href="index.php" class="active"><i class="fa fa-grid"></i> Dashboard</a>
            <a href="schedule.php"><i class="fa fa-calendar-alt"></i> Schedule</a>
            <a href="appointment.php"><i class="fa fa-calendar-check"></i> Appointments</a>
            <a href="patient.php"><i class="fa fa-users"></i> Patients</a>
            <a href="doctors.php"><i class="fa fa-user-md"></i> Doctors</a>
            <a href="#"><i class="fa fa-envelope"></i> Messages</a>
            <a href="#"><i class="fa fa-credit-card"></i> Payments</a>
        </nav>

        <div class="bottom">
            <a class="small-link" href="#"><i class="fa fa-gear"></i> Settings</a>
            <a class="small-link" href="#"><i class="fa fa-question-circle"></i> Support</a>
            <a class="small-link" href="../logout.php"><i class="fa fa-sign-out-alt"></i> Log Out</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main" role="main">
        <div class="topbar">
            <div style="display:flex;flex-direction:column">
                <div style="display:flex;align-items:center;gap:18px">
                    <div style="font-weight:600;font-size:18px">Dashboard</div>
                    <div style="color:var(--muted);font-size:13px">Welcome back, <?php echo $adminName; ?>!</div>
                </div>
                <div style="margin-top:8px;color:var(--muted);font-size:13px">Overview of the clinic performance</div>
            </div>

            <div style="display:flex;align-items:center;gap:14px">
                <div class="search" role="search" aria-label="Search">
                    <i class="fa fa-search" style="color:#93c5fd"></i>
                    <input placeholder="Search" aria-label="Search input">
                </div>

                <div class="top-actions">
                    <button title="Dark mode" style="background:transparent;border:0;color:#cbd5e1;font-size:18px"><i class="fa fa-moon"></i></button>
                    <div class="avatar" title="Profile">
                        <!-- fallback avatar -->
                        <img src="https://i.pravatar.cc/40?img=12" alt="avatar">
                    </div>
                </div>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="cards" role="region" aria-label="Summary cards">
            <div class="card">
                <div class="k">Total Patients</div>
                <div class="v"><?php echo e($totalPatients); ?></div>
                <div style="font-size:12px;color:var(--muted);margin-top:8px">Total active patients</div>
            </div>

            <div class="card">
                <div class="k">Consultation</div>
                <div class="v"><?php echo e(array_sum($chartConsult) ?: '—'); ?></div>
                <div style="font-size:12px;color:var(--muted);margin-top:8px">Last 12 months</div>
            </div>

            <div class="card">
                <div class="k">Procedure</div>
                <div class="v"><?php echo e(array_sum($chartProcedure) ?: '—'); ?></div>
                <div style="font-size:12px;color:var(--muted);margin-top:8px">Last 12 months</div>
            </div>

            <div class="card">
                <div class="k">Payment</div>
                <div class="v">¥<?php echo number_format($newBookings * 180); // dummy calc ?></div>
                <div style="font-size:12px;color:var(--muted);margin-top:8px">Estimated revenue</div>
            </div>
        </div>

        <!-- GRID -->
        <section class="content-grid" aria-label="Content">
            <!-- LEFT: charts + appointments -->
            <div>
                <div class="panel">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <h3>Appointments</h3>
                        <div style="color:var(--muted);font-size:13px">Weekly · Monthly · Yearly</div>
                    </div>

                    <div class="charts" style="margin-top:10px">
                        <div class="chart-box panel" style="padding:12px">
                            <canvas id="appointmentsChart" width="400" height="220" aria-label="Appointments chart"></canvas>
                        </div>
                        <div class="chart-box panel" style="padding:12px">
                            <canvas id="donutChart" width="200" height="200" aria-label="Top treatments"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Bottom row -->
                <div class="bottom-grid">
                    <div class="panel">
                        <h3>Next Patient Details</h3>
                        <?php if($nextPatient): ?>
                        <div class="patient-card">
                            <div class="img"><img src="<?php echo e($nextPatient['photo'] ?? 'https://i.pravatar.cc/80'); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:6px"></div>
                            <div class="meta">
                                <h4><?php echo e($nextPatient['pname']); ?></h4>
                                <p style="color:var(--muted)"><?php echo e($nextPatient['title'] ?? $nextPatient['title'] ?? 'Treatment'); ?></p>
                                <div style="margin-top:8px;color:var(--muted);font-size:13px">
                                    <div><strong>Patient Id:</strong> <?php echo e($nextPatient['pid'] ?? '—'); ?></div>
                                    <div><strong>Last Visit:</strong> <?php echo e(fmtDate($nextPatient['lastvisit'] ?? $nextPatient['appodate'] ?? null)); ?></div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="color:var(--muted)">No next patient scheduled.</div>
                        <?php endif; ?>
                    </div>

                    <div class="panel doctors-list">
                        <h3>Doctors at work</h3>
                        <?php if($doctorsAtWork): foreach($doctorsAtWork as $d): ?>
                        <div class="row">
                            <div>
                                <div style="font-weight:600"><?php echo e($d['docname']); ?></div>
                                <div style="color:var(--muted);font-size:13px"><?php echo e(($d['starttime'] ?? '') ? (fmtTime($d['starttime']).' - '.fmtTime($d['endtime'] ?? '')) : 'Schedule'); ?></div>
                            </div>
                            <div style="text-align:right">
                                <div style="font-weight:700"><?php echo (int)$d['patients']; ?> patients</div>
                                <div class="status">At Work</div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div style="color:var(--muted)">No doctor data.</div>
                        <?php endif; ?>
                    </div>

                    <div class="panel">
                        <h3>Approval requests</h3>
                        <?php if($approvalRequests): foreach($approvalRequests as $a): ?>
                        <div class="approval-row">
                            <div style="flex:1">
                                <div style="font-weight:600"><?php echo e($a['pname']); ?></div>
                                <div style="color:var(--muted);font-size:13px"><?php echo e($a['title'] ?? ''); ?> · <?php echo e(fmtDate($a['appodate'])); ?></div>
                            </div>
                            <div style="display:flex;align-items:center">
                                <div class="icon-btn no"><i class="fa fa-times"></i></div>
                                <div class="icon-btn ok"><i class="fa fa-check"></i></div>
                                <div class="icon-btn msg"><i class="fa fa-envelope"></i></div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div style="color:var(--muted)">No approval requests.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: upcoming list & top treatments legend -->
            <aside style="display:flex;flex-direction:column;gap:12px">
                <div class="panel">
                    <h3>Top Treatments</h3>
                    <div style="display:flex;gap:12px;align-items:center">
                        <div style="flex:1">
                            <?php if($donutLabels): foreach($donutLabels as $i=>$lab): ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.02)">
                                    <div style="display:flex;gap:10px;align-items:center">
                                        <span style="width:12px;height:12px;border-radius:3px;background:var(--accent);display:inline-block"></span>
                                        <div style="font-weight:600"><?php echo e($lab); ?></div>
                                    </div>
                                    <div style="color:var(--muted)"><?php echo e($donutData[$i]); ?></div>
                                </div>
                            <?php endforeach; else: ?>
                                <div style="color:var(--muted)">No treatment data.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h3>Upcoming Appointments</h3>
                    <?php if($upcomingAppointments): foreach($upcomingAppointments as $u): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.02)">
                            <div style="background:linear-gradient(135deg,#374151,#0f1720);padding:8px;border-radius:8px;text-align:center;width:56px">
                                <div style="font-weight:700"><?php echo date('d', strtotime($u['appodate'])); ?></div>
                                <div style="font-size:12px;color:var(--muted)"><?php echo date('M', strtotime($u['appodate'])); ?></div>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600"><?php echo e($u['title']); ?></div>
                                <div style="color:var(--muted);font-size:13px"><?php echo e($u['pname']); ?> · Dr. <?php echo e($u['docname']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div style="color:var(--muted)">No upcoming appointments.</div>
                    <?php endif; ?>
                </div>
            </aside>

        </section>
    </main>
</div>

<!-- Charts JS -->
<script>
const months = <?php echo json_encode($chartMonths ?: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']); ?>;
const consultData = <?php echo json_encode($chartConsult ?: array_fill(0,count($chartMonths ?: [1,2,3,4,5,6,7,8,9,10,11,12]),0)); ?>;
const procData = <?php echo json_encode($chartProcedure ?: array_fill(0,count($chartMonths ?: [1,2,3,4,5,6,7,8,9,10,11,12]),0)); ?>;
const donutLabels = <?php echo json_encode($donutLabels ?: ['Root Canal','Wisdom Tooth','Bleaching','Others']); ?>;
const donutData = <?php echo json_encode($donutData ?: [38,22,30,8]); ?>;

const ctx = document.getElementById('appointmentsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Consultation', data: consultData, backgroundColor: 'rgba(59,130,246,0.9)' },
            { label: 'Procedure', data: procData, backgroundColor: 'rgba(99,102,241,0.8)' }
        ]
    },
    options: {
        maintainAspectRatio:false,
        responsive:true,
        plugins:{legend:{position:'top',labels:{color:'#cbd5e1'}}},
        scales:{
            x:{ ticks:{color:'#cbd5e1'} , grid:{display:false}},
            y:{ ticks:{color:'#cbd5e1'}, grid:{color:'rgba(255,255,255,0.03)'} }
        }
    }
});

const dctx = document.getElementById('donutChart').getContext('2d');
new Chart(dctx, {
    type: 'doughnut',
    data: {
        labels: donutLabels,
        datasets: [{
            data: donutData,
            backgroundColor: ['#2563eb','#60a5fa','#93c5fd','#c7d2fe'],
            borderWidth:0
        }]
    },
    options: {
        maintainAspectRatio:false,
        responsive:true,
        plugins:{legend:{display:false}}
    }
});
</script>

</body>
</html>
