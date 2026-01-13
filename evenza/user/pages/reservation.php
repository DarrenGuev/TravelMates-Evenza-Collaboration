<?php
session_start();
require_once '../../core/connect.php';

$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $session_error = $_SESSION['error_message'];
    if (strpos($session_error, 'payment confirmation') === false && strpos($session_error, 'Invalid or expired') === false) {
        $error_message = $session_error;
    }
    unset($_SESSION['error_message']);
}

$eventId = isset($_GET['eventId']) ? intval($_GET['eventId']) : 0;

$packages = [];
$packageMap = []; // dedupe by tier, prefer latest (highest id)
$packagesQuery = "SELECT packageId, packageName, price FROM packages ORDER BY packageId DESC";
$packagesResult = mysqli_query($conn, $packagesQuery);
if ($packagesResult) {
    while ($row = mysqli_fetch_assoc($packagesResult)) {
        $tier = strtolower(str_replace(' Package', '', $row['packageName']));
        if (!isset($packageMap[$tier])) {
            $packageMap[$tier] = [
                'id' => $row['packageId'],
                'name' => $row['packageName'],
                'tier' => ucfirst($tier),
                'price' => floatval($row['price'])
            ];
        }
    }
    mysqli_free_result($packagesResult);
}

// normalize order Bronze, Silver, Gold
$orderedTiers = ['bronze', 'silver', 'gold'];
foreach ($orderedTiers as $t) {
    if (isset($packageMap[$t])) {
        $packages[] = $packageMap[$t];
    }
}

$event = null;
if ($eventId > 0) {
    $eventQuery = "SELECT eventId, title, category, venue, description FROM events WHERE eventId = ?";
    $eventStmt = mysqli_prepare($conn, $eventQuery);
    if ($eventStmt) {
        mysqli_stmt_bind_param($eventStmt, "i", $eventId);
        mysqli_stmt_execute($eventStmt);
        $eventResult = mysqli_stmt_get_result($eventStmt);
        $eventRow = mysqli_fetch_assoc($eventResult);
        mysqli_stmt_close($eventStmt);
        
        if ($eventRow) {
            $event = [
                'name' => $eventRow['title'],
                'category' => $eventRow['category'] ?? '',
                'date' => 'Date TBA',
                'time' => 'Time TBA',
                'venue' => $eventRow['venue'] ?? 'Venue TBA',
                'description' => $eventRow['description'] ?? ''
            ];
        }
    }
}

if (!$event) {
    header('Location: events.php');
    exit;
}

function calculatePackagePrice($packageTier) {
    switch(strtolower($packageTier)) {
        case 'bronze':
            return 7000;
        case 'silver':
            return 10000;
        case 'gold':
            return 15000;
        default:
            return 0;
    }
}

$selectedPackageId = isset($_POST['packageId']) ? intval($_POST['packageId']) : (isset($_GET['packageId']) ? intval($_GET['packageId']) : ($packages[0]['id'] ?? 1));
$selectedPackage = $packages[0] ?? ['id' => 1, 'name' => 'Bronze Package', 'tier' => 'Bronze', 'price' => 7000];
foreach ($packages as $p) {
    if ($p['id'] == $selectedPackageId) {
        $selectedPackage = $p;
        break;
    }
}

$totalAmount = $selectedPackage['price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $activePage = 'events'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="reservation-page-section py-5 mt-5">
        <div class="container">
            <div aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                    <li class="breadcrumb-item"><a href="eventDetails.php?id=<?php echo $eventId; ?>">Event Details</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reservation</li>
                </ol>
            </div>

            <div class="reservation-layout">
                <div class="reservation-form-column">
                    <div class="luxury-card p-4">
                        <h2 class="page-title mb-4">Reservation Form</h2>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="reservationForm" method="POST" action="../process/reserve.php">
                            <input type="hidden" name="eventId" value="<?php echo $eventId; ?>">
                            <input type="hidden" name="packageId" id="packageId" value="<?php echo $selectedPackage['id']; ?>">
                            <input type="hidden" name="packageTier" id="packageTier" value="<?php echo htmlspecialchars($selectedPackage['tier']); ?>">
                            <input type="hidden" name="packageName" id="packageName" value="<?php echo htmlspecialchars($selectedPackage['name']); ?>">
                            <input type="hidden" name="packagePrice" id="packagePrice" value="<?php echo $selectedPackage['price']; ?>">
                            <div class="mb-4">
                                <label for="fullName" class="form-label">Full Name <span class="required-asterisk">*</span></label>
                                <input type="text" class="form-control luxury-input" id="fullName" name="fullName" required placeholder="Enter your full name">
                                <div class="error-message" id="fullNameError"></div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address <span class="required-asterisk">*</span></label>
                                <input type="email" class="form-control luxury-input" id="email" name="email" required placeholder="your.email@example.com">
                                <div class="error-message" id="emailError"></div>
                            </div>

                            <div class="mb-4">
                                <label for="mobile" class="form-label">Mobile Number <span class="required-asterisk">*</span></label>
                                <input type="tel" class="form-control luxury-input" id="mobile" name="mobile" required placeholder="0921 123 4567">
                                <div class="error-message" id="mobileError"></div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="reservationDate" class="form-label">Preferred Date <span class="required-asterisk">*</span></label>
                                    <input type="date" class="form-control luxury-input" id="reservationDate" name="reservationDate" required style="accent-color: #4A5D4E;">
                                    <div class="error-message" id="reservationDateError"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="eventStartTime" class="form-label">Event Start Time <span class="required-asterisk">*</span></label>
                                    <select class="form-select luxury-input" id="eventStartTime" name="eventStartTime" required style="position: relative; z-index: 10;">
                                        <option value="">Select start time</option>
                                        <option value="08:00 AM">8:00 AM</option>
                                        <option value="09:00 AM">9:00 AM</option>
                                        <option value="10:00 AM">10:00 AM</option>
                                        <option value="11:00 AM">11:00 AM</option>
                                        <option value="12:00 PM">12:00 PM</option>
                                        <option value="01:00 PM">1:00 PM</option>
                                        <option value="02:00 PM">2:00 PM</option>
                                        <option value="03:00 PM">3:00 PM</option>
                                        <option value="04:00 PM">4:00 PM</option>
                                        <option value="05:00 PM">5:00 PM</option>
                                        <option value="06:00 PM">6:00 PM</option>
                                        <option value="07:00 PM">7:00 PM</option>
                                        <option value="08:00 PM">8:00 PM</option>
                                    </select>
                                    <div class="error-message" id="eventStartTimeError"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="eventEndTime" class="form-label">Event End Time <span class="required-asterisk">*</span></label>
                                    <select class="form-select luxury-input" id="eventEndTime" name="eventEndTime" required style="position: relative; z-index: 10;">
                                        <option value="">Select end time</option>
                                        <option value="09:00 AM">9:00 AM</option>
                                        <option value="10:00 AM">10:00 AM</option>
                                        <option value="11:00 AM">11:00 AM</option>
                                        <option value="12:00 PM">12:00 PM</option>
                                        <option value="01:00 PM">1:00 PM</option>
                                        <option value="02:00 PM">2:00 PM</option>
                                        <option value="03:00 PM">3:00 PM</option>
                                        <option value="04:00 PM">4:00 PM</option>
                                        <option value="05:00 PM">5:00 PM</option>
                                        <option value="06:00 PM">6:00 PM</option>
                                        <option value="07:00 PM">7:00 PM</option>
                                        <option value="08:00 PM">8:00 PM</option>
                                        <option value="09:00 PM">9:00 PM</option>
                                        <option value="10:00 PM">10:00 PM</option>
                                        <option value="11:00 PM">11:00 PM</option>
                                    </select>
                                    <div class="error-message" id="eventEndTimeError"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Select Package <span class="required-asterisk">*</span></label>
                                <div class="package-options d-flex gap-2 mt-2 flex-wrap" id="packageOptions">
                                    <?php foreach ($packages as $p): ?>
                                        <div class="package-tile" role="button" tabindex="0" data-id="<?php echo $p['id']; ?>" data-tier="<?php echo htmlspecialchars($p['tier']); ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo $p['price']; ?>">
                                            <div class="package-tile-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                            <div class="package-tile-price">₱ <?php echo number_format($p['price'], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="error-message" id="packageError"></div>
                                <small class="text-muted d-block mt-2">Choose a package to reserve the event as a single purchase.</small>
                            </div>

                            <div class="mobile-summary d-none">
                                <div class="luxury-card reservation-summary p-4 mb-4">
                                    <h4 class="mb-4">Reservation Summary</h4>
                                    
                                    <div class="summary-item mb-3">
                                        <div class="summary-label">Event Name</div>
                                        <div class="summary-value"><?php echo htmlspecialchars($event['name']); ?></div>
                                    </div>

                                    <div class="summary-item mb-3">
                                        <div class="summary-label">Date & Time</div>
                                        <div class="summary-value">
                                            <div id="mobileSummaryDate"><?php echo htmlspecialchars($event['date']); ?></div>
                                            <div class="text-muted small" id="mobileSummaryTimeRange"><?php echo htmlspecialchars($event['time']); ?></div>
                                        </div>
                                    </div>

                                    <div class="summary-item mb-3">
                                        <div class="summary-label">Venue</div>
                                        <div class="summary-value small"><?php echo htmlspecialchars($event['venue']); ?></div>
                                    </div>

                                    <hr class="my-4">
                                    <div class="summary-item mb-2">
                                        <div class="summary-label">Package</div>
                                        <div class="summary-value" id="mobileSummaryPackage"><?php echo htmlspecialchars($selectedPackage['name']); ?></div>
                                    </div>

                                    <hr class="my-4">
                                    <div class="summary-total">
                                        <div class="summary-total-label">Total Amount</div>
                                        <div class="summary-total-value" id="mobileSummaryTotal">₱ <?php echo number_format($totalAmount, 2); ?></div>
                                    </div>

                                    <div class="summary-note mt-4">
                                        <p class="small text-muted mb-0">
                                            Your reservation will be submitted for admin confirmation. Payment will be available once confirmed.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <a href="eventDetails.php?id=<?php echo $eventId; ?>" class="btn btn-outline-luxury flex-fill">Back to Event</a>
                                <button type="submit" class="btn btn-primary-luxury flex-fill" id="submitReservationBtn">Submit Reservation</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="reservation-summary-column">
                    <div class="luxury-card reservation-summary p-4 sticky-summary">
                        <h4 class="mb-4">Reservation Summary</h4>
                        
                        <div class="summary-item mb-3">
                            <div class="summary-label">Event Name</div>
                            <div class="summary-value"><?php echo htmlspecialchars($event['name']); ?></div>
                        </div>

                        <div class="summary-item mb-3">
                            <div class="summary-label">Date & Time</div>
                            <div class="summary-value">
                                <div id="summaryDate"><?php echo htmlspecialchars($event['date']); ?></div>
                                <div class="text-muted small" id="summaryTimeRange"><?php echo htmlspecialchars($event['time']); ?></div>
                            </div>
                        </div>

                        <div class="summary-item mb-3">
                            <div class="summary-label">Venue</div>
                            <div class="summary-value small"><?php echo htmlspecialchars($event['venue']); ?></div>
                        </div>

                        <hr class="my-4">
                        <div class="summary-item mb-2">
                            <div class="summary-label">Package</div>
                            <div class="summary-value" id="summaryPackage"><?php echo htmlspecialchars($selectedPackage['name']); ?></div>
                        </div>

                        <hr class="my-4">
                        <div class="summary-total">
                            <div class="summary-total-label">Total Amount</div>
                            <div class="summary-total-value" id="summaryTotal">₱ <?php echo number_format($totalAmount, 2); ?></div>
                        </div>

                        <div class="summary-note mt-4">
                            <p class="small text-muted mb-0">
                                Your reservation will be submitted for admin confirmation. Payment will be available once confirmed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Error Modal for Form Validation -->
    <div class="modal fade" id="reservationErrorModal" tabindex="-1" aria-labelledby="reservationErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-exclamation-circle custom-alert-icon error-icon"></i>
                    </div>
                    <h5 class="custom-alert-title">Form Incomplete</h5>
                    <p class="custom-alert-message" id="reservationErrorModalMessage">Please fill in all required fields before submitting.</p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn custom-alert-btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reservationSuccessModal" tabindex="-1" aria-labelledby="reservationSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="success-icon-wrapper mx-auto mb-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, #4A5D4A 0%, #6B8E6B 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="white" viewBox="0 0 16 16">
                            <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                        </svg>
                    </div>
                    <h3 class="mb-3" style="font-family: 'Playfair Display', serif;">Reservation Submitted!</h3>
                    <p class="text-muted mb-4">Your reservation has been successfully submitted and is now awaiting admin confirmation.</p>
                    <p class="small text-muted mb-4">Once confirmed, you will be able to proceed with payment from your profile page.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="profile.php" class="btn btn-primary-luxury">View My Reservations</a>
                        <a href="events.php" class="btn btn-outline-luxury">Browse More Events</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const successModal = new bootstrap.Modal(document.getElementById('reservationSuccessModal'));
            successModal.show();
        });
        <?php endif; ?>
    </script>
    <script src="../../assets/js/reservation.js"></script>
</body>
</html>