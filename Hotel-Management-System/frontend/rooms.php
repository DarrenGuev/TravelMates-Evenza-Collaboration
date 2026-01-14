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
$allFeatures = $featureModel->getAllOrdered();

foreach ($allFeatures as $row) {
    $category = $row['categoryName'] ?? 'General';
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
function groupFeaturesByCategory($features)
{
    $grouped = [];
    foreach ($features as $feature) {
        $category = $feature['categoryName'] ?? 'General';
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $feature['featureName'];
    }
    return $grouped;
}
?>

<?php $title = "Rooms "; ?>
<?php include INCLUDES_PATH . '/head.php'; ?>

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
                                                        <label class="form-check-label small"
                                                            for="<?php echo htmlspecialchars($typeId); ?>"><?php echo htmlspecialchars($type['roomType']); ?></label>
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
                                                            <h2 class="accordion-header"
                                                                id="<?php echo htmlspecialchars($headingId); ?>">
                                                                <button class="accordion-button collapsed py-2"
                                                                    type="button" data-bs-toggle="collapse"
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
                                                                                    <input
                                                                                        class="form-check-input filter-checkbox feature-checkbox"
                                                                                        type="checkbox"
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
                                                                        <div class="text-muted small">No features available
                                                                        </div>
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
                                        <label class="form-check-label small"
                                            for="<?php echo htmlspecialchars($typeId); ?>"><?php echo htmlspecialchars($type['roomType']); ?></label>
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
                                    $isAvailable = isset($row['quantity']) && (int)$row['quantity'] > 0;
                                    ?>
                                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3 pb-4 room-card"
                                        data-room-type="<?php echo strtolower($roomTypeName); ?>"
                                        data-price="<?php echo $row['base_price']; ?>"
                                        data-capacity="<?php echo (int) $row['capacity']; ?>"
                                        data-features="<?php echo strtolower(implode(',', $features)); ?>">
                                        <div class="card h-100 bg-transparent shadow rounded-3 <?php echo !$isAvailable ? 'opacity-75' : ''; ?>">
                                            <div
                                                class="ratio ratio-4x3 overflow-hidden rounded-top-3 position-relative gallery-item">
                                                <?php if (!$isAvailable): ?>
                                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 2;">
                                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                                        <i class="bi bi-x-circle me-1"></i>Unavailable
                                                    </span>
                                                </div>
                                                <?php endif; ?>
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
                                                <div class="fst-italic">
                                                    <?php if (!empty($features)) {
                                                        $shown = 0;
                                                        foreach ($features as $featureName) {
                                                            if ($shown >= 3)
                                                                break;
                                                            ?>
                                                            <span
                                                                class="text-white text-muted small me-1 mb-1"><?php echo htmlspecialchars($featureName . " -"); ?></span>
                                                            <?php
                                                            $shown++;
                                                        }
                                                    } else { ?>
                                                        <span class="text-muted small">No features listed</span>
                                                    <?php } ?>
                                                    <span class="text-muted small">and More..</span>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent border-top-0 p-4 pt-0">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php if ($isAvailable): ?>
                                                    <a href="<?php echo FRONTEND_URL; ?>/roomPage.php?roomID=<?php echo $row['roomID']; ?>"
                                                        class="btn btn-warning flex-grow-1">Book Now</a>
                                                    <?php else: ?>
                                                    <button class="btn btn-secondary flex-grow-1" disabled>
                                                        <i class="bi bi-x-circle me-1"></i>Unavailable
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-secondary flex-grow-1" data-bs-toggle="modal"
                                                        data-bs-target="#roomDetailModal<?php echo $row['roomID']; ?>">More
                                                        Details</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="roomDetailModal<?php echo $row['roomID']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($row['roomName']); ?></h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-12 col-md-6 justify-content-center text-center">
                                                            <div class="d-flex align-items-center gallery-item d-inline-block rounded-3 overflow-hidden">
                                                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($row['imagePath']); ?>"
                                                                    alt="<?php echo htmlspecialchars($row['roomName']); ?>"
                                                                    class="img-fluid rounded-3"
                                                                    style="width: 100%; height: 300px; object-fit: cover;">
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6 justify-content-center d-flex flex-column">
                                                            
                                                                <div class="row">
                                                                    <p class="fw-semibold mb-2">Room Details</p>
                                                                    <p class="small mb-1"><strong>Type:</strong>
                                                                        <?php echo htmlspecialchars($roomTypeName); ?></p>
                                                                    <p class="small mb-1"><strong>Capacity:</strong>
                                                                        <?php echo (int) $row['capacity']; ?> Guests</p>
                                                                    <p class="small mb-1"><strong>Available:</strong>
                                                                        <?php echo (int) $row['quantity']; ?> Rooms</p>
                                                                    <p class="small mb-1"><strong>Price:</strong>
                                                                        ₱<?php echo number_format($row['base_price'], 2); ?> / night
                                                                    </p>
                                                                </div>
                                                                <div class="row align-items-center d-flex flex-row">
                                                                    <div class="mb-2 justify-content-evenly">
                                                                        <?php if (!empty($features)) {
                                                                            foreach ($features as $featureName) { ?>
                                                                                <span
                                                                                    class="badge bg-dark mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                                                            <?php }
                                                                        } ?>
                                                                    </div>
                                                                </div>
                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <?php if ($isAvailable): ?>
                                                    <a href="<?php echo FRONTEND_URL; ?>/roomPage.php?roomID=<?php echo $row['roomID']; ?>"
                                                        class="btn btn-warning">Book Now</a>
                                                    <?php else: ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        <i class="bi bi-x-circle me-1"></i>Unavailable
                                                    </button>
                                                    <?php endif; ?>
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
                menu.addEventListener('click', function (e) {
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

                    priceRange.addEventListener('input', function () {
                        priceDisplay.textContent = 'Up to ₱' + parseInt(this.value).toLocaleString();
                        syncPriceRanges(this);
                        applyFilters();
                    });
                }
            });

            roomTypeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    syncCheckboxes(this);
                    applyFilters();
                });
            });

            featureCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    syncCheckboxes(this);
                    updateCategoryBadges();
                    applyFilters();
                });
            });

            guestCapacities.forEach(select => {
                if (select) {
                    select.addEventListener('change', function () {
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
        });
    </script>

</body>

</html>