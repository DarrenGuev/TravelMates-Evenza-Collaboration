<?php
session_start();
require_once '../../core/connect.php';
require_once '../../config/paypal.php';
require_once '../../includes/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userData = [
    'name' => $_SESSION['user_name'] ?? 'User',
    'email' => $_SESSION['user_email'] ?? '',
    'mobile' => $_SESSION['user_mobile'] ?? ''
];

$userQuery = "SELECT fullName, email, phone FROM users WHERE userId = ?";
$userStmt = mysqli_prepare($conn, $userQuery);
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    if ($userRow = mysqli_fetch_assoc($userResult)) {
        $userData['name'] = $userRow['fullName'] ?? $userData['name'];
        $userData['email'] = $userRow['email'] ?? $userData['email'];
        $userData['mobile'] = $userRow['phone'] ?? $userData['mobile'];
    }
    mysqli_stmt_close($userStmt);
}

$reservations = [];
$userId = $_SESSION['user_id'];
$query = "
    SELECT r.*, 
           e.title as eventName, 
           e.venue, 
           p.packageName,
           r.reservationDate as date,
           r.paymentDeadline,
           r.userCancelled,
           CONCAT(COALESCE(r.startTime, ''), ' - ', COALESCE(r.endTime, '')) as time
    FROM reservations r
    LEFT JOIN events e ON r.eventId = e.eventId
    LEFT JOIN packages p ON r.packageId = p.packageId
    WHERE r.userId = ?
    ORDER BY r.reservationDate DESC, r.createdAt DESC
";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $activePage = 'profile'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="profile-page-section py-5 mt-5">
        <div class="container">
            <div class="profile-content-wrapper" style="padding-top: 2rem;">
                <div class="profile-info-column">
                    <div class="luxury-card profile-card">
                        <div class="profile-header-section">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="profile-section-heading">Profile Information</h3>
                        </div>

                        <div class="profile-info-container">
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-user-circle"></i>
                            </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">Full Name</div>
                            <div class="profile-info-value"><?php echo htmlspecialchars($userData['name']); ?></div>
                                </div>
                        </div>

                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-envelope"></i>
                            </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">Email Address</div>
                            <div class="profile-info-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                                </div>
                        </div>

                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-phone"></i>
                            </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">Mobile Number</div>
                            <div class="profile-info-value"><?php echo htmlspecialchars(formatPhoneNumber($userData['mobile'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-action-section">
                            <button type="button" class="btn btn-primary-luxury w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                        </div>
                    </div>
                </div>

                <div class="reservations-column">
                    <div class="luxury-card profile-card">
                        <h3 class="profile-section-heading">My Reservations</h3>
                        
                        <?php if (empty($reservations)): ?>
                            <div class="text-center py-5">
                                <p class="text-muted">You don't have any reservations yet.</p>
                                <a href="events.php" class="btn btn-primary-luxury mt-3">Browse Events</a>
                            </div>
                        <?php else: ?>
                            <div class="reservations-list-wrapper">
                            <div class="reservations-list">
                                <?php foreach ($reservations as $reservation): ?>
                                    <div class="reservation-item luxury-card p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <h5 class="reservation-event-name mb-2"><?php echo htmlspecialchars($reservation['eventName']); ?></h5>
                                                
                                                <div class="reservation-date mb-2">
                                                    <?php if (!empty($reservation['date'])): ?>
                                                        <span><?php echo date('F j, Y', strtotime($reservation['date'])); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($reservation['time']) && $reservation['time'] !== ' - '): ?>
                                                        <span class="text-muted ms-2"><?php echo htmlspecialchars(formatTime12Hour($reservation['time'])); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="reservation-venue text-muted small">
                                                    <?php echo htmlspecialchars($reservation['venue']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                                <div class="ticket-status mb-2">
                                                    <?php 
                                                    $status = strtolower($reservation['status'] ?? 'pending');
                                                    if ($status === 'completed'): ?>
                                                        <span class="status-badge status-completed">
                                                            Completed
                                                        </span>
                                                    <?php elseif ($status === 'confirmed'): ?>
                                                        <span class="status-badge status-confirmed">
                                                            Confirmed
                                                        </span>
                                                    <?php elseif ($status === 'cancelled'): ?>
                                                        <span class="status-badge status-cancelled">
                                                            Cancelled
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-pending">
                                                            Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="ticket-details small text-muted">
                                                    <?php if (isset($reservation['packageName'])): ?>
                                                        <div>Package: <?php echo htmlspecialchars($reservation['packageName']); ?></div>
                                                    <?php else: ?>
                                                        <div>Qty: <?php echo htmlspecialchars($reservation['quantity'] ?? 1); ?></div>
                                                    <?php endif; ?>
                                                    <div>Total: ₱ <?php echo number_format($reservation['totalAmount'] ?? 0, 2); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3 text-center">
                                                <button type="button" class="btn btn-primary-luxury w-100" 
                                                    onclick="openReservationDetails(<?php echo htmlspecialchars(json_encode([
                                                        'reservationId' => $reservation['reservationId'],
                                                        'eventName' => $reservation['eventName'],
                                                        'eventId' => $reservation['eventId'],
                                                        'packageId' => $reservation['packageId'],
                                                        'packageName' => $reservation['packageName'],
                                                        'venue' => $reservation['venue'],
                                                        'date' => $reservation['date'],
                                                        'time' => $reservation['time'],
                                                    'totalAmount' => $reservation['totalAmount'],
                                                    'status' => $reservation['status'],
                                                    'paymentDeadline' => $reservation['paymentDeadline'] ?? null,
                                                    'userCancelled' => $reservation['userCancelled'] ?? 0
                                                ]), ENT_QUOTES); ?>)">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content luxury-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" class="form-control luxury-input" id="editName" value="<?php echo htmlspecialchars($userData['name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control luxury-input" id="editEmail" value="<?php echo htmlspecialchars($userData['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="editMobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control luxury-input" id="editMobile" value="<?php echo htmlspecialchars($userData['mobile']); ?>">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-luxury" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary-luxury" onclick="saveProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <div class="modal fade" id="reservationDetailsModal" tabindex="-1" aria-labelledby="reservationDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservationDetailsModalLabel" style="font-family: 'Playfair Display', serif;">Reservation Details</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Event</label>
                                <div class="fw-semibold" id="modalEventName"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Venue</label>
                                <div id="modalVenue"></div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Date</label>
                                        <div id="modalDate"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Time</label>
                                        <div id="modalTime"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Package</label>
                                <div id="modalPackage"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Reservation ID</label>
                                <div id="modalReservationId"></div>
                            </div>

                            <div class="border-top pt-4 mt-5">
                                <div class="mb-3 fw-semibold" style="font-family: 'Playfair Display', serif; font-size: 1.1rem;">Contact Details</div>
                                <div class="contact-details-grid">
                                    <div class="contact-details-label">Full Name</div>
                                    <div class="contact-details-value" id="modalUserName"><?php echo htmlspecialchars($userData['name']); ?></div>
                                    
                                    <div class="contact-details-label">Email</div>
                                    <div class="contact-details-value" id="modalUserEmail"><?php echo htmlspecialchars($userData['email']); ?></div>
                                    
                                    <div class="contact-details-label">Phone</div>
                                    <div class="contact-details-value" id="modalUserPhone"><?php echo htmlspecialchars(formatPhoneNumber($userData['mobile'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="p-4 rounded status-payment-box" style="background-color: #F9F7F2;">
                                <div class="text-center mb-4">
                                    <label class="form-label text-muted small d-block mb-2">Status</label>
                                    <div id="modalStatus"></div>
                                </div>
                                <hr style="margin: 1.5rem 0;">
                                <div class="text-center mb-4">
                                    <label class="form-label text-muted small d-block mb-2">Total Amount</label>
                                    <div class="h3" style="color: #4A5D4A; font-family: 'Playfair Display', serif;" id="modalAmount"></div>
                                </div>
                                
                                <div id="pendingMessage" class="alert alert-warning text-center" style="display: none;">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Awaiting Confirmation</strong>
                                    <p class="small mb-0 mt-2">Your reservation is pending admin confirmation. Payment will be available once confirmed.</p>
                                </div>
                                
                                <div id="cancelledMessage" class="alert alert-danger text-center" style="display: none;">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Reservation Cancelled</strong>
                                    <p class="small mb-0 mt-2">This reservation has been cancelled.</p>
                                </div>
                                
                                <div id="paidMessage" class="alert alert-success text-center" style="display: none;">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Payment Completed</strong>
                                    <p class="small mb-0 mt-2">Your reservation is confirmed and paid.</p>
                                </div>
                                
                                <div id="paymentDeadlineNotice" class="alert alert-warning text-center" style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Payment Required</strong>
                                    <p class="small mb-2 mt-2">Payment must be settled within <span id="deadlineDays"></span> days.</p>
                                    <p class="small mb-0"><strong>Deadline:</strong> <span id="deadlineDate"></span></p>
                                </div>
                                
                                <div id="paymentSection" style="display: none;">
                                    <p class="small text-muted text-center mb-3">Your reservation is confirmed. Complete payment below.</p>
                                    <div id="paypal-button-container-modal"></div>
                                </div>
                                
                                <div id="cancelReservationSection" class="mt-3" style="display: none;">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="showCancelConfirmation()">
                                        <i class="fas fa-times-circle me-2"></i>Cancel Reservation
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-luxury" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelReservationModal" tabindex="-1" aria-labelledby="cancelReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cancel-reservation-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelReservationModalLabel">Cancel Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cancel-reservation-body text-center">
                    <div class="cancel-icon-wrapper mb-4">
                        <i class="fas fa-exclamation-circle cancel-icon"></i>
                    </div>
                    <h5 class="cancel-header">Are you sure you want to continue?</h5>
                    <p class="cancel-subtext">Once cancelled, this reservation cannot be modified by the admin.</p>
                </div>
                <div class="modal-footer cancel-reservation-footer justify-content-center">
                    <button type="button" class="btn cancel-btn-no" data-bs-dismiss="modal">No</button>
                    <button type="button" class="btn cancel-btn-yes" onclick="confirmCancelReservation()">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-check-circle custom-alert-icon success-icon"></i>
                    </div>
                    <h5 class="custom-alert-title">Action Successful</h5>
                    <p class="custom-alert-message" id="successModalMessage"></p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn custom-alert-btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-exclamation-circle custom-alert-icon error-icon"></i>
                    </div>
                    <h5 class="custom-alert-title">Action Failed</h5>
                    <p class="custom-alert-message" id="errorModalMessage"></p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn custom-alert-btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo getPayPalClientId(); ?>&currency=<?php echo PAYPAL_CURRENCY; ?>&intent=capture"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/profile.js"></script>
</body>
</html>

