<?php
session_start();
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$successToken = isset($_GET['success']) ? trim($_GET['success']) : '';
$transactionId = isset($_GET['tx']) ? trim($_GET['tx']) : '';

$isAuthorized = false;
if (!empty($successToken) && isset($_SESSION['payment_success_token'])) {
    if ($successToken === $_SESSION['payment_success_token']) {
        if (isset($_SESSION['payment_success_time']) && (time() - $_SESSION['payment_success_time']) < 300) {
            $isAuthorized = true;
        }
    }
}

if (!$isAuthorized) {
    $_SESSION['error_message'] = 'Invalid or expired payment confirmation link. Please complete your payment to view confirmation.';
    header('Location: ../../index.php');
    exit;
}

$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$reservationData = isset($_SESSION['pending_reservation_data']) ? $_SESSION['pending_reservation_data'] : null;

$reservationId = isset($_SESSION['payment_reservation_id']) ? intval($_SESSION['payment_reservation_id']) : 0;
$eventId = isset($_SESSION['payment_event_id']) ? intval($_SESSION['payment_event_id']) : ($reservationData ? $reservationData['eventId'] : 0);
$packageId = isset($_SESSION['payment_package_id']) ? intval($_SESSION['payment_package_id']) : ($reservationData ? $reservationData['packageId'] : 0);
$amount = isset($_SESSION['payment_amount']) ? floatval($_SESSION['payment_amount']) : ($reservationData ? $reservationData['totalAmount'] : 0);

if (empty($transactionId) && isset($_SESSION['payment_transaction_id'])) {
    $transactionId = $_SESSION['payment_transaction_id'];
}

$errorOccurred = false;
$errorMessage = '';
$transactionReference = '';
$reservationSaved = false;
$paymentSaved = false;

$event = null;
$package = null;
$reservationCode = null;

if ($reservationId > 0) {
    $resCodeQuery = "SELECT reservationCode FROM reservations WHERE reservationId = ?";
    $resCodeStmt = mysqli_prepare($conn, $resCodeQuery);
    if ($resCodeStmt) {
        mysqli_stmt_bind_param($resCodeStmt, "i", $reservationId);
        mysqli_stmt_execute($resCodeStmt);
        $resCodeResult = mysqli_stmt_get_result($resCodeStmt);
        if ($resCodeRow = mysqli_fetch_assoc($resCodeResult)) {
            $reservationCode = $resCodeRow['reservationCode'];
        }
        mysqli_stmt_close($resCodeStmt);
    }
}

if ($eventId > 0) {
    $eventQuery = "SELECT eventId, title, venue, description FROM events WHERE eventId = ?";
    $eventStmt = mysqli_prepare($conn, $eventQuery);
    if ($eventStmt) {
        mysqli_stmt_bind_param($eventStmt, "i", $eventId);
        mysqli_stmt_execute($eventStmt);
        $eventResult = mysqli_stmt_get_result($eventStmt);
        $event = mysqli_fetch_assoc($eventResult);
        mysqli_stmt_close($eventStmt);
    }
}

if ($packageId > 0) {
    $packageQuery = "SELECT packageId, packageName, price FROM packages WHERE packageId = ?";
    $packageStmt = mysqli_prepare($conn, $packageQuery);
    if ($packageStmt) {
        mysqli_stmt_bind_param($packageStmt, "i", $packageId);
        mysqli_stmt_execute($packageStmt);
        $packageResult = mysqli_stmt_get_result($packageStmt);
        $package = mysqli_fetch_assoc($packageResult);
        mysqli_stmt_close($packageStmt);
    }
}

$packageTier = 'Unknown';
if ($package && !empty($package['packageName'])) {
    $packageTier = str_replace(' Package', '', $package['packageName']);
    $packageName = $package['packageName'];
} else {
    $packageName = 'Package';
}

if ($reservationId <= 0 && $userId > 0 && $eventId > 0 && $packageId > 0 && $reservationData) {
    $reservationDate = $reservationData['reservationDate'] ?? date('Y-m-d');
    $startTime = $reservationData['startTime'] ?? null;
    $endTime = $reservationData['endTime'] ?? null;
    $totalAmount = $reservationData['totalAmount'] ?? $amount;
    
    $reservationCode = generateUniqueReservationCode($conn);
    
    $reservationQuery = "INSERT INTO reservations (userId, eventId, packageId, reservationDate, startTime, endTime, totalAmount, status, reservationCode) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?)";
    
    $reservationStmt = mysqli_prepare($conn, $reservationQuery);
    
    if ($reservationStmt) {
        mysqli_stmt_bind_param($reservationStmt, "iisssdss", $userId, $eventId, $packageId, $reservationDate, $startTime, $endTime, $totalAmount, $reservationCode);
        
        if (mysqli_stmt_execute($reservationStmt)) {
            $reservationId = mysqli_insert_id($conn);
            $reservationSaved = true;
        } else {
            $errorOccurred = true;
            $errorMessage = 'Failed to save reservation details.';
            $transactionReference = $transactionId;
        }
        
        mysqli_stmt_close($reservationStmt);
    } else {
        $errorOccurred = true;
        $errorMessage = 'Database error while saving reservation.';
        $transactionReference = $transactionId;
    }
} elseif ($reservationId > 0) {
    $checkCodeQuery = "SELECT reservationCode FROM reservations WHERE reservationId = ?";
    $checkCodeStmt = mysqli_prepare($conn, $checkCodeQuery);
    $existingCode = null;
    if ($checkCodeStmt) {
        mysqli_stmt_bind_param($checkCodeStmt, "i", $reservationId);
        mysqli_stmt_execute($checkCodeStmt);
        $checkCodeResult = mysqli_stmt_get_result($checkCodeStmt);
        if ($codeRow = mysqli_fetch_assoc($checkCodeResult)) {
            $existingCode = $codeRow['reservationCode'];
        }
        mysqli_stmt_close($checkCodeStmt);
    }
    
        if (empty($existingCode)) {
            $reservationCode = generateUniqueReservationCode($conn);
            $updateQuery = "UPDATE reservations SET status = 'completed', reservationCode = ? WHERE reservationId = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "si", $reservationCode, $reservationId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        } else {
            $reservationCode = $existingCode;
            $updateQuery = "UPDATE reservations SET status = 'completed' WHERE reservationId = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, "i", $reservationId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        }
    
    if ($amount <= 0) {
        $resQuery = "SELECT totalAmount FROM reservations WHERE reservationId = ?";
        $resStmt = mysqli_prepare($conn, $resQuery);
        if ($resStmt) {
            mysqli_stmt_bind_param($resStmt, "i", $reservationId);
            mysqli_stmt_execute($resStmt);
            $resResult = mysqli_stmt_get_result($resStmt);
            if ($resRow = mysqli_fetch_assoc($resResult)) {
                $amount = floatval($resRow['totalAmount']);
            }
            mysqli_stmt_close($resStmt);
        }
    }
    $reservationSaved = true;
} else {
    $errorOccurred = true;
    $errorMessage = 'Reservation data not found. Please contact support.';
    $transactionReference = $transactionId;
}

if ($reservationSaved && !$errorOccurred && $reservationId > 0) {
    $checkPaymentQuery = "SELECT paymentId FROM payments WHERE transactionId = ?";
    $checkStmt = mysqli_prepare($conn, $checkPaymentQuery);
    $paymentExists = false;
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "s", $transactionId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_num_rows($checkResult) > 0) {
            $paymentExists = true;
        }
        mysqli_stmt_close($checkStmt);
    }
    
    if (!$paymentExists) {
        $paymentQuery = "INSERT INTO payments (reservationId, userId, transactionId, amount, packageName, paymentStatus) 
                         VALUES (?, ?, ?, ?, ?, 'completed')";
        
        $paymentStmt = mysqli_prepare($conn, $paymentQuery);
        
        if ($paymentStmt) {
            mysqli_stmt_bind_param($paymentStmt, "iisds", $reservationId, $userId, $transactionId, $amount, $packageName);
            
            if (mysqli_stmt_execute($paymentStmt)) {
                $paymentSaved = true;
            } else {
                $errorOccurred = true;
                $errorMessage = 'Failed to save payment details.';
                $transactionReference = $transactionId;
            }
            
            mysqli_stmt_close($paymentStmt);
        } else {
            $errorOccurred = true;
            $errorMessage = 'Database error while saving payment.';
            $transactionReference = $transactionId;
        }
    } else {
        $paymentSaved = true;
    }
    
    if ($reservationSaved && $paymentSaved && $reservationId > 0) {
        $statusCheckQuery = "SELECT status FROM reservations WHERE reservationId = ?";
        $statusCheckStmt = mysqli_prepare($conn, $statusCheckQuery);
        if ($statusCheckStmt) {
            mysqli_stmt_bind_param($statusCheckStmt, "i", $reservationId);
            mysqli_stmt_execute($statusCheckStmt);
            $statusResult = mysqli_stmt_get_result($statusCheckStmt);
            
            if ($statusRow = mysqli_fetch_assoc($statusResult)) {
                
            }
            mysqli_stmt_close($statusCheckStmt);
        }
        
        // Fetch transaction ID from payments table
        if (empty($transactionId)) {
            $txQuery = "SELECT transactionId FROM payments WHERE reservationId = ? ORDER BY paymentId DESC LIMIT 1";
            $txStmt = mysqli_prepare($conn, $txQuery);
            if ($txStmt) {
                mysqli_stmt_bind_param($txStmt, "i", $reservationId);
                mysqli_stmt_execute($txStmt);
                $txResult = mysqli_stmt_get_result($txStmt);
                if ($txRow = mysqli_fetch_assoc($txResult)) {
                    $transactionId = $txRow['transactionId'];
                }
                mysqli_stmt_close($txStmt);
            }
        }
    }
}

unset($_SESSION['payment_success_token']);
unset($_SESSION['payment_success_time']);
unset($_SESSION['payment_transaction_id']);
unset($_SESSION['payment_reservation_id']);
unset($_SESSION['payment_event_id']);
unset($_SESSION['payment_package_id']);
unset($_SESSION['payment_amount']);
unset($_SESSION['pending_reservation_id']);
unset($_SESSION['pending_event_id']);
unset($_SESSION['pending_package_id']);
unset($_SESSION['pending_amount']);
unset($_SESSION['pending_reservation_data']);

if (!$event) {
    $event = [
        'title' => 'Event Not Found',
        'venue' => 'N/A'
    ];
}

if (!$package) {
    $package = [
        'packageName' => $packageName,
        'price' => $amount
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .confirmation-page-section {
            background: linear-gradient(135deg, #F9F7F2 0%, #FFFFFF 100%);
            min-height: 100vh;
        }
        .confirmation-card {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(74, 93, 74, 0.1);
        }
        .thank-you-message {
            padding: 3rem 0 2rem;
        }
        .success-icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #4A5D4A 0%, #6B8E6B 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(74, 93, 74, 0.3);
        }
        .success-icon-wrapper i {
            font-size: 2.5rem;
            color: #FFFFFF;
        }
        .thank-you-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }
        .thank-you-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 1.2rem;
            color: #666666;
            font-weight: 400;
        }
        .confirmation-details {
            background: #F9F7F2;
            border-radius: 15px;
            padding: 2.5rem;
            margin: 2rem 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 0;
            border-bottom: 1px solid rgba(74, 93, 74, 0.1);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #666666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: #1A1A1A;
            font-weight: 600;
            text-align: right;
        }
        .transaction-id {
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            color: #4A5D4A;
            background: #F0F0F0;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
        }
        .package-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .package-badge.bronze {
            background: linear-gradient(135deg, #B8956A 0%, #9A7A52 100%);
            color: white;
        }
        .package-badge.silver {
            background: linear-gradient(135deg, #E8E8E8 0%, #C8C8C8 100%);
            color: #2C2C2C;
        }
        .package-badge.gold {
            background: linear-gradient(135deg, #F4D03F 0%, #D4AF37 100%);
            color: #2C2C2C;
        }
        .error-message-box {
            background: #FFF5F5;
            border: 2px solid #FEB2B2;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
        }
        .error-message-box i {
            font-size: 3rem;
            color: #E53E3E;
            margin-bottom: 1rem;
        }
        .error-message-box h3 {
            font-family: 'Playfair Display', serif;
            color: #1A1A1A;
            margin-bottom: 1rem;
        }
        .error-message-box p {
            color: #666666;
            margin-bottom: 1.5rem;
        }
        .transaction-ref-box {
            background: #F9F7F2;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        .transaction-ref-box strong {
            color: #4A5D4A;
        }
        .confirmation-actions {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(74, 93, 74, 0.1);
        }
        .btn-view-reservations {
            background: linear-gradient(135deg, #4A5D4E 0%, #5A6B4F 100%);
            border: none;
            color: #FFFFFF;
            padding: 1rem 2.5rem;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 93, 78, 0.2);
            text-decoration: none;
            display: inline-block;
        }
        .btn-view-reservations:hover {
            background: linear-gradient(135deg, #6B5D4A 0%, #8B7B5F 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 93, 74, 0.3);
            color: #FFFFFF;
        }
        .star-icon {
            color: #ddd !important;
            transition: color 0.2s ease;
        }
        .star-icon:hover,
        .star-rating:hover .star-icon {
            color: #FFD700 !important;
        }
        .star-icon.fas.text-warning {
            color: #FFD700 !important;
        }
        .star-rating .star-icon.fas {
            color: #FFD700 !important;
        }
        .contact-email-link {
            color: #4A5D4A;
            text-decoration: underline;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .contact-email-link:hover {
            color: #6B8E6B;
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .thank-you-title {
                font-size: 2rem;
            }
            .confirmation-details {
                padding: 1.5rem;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .detail-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = ''; include __DIR__ . '/includes/nav.php'; ?>

    <div class="confirmation-page-section py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($errorOccurred): ?>
                        <div class="confirmation-card p-5">
                            <div class="error-message-box">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>We're Sorry</h3>
                                <p><?php echo htmlspecialchars($errorMessage); ?></p>
                                <p class="small">Don't worry, your payment was successful. Our team has been notified and will process your reservation manually.</p>
                                
                                <?php if (!empty($transactionReference)): ?>
                                    <div class="transaction-ref-box">
                                        <strong>Transaction Reference:</strong><br>
                                        <span class="transaction-id"><?php echo htmlspecialchars($transactionReference); ?></span>
                                        <p class="small mt-2 mb-0">Please save this reference number and contact our support team at <a href="mailto:support@evenza.com">support@evenza.com</a> if you need assistance.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <a href="profile.php" class="btn btn-primary-luxury">View My Profile</a>
                                    <a href="../../index.php" class="btn btn-outline-luxury ms-2">Return Home</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="confirmation-card p-5">
                            <div class="thank-you-message text-center">
                                <div class="success-icon-wrapper">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h1 class="thank-you-title">Thank You!</h1>
                                <p class="thank-you-subtitle">Your payment has been confirmed successfully</p>
                            </div>

                            <div class="confirmation-details">
                                <?php if (!empty($reservationCode)): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Reservation Code</span>
                                    <span class="detail-value">
                                        <span class="transaction-id" style="font-size: 1.2rem; font-weight: bold; letter-spacing: 2px;"><?php echo htmlspecialchars($reservationCode); ?></span>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Package Tier</span>
                                    <span class="detail-value">
                                        <span class="package-badge <?php echo strtolower($packageTier); ?>">
                                            <?php echo htmlspecialchars($packageTier); ?> Package
                                        </span>
                                    </span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Event</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['title']); ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Amount Paid</span>
                                    <span class="detail-value">₱ <?php echo number_format($amount, 2); ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="detail-label">Reservation ID</span>
                                    <span class="detail-value">#<?php echo $reservationId; ?></span>
                                </div>
                                
                                <?php if (!empty($transactionId)): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Transaction ID</span>
                                    <span class="detail-value">
                                        <span class="transaction-id"><?php echo htmlspecialchars($transactionId); ?></span>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="confirmation-actions text-center">
                                <a href="profile.php" class="btn btn-view-reservations">
                                    <i class="fas fa-calendar-check me-2"></i>View Reservations
                                </a>
                            </div>

                            <?php
                            // Check if review already exists for this reservation
                            $reviewExists = false;
                            $existingReview = null;
                            if ($reservationId > 0) {
                                $reviewCheckQuery = "SELECT reviewId, rating, comment FROM reviews WHERE reservationId = ?";
                                $reviewCheckStmt = mysqli_prepare($conn, $reviewCheckQuery);
                                if ($reviewCheckStmt) {
                                    mysqli_stmt_bind_param($reviewCheckStmt, "i", $reservationId);
                                    mysqli_stmt_execute($reviewCheckStmt);
                                    $reviewCheckResult = mysqli_stmt_get_result($reviewCheckStmt);
                                    $existingReview = mysqli_fetch_assoc($reviewCheckResult);
                                    mysqli_stmt_close($reviewCheckStmt);
                                    $reviewExists = !empty($existingReview);
                                }
                            }
                            ?>

                            <?php if (!$reviewExists && $reservationId > 0): ?>
                            <div class="review-section mt-5 pt-4 border-top">
                                <h4 class="text-center mb-4">Share Your Experience</h4>
                                <p class="text-center text-muted mb-4">We'd love to hear about your experience with our reservation process!</p>
                                
                                <div class="review-form-container">
                                    <form id="reviewForm" class="review-form">
                                        <input type="hidden" id="reviewReservationId" value="<?php echo $reservationId; ?>">
                                        
                                        <div class="mb-4">
                                            <label class="form-label text-center d-block mb-3">
                                                <strong>Rate your experience <span class="text-danger">*</span></strong>
                                            </label>
                                            <div class="star-rating d-flex justify-content-center" id="starRating" style="gap: 0.5rem; margin: 1rem 0;">
                                                <i class="far fa-star star-icon" data-rating="1" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                                                <i class="far fa-star star-icon" data-rating="2" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                                                <i class="far fa-star star-icon" data-rating="3" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                                                <i class="far fa-star star-icon" data-rating="4" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                                                <i class="far fa-star star-icon" data-rating="5" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s;"></i>
                                            </div>
                                            <input type="hidden" id="reviewRating" name="rating" required>
                                            <div class="invalid-feedback text-center d-block" id="ratingError" style="display: none;"></div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="reviewComment" class="form-label">
                                                <strong>Your Feedback (Optional)</strong>
                                            </label>
                                            <textarea class="form-control luxury-input" id="reviewComment" name="comment" rows="4" placeholder="Tell us about your experience with our reservation process..."></textarea>
                                        </div>

                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary-luxury" id="submitReviewBtn">
                                                <i class="fas fa-paper-plane me-2"></i>Submit Review
                                            </button>
                                        </div>

                                        <div id="reviewMessage" class="mt-3 text-center"></div>
                                    </form>
                                </div>
                            </div>
                            <?php elseif ($reviewExists): ?>
                            <div class="review-section mt-5 pt-4 border-top">
                                <h4 class="text-center mb-4">Your Review</h4>
                                <div class="alert alert-success text-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Thank you for your review!</strong>
                                    <div class="mt-2">
                                        <?php
                                        $rating = intval($existingReview['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-muted"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($existingReview['comment'])): ?>
                                        <p class="mt-2 mb-0"><?php echo htmlspecialchars($existingReview['comment']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="luxury-card p-4 mt-4">
                        <h5 class="mb-3 text-center">What's Next?</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                You will receive a confirmation email shortly
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Your tickets are available in your profile
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                For any questions, contact us at <a href="mailto:evenzacompany@gmail.com" class="contact-email-link">evenzacompany@gmail.com</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/review.js"></script>
</body>
</html>
