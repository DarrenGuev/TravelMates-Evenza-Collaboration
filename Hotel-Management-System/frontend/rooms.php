<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once DBCONNECT_PATH . '/connect.php';
require_once CLASSES_PATH . '/autoload.php';

// Initialize models
$userModel = new User();
$roomModel = new Room();
$roomTypeModel = new RoomType();
$featureModel = new Feature();
$featureCategoryModel = new FeatureCategory();

$userData = null;
if (isset($_SESSION['userID'])) {
    $userID = (int) $_SESSION['userID'];
    $user = $userModel->find($userID);
    if ($user) {
        $userData = [
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'email' => $user['email'],
            'phoneNumber' => $user['phoneNumber'],
            'fullName' => $user['firstName'] . ' ' . $user['lastName']
        ];
    }
}

// Get all room types as array
$roomTypesData = $roomTypeModel->getAll();

// Get all unique features from the database grouped by category
// Initialize categories from featureCategories so even empty categories show
$categoriesData = $featureCategoryModel->getAll();

$featuresByCategory = [];

// Initialize categories (so even categories with zero linked features still render)
foreach ($categoriesData as $cat) {
    $categoryName = $cat['categoryName'] ?? 'General';
    if (!isset($featuresByCategory[$categoryName])) {
        $featuresByCategory[$categoryName] = [];
    }
}

// Load features and attach them to categories (show all features, even if not yet used by a room)
$allFeatures = $featureModel->getAll('category, featureName');

foreach ($allFeatures as $row) {
    $category = $row['category'] ?? 'General';
    $featureName = $row['featureName'];

    if (!isset($featuresByCategory[$category])) {
        $featuresByCategory[$category] = [];
    }

    if ($featureName && !in_array($featureName, $featuresByCategory[$category], true)) {
        $featuresByCategory[$category][] = $featureName;
    }
}

function getRoomFeaturesArray($roomID, $roomModel = null)
{
    if ($roomModel === null) {
        $roomModel = new Room();
    }
    return $roomModel->getFeatures($roomID);
}

// Helper function to group features by category
function groupFeaturesByCategory($features) {
    $grouped = [];
    foreach ($features as $feature) {
        $category = $feature['category'] ?? 'General';
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $feature['featureName'];
    }
    return $grouped;
}

// REMOVE icon helpers to keep all categories visually identical, even if admins add new ones.
// function getCategoryIcon($category) { ... }  <-- delete or ignore this function if present
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Rooms</title>
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/logo/logoW.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-12 col-lg-3 col-xl-2 px-0">
                <div class="sticky-top" style="top:70px; z-index: 10;">
                    <div class="bg-body-tertiary border p-4 pt-5 mt-5">
                        <div class="d-lg-none">
                            <div class="accordion" id="filterAccordion">
                                <div class="accordion-item mt-2">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseFilter"
                                            aria-expanded="false" aria-controls="collapseFilter">
                                            Filter Rooms
                                        </button>
                                    </h2>
                                    <div id="collapseFilter" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <!-- Room Type -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <h6 class="fw-semibold mb-3 text-secondary">Room Type</h6>
                                                <?php 
                                                foreach ($roomTypesData as $type) { 
                                                    $typeValue = strtolower($type['roomType']);
                                                    $typeId = 'type' . str_replace(' ', '', $type['roomType']) . 'Mobile';
                                                ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input filter-checkbox" type="checkbox" 
                                                        value="<?php echo htmlspecialchars($typeValue); ?>"
                                                        id="<?php echo htmlspecialchars($typeId); ?>">
                                                    <label class="form-check-label small" for="<?php echo htmlspecialchars($typeId); ?>"><?php echo htmlspecialchars($type['roomType']); ?></label>
                                                </div>
                                                <?php } ?>
                                            </div>

                                            <!-- Price Range -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <h6 class="fw-semibold mb-3 text-secondary">Price Range (₱)</h6>
                                                <input type="range" class="form-range" min="1000" max="20000" step="100"
                                                    id="priceRangeMobile" value="20000">
                                                <div class="d-flex justify-content-between small text-muted">
                                                    <span>₱1,000</span>
                                                    <span>₱20,000</span>
                                                </div>
                                            </div>

                                            <!-- Features -->
                                            <div class="border-bottom pb-3 mb-3">
                                                <h6 class="fw-semibold mb-3 text-secondary">Features</h6>

                                                <div class="accordion" id="featuresAccordionMobile">
                                                    <?php
                                                    $i = 0;
                                                    foreach ($featuresByCategory as $category => $features) {
                                                        $i++;
                                                        $catKey = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '', $category));
                                                        $headingId = "featuresMobileHeading{$i}_{$catKey}";
                                                        $collapseId = "featuresMobileCollapse{$i}_{$catKey}";
                                                    ?>
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="<?php echo htmlspecialchars($headingId); ?>">
                                                                <button class="accordion-button collapsed py-2" type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?php echo htmlspecialchars($collapseId); ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?php echo htmlspecialchars($collapseId); ?>">
                                                                    <?php echo htmlspecialchars($category); ?>
                                                                </button>
                                                            </h2>
                                                            <div id="<?php echo htmlspecialchars($collapseId); ?>"
                                                                class="accordion-collapse collapse"
                                                                aria-labelledby="<?php echo htmlspecialchars($headingId); ?>"
                                                                data-bs-parent="#featuresAccordionMobile">
                                                                <div class="accordion-body pt-2">
                                                                    <?php if (!empty($features)) { ?>
                                                                        <div class="d-flex flex-column gap-2">
                                                                            <?php foreach ($features as $feature) {
                                                                                $featureId = 'feature' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '', $feature)) . 'Mobile';
                                                                                $featureValue = strtolower($feature);
                                                                            ?>
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input filter-checkbox feature-checkbox" type="checkbox"
                                                                                        value="<?php echo htmlspecialchars($featureValue); ?>"
                                                                                        id="<?php echo htmlspecialchars($featureId); ?>">
                                                                                    <label class="form-check-label small"
                                                                                        for="<?php echo htmlspecialchars($featureId); ?>">
                                                                                        <?php echo htmlspecialchars($feature); ?>
                                                                                    </label>
                                                                                </div>
                                                                            <?php } ?>
                                                                        </div>
                                                                    <?php } else { ?>
                                                                        <div class="text-muted small">No features available</div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                            <!-- Guest Capacity -->
                                            <div class="pb-3 mb-3">
                                                <h6 class="fw-semibold mb-3 text-secondary">Guest Capacity</h6>
                                                <select class="form-select" id="guestCapacityMobile">
                                                    <option value="">Any</option>
                                                    <option value="1">1 Guest</option>
                                                    <option value="2">2 Guests</option>
                                                    <option value="3">3 Guests</option>
                                                    <option value="4">4 Guests</option>
                                                    <option value="5">5+ Guests</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-none d-lg-block">
                            <h5 class="fw-bold mb-4">Filter Rooms</h5>
                            <!-- Room Type -->
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="fw-semibold mb-3 text-secondary">Room Type</h6>
                                <?php 
                                foreach ($roomTypesData as $type) { 
                                    $typeValue = strtolower($type['roomType']);
                                    $typeId = 'type' . str_replace(' ', '', $type['roomType']);
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                        value="<?php echo htmlspecialchars($typeValue); ?>" 
                                        id="<?php echo htmlspecialchars($typeId); ?>">
                                    <label class="form-check-label small" for="<?php echo htmlspecialchars($typeId); ?>"><?php echo htmlspecialchars($type['roomType']); ?></label>
                                </div>
                                <?php } ?>
                            </div>

                            <!-- Price Range -->
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="fw-semibold mb-3 text-secondary">Price Range (₱)</h6>
                                <input type="range" class="form-range" min="1000" max="20000" step="100" id="priceRange"
                                    value="20000">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>₱1,000</span>
                                    <span>₱20,000</span>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="fw-semibold mb-3 text-secondary">Features</h6>

                                <div class="accordion" id="featuresAccordionDesktop">
                                    <?php
                                    $i = 0;
                                    foreach ($featuresByCategory as $category => $features) {
                                        $i++;
                                        $catKey = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '', $category));
                                        $headingId = "featuresDesktopHeading{$i}_{$catKey}";
                                        $collapseId = "featuresDesktopCollapse{$i}_{$catKey}";
                                    ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="<?php echo htmlspecialchars($headingId); ?>">
                                                <button class="accordion-button collapsed py-2" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#<?php echo htmlspecialchars($collapseId); ?>"
                                                    aria-expanded="false"
                                                    aria-controls="<?php echo htmlspecialchars($collapseId); ?>">
                                                    <?php echo htmlspecialchars($category); ?>
                                                </button>
                                            </h2>
                                            <div id="<?php echo htmlspecialchars($collapseId); ?>"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="<?php echo htmlspecialchars($headingId); ?>"
                                                data-bs-parent="#featuresAccordionDesktop">
                                                <div class="accordion-body pt-2">
                                                    <?php if (!empty($features)) { ?>
                                                        <div class="d-flex flex-column gap-2">
                                                            <?php foreach ($features as $feature) {
                                                                $featureId = 'feature' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '', $feature));
                                                                $featureValue = strtolower($feature);
                                                            ?>
                                                                <div class="form-check">
                                                                    <input class="form-check-input feature-checkbox" type="checkbox"
                                                                        value="<?php echo htmlspecialchars($featureValue); ?>"
                                                                        id="<?php echo htmlspecialchars($featureId); ?>">
                                                                    <label class="form-check-label small"
                                                                        for="<?php echo htmlspecialchars($featureId); ?>">
                                                                        <?php echo htmlspecialchars($feature); ?>
                                                                    </label>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="text-muted small">No features available</div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Guest Capacity -->
                            <div class="pb-3 mb-3">
                                <h6 class="fw-semibold mb-3 text-secondary">Guest Capacity</h6>
                                <select class="form-select" id="guestCapacity">
                                    <option value="">Any</option>
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5+ Guests</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Listings -->
            <div class="col-12 col-lg-9 col-xl-10 p-4 mt-5">
                <h2 class="text-center fw-bold mb-4 mt-5">ROOMS</h2>
                <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;"></div>

                <?php
                foreach ($roomTypesData as $roomType) {
                    $roomsData = $roomModel->getByType($roomType['roomTypeID']);
                    if (count($roomsData) > 0) {
                        ?>
                        <div class="container">
                            <div class="row mt-5">
                                <div class="col">
                                    <h2 class="fw-bold mb-3">
                                        <?php echo htmlspecialchars($roomType['roomType']); ?> Room
                                    </h2>
                                </div>
                            </div>
                            <div class="row" id="<?php echo strtolower($roomType['roomType']); ?>RoomCards">
                                <?php foreach ($roomsData as $row) {
                                    $featuresData = $roomModel->getFeatures($row['roomID']);
                                    $features = [];
                                    foreach ($featuresData as $feature) {
                                        $features[] = $feature['featureName'];
                                    }
                                    $roomTypeName = $roomType['roomType'];
                                    ?>
                                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3 pb-4 room-card" 
                                        data-room-type="<?php echo strtolower($roomTypeName); ?>"
                                        data-price="<?php echo $row['base_price']; ?>"
                                        data-capacity="<?php echo (int) $row['capacity']; ?>"
                                        data-features="<?php echo strtolower(implode(',', $features)); ?>">
                                        <div class="card h-100 bg-transparent shadow rounded-3">
                                            <div class="ratio ratio-4x3 overflow-hidden rounded-top-3 position-relative gallery-item">
                                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($row['imagePath']); ?>"
                                                    class="card-img-top img-fluid"
                                                    alt="<?php echo htmlspecialchars($row['roomName']); ?>">
                                            </div>
                                            <div class="card-body p-4">
                                                <h5 class="card-title fw-bold mb-1">
                                                    <?php echo htmlspecialchars($row['roomName']); ?>
                                                </h5>
                                                <p class="text-secondary fst-italic small mb-2">
                                                    <?php echo htmlspecialchars($roomTypeName); ?> Room • Max
                                                    <?php echo (int) $row['capacity']; ?> Guests
                                                </p>
                                                <p class="fw-semibold mb-3">₱<?php echo number_format($row['base_price'], 2); ?> /
                                                    night</p>
                                                <div class="mb-3">
                                                    <?php if (!empty($features)) {
                                                        foreach ($features as $featureName) { ?>
                                                            <span
                                                                class="badge bg-dark text-white me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                                        <?php }
                                                    } else { ?>
                                                        <span class="text-muted small">No features listed</span>
                                                    <?php } ?>
                                                </div>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button class="btn btn-warning" data-bs-toggle="modal"
                                                        data-bs-target="#bookingModal<?php echo $row['roomID']; ?>">Book
                                                        Now</button>
                                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal"
                                                        data-bs-target="#roomDetailModal<?php echo $row['roomID']; ?>">More
                                                        Details</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="roomDetailModal<?php echo $row['roomID']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($row['roomName']); ?></h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-12 justify-content-center text-center">
                                                            <div class="position-relative gallery-item d-inline-block rounded-3 overflow-hidden mb-3">
                                                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($row['imagePath']); ?>"
                                                                    alt="<?php echo htmlspecialchars($row['roomName']); ?>"
                                                                    class="img-fluid rounded-3">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-8">
                                                            <p class="fw-semibold mb-2">Room Details</p>
                                                            <p class="small text-secondary mb-1"><strong>Type:</strong>
                                                                <?php echo htmlspecialchars($row['roomTypeName']); ?></p>
                                                            <p class="small text-secondary mb-1"><strong>Capacity:</strong>
                                                                <?php echo (int) $row['capacity']; ?> Guests</p>
                                                            <p class="small text-secondary mb-1"><strong>Available:</strong>
                                                                <?php echo (int) $row['quantity']; ?> Rooms</p>
                                                            <p class="small text-secondary mb-1"><strong>Price:</strong>
                                                                ₱<?php echo number_format($row['base_price'], 2); ?> / night</p>
                                                        </div>
                                                        <div class="col-4 align-items-center d-flex">
                                                            <div class="mb-2 justify-content-evenly">
                                                                <?php if (!empty($features)) {
                                                                    foreach ($features as $featureName) { ?>
                                                                        <span
                                                                            class="badge bg-dark me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                                                    <?php }
                                                                } ?>
                                                            </div>
                                                            <div class="mb-2 justify-content-evenly">

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-warning" data-bs-toggle="modal"
                                                        data-bs-target="#bookingModal<?php echo $row['roomID']; ?>">Book
                                                        Now</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="bookingModal<?php echo $row['roomID']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($row['roomName']); ?></h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="bookingForm<?php echo $row['roomID']; ?>"
                                                        action="php/process_booking.php" method="POST">
                                                        <input type="hidden" name="roomID" value="<?php echo $row['roomID']; ?>">
                                                        <input type="hidden" name="totalPrice"
                                                            id="totalPriceInput<?php echo $row['roomID']; ?>"
                                                            value="<?php echo $row['base_price']; ?>">
                                                        <input type="hidden" name="paymentMethod"
                                                            id="paymentMethodInput<?php echo $row['roomID']; ?>" value="">

                                                        <div class="row">
                                                            <div class="col-12 col-xl-7 justify-content-center text-center">
                                                                <div class="position-relative gallery-item d-inline-block rounded-3 overflow-hidden mb-3">
                                                                    <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($row['imagePath']); ?>"
                                                                        alt="<?php echo htmlspecialchars($row['roomName']); ?>"
                                                                        class="img-fluid rounded-3">
                                                                </div>
                                                                <div class="col-12 text-start mx-3">
                                                                    <p class="fw-semibold mb-2">Features:</p>
                                                                    <div class="mb-3">
                                                                        <?php if (!empty($features)) {
                                                                            foreach ($features as $featureName) { ?>
                                                                                <span
                                                                                    class="badge bg-dark me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                                                            <?php }
                                                                        } ?>
                                                                    </div>
                                                                    <p class="small text-secondary"><strong>Type:</strong>
                                                                        <?php echo htmlspecialchars($roomTypeName); ?> |
                                                                        <strong>Capacity:</strong>
                                                                        <?php echo (int) $row['capacity']; ?> Guests |
                                                                        <strong>Price:</strong>
                                                                        ₱<?php echo number_format($row['base_price'], 2); ?> / night
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-xl-5">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <p class="text-start fw-bold mb-1">Guest information</p>
                                                                        <?php if ($userData): ?>
                                                                            <div class="row g-2">
                                                                                <div class="col-12 col-sm-6">
                                                                                    <input type="text" name="firstName" id="firstName"
                                                                                        class="form-control mb-2"
                                                                                        placeholder="First name"
                                                                                        value="<?php echo htmlspecialchars($userData['firstName'] ?? '', ENT_QUOTES); ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="col-12 col-sm-6">
                                                                                    <input type="text" name="lastName" id="lastName"
                                                                                        class="form-control mb-2"
                                                                                        placeholder="Last name"
                                                                                        value="<?php echo htmlspecialchars($userData['lastName'] ?? '', ENT_QUOTES); ?>"
                                                                                        required>
                                                                                </div>
                                                                            </div>
                                                                            <input type="email" name="email" class="form-control mb-2"
                                                                                placeholder="Email"
                                                                                value="<?php echo htmlspecialchars($userData['email'] ?? '', ENT_QUOTES); ?>"
                                                                                required>
                                                                            <input type="tel" name="phoneNumber"
                                                                                class="form-control mb-2"
                                                                                placeholder="Phone Number (7-12 digits, optional +country)"
                                                                                value="<?php echo htmlspecialchars($userData['phoneNumber'] ?? '', ENT_QUOTES); ?>"
                                                                                pattern="^\+?[0-9]{7,12}$"
                                                                                title="Enter 7 to 12 digits (optional leading +country code)"
                                                                                inputmode="tel" maxlength="13"
                                                                                oninput="this.value = this.value.replace(/(?!^\+)[^0-9]/g, '').replace(/(?!^)\+/g, '')"
                                                                                required>
                                                                            <div class="invalid-feedback">Please enter a valid phone
                                                                                number (7-12 digits).</div>
                                                                        <?php else: ?>
                                                                            <div class="alert alert-warning small">
                                                                                <i class="bi bi-info-circle me-1"></i>Please log in to
                                                                                book a room. Fields are disabled until you sign in.
                                                                            </div>
                                                                            <div class="row g-2">
                                                                                <div class="col-12 col-sm-6">
                                                                                    <input type="text" name="firstName" id="firstName"
                                                                                        disabled class="form-control mb-2"
                                                                                        placeholder="First name"
                                                                                        value="<?php echo htmlspecialchars($userData['firstName'] ?? '', ENT_QUOTES); ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="col-12 col-sm-6">
                                                                                    <input type="text" name="lastName" id="lastName"
                                                                                        disabled class="form-control mb-2"
                                                                                        placeholder="Last name"
                                                                                        value="<?php echo htmlspecialchars($userData['lastName'] ?? '', ENT_QUOTES); ?>"
                                                                                        required>
                                                                                </div>
                                                                            </div>
                                                                            <input type="text" class="form-control mb-2"
                                                                                placeholder="Last name" disabled>
                                                                            <input type="email" class="form-control mb-2"
                                                                                placeholder="Email" disabled>
                                                                            <input type="tel" class="form-control mb-2"
                                                                                placeholder="Phone Number (7-12 digits, optional +country)"
                                                                                disabled pattern="^\+?[0-9]{7,12}$"
                                                                                title="Enter 7 to 12 digits (optional leading +country code)"
                                                                                inputmode="tel" maxlength="13">
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <p class="text-start fw-bold mt-1 mb-1">Booking details</p>
                                                                    <div class="col-6">
                                                                        <label for="checkIn<?php echo $row['roomID']; ?>"
                                                                            class="form-label mb-0">Check-in</label>
                                                                        <input type="date" name="checkInDate"
                                                                            id="checkIn<?php echo $row['roomID']; ?>"
                                                                            class="form-control mb-1" <?php echo !$userData ? 'disabled' : ''; ?> required>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label for="checkOut<?php echo $row['roomID']; ?>"
                                                                            class="form-label mb-0">Check-out</label>
                                                                        <input type="date" name="checkOutDate"
                                                                            id="checkOut<?php echo $row['roomID']; ?>"
                                                                            class="form-control mb-1" <?php echo !$userData ? 'disabled' : ''; ?> required>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <div class="alert alert-danger py-2 mb-2" id="dateError<?php echo $row['roomID']; ?>" style="display: none;">
                                                                            <small><i class="bi bi-exclamation-triangle me-1"></i><span id="dateErrorMessage<?php echo $row['roomID']; ?>"></span></small>
                                                                        </div>
                                                                    </div>                                          
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-3">
                                                                        <label for="guests<?php echo $row['roomID']; ?>"
                                                                            class="form-label mb-0">Guests</label>
                                                                        <input type="number" name="numberOfGuests"
                                                                            id="guests<?php echo $row['roomID']; ?>"
                                                                            class="form-control mb-1" min="1"
                                                                            max="<?php echo (int) $row['capacity']; ?>" value="1"
                                                                            <?php echo !$userData ? 'disabled' : ''; ?> required>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-12 m-2">
                                                                        <p class="text-start text-sm text-secondary fw-bold m-1">
                                                                            Booking Summary</p>
                                                                        <div class="row">
                                                                            <div class="col-12 ms-2 text-start">
                                                                                <p class="mb-1"><strong>Room:</strong>
                                                                                    <?php echo htmlspecialchars($row['roomName']); ?>
                                                                                </p>
                                                                                <p class="mb-1"><strong>Dates:</strong> <span
                                                                                        id="summaryDates<?php echo $row['roomID']; ?>">-</span>
                                                                                </p>
                                                                                <p class="mb-1"><strong>Duration:</strong> <span
                                                                                        id="summaryNights<?php echo $row['roomID']; ?>">-</span>
                                                                                </p>
                                                                                <p class="mb-1"><strong>Guests:</strong> <span
                                                                                        id="summaryGuests<?php echo $row['roomID']; ?>">1</span>
                                                                                </p>
                                                                                <p class="mb-1 fw-bold text-warning">
                                                                                    <strong>Total:</strong> ₱<span
                                                                                        id="summaryTotal<?php echo $row['roomID']; ?>"><?php echo number_format($row['base_price'], 2); ?></span>
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-warning"
                                                        onclick="openPaymentModal(<?php echo $row['roomID']; ?>, <?php echo $row['base_price']; ?>)">Proceed
                                                        to Payment</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Modal -->
                                    <div class="modal fade" id="paymentModal<?php echo $row['roomID']; ?>" tabindex="-1"
                                        data-bs-backdrop="static">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Payment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        onclick="closePaymentModal(<?php echo $row['roomID']; ?>)"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="text-center mb-4">
                                                        <h6 class="text-muted">Total Amount</h6>
                                                        <h2 class="text-warning fw-bold">₱<span
                                                                id="paymentTotal<?php echo $row['roomID']; ?>">0.00</span></h2>
                                                    </div>

                                                    <h6 class="fw-bold mb-3">Select Payment Method</h6>
                                                    <div class="d-grid gap-2">
                                                        <button type="button" class="btn btn-outline-secondary payment-method-btn"
                                                            data-method="paypal"
                                                            onclick="selectPayment(<?php echo $row['roomID']; ?>, 'paypal')">
                                                            <i class="bi bi-paypal me-2"></i>PayPal
                                                        </button>
                                                    </div>

                                                    <!-- Payment Details Section (hidden by default) -->
                                                    <div id="paymentDetails<?php echo $row['roomID']; ?>" class="mt-4"
                                                        style="display: none;">
                                                        <hr>
                                                        <!-- Only PayPal is supported: other payment detail sections removed -->
                                                        <div id="paypalDetails<?php echo $row['roomID']; ?>" class="payment-detail"
                                                            style="display: none;">
                                                            <div class="alert alert-info small">
                                                                <i class="bi bi-info-circle me-1"></i>You will be redirected to
                                                                PayPal to complete your secure online payment.
                                                            </div>
                                                        </div>

                                                        <button type="button" class="btn btn-warning w-100 mt-3"
                                                            onclick="confirmPayment(<?php echo $row['roomID']; ?>)">
                                                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php } ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>


    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script src="<?php echo JS_URL; ?>/showAlert.js"></script>
    <script>
        function initializeFilters() {
            const roomTypeCheckboxes = document.querySelectorAll('input[id^="type"]');
            const featureCheckboxes = document.querySelectorAll('.feature-checkbox');
            const priceRanges = document.querySelectorAll('#priceRange, #priceRangeMobile');
            const guestCapacities = document.querySelectorAll('#guestCapacity, #guestCapacityMobile');
            
            // Prevent dropdown from closing when clicking on checkboxes inside
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    if (e.target.classList.contains('form-check-input') || 
                        e.target.classList.contains('form-check-label') ||
                        e.target.closest('.form-check')) {
                        e.stopPropagation();
                    }
                });
            });
            
            priceRanges.forEach(priceRange => {
                if (priceRange) {
                    const priceDisplay = document.createElement('div');
                    priceDisplay.className = 'text-center fw-semibold mb-2';
                    priceDisplay.id = 'priceDisplay' + (priceRange.id.includes('Mobile') ? 'Mobile' : '');
                    priceDisplay.textContent = 'Up to ₱' + parseInt(priceRange.value).toLocaleString();
                    priceRange.parentElement.insertBefore(priceDisplay, priceRange);
                    
                    priceRange.addEventListener('input', function() {
                        priceDisplay.textContent = 'Up to ₱' + parseInt(this.value).toLocaleString();
                        syncPriceRanges(this);
                        applyFilters();
                    });
                }
            });
            
            roomTypeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    syncCheckboxes(this);
                    applyFilters();
                });
            });
            
            featureCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    syncCheckboxes(this);
                    updateCategoryBadges();
                    applyFilters();
                });
            });
            
            guestCapacities.forEach(select => {
                if (select) {
                    select.addEventListener('change', function() {
                        syncGuestCapacity(this);
                        applyFilters();
                    });
                }
            });
        }
        
        function updateCategoryBadges() {
            return;
        }
        
        function syncCheckboxes(sourceCheckbox) {
            const value = sourceCheckbox.value;
            const isChecked = sourceCheckbox.checked;
            const isMobile = sourceCheckbox.id.includes('Mobile');
            const baseId = sourceCheckbox.id.replace('Mobile', '');
            
            const targetId = isMobile ? baseId : baseId + 'Mobile';
            const targetCheckbox = document.getElementById(targetId);
            
            if (targetCheckbox) {
                targetCheckbox.checked = isChecked;
            }
        }
        
        function syncPriceRanges(sourceRange) {
            const value = sourceRange.value;
            const isMobile = sourceRange.id.includes('Mobile');
            const targetId = isMobile ? 'priceRange' : 'priceRangeMobile';
            const targetRange = document.getElementById(targetId);
            
            if (targetRange) {
                targetRange.value = value;
                const displayId = 'priceDisplay' + (isMobile ? '' : 'Mobile');
                const targetDisplay = document.getElementById(displayId);
                if (targetDisplay) {
                    targetDisplay.textContent = 'Up to ₱' + parseInt(value).toLocaleString();
                }
            }
        }
        
        function syncGuestCapacity(sourceSelect) {
            const value = sourceSelect.value;
            const isMobile = sourceSelect.id.includes('Mobile');
            const targetId = isMobile ? 'guestCapacity' : 'guestCapacityMobile';
            const targetSelect = document.getElementById(targetId);
            
            if (targetSelect) {
                targetSelect.value = value;
            }
        }
        
        function applyFilters() {
            const selectedTypes = Array.from(document.querySelectorAll('input[id^="type"]:not([id*="Mobile"]):checked'))
                .map(cb => cb.value.toLowerCase());
            
            const selectedFeatures = Array.from(document.querySelectorAll('.feature-checkbox:not([id*="Mobile"]):checked'))
                .map(cb => cb.value.toLowerCase());
            
            const priceRange = document.getElementById('priceRange');
            const maxPrice = priceRange ? parseFloat(priceRange.value) : Infinity;
            
            const guestCapacity = document.getElementById('guestCapacity');
            const minCapacity = guestCapacity ? parseInt(guestCapacity.value) || 0 : 0;
            
            // Get all room cards
            const roomCards = document.querySelectorAll('.room-card');
            let visibleCount = 0;
            
            roomCards.forEach(card => {
                const cardType = card.getAttribute('data-room-type');
                const cardPrice = parseFloat(card.getAttribute('data-price'));
                const cardCapacity = parseInt(card.getAttribute('data-capacity'));
                const cardFeatures = card.getAttribute('data-features').split(',').filter(f => f.trim() !== '');
                
                let show = true;
                
                // Filter by room type
                if (selectedTypes.length > 0 && !selectedTypes.includes(cardType)) {
                    show = false;
                }
                
                // Filter by price
                if (cardPrice > maxPrice) {
                    show = false;
                }
                
                // Filter by capacity
                if (minCapacity > 0 && cardCapacity < minCapacity) {
                    show = false;
                }
                
                // Filter by features (all selected features must be present)
                if (selectedFeatures.length > 0) {
                    const normalizedCardFeatures = cardFeatures.map(f => f.trim().toLowerCase()).filter(Boolean);
                    const hasAllFeatures = selectedFeatures.every(selected =>
                        normalizedCardFeatures.some(cardFeature => cardFeature.includes(selected))
                    );
                    if (!hasAllFeatures) {
                        show = false;
                    }
                }
                
                // Show/hide card
                if (show) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            document.querySelectorAll('[id$="RoomCards"]').forEach(section => {
                const visibleCards = section.querySelectorAll('.room-card:not([style*="display: none"])').length;
                const container = section.closest('.container');
                
                if (visibleCards > 0) {
                    section.style.display = '';
                    if (container) container.style.display = '';
                } else {
                    section.style.display = 'none';
                    if (container) container.style.display = 'none';
                }
            });
            
            if (visibleCount === 0) {
                showNoResultsMessage();
            } else {
                removeNoResultsMessage();
            }
        }
        
        function showNoResultsMessage() {
            removeNoResultsMessage();
            const container = document.querySelector('.col-12.col-lg-9.col-xl-10');
            const message = document.createElement('div');
            message.id = 'noResultsMessage';
            message.className = 'alert alert-info text-center mt-5';
            message.innerHTML = '<h5>No rooms match your filters</h5><p>Try adjusting your filter criteria</p>';
            container.appendChild(message);
        }
        
        function removeNoResultsMessage() {
            const existing = document.getElementById('noResultsMessage');
            if (existing) existing.remove();
        }

        document.addEventListener('DOMContentLoaded', function () {

            initializeFilters();
            
            <?php
            foreach ($roomTypesData as $roomType) {
                $roomsForSetup = $roomModel->getByType($roomType['roomTypeID']);
                foreach ($roomsForSetup as $room) {
                    ?>
                    setupBookingCalculation(<?php echo $room['roomID']; ?>, <?php echo $room['base_price']; ?>);
                    <?php
                }
            }
            ?>
        });

        function setupBookingCalculation(roomID, basePrice) {
            const checkIn = document.getElementById('checkIn' + roomID);
            const checkOut = document.getElementById('checkOut' + roomID);
            const guests = document.getElementById('guests' + roomID);

            if (checkIn && checkOut && guests) {
                const today = new Date().toISOString().split('T')[0];
                checkIn.min = today;
                checkOut.min = today;

                checkIn.addEventListener('change', () => {
                    // Update checkout minimum to be at least one day after check-in
                    if (checkIn.value) {
                        const checkInDate = new Date(checkIn.value);
                        checkInDate.setDate(checkInDate.getDate() + 1);
                        checkOut.min = checkInDate.toISOString().split('T')[0];
                        
                        // Clear checkout if it's before the new minimum
                        if (checkOut.value && new Date(checkOut.value) <= new Date(checkIn.value)) {
                            checkOut.value = '';
                        }
                    }
                    updateSummary(roomID, basePrice);
                });
                checkOut.addEventListener('change', () => updateSummary(roomID, basePrice));
                guests.addEventListener('change', () => updateSummary(roomID, basePrice));
            }
        }

        function updateSummary(roomID, basePrice) {
            const checkIn = document.getElementById('checkIn' + roomID).value;
            const checkOut = document.getElementById('checkOut' + roomID).value;
            const guests = document.getElementById('guests' + roomID).value;
            const errorDiv = document.getElementById('dateError' + roomID);
            const errorMsg = document.getElementById('dateErrorMessage' + roomID);

            if (errorDiv) errorDiv.style.display = 'none';

            if (checkIn && checkOut) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);
                const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

                if (checkOutDate <= checkInDate) {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Check-out date must be after check-in date.';
                        errorDiv.style.display = 'block';
                    }
                    document.getElementById('summaryDates' + roomID).textContent = '-';
                    document.getElementById('summaryNights' + roomID).textContent = '-';
                    document.getElementById('summaryTotal' + roomID).textContent = '0.00';
                    document.getElementById('totalPriceInput' + roomID).value = '0';
                    return;
                }

                if (nights > 0) {
                    const total = basePrice * nights;
                    document.getElementById('summaryDates' + roomID).textContent =
                        checkInDate.toLocaleDateString() + ' - ' + checkOutDate.toLocaleDateString();
                    document.getElementById('summaryNights' + roomID).textContent = nights + ' night(s)';
                    document.getElementById('summaryGuests' + roomID).textContent = guests;
                    document.getElementById('summaryTotal' + roomID).textContent = total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('totalPriceInput' + roomID).value = total;
                } else {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Invalid date range. Please select valid check-in and check-out dates.';
                        errorDiv.style.display = 'block';
                    }
                }
            }
        }

        let selectedPaymentMethod = {};

        function openPaymentModal(roomID, basePrice) {
            const form = document.getElementById('bookingForm' + roomID);
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const checkIn = document.getElementById('checkIn' + roomID).value;
            const checkOut = document.getElementById('checkOut' + roomID).value;
            const errorDiv = document.getElementById('dateError' + roomID);
            const errorMsg = document.getElementById('dateErrorMessage' + roomID);
            
            if (checkIn && checkOut) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);
                
                if (checkOutDate <= checkInDate) {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Check-out date must be after check-in date. Please correct your dates before proceeding.';
                        errorDiv.style.display = 'block';
                    }
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
            }

            const total = document.getElementById('totalPriceInput' + roomID).value;
            
            // Validate total price
            if (!total || parseFloat(total) <= 0) {
                if (errorDiv && errorMsg) {
                    errorMsg.textContent = 'Please select valid dates to calculate the total price.';
                    errorDiv.style.display = 'block';
                }
                return;
            }
            
            document.getElementById('paymentTotal' + roomID).textContent =
                parseFloat(total).toLocaleString('en-PH', { minimumFractionDigits: 2 });

            const bookingModal = bootstrap.Modal.getInstance(document.getElementById('bookingModal' + roomID));
            bookingModal.hide();

            setTimeout(() => {
                const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal' + roomID));
                paymentModal.show();
            }, 300);
        }

        function closePaymentModal(roomID) {
            selectedPaymentMethod[roomID] = null;
            // hide any payment detail sections
            document.querySelectorAll('#paymentModal' + roomID + ' .payment-detail').forEach(el => {
                el.style.display = 'none';
            });
            // hide the payment details container
            const paymentContainerHide = document.getElementById('paymentDetails' + roomID);
            if (paymentContainerHide) paymentContainerHide.style.display = 'none';
            // reset buttons
            document.querySelectorAll('#paymentModal' + roomID + ' .payment-method-btn').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
            });
        }

        function selectPayment(roomID, method) {
            selectedPaymentMethod[roomID] = method;
            const input = document.getElementById('paymentMethodInput' + roomID);
            if (input) input.value = method;

            // remove active state on all buttons
            document.querySelectorAll('#paymentModal' + roomID + ' .payment-method-btn').forEach(btn => btn.classList.remove('active'));

            // mark the chosen button active (we added data-method attribute on PayPal button)
            const chosenBtn = document.querySelector('#paymentModal' + roomID + ' .payment-method-btn[data-method="' + method + '"]');
            if (chosenBtn) chosenBtn.classList.add('active');

            document.querySelectorAll('#paymentModal' + roomID + ' .payment-detail').forEach(el => el.style.display = 'none');
            const paymentContainer = document.getElementById('paymentDetails' + roomID);
            if (paymentContainer) paymentContainer.style.display = 'block';
            const detail = document.getElementById(method + 'Details' + roomID);
            if (detail) detail.style.display = 'block';
        }

        function confirmPayment(roomID) {
            if (!selectedPaymentMethod[roomID]) {
                showAlert('Please choose a payment method to continue with your booking.', 'warning', 'Payment Method Required');
                return;
            }

            const btn = (event && event.target) ? event.target : null;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }

            if (selectedPaymentMethod[roomID] === 'paypal') {
                const total = document.getElementById('totalPriceInput' + roomID).value;
                const form = document.getElementById('bookingForm' + roomID);
                if (!form) {
                    showAlert('We encountered a technical issue loading the booking form. Please refresh the page and try again.', 'danger', 'Form Error');
                    if (btn) btn.disabled = false;
                    return;
                }

                const fd = new FormData(form);
                fd.append('ajax', '1');
                fd.set('paymentMethod', 'paypal');

                fetch('<?php echo FRONTEND_URL; ?>/php/process_booking.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                }).then(r => r.json()).then(data => {
                    if (data && data.success && data.bookingID) {
                        const url = '<?php echo BASE_URL; ?>/integrations/paypal/create_order.php?roomID=' + encodeURIComponent(roomID) + '&amount=' + encodeURIComponent(total) + '&bookingID=' + encodeURIComponent(data.bookingID);
                        window.location.href = url;
                    } else {
                        showAlert('Unable to process your booking at this time. Your information has been saved, but we couldn\'t connect to PayPal. Please try again or contact support if the issue persists.', 'danger', 'Booking Failed');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'Confirm Payment';
                        }
                    }
                }).catch(err => {
                    console.error(err);
                    showAlert('A network error occurred while processing your booking. Please check your internet connection and try again. If the problem continues, please contact our support team.', 'danger', 'Connection Error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Confirm Payment';
                    }
                });
                return;
            }

            // Default: submit booking form (other payment methods handled by server)
            setTimeout(() => {
                document.getElementById('bookingForm' + roomID).submit();
            }, 800);
        }
    </script>

</body>
</html>