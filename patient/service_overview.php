<?php
// Service Overview Information
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
?>

<!-- Add this modal HTML before the closing </body> tag -->
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
                    <!-- Benefits will be inserted here -->
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

function openServiceModal(serviceName) {
    currentService = serviceName;
    const modal = document.getElementById('serviceOverviewModal');
    const overview = serviceOverviewData[serviceName];
    
    if (!overview) return;
    
    // Set service icon
    document.getElementById('modalServiceIcon').textContent = serviceIcons[serviceName] || '🦷';
    
    // Set service title
    document.getElementById('modalServiceTitle').textContent = serviceName;
    
    // Set service price
    const price = servicePrices[serviceName] || 0;
    document.getElementById('modalServicePrice').textContent = '₱' + price.toLocaleString();
    
    // Set description
    document.getElementById('modalDescription').textContent = overview.description;
    
    // Set benefits
    const benefitsList = document.getElementById('modalBenefits');
    benefitsList.innerHTML = '';
    overview.benefits.forEach(benefit => {
        const li = document.createElement('li');
        li.textContent = benefit;
        benefitsList.appendChild(li);
    });
    
    // Set duration and frequency
    document.getElementById('modalDuration').textContent = overview.duration;
    document.getElementById('modalFrequency').textContent = overview.frequency;
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeServiceModal() {
    const modal = document.getElementById('serviceOverviewModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function bookService() {
    window.location.href = 'booking.php?service=' + encodeURIComponent(currentService);
}

// Close modal when clicking outside
document.getElementById('serviceOverviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeServiceModal();
    }
});
</script>

<!-- Updated Service Card HTML Structure -->
<!-- Replace the service card anchor tag with this structure: -->
<?php /*
REPLACE THIS SECTION IN YOUR ORIGINAL CODE:

<a href="?service=<?php echo urlencode($service); ?>" class="service-card <?php echo ($selectedService == $service) ? 'active' : ''; ?>">
    ...existing content...
    <button type="button" class="quick-book-btn">
        <i class="fas fa-calendar-plus"></i> Book Now
    </button>
</a>

WITH THIS:
*/ ?>

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
        <button type="button" class="view-overview-btn" onclick="openServiceModal('<?php echo htmlspecialchars($service, ENT_QUOTES); ?>')">
            <i class="fas fa-info-circle"></i> Overview
        </button>
        <button type="button" class="quick-book-btn" onclick="window.location.href='?service=<?php echo urlencode($service); ?>'">
            <i class="fas fa-calendar-plus"></i> Book Now
        </button>
    </div>
</div>