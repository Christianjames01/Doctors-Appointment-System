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
    $userprofile = $userfetch["profile_picture"] ?? null;
} else {
    header("location: ../login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Service prices
$service_prices = [
    'General Checkup' => 500,
    'Teeth Cleaning' => 800,
    'Teeth Whitening' => 3000,
    'Tooth Extraction' => 1500,
    'Dental Filling' => 1200,
    'Root Canal Treatment' => 5000,
    'Braces Consultation' => 1000,
    'Dental Crown' => 4000,
    'Dental Bridge' => 6000,
    'Dental Implant' => 15000,
    'Gum Treatment' => 2000,
    'Emergency Dental Care' => 2500,
    'Pediatric Dentistry' => 800,
    'Orthodontics' => 3500,
    'Cosmetic Dentistry' => 4500
];

// Service overview information
$service_overview = [
    'General Checkup' => [
        'description' => 'A comprehensive oral examination to assess your overall dental health. Our dentists will check for cavities, gum disease, oral cancer signs, and other dental issues.',
        'benefits' => [
            'Early detection of dental problems',
            'Prevention of serious dental issues',
            'Professional cleaning and advice',
            'X-rays if necessary',
            'Personalized treatment plan'
        ],
        'duration' => '30-45 minutes',
        'frequency' => 'Every 6 months'
    ],
    'Teeth Cleaning' => [
        'description' => 'Professional dental cleaning to remove plaque, tartar, and stains from your teeth. Includes polishing and fluoride treatment for optimal oral health.',
        'benefits' => [
            'Removes plaque and tartar buildup',
            'Prevents gum disease and cavities',
            'Freshens breath',
            'Brightens your smile',
            'Reduces risk of tooth decay'
        ],
        'duration' => '45-60 minutes',
        'frequency' => 'Every 6 months'
    ],
    'Teeth Whitening' => [
        'description' => 'Professional teeth whitening treatment to brighten your smile. Uses safe, effective bleaching agents to remove stains and discoloration.',
        'benefits' => [
            'Noticeably whiter teeth (3-8 shades)',
            'Safe and effective results',
            'Long-lasting whitening effect',
            'Boosts confidence',
            'Professional supervision'
        ],
        'duration' => '60-90 minutes',
        'frequency' => 'Every 1-2 years'
    ],
    'Tooth Extraction' => [
        'description' => 'Safe removal of damaged, decayed, or problematic teeth. Performed with local anesthesia to ensure your comfort throughout the procedure.',
        'benefits' => [
            'Relief from tooth pain',
            'Prevents infection spread',
            'Makes room for orthodontic treatment',
            'Removes damaged teeth',
            'Quick recovery time'
        ],
        'duration' => '20-40 minutes',
        'frequency' => 'As needed'
    ],
    'Dental Filling' => [
        'description' => 'Restoration of teeth damaged by decay using composite materials that match your natural tooth color. Preserves tooth structure and function.',
        'benefits' => [
            'Stops cavity progression',
            'Restores tooth function',
            'Natural-looking results',
            'Prevents further decay',
            'Strengthens damaged teeth'
        ],
        'duration' => '30-60 minutes',
        'frequency' => 'As needed'
    ],
    'Root Canal Treatment' => [
        'description' => 'Advanced treatment to save infected or damaged teeth. Removes infected pulp, cleans the canal, and seals it to prevent further infection.',
        'benefits' => [
            'Saves your natural tooth',
            'Eliminates severe tooth pain',
            'Prevents abscess formation',
            'Restores normal function',
            'Avoids tooth extraction'
        ],
        'duration' => '90-120 minutes',
        'frequency' => 'As needed'
    ],
    'Braces Consultation' => [
        'description' => 'Comprehensive orthodontic evaluation to assess your teeth alignment and bite. Includes discussion of treatment options and personalized recommendations.',
        'benefits' => [
            'Expert orthodontic assessment',
            'Treatment plan customization',
            'Cost estimate provided',
            'Timeline discussion',
            'All questions answered'
        ],
        'duration' => '45-60 minutes',
        'frequency' => 'One-time'
    ],
    'Dental Crown' => [
        'description' => 'Custom-made cap that covers a damaged tooth to restore its shape, size, and strength. Made from durable materials for long-lasting results.',
        'benefits' => [
            'Protects weak or damaged teeth',
            'Restores tooth function',
            'Natural appearance',
            'Long-lasting solution',
            'Improves tooth strength'
        ],
        'duration' => '2 visits (1-2 hours each)',
        'frequency' => 'Lasts 10-15 years'
    ],
    'Dental Bridge' => [
        'description' => 'Fixed prosthetic device to replace one or more missing teeth. Bridges the gap using crowns on adjacent teeth for support.',
        'benefits' => [
            'Restores your smile',
            'Maintains face shape',
            'Distributes bite forces properly',
            'Prevents teeth shifting',
            'Improves chewing ability'
        ],
        'duration' => '2-3 visits',
        'frequency' => 'Lasts 5-15 years'
    ],
    'Dental Implant' => [
        'description' => 'Permanent tooth replacement solution using titanium posts surgically placed in the jawbone. Topped with natural-looking crowns.',
        'benefits' => [
            'Permanent tooth replacement',
            'Looks and feels natural',
            'Prevents bone loss',
            'No impact on adjacent teeth',
            'High success rate (95%+)'
        ],
        'duration' => '3-6 months (multiple visits)',
        'frequency' => 'Lifetime solution'
    ],
    'Gum Treatment' => [
        'description' => 'Treatment for gum disease including deep cleaning, scaling, and root planing. Addresses inflammation and infection to restore gum health.',
        'benefits' => [
            'Treats gum disease',
            'Prevents tooth loss',
            'Reduces inflammation',
            'Eliminates bad breath',
            'Improves overall health'
        ],
        'duration' => '60-90 minutes',
        'frequency' => 'As prescribed'
    ],
    'Emergency Dental Care' => [
        'description' => 'Immediate treatment for urgent dental problems including severe pain, trauma, infections, or lost teeth. Available for dental emergencies.',
        'benefits' => [
            'Fast pain relief',
            'Prevents complications',
            'Same-day treatment',
            'Expert emergency care',
            'Saves damaged teeth'
        ],
        'duration' => 'Varies (30-90 minutes)',
        'frequency' => 'As needed'
    ],
    'Pediatric Dentistry' => [
        'description' => 'Specialized dental care for children from infancy through teenage years. Focuses on prevention, education, and gentle treatment.',
        'benefits' => [
            'Child-friendly environment',
            'Preventive care focus',
            'Early problem detection',
            'Builds good dental habits',
            'Reduces dental anxiety'
        ],
        'duration' => '30-45 minutes',
        'frequency' => 'Every 6 months'
    ],
    'Orthodontics' => [
        'description' => 'Comprehensive treatment to correct misaligned teeth and bite problems using braces or aligners. Improves both function and aesthetics.',
        'benefits' => [
            'Straightens teeth',
            'Improves bite alignment',
            'Enhances facial aesthetics',
            'Easier teeth cleaning',
            'Boosts self-confidence'
        ],
        'duration' => '12-24 months',
        'frequency' => 'One-time treatment'
    ],
    'Cosmetic Dentistry' => [
        'description' => 'Aesthetic dental procedures to enhance your smile including veneers, bonding, and smile makeovers. Combines multiple treatments for optimal results.',
        'benefits' => [
            'Complete smile transformation',
            'Corrects multiple aesthetic issues',
            'Customized to your goals',
            'Long-lasting results',
            'Dramatically improves appearance'
        ],
        'duration' => 'Multiple visits',
        'frequency' => 'Varies by treatment'
    ]
];

// Handle direct service booking
if(isset($_GET['service']) && !empty($_GET['service'])){
    $selected_service = $database->real_escape_string($_GET['service']);
    
    // Redirect to booking page with service pre-selected
    header("Location: booking.php?service=" . urlencode($selected_service));
    exit();
}

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
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/schedule.css">
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/service-overview-modal.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="nav-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
           <div class="logo-container">
    <div class="logo">
        <img src="/dental-clinic-appointment-system/img/images.png" alt="Dr. Dental Clinic Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
    </div>
    <span class="logo-text">Dr. Dental Clinic</span>
</div>
        </div>
        <div class="user-profile-nav">
            <div class="user-avatar-nav">
                <?php if (!empty($userprofile) && file_exists('../' . $userprofile)): ?>
                    <img src="../<?php echo htmlspecialchars($userprofile); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo htmlspecialchars(strtoupper(substr($username, 0, 2))); ?>
                <?php endif; ?>
            </div>
            <div class="user-info-nav">
                <h4><?php echo htmlspecialchars(substr($username, 0, 15)); ?></h4>
                <p>Patient</p>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-menu">
            <div class="nav-section-title">MAIN MENU</div>
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link active">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Available Sessions</span>
                </a>
            </div>
            
            <div class="nav-section-title">MY APPOINTMENTS</div>
            <div class="nav-item">
                <a href="booking.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Book Appointment</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="appointment-history.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Appointment History</span>
                </a>
            </div>
            
            <div class="nav-section-title">ACCOUNT</div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>

        <div class="logout-section">
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <h1>Available Services & Sessions 📅</h1>
                    <p>Choose a service and book your dental appointment</p>
                </div>
                <div class="header-right">
                    <div class="date-label">Today's Date</div>
                    <div class="date-value">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('F j, Y'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="services-section">
            <h2 class="section-title">🦷 Choose a Dental Service</h2>
            <p class="section-subtitle">Select a service to book your appointment and view pricing</p>
            
         <!-- STEP 1: Replace your services grid section with this -->
<div class="services-grid">
    <?php foreach($service_prices as $service => $price): ?>
        <div class="service-card <?php echo ($selectedService == $service) ? 'active' : ''; ?>">
            <span class="service-icon">
                <?php
                $icons = [
                    'General Checkup' => '🔍',
                    'Teeth Cleaning' => '✨',
                    'Teeth Whitening' => '💎',
                    'Tooth Extraction' => '🦷',
                    'Dental Filling' => '🔧',
                    'Root Canal Treatment' => '🏥',
                    'Braces Consultation' => '🔩',
                    'Dental Crown' => '👑',
                    'Dental Bridge' => '🌉',
                    'Dental Implant' => '⚙️',
                    'Gum Treatment' => '🌸',
                    'Emergency Dental Care' => '🚨',
                    'Pediatric Dentistry' => '👶',
                    'Orthodontics' => '😁',
                    'Cosmetic Dentistry' => '💄'
                ];
                echo $icons[$service] ?? '🦷';
                ?>
            </span>
            <span class="service-name"><?php echo htmlspecialchars($service); ?></span>
            <span class="service-price">₱<?php echo number_format($price); ?></span>
            
            <div class="service-card-actions">
                <button type="button" 
                        class="view-overview-btn" 
                        data-service-name="<?php echo htmlspecialchars($service); ?>">
                    <i class="fas fa-info-circle"></i> Overview
                </button>
                <button type="button" 
                        class="quick-book-btn" 
                        onclick="window.location.href='?service=<?php echo urlencode($service); ?>'">
                    <i class="fas fa-calendar-plus"></i> Book Now
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- STEP 2: Replace ALL your existing modal-related JavaScript with this -->
<script>
// Service overview data from PHP
const serviceOverviewData = <?php echo json_encode($service_overview); ?>;
const servicePrices = <?php echo json_encode($service_prices); ?>;
const serviceIcons = {
    'General Checkup': '🔍',
    'Teeth Cleaning': '✨',
    'Teeth Whitening': '💎',
    'Tooth Extraction': '🦷',
    'Dental Filling': '🔧',
    'Root Canal Treatment': '🏥',
    'Braces Consultation': '🔩',
    'Dental Crown': '👑',
    'Dental Bridge': '🌉',
    'Dental Implant': '⚙️',
    'Gum Treatment': '🌸',
    'Emergency Dental Care': '🚨',
    'Pediatric Dentistry': '👶',
    'Orthodontics': '😁',
    'Cosmetic Dentistry': '💄'
};

let currentService = '';

// Initialize modal functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing modal functionality...');
    
    // Attach event listeners to all overview buttons
    const overviewButtons = document.querySelectorAll('.view-overview-btn');
    console.log('Found', overviewButtons.length, 'overview buttons');
    
    overviewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const serviceName = this.getAttribute('data-service-name');
            console.log('Button clicked for service:', serviceName);
            
            if (serviceName) {
                openServiceModal(serviceName);
            } else {
                console.error('No service name found on button');
            }
        });
    });
    
    // Close modal when clicking outside
    const modal = document.getElementById('serviceOverviewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeServiceModal();
            }
        });
    }
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeServiceModal();
        }
    });
});

function openServiceModal(serviceName) {
    console.log('Opening modal for:', serviceName);
    
    currentService = serviceName;
    const modal = document.getElementById('serviceOverviewModal');
    
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }
    
    const overview = serviceOverviewData[serviceName];
    
    if (!overview) {
        console.error('No overview data found for:', serviceName);
        return;
    }
    
    // Update modal content
    const iconElement = document.getElementById('modalServiceIcon');
    const titleElement = document.getElementById('modalServiceTitle');
    const priceElement = document.getElementById('modalServicePrice');
    const descElement = document.getElementById('modalDescription');
    const benefitsElement = document.getElementById('modalBenefits');
    const durationElement = document.getElementById('modalDuration');
    const frequencyElement = document.getElementById('modalFrequency');
    
    // Check if all elements exist
    if (!iconElement || !titleElement || !priceElement || !descElement || 
        !benefitsElement || !durationElement || !frequencyElement) {
        console.error('Some modal elements are missing!');
        return;
    }
    
    // Set content
    iconElement.textContent = serviceIcons[serviceName] || '🦷';
    titleElement.textContent = serviceName;
    
    const price = servicePrices[serviceName] || 0;
    priceElement.textContent = '₱' + price.toLocaleString();
    
    descElement.textContent = overview.description;
    
    // Clear and populate benefits
    benefitsElement.innerHTML = '';
    overview.benefits.forEach(function(benefit) {
        const li = document.createElement('li');
        li.textContent = benefit;
        benefitsElement.appendChild(li);
    });
    
    durationElement.textContent = overview.duration;
    frequencyElement.textContent = overview.frequency;
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    console.log('Modal opened successfully');
}

function closeServiceModal() {
    console.log('Closing modal');
    const modal = document.getElementById('serviceOverviewModal');
    
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function bookService() {
    if (currentService) {
        window.location.href = 'booking.php?service=' + encodeURIComponent(currentService);
    }
}

// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (sidebar && menuToggle) {
        const isClickInside = sidebar.contains(event.target) || menuToggle.contains(event.target);
        
        if (!isClickInside && window.innerWidth <= 1024) {
            sidebar.classList.remove('active');
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    }
});
</script>
<!-- SERVICE OVERVIEW MODAL -->
<!-- Place this RIGHT BEFORE the closing </body> tag in schedule.php -->

<!-- Service Overview Modal -->
<div id="serviceOverviewModal" class="service-overview-modal">
    <div class="modal-content">
        <div class="modal-header">
            <button class="modal-close" onclick="closeServiceModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-title-wrapper">
                <span class="modal-service-icon" id="modalServiceIcon">🦷</span>
                <h2 class="modal-service-title" id="modalServiceTitle">Service Name</h2>
            </div>
            <div class="modal-service-price" id="modalServicePrice">₱0</div>
        </div>
        
        <div class="modal-body">
            <div class="overview-section">
                <h3 class="overview-heading">
                    <i class="fas fa-info-circle"></i>
                    Description
                </h3>
                <p class="overview-text" id="modalDescription">Service description</p>
            </div>
            
            <div class="overview-section">
                <h3 class="overview-heading">
                    <i class="fas fa-check-circle"></i>
                    Benefits
                </h3>
                <ul class="benefits-list" id="modalBenefits">
                    <!-- Benefits will be populated here -->
                </ul>
            </div>
            
            <div class="overview-section">
                <h3 class="overview-heading">
                    <i class="fas fa-clock"></i>
                    Time & Frequency
                </h3>
                <div class="duration-info">
                    <div class="duration-item">
                        <div class="duration-label">Duration</div>
                        <div class="duration-value" id="modalDuration">-</div>
                    </div>
                    <div class="duration-item">
                        <div class="duration-label">Recommended</div>
                        <div class="duration-value" id="modalFrequency">-</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="modal-cancel-btn" onclick="closeServiceModal()">
                <i class="fas fa-times"></i>
                Close
            </button>
            <button class="modal-book-btn" id="modalBookBtn" onclick="bookService()">
                <i class="fas fa-calendar-plus"></i>
                Book Appointment
            </button>
        </div>
    </div>
</div>

</body>
</html>