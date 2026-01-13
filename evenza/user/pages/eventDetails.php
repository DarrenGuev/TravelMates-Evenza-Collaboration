<?php
session_start();
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

function getEventImagePath($imagePath) {
    $imageDir = '../../assets/images/event_images/';
    $placeholder = $imageDir . 'placeholder.jpg';
    
    if (empty($imagePath)) {
        return $placeholder;
    }
    
    $imagePath = ltrim($imagePath, '/\\');
    
    if (strpos($imagePath, '../../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../../assets/images/event_images/'));
    }
    if (strpos($imagePath, '../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../assets/images/event_images/'));
    }
    if (strpos($imagePath, 'assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('assets/images/event_images/'));
    }
    
    $filename = basename($imagePath);
    $filename = str_replace(['/', '\\'], '', $filename);
    $imagePath = $imageDir . $filename;
    
    $fullPath = realpath(__DIR__ . '/' . $imagePath);
    if ($fullPath && file_exists($fullPath)) {
        return $imagePath;
    }
    
    return $placeholder;
}

$event = null;
$eventImagePath = 'assets/images/event_images/placeholder.jpg';

if ($eventId > 0) {
    $query = "SELECT * FROM events WHERE eventId = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($event) {
            $eventImagePath = getEventImagePath($event['imagePath']);
            
            if (stripos($event['title'], 'wine') !== false || stripos($event['title'], 'tasting') !== false) {
                if (file_exists(__DIR__ . '/../../assets/images/event_images/wineTasting.jpg')) {
                    $eventImagePath = '../../assets/images/event_images/wineTasting.jpg';
                }
            }
            
            if (isset($event['eventDate']) && !empty($event['eventDate'])) {
                $eventDate = new DateTime($event['eventDate']);
                $event['formattedDate'] = $eventDate->format('F j, Y');
            } else {
                $event['formattedDate'] = 'Date TBA';
            }
            
            if (empty($event['venueAddress'])) {
                $event['venueAddress'] = 'Ambulong, Tanauan City, Batangas';
            }
        }
    } else {
        $event = null;
    }
}

function getPackageFeatures($category, $tier, $eventTitle = '') {
    $category = strtolower(trim($category ?? ''));
    $eventTitle = strtolower(trim($eventTitle ?? ''));
    
    if (stripos($eventTitle, 'art') !== false || stripos($eventTitle, 'exhibition') !== false || 
        stripos($eventTitle, 'auction') !== false || stripos($eventTitle, 'wine') !== false || 
        stripos($eventTitle, 'tasting') !== false || stripos($category, 'art') !== false || 
        stripos($category, 'exhibition') !== false || stripos($category, 'auction') !== false ||
        stripos($category, 'wine') !== false || stripos($category, 'tasting') !== false ||
        stripos($category, 'premium') !== false) {
        $category = 'premium';
    } elseif (stripos($category, 'business') !== false || stripos($category, 'conference') !== false) {
        $category = 'business';
    } elseif (stripos($category, 'wedding') !== false || stripos($category, 'weddings') !== false) {
        $category = 'weddings';
    } elseif (stripos($category, 'workshop') !== false || stripos($category, 'workshops') !== false || 
              stripos($category, 'seminar') !== false || stripos($category, 'training') !== false) {
        $category = 'workshops';
    } elseif (stripos($category, 'social') !== false || stripos($category, 'socials') !== false || 
              stripos($category, 'gala') !== false) {
        $category = 'socials';
    } else {
        $category = 'business';
    }
    
    $tier = strtolower($tier);
    
    $features = [
        'business' => [
            'bronze' => [
                'Event access',
                'High-speed Wi-Fi'
            ],
            'silver' => [
                'Event access',
                'High-speed Wi-Fi',
                'Projector Access',
                'Coffee/Tea Station'
            ],
            'gold' => [
                'Event access',
                'High-speed Wi-Fi',
                'Projector Access',
                'Coffee/Tea Station',
                'VIP lounge access',
                'Priority Seating',
                'Premium Catering'
            ]
        ],
        'weddings' => [
            'bronze' => [
                'Venue rental',
                'Floral Arrangements'
            ],
            'silver' => [
                'Venue rental',
                'Floral Arrangements',
                'Champagne Toast',
                'Standard catering'
            ],
            'gold' => [
                'Venue rental',
                'Floral Arrangements',
                'Champagne Toast',
                'Bridal Suite Access',
                'Premium open bar',
                'Priority Seating',
                'Premium Catering',
                'Professional photography'
            ]
        ],
        'workshops' => [
            'bronze' => [
                'Event access',
                'Materials & Kits',
                'Certificate of Completion'
            ],
            'silver' => [
                'Event access',
                'Materials & Kits',
                'Certificate of Completion',
                'Lunch Buffet'
            ],
            'gold' => [
                'Event access',
                'Materials & Kits',
                'Certificate of Completion',
                'Lunch Buffet',
                'VIP lounge access',
                'Priority Seating',
                'Premium Catering',
                'Private speaker Q&A'
            ]
        ],
        'socials' => [
            'bronze' => [
                'Event access',
                'Party Decor'
            ],
            'silver' => [
                'Event access',
                'Party Decor',
                'Music/DJ Setup',
                'Finger Food Buffet'
            ],
            'gold' => [
                'Event access',
                'Party Decor',
                'Music/DJ Setup',
                'Finger Food Buffet',
                'VIP event access',
                'Premium open bar',
                'Priority Seating',
                'Premium Catering',
                'VIP lounge access'
            ]
        ],
        'premium' => [
            'bronze' => [
                'Event access',
                'Finger Food Buffet'
            ],
            'silver' => [
                'Event access',
                'Finger Food Buffet',
                'Premium open bar',
                'VIP lounge access'
            ],
            'gold' => [
                'Event access',
                'Finger Food Buffet',
                'Premium open bar',
                'VIP lounge access',
                'VIP event access',
                'Priority Seating',
                'Premium Catering',
                'Private viewing access'
            ]
        ]
    ];
    
    return $features[$category][$tier] ?? $features['business'][$tier] ?? [];
}

$packages = [];
$packageMap = []; // ensure only one entry per tier
$packagesQuery = "SELECT packageId, packageName, price FROM packages ORDER BY packageId DESC";
$packagesResult = mysqli_query($conn, $packagesQuery);
if ($packagesResult) {
    while ($row = mysqli_fetch_assoc($packagesResult)) {
        $tier = strtolower(str_replace(' Package', '', $row['packageName']));
        if (!isset($packageMap[$tier])) {
            $packageMap[$tier] = [
                'id' => $row['packageId'],
                'name' => $row['packageName'],
                'tier' => $tier,
                'price' => floatval($row['price'])
            ];
        }
    }
    mysqli_free_result($packagesResult);
}

// Normalize order Bronze, Silver, Gold
$orderedTiers = ['bronze', 'silver', 'gold'];
foreach ($orderedTiers as $t) {
    if (isset($packageMap[$t])) {
        $packages[] = $packageMap[$t];
    }
}

// Fetch per-event package inclusions
$packageInclusions = [
    'bronze' => [],
    'silver' => [],
    'gold'   => []
];
$incQuery = "SELECT packageTier, inclusionText FROM package_inclusions WHERE eventId = ? ORDER BY displayOrder ASC, inclusionId ASC";
$incStmt = mysqli_prepare($conn, $incQuery);
if ($incStmt) {
    mysqli_stmt_bind_param($incStmt, "i", $eventId);
    mysqli_stmt_execute($incStmt);
    $incResult = mysqli_stmt_get_result($incStmt);
    while ($incRow = mysqli_fetch_assoc($incResult)) {
        $tierKey = strtolower($incRow['packageTier']);
        if (isset($packageInclusions[$tierKey])) {
            $packageInclusions[$tierKey][] = $incRow['inclusionText'];
        }
    }
    mysqli_stmt_close($incStmt);
}

if (!$event) {
    header('Location: events.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .pricing-section {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .packages-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .package-card {
            flex: 1;
            min-width: 200px;
            border: 2px solid;
            border-radius: 20px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }
        .package-card[data-package-tier="bronze"] {
            background: linear-gradient(135deg, #B8956A 0%, #9A7A52 100%);
            border: 2px solid #8B6A42;
            color: white;
            box-shadow: 0 4px 12px rgba(184, 149, 106, 0.3);
        }
        .package-card[data-package-tier="silver"] {
            background: #E8E8E8;
            border: 1px solid #CCCCCC;
            color: #2C2C2C;
            box-shadow: 0 4px 12px rgba(212, 212, 212, 0.3);
        }
        .package-card[data-package-tier="gold"] {
            background: linear-gradient(135deg, #F4D03F 0%, #D4AF37 100%);
            border: 2px solid #C4A027;
            color: #2C2C2C;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }
        .package-card[data-package-tier="bronze"]:hover {
            background: linear-gradient(135deg, #C4A575 0%, #A8855F 100%);
            border: 2px solid #8B6A42;
            box-shadow: 0 6px 20px rgba(184, 149, 106, 0.4);
            transform: translateY(-2px);
        }
        .package-card[data-package-tier="silver"]:hover {
            background: #F0F0F0;
            border: none;
            box-shadow: 0 6px 20px rgba(212, 212, 212, 0.4);
            transform: translateY(-2px);
        }
        .package-card[data-package-tier="gold"]:hover {
            background: linear-gradient(135deg, #F8DC5F 0%, #E4C247 100%);
            border: 2px solid #C4A027;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
            transform: translateY(-2px);
        }
        .package-header {
            display: flex;
            flex-direction: column;
            text-align: center;
            justify-content: center;
        }
        .package-name {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            margin-bottom: 0.75rem;
        }
        .package-card[data-package-tier="bronze"] .package-name,
        .package-card[data-package-tier="bronze"] .package-price {
            color: white;
        }
        .package-card[data-package-tier="silver"] .package-name,
        .package-card[data-package-tier="silver"] .package-price,
        .package-card[data-package-tier="gold"] .package-name,
        .package-card[data-package-tier="gold"] .package-price {
            color: #2C2C2C;
        }
        .package-price {
            font-size: 1.3rem;
            font-weight: 700;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin-top: auto;
        }
        .package-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        .package-modal-overlay.show {
            display: flex;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .package-modal {
            background-color: #FFFFFF;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        @keyframes popIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        .package-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(74, 93, 74, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        .package-modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #1A1A1A;
            margin: 0;
        }
        .package-modal-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4A5D4A;
            font-family: 'Playfair Display', serif;
        }
        .package-modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 2rem;
            color: #4A5D4A;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            line-height: 1;
            padding: 0;
        }
        .package-modal-close:hover {
            background-color: rgba(74, 93, 74, 0.1);
            color: #1A1A1A;
            transform: rotate(90deg);
        }
        .package-modal-body {
            padding: 1.5rem;
        }
        .package-modal-features {
            margin-bottom: 2rem;
        }
        .package-modal-features h6 {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 600;
            color: #1A1A1A;
            margin-bottom: 1rem;
        }
        .modal-feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .modal-feature-list li {
            padding: 0.75rem 0;
            color: rgba(26, 26, 26, 0.8);
            font-size: 0.95rem;
            position: relative;
            padding-left: 2rem;
            border-bottom: 1px solid rgba(74, 93, 74, 0.08);
        }
        .modal-feature-list li:last-child {
            border-bottom: none;
        }
        .modal-feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4A5D4A;
            font-weight: 600;
            font-size: 1rem;
        }
        @media (max-width: 768px) {
            .packages-container {
                flex-direction: column;
            }
            .package-card {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'events'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="event-details-section py-5 mt-5">
        <div class="container">
            <div aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($event['title']); ?></li>
                </ol>
            </div>

            <div class="event-details-layout">
                <div class="event-main-content">
                    <div class="event-detail-image mb-4">
                        <img src="<?php echo htmlspecialchars($eventImagePath); ?>" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>" 
                             class="event-hero-image rounded"
                             onerror="this.src='../../assets/images/event_images/placeholder.jpg'">
                    </div>

                    <div class="luxury-card p-4 mb-4">
                        <h1 class="event-detail-name mb-3"><?php echo htmlspecialchars($event['title']); ?></h1>
                        
                        <div class="event-detail-description mb-4">
                            <p><?php echo !empty($event['description']) ? nl2br(htmlspecialchars($event['description'])) : 'No description available for this event.'; ?></p>
                        </div>

                        <hr class="my-4">

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                    </div>
                                    <div class="detail-content">
                                        <h6 class="detail-label">Venue</h6>
                                        <p class="detail-value"><?php echo htmlspecialchars($event['venue']); ?></p>
                                        <p class="detail-value text-muted small"><?php echo htmlspecialchars($event['venueAddress']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($event['eventDate']) && !empty($event['eventDate'])): ?>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                    </div>
                                    <div class="detail-content">
                                        <h6 class="detail-label">Date</h6>
                                        <p class="detail-value"><?php echo htmlspecialchars($event['formattedDate']); ?></p>
                                        <?php if (isset($event['eventTime']) && !empty($event['eventTime'])): ?>
                                            <p class="detail-value text-muted small"><?php echo htmlspecialchars(formatTime12Hour($event['eventTime'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Dynamic Tiered Pricing Section -->
                        <div class="pricing-section mt-4 mb-4">
                            <h5 class="detail-label mb-4">Package Options</h5>
                            <div class="packages-container">
                                <?php 
                                $eventCategory = $event['category'] ?? 'Business';
                                $eventTitle = $event['title'] ?? '';
                                foreach ($packages as $package): 
                                    $tierKey = strtolower($package['tier']);
                                    $features = !empty($packageInclusions[$tierKey])
                                        ? $packageInclusions[$tierKey]
                                        : getPackageFeatures($eventCategory, $tierKey, $eventTitle);
                                ?>
                                    <div class="package-card" 
                                         data-package-id="<?php echo $package['id']; ?>"
                                         data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                                         data-package-price="<?php echo $package['price']; ?>"
                                         data-package-tier="<?php echo htmlspecialchars(strtolower($package['tier'])); ?>"
                                         data-package-features='<?php echo json_encode($features); ?>'>
                                        <div class="package-header">
                                            <h6 class="package-name"><?php echo htmlspecialchars($package['name']); ?></h6>
                                            <div class="package-price">₱ <?php echo number_format($package['price'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="reservation-section inquiry-section p-4 text-center mt-4">
                            <?php $link = isset($_SESSION['user_id']) ? 'reservation.php?eventId=' . $eventId : 'login.php?redirect=' . urlencode('reservation.php?eventId=' . $eventId); ?>
                            <div class="d-flex justify-content-center">
                                <a href="<?php echo $link; ?>" class="btn btn-primary-luxury w-100">Inquire Reservation</a>
                            </div>
                        </div>
                    </div> <!-- end .luxury-card -->
                </div>

                <div class="event-sidebar">
                    <div class="luxury-card ai-assistant-container p-4 mb-4">
                        <div class="ai-assistant-header mb-3">
                            <div class="ai-icon">
                                <span class="ai-logo">E</span>
                            </div>
                            <h5 class="mb-0">AI Assistant</h5>
                        </div>
                        <p class="text-muted mb-4" style="font-size: 0.9rem; line-height: 1.5;">Need help? Ask me anything about this event!</p>
                        <div class="ai-chat-box mb-3">
                            <div class="ai-message">
                                <p class="mb-0">Hello! I'm here to help you with any questions about this event. What would you like to know?</p>
                            </div>
                        </div>
                        <div class="ai-input-container">
                            <input type="text" class="ai-input-field" id="aiQuestion" placeholder="Ask a question...">
                            <button class="ai-send-button" type="button" id="aiSendButton" onclick="askAI()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 16 16">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm-1.138-1.138L13.229 1.5 4.577 8.932l.921.001Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="luxury-card p-4">
                        <h5 class="mb-4">Frequently Asked Questions</h5>
                        <div class="faq-list">
                            <div class="faq-item mb-3">
                                <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Can I request additional customizations for my chosen package?
                                </button>
                                <div class="collapse" id="faq1">
                                    <div class="faq-answer">
                                        Absolutely. While our Bronze, Silver, and Gold tiers provide a comprehensive foundation, our team is happy to discuss bespoke add-ons such as floral upgrades or specific technical requirements during your consultation.
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item mb-3">
                                <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What is the timeframe for modifying or canceling a reservation?
                                </button>
                                <div class="collapse" id="faq2">
                                    <div class="faq-answer">
                                        Reservations can be modified up to 14 days before the event date. Cancellations made within this window are subject to our standard refund policy as outlined in your confirmation email.
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item mb-3">
                                <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Are parking and accessibility services included at the venue?
                                </button>
                                <div class="collapse" id="faq3">
                                    <div class="faq-answer">
                                        Yes, all our partner venues offer complimentary valet parking for Gold package holders and accessible entry points for all guests to ensure a seamless experience.
                                    </div>
                                </div>
                            </div>
                            <div class="faq-item mb-3">
                                <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What technical equipment is provided for business or gala events?
                                </button>
                                <div class="collapse" id="faq4">
                                    <div class="faq-answer">
                                        Our venues are equipped with high-speed WiFi and standard AV setups. Silver and Gold packages include dedicated technical support and advanced projection systems if required.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="packageModal" class="package-modal-overlay">
        <div class="package-modal">
            <div class="package-modal-header">
                <div>
                    <h5 class="package-modal-title" id="modalPackageName">Package Name</h5>
                    <div class="package-modal-price" id="modalPackagePrice">₱ 0.00</div>
                </div>
                <button type="button" class="package-modal-close" onclick="closePackageModal()" aria-label="Close">
                    ×
                </button>
            </div>
            <div class="package-modal-body">
                <div class="package-modal-features">
                    <h6>What's Included</h6>
                    <ul class="modal-feature-list" id="modalFeatureList">
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script type="application/json" id="eventData">
    {
        "title": <?php echo json_encode($event['title']); ?>,
        "description": <?php echo json_encode($event['description'] ?? ''); ?>,
        "venue": <?php echo json_encode($event['venue'] ?? ''); ?>,
        "venueAddress": <?php echo json_encode($event['venueAddress'] ?? ''); ?>,
        "formattedDate": <?php echo json_encode($event['formattedDate'] ?? ''); ?>,
        "eventTime": <?php echo json_encode($event['eventTime'] ?? ''); ?>,
        "packages": <?php 
            $eventCategory = $event['category'] ?? 'Business';
            $eventTitle = $event['title'] ?? '';
            $packagesForJson = [];
            foreach ($packages as $package) {
                $tierKey = strtolower($package['tier']);
                $features = !empty($packageInclusions[$tierKey])
                    ? $packageInclusions[$tierKey]
                    : getPackageFeatures($eventCategory, $tierKey, $eventTitle);
                $packagesForJson[] = [
                    'name' => $package['name'],
                    'price' => $package['price'],
                    'tier' => $package['tier'],
                    'inclusions' => array_values($features)
                ];
            }
            echo json_encode($packagesForJson, JSON_UNESCAPED_UNICODE);
        ?>,
        "faqs": [
            {
                "question": "Can I request additional customizations for my chosen package?",
                "answer": "Absolutely. While our Bronze, Silver, and Gold tiers provide a comprehensive foundation, our team is happy to discuss bespoke add-ons such as floral upgrades or specific technical requirements during your consultation."
            },
            {
                "question": "What is the timeframe for modifying or canceling a reservation?",
                "answer": "Reservations can be modified up to 14 days before the event date. Cancellations made within this window are subject to our standard refund policy as outlined in your confirmation email."
            },
            {
                "question": "Are parking and accessibility services included at the venue?",
                "answer": "Yes, all our partner venues offer complimentary valet parking for Gold package holders and accessible entry points for all guests to ensure a seamless experience."
            },
            {
                "question": "What technical equipment is provided for business or gala events?",
                "answer": "Our venues are equipped with high-speed WiFi and standard AV setups. Silver and Gold packages include dedicated technical support and advanced projection systems if required."
            }
        ]
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/event-details.js"></script>
    <script>
        function openPackageModal(packageId, packageName, packagePrice, features) {
            const modal = document.getElementById('packageModal');
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }
            
            const modalTitle = document.getElementById('modalPackageName');
            const modalPrice = document.getElementById('modalPackagePrice');
            const modalFeatures = document.getElementById('modalFeatureList');
            
            if (!modalTitle || !modalPrice || !modalFeatures) {
                console.error('Modal elements not found!');
                return;
            }
            
            modalTitle.textContent = packageName;
            modalPrice.textContent = '₱ ' + parseFloat(packagePrice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            modalFeatures.innerHTML = '';
            if (Array.isArray(features) && features.length > 0) {
                features.forEach(feature => {
                    const li = document.createElement('li');
                    li.textContent = feature;
                    modalFeatures.appendChild(li);
                });
            } else {
                const li = document.createElement('li');
                li.textContent = 'No features available';
                modalFeatures.appendChild(li);
            }
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closePackageModal() {
            const modal = document.getElementById('packageModal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const packageCards = document.querySelectorAll('.package-card');
            packageCards.forEach(card => {
                card.addEventListener('click', function() {
                    const packageId = this.getAttribute('data-package-id');
                    const packageName = this.getAttribute('data-package-name');
                    const packagePrice = parseFloat(this.getAttribute('data-package-price'));
                    const featuresJson = this.getAttribute('data-package-features');
                    
                    let features = [];
                    try {
                        features = JSON.parse(featuresJson);
                    } catch(e) {
                        console.error('Error parsing features:', e);
                    }
                    
                    openPackageModal(packageId, packageName, packagePrice, features);
                });
            });
            
            const modal = document.getElementById('packageModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closePackageModal();
                    }
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePackageModal();
                }
            });
        });
    </script>
</body>
</html>

