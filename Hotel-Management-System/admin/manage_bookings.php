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

// [MODULARIZED] Booking Action Logic moved to: php/booking_status.php

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
                                                <td><strong>#<?php echo $booking['bookingID']; ?></strong></td>
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
                                                <td><strong>₱<?php echo number_format($booking['totalPrice'], 2); ?></strong></td>
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
                                                        <button class="btn btn-outline-primary" onclick="viewBooking(<?php echo $booking['bookingID']; ?>)" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($booking['bookingStatus'] === 'pending'): ?>
                                                            <button class="btn btn-outline-success" onclick="updateBookingStatus(<?php echo $booking['bookingID']; ?>, 'confirm')" title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger" onclick="updateBookingStatus(<?php echo $booking['bookingID']; ?>, 'cancel')" title="Reject">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($booking['bookingStatus'] === 'confirmed'): ?>
                                                            <button class="btn btn-outline-info" onclick="updateBookingStatus(<?php echo $booking['bookingID']; ?>, 'complete')" title="Mark as Completed">
                                                                <i class="bi bi-flag"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-outline-secondary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $booking['bookingID']; ?>"
                                                                title="Edit Booking">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Edit Details Inline Modal -->
                                                    <div class="modal fade" id="editModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Booking #<?php echo $booking['bookingID']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form onsubmit="return submitEditForm(this)">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="bookingID" value="<?php echo $booking['bookingID']; ?>">
                                                                        <input type="hidden" name="bookingAction" value="edit">
                                                                        
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
                                                </td>
                                            </tr>
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

    <!-- Modals -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/view_booking.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/booking_status_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        const allBookingsData = <?php echo json_encode($bookingsData); ?>;
        
        // Check if booking was cancelled by user and disable edit buttons
        document.addEventListener('DOMContentLoaded', function() {
            allBookingsData.forEach(booking => {
                if (booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1) {
                    // Find and disable edit button for this booking
                    const editBtn = document.querySelector(`button[data-bs-target="#editModal${booking.bookingID}"]`);
                    if (editBtn) {
                        editBtn.disabled = true;
                        editBtn.classList.remove('btn-outline-secondary');
                        editBtn.classList.add('btn-outline-secondary', 'opacity-50');
                        editBtn.title = 'Cannot edit - Cancelled by user';
                        editBtn.innerHTML = '<i class="bi bi-lock"></i>';
                    }
                }
            });
        });
        
        function submitEditForm(form) {
            const formData = new FormData(form);
            // Close the modal first
            const modalEl = form.closest('.modal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if(modalInstance) modalInstance.hide();
            
            // Show loading
            showBookingLoading('edit'); 

            fetch('php/booking_status.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showBookingResult(data.success, data.message);
                if(data.success) {
                   // Ensure reload happens
                   const resultModalEl = document.getElementById('bookingResultModal');
                   if(resultModalEl) resultModalEl.setAttribute('data-reload', 'true');
                }
            })
            .catch(err => {
                showBookingResult(false, 'Error: ' + err.message);
            });
            return false;
        }
    </script>
    <script src="javascript/admin.js"></script>
</body>
</html>