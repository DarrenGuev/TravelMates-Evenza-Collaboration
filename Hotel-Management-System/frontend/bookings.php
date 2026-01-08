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

function getBookingRoomFeaturesArray($roomID, $roomModel = null) {
    if ($roomModel === null) {
        $roomModel = new Room();
    }
    $features = $roomModel->getFeatures($roomID);
    return array_column($features, 'featureName');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - My Bookings</title>
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/flag.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
</head>

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
                    $features = getBookingRoomFeaturesArray($booking['roomID'], $roomModel);
                    
                    $checkIn = new DateTime($booking['checkInDate']);
                    $checkOut = new DateTime($booking['checkOutDate']);
                    $nights = $checkIn->diff($checkOut)->days;
                    
                    $statusBadgeClass = match($booking['bookingStatus']) {
                        'confirmed' => 'bg-success',
                        'pending' => 'bg-warning text-dark',
                        'cancelled' => 'bg-danger',
                        'completed' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    
                    $paymentBadgeClass = match($booking['paymentStatus']) {
                        'paid' => 'bg-success',
                        'pending' => 'bg-warning text-dark',
                        'refunded' => 'bg-secondary',
                        default => 'bg-secondary'
                    };
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
                                        <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($booking['roomName']); ?></h5>
                                        <span class="badge <?php echo $statusBadgeClass; ?> fs-6">
                                            <?php echo ucfirst($booking['bookingStatus']); ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($booking['roomType']); ?> Room • Booking #<?php echo $booking['bookingID']; ?></p>
                                    
                                    <!-- Features -->
                                    <div class="mb-3">
                                        <?php foreach ($features as $featureName): ?>
                                            <span class="badge bg-dark me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Booking Info -->
                                    <div class="row">
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Check-in</small>
                                            <strong><?php echo date('M d, Y', strtotime($booking['checkInDate'])); ?></strong>
                                        </div>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Check-out</small>
                                            <strong><?php echo date('M d, Y', strtotime($booking['checkOutDate'])); ?></strong>
                                        </div>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Duration</small>
                                            <strong><?php echo $nights; ?> night(s)</strong>
                                        </div>
                                        <div class="col-6 col-sm-4 mb-2">
                                            <small class="text-muted d-block">Guests</small>
                                            <strong><?php echo $booking['numberOfGuests']; ?> guest(s)</strong>
                                        </div>
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
                                    
                                    <div class="d-grid gap-2">
                                        <?php if ($booking['bookingStatus'] === 'confirmed'): ?>
                                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $booking['bookingID']; ?>">
                                                <i class="bi bi-receipt me-1"></i>View Receipt
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['bookingStatus'] === 'pending'): ?>
                                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['bookingID']; ?>">
                                                <i class="bi bi-x-circle me-1"></i>Cancel Booking
                                            </button>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted text-center">
                                            Booked on <?php echo date('M d, Y', strtotime($booking['createdAt'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal -->
                <?php if ($booking['bookingStatus'] === 'pending'): ?>
                <div class="modal fade" id="cancelModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Cancel Booking</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="php/cancel_booking.php" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                    <p>Are you sure you want to cancel your booking for <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong>?</p>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-info-circle me-1"></i>This action cannot be undone.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                    <button type="submit" class="btn btn-danger">Yes, Cancel Booking</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Receipt Modal -->
                <?php if ($booking['bookingStatus'] === 'confirmed'): ?>
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
    <script>
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
    </script>
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
</body>
</html>