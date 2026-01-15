<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once DBCONNECT_PATH . '/connect.php';
require_once __DIR__ . '/../classes/autoload.php';

Auth::requireLogin('login.php');
$userID = Auth::getUserId();

$bookingModel = new Booking();
$roomModel = new Room();

$bookingsData = $bookingModel->getByUserWithDetails($userID);

// Fetch Evenza reservations from API
try {
    $evenzaApiUrl = 'http://172.20.10.10/TravelMates-Evenza-Collaboration/evenza/api/user-bookings.php?userId=' . $userID;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $evenzaApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if (!$curlError && $httpCode === 200) {
        $evenzaData = json_decode($response, true);
        if (isset($evenzaData['success']) && $evenzaData['success'] && isset($evenzaData['data'])) {
            // Transform Evenza reservations to match booking structure
            foreach ($evenzaData['data'] as $reservation) {
                $transformedReservation = [
                    'bookingID' => $reservation['reservationId'],
                    'roomName' => isset($reservation['eventName']) ? $reservation['eventName'] : 'Event Reservation',
                    'imagePath' => 'images/carousel/placeholder.jpg', // Default placeholder
                    'checkInDate' => $reservation['date'],
                    'checkOutDate' => $reservation['date'],
                    'totalPrice' => isset($reservation['totalAmount']) ? $reservation['totalAmount'] : 0,
                    'bookingStatus' => isset($reservation['status']) ? $reservation['status'] : 'pending',
                    'paymentStatus' => 'paid',
                    'createdAt' => isset($reservation['createdAt']) ? $reservation['createdAt'] : null,
                    'fullName' => Auth::getDisplayName(),
                    'email' => '', // Not provided by API
                    'phoneNumber' => '', // Not provided by API
                    'numberOfGuests' => 1,
                    'paymentMethod' => 'paypal',
                    'source' => 'evenza',
                    'packageName' => isset($reservation['packageName']) ? $reservation['packageName'] : '',
                    'venue' => isset($reservation['venue']) ? $reservation['venue'] : '',
                    'time' => isset($reservation['time']) ? $reservation['time'] : ''
                ];
                $bookingsData[] = $transformedReservation;
            }
        }
    }
} catch (Exception $e) {
    // Silently fail - just show hotel bookings
    error_log('Failed to fetch Evenza reservations: ' . $e->getMessage());
}

// Sort all bookings by check-in date (most recent first)
usort($bookingsData, function($a, $b) {
    $dateA = isset($a['checkInDate']) ? strtotime($a['checkInDate']) : 0;
    $dateB = isset($b['checkInDate']) ? strtotime($b['checkInDate']) : 0;
    return $dateB - $dateA;
});

function getBookingRoomFeaturesArray($roomID, $roomModel = null) {
    if ($roomModel === null) {
        $roomModel = new Room();
    }
    $features = $roomModel->getFeatures($roomID);
    return array_column($features, 'featureName');
}
?>
<?php $title = "My Bookings "; ?>
<?php include INCLUDES_PATH . '/head.php'; ?>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <!-- Page Header -->
    <div class="container-fluid bg-body-tertiary" style="padding-top: 7rem; padding-bottom: 3rem;">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="fw-bold mb-2"><i class="bi bi-calendar-check me-2"></i>My Bookings</h1>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars(Auth::getDisplayName()); ?>! Manage your reservations here.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bookings List -->
        <?php if (count($bookingsData) > 0): ?>
            <div class="row g-4">
                <?php foreach ($bookingsData as $booking): 
                    // Check if this is an Evenza booking or Hotel booking
                    $isEvenzaBooking = isset($booking['source']) && $booking['source'] === 'evenza';
                    
                    // Get features only for hotel bookings
                    $features = [];
                    if (!$isEvenzaBooking && isset($booking['roomID'])) {
                        $features = getBookingRoomFeaturesArray($booking['roomID'], $roomModel);
                    }
                    
                    // Calculate nights
                    $checkIn = new DateTime($booking['checkInDate']);
                    $checkOut = new DateTime($booking['checkOutDate']);
                    $nights = $checkIn->diff($checkOut)->days;
                    if ($nights == 0 && $isEvenzaBooking) {
                        $nights = 1; // Default for event bookings
                    }
                    
                    $statusBadgeClass = match($booking['bookingStatus']) {
                        'confirmed' => 'bg-success',
                        'pending' => 'bg-warning',
                        'cancelled' => 'bg-danger',
                        'completed' => 'bg-info',
                        'approved' => 'bg-success',
                        default => 'bg-secondary'
                    };
                    
                    $paymentBadgeClass = match($booking['paymentStatus']) {
                        'paid' => 'bg-success',
                        'pending' => 'bg-warning text-dark',
                        'refunded' => 'bg-secondary',
                        default => 'bg-secondary'
                    };
                    
                    // Determine room type display
                    $roomTypeDisplay = '';
                    if ($isEvenzaBooking) {
                        $roomTypeDisplay = isset($booking['packageName']) ? $booking['packageName'] : 'Event';
                    } else {
                        $roomTypeDisplay = isset($booking['roomType']) ? $booking['roomType'] : '';
                    }
                ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="row g-0">
                            <!-- Room Image -->
                            <div class="col-12 col-md-3">
                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($booking['imagePath']); ?>" 
                                    alt="<?php echo htmlspecialchars($booking['roomName']); ?>" 
                                    class="img-fluid h-100 w-100" style="object-fit: cover; min-height: 200px;">
                            </div>
                            
                            <!-- Booking Details -->
                            <div class="col-12 col-md-6">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title fw-bold mb-0">
                                            <?php echo htmlspecialchars($booking['roomName']); ?>
                                            <?php if ($isEvenzaBooking): ?>
                                                <span class="badge bg-info ms-2" style="font-size: 0.7rem;">Event Booking</span>
                                            <?php endif; ?>
                                        </h5>
                                        <span class="btn btn <?php echo $statusBadgeClass; ?> display-none pe-none ms-2 border-0 fs-5">
                                            <?php echo ucfirst($booking['bookingStatus']); ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <?php if ($isEvenzaBooking): ?>
                                            <?php echo htmlspecialchars($roomTypeDisplay); ?>
                                            <?php if (isset($booking['venue']) && $booking['venue']): ?>
                                                • <?php echo htmlspecialchars($booking['venue']); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($roomTypeDisplay); ?> Room
                                        <?php endif; ?>
                                        • Booking #<?php echo $booking['bookingID']; ?>
                                    </p>
                                    
                                    <!-- Features (Hotel bookings only) -->
                                    <?php if (!$isEvenzaBooking && count($features) > 0): ?>
                                    <div class="mb-3">
                                        <?php foreach ($features as $featureName): ?>
                                            <span class="badge bg-dark me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Booking Info -->
                                    <div class="row">
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block"><?php echo $isEvenzaBooking ? 'Event Date' : 'Check-in'; ?></small>
                                            <strong><?php echo date('M d, Y', strtotime($booking['checkInDate'])); ?></strong>
                                        </div>
                                        <?php if (!$isEvenzaBooking || $nights > 0): ?>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block"><?php echo $isEvenzaBooking ? 'End Date' : 'Check-out'; ?></small>
                                            <strong><?php echo date('M d, Y', strtotime($booking['checkOutDate'])); ?></strong>
                                        </div>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Duration</small>
                                            <strong><?php echo $nights; ?> <?php echo $isEvenzaBooking ? 'day(s)' : 'night(s)'; ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (isset($booking['time']) && $booking['time'] && $isEvenzaBooking): ?>
                                        <div class="col-12 col-sm-8 mb-2">
                                            <small class="text-muted d-block">Event Time</small>
                                            <strong><?php echo htmlspecialchars($booking['time']); ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!$isEvenzaBooking): ?>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Guests</small>
                                            <strong><?php echo $booking['numberOfGuests']; ?> guest(s)</strong>
                                        </div>
                                        <?php endif; ?>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Payment</small>
                                            <?php
                                                    $pm = $booking['paymentMethod'] ?? '';
                                                    $ps = strtolower($booking['paymentStatus'] ?? '');
                                                    if ($pm !== '') {
                                                        if (strtolower($pm) === 'paypal') {
                                                            echo '<strong><i class="bi bi-paypal me-1"></i>PayPal</strong>';
                                                        } else {
                                                            echo '<strong>' . ucfirst(str_replace('_', ' ', $pm)) . '</strong>';
                                                        }
                                                    } elseif ($ps === 'paid') {
                                                        echo '<strong><i class="bi bi-paypal me-1"></i>PayPal</strong>';
                                                    } else {
                                                        echo '<strong>-</strong>';
                                                    }
                                                ?>
                                        </div>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Payment Status</small>
                                            <span class="badge <?php echo $paymentBadgeClass; ?>"><?php echo ucfirst($booking['paymentStatus']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Price & Actions -->
                            <div class="col-12 col-md-3 bg-body-tertiary">
                                <div class="card-body p-4 h-100 d-flex flex-column justify-content-between">
                                    <div class="text-center text-md-end mb-3">
                                        <small class="text-muted d-block">Total Price</small>
                                        <h4 class="fw-bold text-warning mb-0">₱<?php echo number_format($booking['totalPrice'], 2); ?></h4>
                                    </div>
                                    
                                    <!-- Only show action buttons for hotel bookings, not Evenza bookings -->
                                    <?php if (!$isEvenzaBooking): ?>
                                    <div class="d-grid gap-2 justify-content-center">
                                        <?php if ($booking['bookingStatus'] === 'confirmed'): ?>
                                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $booking['bookingID']; ?>">
                                                <i class="bi bi-receipt me-1"></i>View Receipt
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['bookingID']; ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Request Refund
                                            </button>
                                        <?php endif; ?>

                                        <?php
                                            // Show refund button for any paid booking except when completed.
                                            $isPaid = strtolower($booking['paymentStatus'] ?? '') === 'paid';
                                            if ($isPaid && $booking['bookingStatus'] !== 'completed' && $booking['bookingStatus'] !== 'confirmed'): ?>
                                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal<?php echo $booking['bookingID']; ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Request Refund
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['bookingStatus'] === 'pending'): 
                                            // If user has already requested a refund, show waiting badge instead of cancel button
                                            $isUserRefundRequest = isset($booking['cancelledByUser']) && $booking['cancelledByUser'] == 1;
                                        ?>
                                            <?php if ($isUserRefundRequest): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-warning text-dark text-center py-2">Waiting for refund approval</span>
                                                    <button type="button" class="btn btn-link p-0 ms-2 refund-info-btn" 
                                                            data-bookingid="<?php echo $booking['bookingID']; ?>" 
                                                            data-reason="<?php echo htmlspecialchars($booking['refundReason'] ?? '', ENT_QUOTES); ?>"
                                                            title="Refund details">
                                                        <i class="bi bi-info-circle"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['bookingID']; ?>">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel Booking
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted text-center">
                                            Booked on <?php echo date('M d, Y', strtotime($booking['createdAt'])); ?>
                                        </small>
                                    </div>
                                    <?php else: ?>
                                    <!-- Evenza bookings: just show booked date -->
                                    <div class="d-grid gap-2 justify-content-center">
                                        <small class="text-muted text-center">
                                            Booked on <?php echo isset($booking['createdAt']) ? date('M d, Y', strtotime($booking['createdAt'])) : 'N/A'; ?>
                                        </small>
                                        <small class="text-info text-center">
                                            <i class="bi bi-info-circle me-1"></i>Event booking from Evenza system
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancel/Refund Modal (Only for Hotel bookings) -->
                <?php if (!$isEvenzaBooking && ($booking['bookingStatus'] === 'pending' || $booking['bookingStatus'] === 'confirmed')): ?>
                <?php 
                    $isConfirmed = $booking['bookingStatus'] === 'confirmed';
                    $isPaid = $booking['paymentStatus'] === 'paid';
                    $modalHeaderClass = $isConfirmed ? 'bg-warning' : 'bg-danger';
                    $modalTitle = $isConfirmed ? 'Request Refund' : 'Cancel Booking';
                    $modalIcon = $isConfirmed ? 'arrow-counterclockwise' : 'exclamation-triangle';
                    $buttonText = $isConfirmed ? 'Submit Refund Request' : 'Yes, Cancel Booking';
                    $buttonClass = $isConfirmed ? 'btn-warning' : 'btn-danger';
                ?>
                <div class="modal fade" id="cancelModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header <?php echo $modalHeaderClass; ?> text-white">
                                <h5 class="modal-title"><i class="bi bi-<?php echo $modalIcon; ?> me-2"></i><?php echo $modalTitle; ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="php/cancel_booking.php" method="POST" onsubmit="return confirmCancellation(this, <?php echo $isConfirmed ? 'true' : 'false'; ?>);">
                                <div class="modal-body">
                                    <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                    
                                    <?php if ($isConfirmed): ?>
                                        <p>You are requesting a refund for your booking:</p>
                                        <div class="card mb-3 bg-body-secondary border-0">
                                            <div class="card-body">
                                                <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong><br>
                                                <small class="text-muted">
                                                    Check-in: <?php echo date('M d, Y', strtotime($booking['checkInDate'])); ?><br>
                                                    Total Paid: ₱<?php echo number_format($booking['totalPrice'], 2); ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="refundReason<?php echo $booking['bookingID']; ?>" class="form-label">
                                                <strong>Reason for Refund Request <span class="text-danger">*</span></strong>
                                            </label>
                                            <textarea class="form-control" 
                                                      id="refundReason<?php echo $booking['bookingID']; ?>" 
                                                      name="refundReason" 
                                                      rows="4" 
                                                      required 
                                                      placeholder="Please explain why you need to cancel this confirmed booking..."
                                                      minlength="10"
                                                      maxlength="500"></textarea>
                                            <small class="text-muted">Minimum 10 characters. This will be reviewed by our admin team.</small>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Please note:</strong>
                                            <ul class="mb-0 mt-2 small">
                                                <li>Your refund request will be reviewed by our admin team</li>
                                                <li>Processing time: 3-5 business days</li>
                                                <li>Refund will be processed to your original payment method</li>
                                                <li>You will be notified via email once processed</li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <p>Are you sure you want to cancel your booking for <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong>?</p>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-info-circle me-1"></i>This action cannot be undone.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                    <button type="submit" class="btn btn-secondary <?php echo $buttonClass; ?>">
                                        <i class="bi bi-<?php echo $isConfirmed ? 'send' : 'x-circle'; ?> me-1"></i><?php echo $buttonText; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Refund Modal for other paid statuses (e.g., cancelled or pending but paid) - Only for Hotel bookings -->
                <?php if (!$isEvenzaBooking && $isPaid && $booking['bookingStatus'] !== 'completed' && $booking['bookingStatus'] !== 'confirmed'): ?>
                <div class="modal fade" id="refundModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-white">
                                <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Request Refund</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="php/cancel_booking.php" method="POST" onsubmit="return confirmCancellation(this, true);">
                                <div class="modal-body">
                                    <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                    <input type="hidden" name="isRefundRequest" value="1">
                                    <p>You are requesting a refund for your booking:</p>
                                    <div class="card bg-body mb-3">
                                        <div class="card-body">
                                            <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong><br>
                                            <small class="text-muted">
                                                Check-in: <?php echo date('M d, Y', strtotime($booking['checkInDate'])); ?><br>
                                                Total Paid: ₱<?php echo number_format($booking['totalPrice'], 2); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="refundReason<?php echo $booking['bookingID']; ?>_other" class="form-label">
                                            <strong>Reason for Refund Request <span class="text-danger">*</span></strong>
                                        </label>
                                        <textarea class="form-control" 
                                                  id="refundReason<?php echo $booking['bookingID']; ?>_other" 
                                                  name="refundReason" 
                                                  rows="4" 
                                                  required 
                                                  placeholder="Please explain why you need to request a refund..."
                                                  minlength="10"
                                                  maxlength="500"></textarea>
                                        <small class="text-muted">Minimum 10 characters. This will be reviewed by our admin team.</small>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>Please note:</strong>
                                        <ul class="mb-0 mt-2 small">
                                            <li>Your refund request will be reviewed by our admin team</li>
                                            <li>Processing time: 3-5 business days</li>
                                            <li>Refund will be processed to your original payment method</li>
                                            <li>You will be notified via email once processed</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-send me-1"></i>Submit Refund Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Receipt Modal (Only for Hotel bookings) -->
                <?php if (!$isEvenzaBooking && $booking['bookingStatus'] === 'confirmed'): ?>
                <div class="modal fade" id="receiptModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Booking Receipt</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="<?php echo IMAGES_URL; ?>/logo/logoB.png" alt="TravelMates" style="width: 150px;">
                                    <h4 class="fw-bold mt-3">TravelMates Hotel</h4>
                                    <p class="text-muted">Official Booking Confirmation</p>
                                </div>
                                
                                <hr>
                                
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <strong>Booking Reference:</strong><br>
                                        <span class="text-muted">#<?php echo str_pad($booking['bookingID'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>Booking Date:</strong><br>
                                        <span class="text-muted"><?php echo date('F d, Y', strtotime($booking['createdAt'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="card bg-body-tertiary mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">Guest Information</h6>
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Full Name</small><br>
                                                <strong><?php echo htmlspecialchars($booking['fullName']); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Email</small><br>
                                                <strong><?php echo htmlspecialchars($booking['email']); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Phone Number</small><br>
                                                <strong><?php echo htmlspecialchars($booking['phoneNumber']); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Number of Guests</small><br>
                                                <strong><?php echo $booking['numberOfGuests']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card bg-body-tertiary mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">Room Details</h6>
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Room</small><br>
                                                <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Room Type</small><br>
                                                <strong><?php echo htmlspecialchars($booking['roomType']); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Check-in Date</small><br>
                                                <strong><?php echo date('F d, Y', strtotime($booking['checkInDate'])); ?></strong>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted">Check-out Date</small><br>
                                                <strong><?php echo date('F d, Y', strtotime($booking['checkOutDate'])); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card bg-warning bg-opacity-10 border-warning">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-sm-6">
                                                <h6 class="fw-bold mb-1">Payment Details</h6>
                                                <small class="text-muted">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['paymentMethod'])); ?> • 
                                                    <?php echo ucfirst($booking['paymentStatus']); ?>
                                                </small>
                                            </div>
                                            <div class="col-sm-6 text-sm-end">
                                                <small class="text-muted">Total Amount</small>
                                                <h3 class="fw-bold text-warning mb-0">₱<?php echo number_format($booking['totalPrice'], 2); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <small class="text-muted">Thank you for choosing TravelMates Hotel!</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i>Print Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Bookings -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                            <h4 class="fw-bold">No Bookings Yet</h4>
                            <p class="text-muted mb-4">You haven't made any reservations yet. Start exploring our rooms!</p>
                            <a href="<?php echo FRONTEND_URL; ?>/rooms.php" class="btn btn-warning">
                                <i class="bi bi-search me-1"></i>Browse Rooms
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <!-- Refund Info Modal -->
    <div class="modal fade" id="refundInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Refund Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="refundInfoMessage">Your refund request is under review by our admin team. Typical processing time is 3-5 business days.</p>
                    <div id="refundReasonBlock" class="mt-3 d-none">
                        <h6 class="fw-bold">Reason provided</h6>
                        <p id="refundReasonText" class="small text-muted mb-0"></p>
                    </div>
                    <div class="alert alert-info mt-3 small mb-0">
                        <i class="bi bi-clock-history me-1"></i>
                        Admin will review your request and process the refund to your original payment method if approved.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
        
        // Client-side validation for refund request
        function confirmCancellation(form, isRefundRequest) {
            if (isRefundRequest) {
                const reasonTextarea = form.querySelector('textarea[name="refundReason"]');
                if (!reasonTextarea) return true;
                
                const reason = reasonTextarea.value.trim();
                
                // Validate minimum length
                if (reason.length < 10) {
                    alert('Please provide a detailed reason for your refund request (minimum 10 characters).');
                    reasonTextarea.focus();
                    return false;
                }
                
                // Validate maximum length
                if (reason.length > 500) {
                    alert('Reason is too long. Please keep it under 500 characters.');
                    reasonTextarea.focus();
                    return false;
                }
                
                // Show loading indicator
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
                }
            }
            
            return true;
        }
    </script>
        <script>
            // Show refund info modal when user clicks the info button next to the waiting badge
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.refund-info-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const bookingID = this.dataset.bookingid || '';
                        const reason = this.dataset.reason || '';
                        const modalEl = document.getElementById('refundInfoModal');
                        const reasonBlock = document.getElementById('refundReasonBlock');
                        const reasonText = document.getElementById('refundReasonText');
                        const infoMessage = document.getElementById('refundInfoMessage');

                        if (bookingID) {
                            infoMessage.innerHTML = `Your refund request for booking #${bookingID} is under review by our admin team. Typical processing time is 3-5 business days.`;
                        }

                        if (reason && reason.trim().length > 0) {
                            reasonText.textContent = reason;
                            reasonBlock.classList.remove('d-none');
                        } else {
                            reasonBlock.classList.add('d-none');
                        }

                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    });
                });
            });
        </script>
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
</body>
</html>