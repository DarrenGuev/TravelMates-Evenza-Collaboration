<?php
session_start();
require_once '../../core/connect.php';
require_once '../../config/paypal.php';
require_once '../../includes/helpers.php';

$success_message = '';
$error_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$eventId = isset($_POST['eventId']) ? intval($_POST['eventId']) : (isset($_GET['eventId']) ? intval($_GET['eventId']) : 1);
$packageTier = isset($_POST['packageTier']) ? htmlspecialchars($_POST['packageTier']) : (isset($_GET['packageTier']) ? htmlspecialchars($_GET['packageTier']) : '');
$packageName = isset($_POST['packageName']) ? htmlspecialchars($_POST['packageName']) : (isset($_GET['packageName']) ? htmlspecialchars($_GET['packageName']) : '');
$packagePrice = isset($_POST['packagePrice']) ? floatval($_POST['packagePrice']) : (isset($_GET['packagePrice']) ? floatval($_GET['packagePrice']) : 0.0);
$fullName = isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '');
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '');
$mobile = isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : (isset($_SESSION['user_mobile']) ? $_SESSION['user_mobile'] : '');

if (!empty($packageTier) && empty($packageName)) {
    $packageName = $packageTier . ' Package';
}

$event = null;
if ($eventId > 0) {
    $eventQuery = "SELECT eventId, title, category, venue FROM events WHERE eventId = ?";
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
                'venue' => $eventRow['venue'] ?? 'Venue TBA'
            ];
        }
    }
}

if (!$event) {
    header('Location: events.php');
    exit;
}

$totalAmount = $packagePrice;

$paymentStatus = isset($_GET['status']) ? $_GET['status'] : 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $activePage = 'events'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="payment-page-section py-5 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                            <li class="breadcrumb-item"><a href="eventDetails.php?id=<?php echo $eventId; ?>">Event Details</a></li>
                            <li class="breadcrumb-item"><a href="reservation.php?eventId=<?php echo $eventId; ?>&package=<?php echo urlencode($packageName); ?>">Reservation</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Payment</li>
                        </ol>
                    </div>

                    <div class="luxury-card payment-summary-card p-5 mb-4">
                        <h2 class="page-title mb-4 text-center">Payment Summary</h2>
                        
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
                        
                        <div class="payment-summary-content">
                            <div class="payment-summary-item mb-4">
                                <div class="payment-label">Event Name</div>
                                <div class="payment-value"><?php echo htmlspecialchars($event['name']); ?></div>
                            </div>

                            <div class="payment-summary-item mb-4">
                                <div class="payment-label">Package</div>
                                <div class="payment-value"><?php echo htmlspecialchars($packageName); ?> - ₱ <?php echo number_format($packagePrice, 2); ?></div>
                            </div>

                            <hr class="my-4">

                            <div class="payment-total">
                                <div class="payment-total-label">Total Amount</div>
                                <div class="payment-total-value">₱ <?php echo number_format($totalAmount, 2); ?></div>
                            </div>
                        </div>

                        <?php if ($paymentStatus === 'pending'): ?>
                            <div class="payment-button-section mt-5">
                                <?php
                                if (!isset($_SESSION['pending_reservation_data'])) {
                                    $_SESSION['pending_reservation_data'] = [
                                        'userId' => isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0,
                                        'eventId' => $eventId,
                                        'packageId' => isset($_GET['packageId']) ? intval($_GET['packageId']) : 0,
                                        'packageName' => $packageName,
                                        'packageTier' => $packageTier,
                                        'reservationDate' => date('Y-m-d'),
                                        'startTime' => null,
                                        'endTime' => null,
                                        'totalAmount' => $packagePrice
                                    ];
                                }
                                
                                $_SESSION['pending_event_id'] = $eventId;
                                $_SESSION['pending_package_id'] = isset($_GET['packageId']) ? intval($_GET['packageId']) : 0;
                                $_SESSION['pending_amount'] = $packagePrice;
                                ?>
                                <div id="paypal-button-container"></div>
                                <p class="text-center text-muted small mt-3 mb-0">
                                    Secure payment powered by PayPal. You will be redirected to PayPal to complete your payment.
                                </p>
                                
                                <input type="hidden" id="paypal-event-id" value="<?php echo $eventId; ?>">
                                <input type="hidden" id="paypal-package-id" value="<?php echo isset($_GET['packageId']) ? intval($_GET['packageId']) : 0; ?>">
                                <input type="hidden" id="paypal-amount" value="<?php echo $packagePrice; ?>">
                            </div>
                        <?php endif; ?>

                        <div id="statusMessages" class="mt-4">
                            <?php if ($paymentStatus === 'processing'): ?>
                                <div class="status-message status-processing">
                                    <div class="status-icon">
                                    </div>
                                    <div class="status-content">
                                        <h5>Payment Processing</h5>
                                        <p>Your payment is being processed. Please wait...</p>
                                    </div>
                                </div>
                            <?php elseif ($paymentStatus === 'success'): ?>
                                <div class="status-message status-success">
                                    <div class="status-icon">
                                    </div>
                                    <div class="status-content">
                                        <h5>Payment Successful</h5>
                                        <p>Your payment has been processed successfully. Redirecting to confirmation page...</p>
                                    </div>
                                </div>
                                <script>
                                    setTimeout(function() {
                                        window.location.href = '../../index.php';
                                    }, 2000);
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="luxury-card p-4">
                        <h5 class="mb-3">Payment Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Reservation Details:</strong>
                                <ul class="list-unstyled mt-2">
                                    <li><small>Name: <?php echo htmlspecialchars($fullName); ?></small></li>
                                    <li><small>Email: <?php echo htmlspecialchars($email); ?></small></li>
                                    <li><small>Mobile: <?php echo htmlspecialchars(formatPhoneNumber($mobile)); ?></small></li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Event Details:</strong>
                                <ul class="list-unstyled mt-2">
                                    <li><small>Date: <?php echo htmlspecialchars($event['date']); ?></small></li>
                                    <li><small>Time: <?php echo htmlspecialchars($event['time']); ?></small></li>
                                    <li><small>Venue: <?php echo htmlspecialchars($event['venue']); ?></small></li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <small>
                                <strong>Secure Payment:</strong> Your payment information is encrypted and secure. 
                                We use PayPal's secure payment processing system to ensure your financial data is protected.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo getPayPalClientId(); ?>&currency=<?php echo PAYPAL_CURRENCY; ?>&intent=capture"></script>
    <script src="../../assets/js/payment.js?v=<?php echo time(); ?>"></script>
</body>
</html>

