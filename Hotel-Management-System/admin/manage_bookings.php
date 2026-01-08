<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

Auth::requireAdmin('../frontend/login.php');

$bookingModel = new Booking();
$userModel = new User();

require_once SMS_PATH . '/SmsService.php';
require_once GMAIL_PATH . '/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingID = (int)$_POST['bookingID'];
    $action = $_POST['action'];
    
    // Get booking details for SMS
    $booking = $bookingModel->find($bookingID);
    $customer = $booking ? $userModel->find($booking['userID']) : null;
    $phoneNumber = $booking['phoneNumber'] ?? '';
    $checkInDate = $booking['checkInDate'] ?? '';
    $customerName = $customer ? trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '')) : '';
    
    if ($action === 'confirm') {
        $bookingModel->confirm($bookingID);
        
        // Send SMS notification
        if (!empty($phoneNumber)) {
            try {
                $smsService = new SmsService();
                $smsService->sendBookingApprovalSms($bookingID, $phoneNumber, $customerName, $checkInDate);
            } catch (Exception $e) {
                error_log('SMS Error: ' . $e->getMessage());
            }
        }
        
        // Send email receipt automatically
        try {
            $emailService = new EmailService();
            $bookingData = $bookingModel->getByIdWithDetails($bookingID);
            
            if ($bookingData && !empty($bookingData['email'])) {
                $bookingData['customerName'] = trim($bookingData['firstName'] . ' ' . $bookingData['lastName']);
                $emailResult = $emailService->sendBookingReceipt($bookingData);
                if (!$emailResult['success']) {
                    error_log('Email Receipt Error for Booking #' . $bookingID . ': ' . ($emailResult['error'] ?? 'Unknown error'));
                }
            }
        } catch (Exception $e) {
            error_log('Email Service Error: ' . $e->getMessage());
        }
        
        $message = "Booking confirmed successfully!";
        $messageType = "success";
    } elseif ($action === 'cancel') {
        $bookingModel->cancel($bookingID);
        
        // Send SMS notification
        if (!empty($phoneNumber)) {
            try {
                $smsService = new SmsService();
                $smsService->sendBookingCancelledSms($bookingID, $phoneNumber, $customerName);
            } catch (Exception $e) {
                error_log('SMS Error: ' . $e->getMessage());
            }
        }
        
        $message = "Booking cancelled successfully!";
        $messageType = "warning";
    } elseif ($action === 'complete') {
        $bookingModel->complete($bookingID);
        
        // Send SMS notification
        if (!empty($phoneNumber)) {
            try {
                $smsService = new SmsService();
                $smsService->sendBookingCompletedSms($bookingID, $phoneNumber, $customerName);
            } catch (Exception $e) {
                error_log('SMS Error: ' . $e->getMessage());
            }
        }
        
        $message = "Booking marked as completed!";
        $messageType = "info";
    } elseif ($action === 'edit') {
        $newStatus = $_POST['newStatus'];
        $newPaymentStatus = $_POST['newPaymentStatus'];
        
        // Validate status values
        $validStatuses = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED];
        $validPaymentStatuses = [Booking::PAYMENT_PENDING, Booking::PAYMENT_PAID, Booking::PAYMENT_REFUNDED];
        
        if (in_array($newStatus, $validStatuses) && in_array($newPaymentStatus, $validPaymentStatuses)) {
            $bookingModel->update($bookingID, [
                'bookingStatus' => $newStatus,
                'paymentStatus' => $newPaymentStatus,
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
            
            $message = "Booking updated successfully!";
            $messageType = "success";
        } else {
            $message = "Invalid status value!";
            $messageType = "danger";
        }
    }
}

// Get bookings based on status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($statusFilter !== 'all') {
    $bookingsData = $bookingModel->getByStatusWithDetails($statusFilter);
} else {
    $bookingsData = $bookingModel->getAllWithDetails();
}

// Get counts for each status
$countAll = $bookingModel->count();
$countPending = $bookingModel->countBy('bookingStatus', Booking::STATUS_PENDING);
$countConfirmed = $bookingModel->countBy('bookingStatus', Booking::STATUS_CONFIRMED);
$countCancelled = $bookingModel->countBy('bookingStatus', Booking::STATUS_CANCELLED);
$countCompleted = $bookingModel->countBy('bookingStatus', Booking::STATUS_COMPLETED);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/css/admin.css">
</head>

<body class="bg-light">
    <?php include INCLUDES_PATH . '/loader.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include ADMIN_INCLUDES_PATH . '/sidebar.php'; ?>

            <div class="col-12 col-lg-10 p-3 p-lg-4">
                <div class="page-header">
                    <h2>Manage Bookings</h2>
                    <p>View and manage all customer bookings</p>
                </div>

                <?php if (isset($message)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-4 mb-4">
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar3 display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countAll; ?></h3>
                                <small>Total Bookings</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <i class="bi bi-hourglass-split display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countPending; ?></h3>
                                <small>Pending</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countConfirmed; ?></h3>
                                <small>Confirmed</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-x-circle display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countCancelled; ?></h3>
                                <small>Cancelled</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="bi bi-flag display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countCompleted; ?></h3>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="?status=all">All</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="?status=pending">Pending</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>" href="?status=confirmed">Confirmed</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>" href="?status=cancelled">Cancelled</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>" href="?status=completed">Completed</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <?php if (count($bookingsData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Guest</th>
                                                <th>Room</th>
                                                <th>Dates</th>
                                                <th>Total</th>
                                                <th>Payment</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookingsData as $booking): 
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
                                            <tr>
                                                <td>
                                                    <strong>#<?php echo $booking['bookingID']; ?></strong>
                                                </td>
                                                
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['roomName']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($booking['roomType']); ?></small>
                                                    </div>
                                                </td>

                                                <td>
                                                    <small>
                                                        <?php echo date('M d', strtotime($booking['checkInDate'])); ?> - 
                                                        <?php echo date('M d, Y', strtotime($booking['checkOutDate'])); ?>
                                                    </small>
                                                </td>

                                                <td>
                                                    <strong>₱<?php echo number_format($booking['totalPrice'], 2); ?></strong>
                                                </td>

                                                <td>
                                                    <span class="badge <?php echo $paymentBadgeClass; ?>">
                                                        <?php echo ucfirst($booking['paymentStatus']); ?>
                                                    </span>
                                                    <br><small class="text-muted">
                                                        <?php
                                                            $pm = $booking['paymentMethod'] ?? '';
                                                            $ps = strtolower($booking['paymentStatus'] ?? '');
                                                            if ($pm !== '') {
                                                                if (strtolower($pm) === 'paypal') {
                                                                    echo '<i class="bi bi-paypal me-1"></i>PayPal';
                                                                } else {
                                                                    echo ucfirst(str_replace('_', ' ', $pm));
                                                                }
                                                            } elseif ($ps === 'paid') {
                                                                echo '<i class="bi bi-paypal me-1"></i>PayPal';
                                                            } else {
                                                                echo '-';
                                                            }
                                                        ?>
                                                    </small>
                                                </td>

                                                <td>
                                                    <span class="badge <?php echo $statusBadgeClass; ?>">
                                                        <?php echo ucfirst($booking['bookingStatus']); ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $booking['bookingID']; ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($booking['bookingStatus'] === 'pending'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                                                <input type="hidden" name="action" value="confirm">
                                                                <button type="submit" class="btn btn-outline-success" title="Approve">
                                                                    <i class="bi bi-check-lg"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                                                <input type="hidden" name="action" value="cancel">
                                                                <button type="submit" class="btn btn-outline-danger" title="Reject">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($booking['bookingStatus'] === 'confirmed'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                                                <input type="hidden" name="action" value="complete">
                                                                <button type="submit" class="btn btn-outline-info" title="Mark as Completed">
                                                                    <i class="bi bi-flag"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <button class="btn btn-outline-secondary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $booking['bookingID']; ?>"
                                                                title="Edit Booking">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Booking Details #<?php echo $booking['bookingID']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6 class="fw-bold mb-3">Guest Information</h6>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['fullName']); ?></p>
                                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phoneNumber']); ?></p>
                                                                    <p><strong>Guests:</strong> <?php echo $booking['numberOfGuests']; ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6 class="fw-bold mb-3">Booking Information</h6>
                                                                    <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['roomName']); ?> (<?php echo htmlspecialchars($booking['roomType']); ?>)</p>
                                                                    <p><strong>Check-in:</strong> <?php echo date('F d, Y', strtotime($booking['checkInDate'])); ?></p>
                                                                    <p><strong>Check-out:</strong> <?php echo date('F d, Y', strtotime($booking['checkOutDate'])); ?></p>
                                                                    <p><strong>Total Price:</strong> ₱<?php echo number_format($booking['totalPrice'], 2); ?></p>
                                                                        <p><strong>Payment:</strong> <?php
                                                                            $pm = $booking['paymentMethod'] ?? '';
                                                                            $ps = strtolower($booking['paymentStatus'] ?? '');
                                                                            if ($pm !== '') {
                                                                                if (strtolower($pm) === 'paypal') {
                                                                                    echo 'PayPal';
                                                                                } else {
                                                                                    echo ucfirst(str_replace('_', ' ', $pm));
                                                                                }
                                                                            } elseif ($ps === 'paid') {
                                                                                echo 'PayPal';
                                                                            } else {
                                                                                echo '-';
                                                                            }
                                                                            echo ' (' . ucfirst($booking['paymentStatus']) . ')';
                                                                        ?></p>
                                                                    <p><strong>Status:</strong> <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo ucfirst($booking['bookingStatus']); ?></span></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Booking #<?php echo $booking['bookingID']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                                                <input type="hidden" name="action" value="edit">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Guest</label>
                                                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']); ?></p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Room</label>
                                                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($booking['roomName']); ?> (<?php echo htmlspecialchars($booking['roomType']); ?>)</p>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="newStatus<?php echo $booking['bookingID']; ?>" class="form-label fw-bold">Booking Status</label>
                                                                    <select class="form-select" id="newStatus<?php echo $booking['bookingID']; ?>" name="newStatus" required>
                                                                        <option value="pending" <?php echo $booking['bookingStatus'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="confirmed" <?php echo $booking['bookingStatus'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                                        <option value="cancelled" <?php echo $booking['bookingStatus'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                        <option value="completed" <?php echo $booking['bookingStatus'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="newPaymentStatus<?php echo $booking['bookingID']; ?>" class="form-label fw-bold">Payment Status</label>
                                                                    <select class="form-select" id="newPaymentStatus<?php echo $booking['bookingID']; ?>" name="newPaymentStatus" required>
                                                                        <option value="pending" <?php echo $booking['paymentStatus'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="paid" <?php echo $booking['paymentStatus'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                                        <option value="refunded" <?php echo $booking['paymentStatus'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="alert alert-info small mb-0">
                                                                    <i class="bi bi-info-circle me-1"></i>
                                                                    Use this to correct accidental status changes. Note: SMS notifications are not sent when editing.
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox display-1 text-muted"></i>
                                    <h5 class="mt-3">No bookings found</h5>
                                    <p class="text-muted">There are no bookings matching your filter.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>
