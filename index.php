<?php
require_once 'connection.php';

// Fetch clinic settings from database
$clinicSettings = [
    'name' => 'Dr. Dental Clinic Center',
    'email' => 'info@dentalcare.com',
    'phone' => '+63 123 456 7890',
    'address' => '123 Main Street, City, Philippines',
    'opening_hours' => 'Everyday 10:00 AM - 10:00 PM'
];

try {
    $result = $database->query("SELECT * FROM clinic_settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $clinicSettings = [
            'name' => $row['clinic_name'] ?? $clinicSettings['name'],
            'email' => $row['clinic_email'] ?? $clinicSettings['email'],
            'phone' => $row['clinic_phone'] ?? $clinicSettings['phone'],
            'address' => $row['clinic_address'] ?? $clinicSettings['address'],
            'opening_hours' => $row['opening_hours'] ?? $clinicSettings['opening_hours']
        ];
    }
} catch (Exception $e) {
    // Use default values if table doesn't exist yet
}

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($clinicSettings['name']); ?> - Your Smile is Our Priority</title>
    <link rel="stylesheet" href="/dental-clinic-appointment-system/css/index.css">
    <style>
        /* Additional styles for the new footer section */
        .info-footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 50px 20px 30px;
            margin-top: 0;
        }

        .info-footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            font-family: 'Brush Script MT', cursive;
            font-size: 2rem;
            margin-bottom: 20px;
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .footer-section p,
        .footer-section a {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
            margin: 10px 0;
            text-decoration: none;
            display: block;
        }

        .footer-section a:hover {
            color: #ffffff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .footer-section i {
            margin-right: 10px;
            color: #ffffff;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-links a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .footer-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 30px 0 20px;
        }

        .footer-bottom {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .footer-bottom p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="navbar">
        <div class="brand">
            <img src="img/images.png" alt="<?php echo e($clinicSettings['name']); ?> Logo" class="logo">
            <span class="clinic-name"><?php echo e($clinicSettings['name']); ?></span>
        </div>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="services.html">Services</a>
            <a href="about.html">About</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-text">
            <h1>Your best life<br>begins with a Smile</h1>
            <p>Join us at <?php echo e($clinicSettings['name']); ?>, where we prioritize your oral health and well-being. Our team of experienced professionals is dedicated to providing you with the highest quality dental care in a comfortable and welcoming environment.</p>
            <a href="booking.php" class="cta-btn">BOOK NOW</a>
        </div>

        <div class="slider">
            <div class="slide active">
                <img src="img/doctor.jpg" alt="Professional Dentist">
            </div>
            <div class="slide">
                <img src="img/chart.jpg" alt="Dental Care">
            </div>
            <div class="slide">
                <img src="img/card.jpg" alt="Modern Clinic">
            </div>
            <div class="slider-dots">
                <span class="dot active" onclick="goToSlide(0)"></span>
                <span class="dot" onclick="goToSlide(1)"></span>
                <span class="dot" onclick="goToSlide(2)"></span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="section-header">
            <h2>Why Choose Us?</h2>
            <p>Experience exceptional dental care with our dedicated team</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🦷</div>
                <h3>Expert Care</h3>
                <p>Experienced dental professionals committed to your oral health</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⏰</div>
                <h3>Flexible Hours</h3>
                <p>Convenient appointment times to fit your busy schedule</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3>Modern Technology</h3>
                <p>State-of-the-art equipment for the best treatment outcomes</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">😊</div>
                <h3>Comfortable Environment</h3>
                <p>Relaxing atmosphere to ease your dental anxiety</p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>10,000+</h3>
                <p>Happy Patients</p>
            </div>
            <div class="stat-item">
                <h3>15+</h3>
                <p>Years Experience</p>
            </div>
            <div class="stat-item">
                <h3>15</h3>
                <p>Expert Dentists</p>
            </div>
        </div>
    </section>

    <!-- Info Footer Section (New) -->
    <footer class="info-footer" style="margin-top: 0;">
        <div class="info-footer-content">
            <!-- Contact Us Section -->
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="📍">📍</i> <?php echo e($clinicSettings['address']); ?></p>
                <p><i class="📞">📞</i> Call <?php echo e($clinicSettings['phone']); ?></p>
                <p><i class="✉️">✉️</i> <?php echo e($clinicSettings['email']); ?></p>
            </div>

            <!-- About Section -->
            <div class="footer-section">
                <h3><?php echo e($clinicSettings['name']); ?></h3>
                <p>We Make Your Smile Happy Again.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook">f</a>
                    <a href="#" aria-label="Twitter">🐦</a>
                    <a href="#" aria-label="LinkedIn">in</a>
                    <a href="#" aria-label="Instagram">📷</a>
                    <a href="#" aria-label="Pinterest">P</a>
                </div>
            </div>

            <!-- Opening Hours Section -->
            <div class="footer-section">
                <h3>Opening Hours</h3>
                <p><?php echo e($clinicSettings['opening_hours']); ?></p>
            </div>
        </div>

        <hr class="footer-divider">

        <!-- Copyright Section -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> All Rights Reserved By <?php echo e($clinicSettings['name']); ?></p>
            <p>Developer : Christian Pogi</p>
        </div>
    </footer>

    <script>
        // Slider functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) slide.classList.add('active');
            });
            dots.forEach((dot, i) => {
                dot.classList.remove('active');
                if (i === index) dot.classList.add('active');
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }

        function goToSlide(index) {
            currentSlide = index;
            showSlide(currentSlide);
        }

        // Auto slide
        setInterval(nextSlide, 4000);
    </script>
</body>
</html>