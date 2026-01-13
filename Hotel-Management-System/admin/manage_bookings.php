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
                                <!-- Pagination Info & Controls -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="text-muted" id="bookingsPaginationInfo">
                                        Showing <span id="bookingsShowingStart">0</span>-<span id="bookingsShowingEnd">0</span> of <span id="bookingsTotal">0</span>
                                    </div>
                                    <nav aria-label="Bookings pagination">
                                        <ul class="pagination pagination-sm mb-0" id="bookingsPaginationControls">
                                            <!-- Pagination buttons will be generated by JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
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
                                        <tbody id="manageBookingsTableBody">
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
                                                
                                                // Check if this is a refund request
                                                // Pending status with cancelledByUser flag means awaiting refund approval
                                                $isRefundRequest = $booking['bookingStatus'] === 'pending' && 
                                                                   isset($booking['cancelledByUser']) && 
                                                                   $booking['cancelledByUser'] == 1;
                                                
                                                // Check if this is a completed refund (cancelled by user)
                                                $isRefunded = $booking['bookingStatus'] === 'cancelled' && 
                                                              isset($booking['cancelledByUser']) && 
                                                              $booking['cancelledByUser'] == 1;
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
                                                    <?php if ($isRefundRequest): ?>
                                                        <span class="badge bg-warning text-dark" title="User has requested a refund">
                                                            <i class="bi bi-clock-history me-1"></i>Refund Requested
                                                        </span>
                                                    <?php elseif ($isRefunded): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle me-1"></i>Refunded
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge <?php echo $statusBadgeClass; ?>">
                                                            <?php echo ucfirst($booking['bookingStatus']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="viewBooking(<?php echo $booking['bookingID']; ?>)" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($isRefundRequest): ?>
                                                            <button class="btn btn-outline-warning" onclick="processRefund(<?php echo $booking['bookingID']; ?>)" title="Process Refund">
                                                                <i class="bi bi-cash-coin"></i>
                                                            </button>
                                                        <?php elseif ($booking['bookingStatus'] === 'pending'): ?>
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

                                <!-- Bottom Pagination -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted" id="bookingsPaginationInfoBottom">
                                        Showing <span id="bookingsShowingStartBottom">0</span>-<span id="bookingsShowingEndBottom">0</span> of <span id="bookingsTotalBottom">0</span>
                                    </div>
                                    <nav aria-label="Bookings pagination bottom">
                                        <ul class="pagination pagination-sm mb-0" id="bookingsPaginationControlsBottom">
                                            <!-- Pagination buttons will be generated by JavaScript -->
                                        </ul>
                                    </nav>
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

    <script src="javascript/pagination.js"></script>

    <script>
        const allBookingsData = <?php echo json_encode($bookingsData); ?>;
        
        // Process refund function
        function processRefund(bookingID) {
            // Find booking data from allBookingsData
            const booking = allBookingsData.find(b => b.bookingID == bookingID);
            const refundReason = booking && booking.refundReason ? booking.refundReason : 'No reason provided';
            
            // Store booking info for confirmation
            document.getElementById('bookingStatusBookingID').value = bookingID;
            document.getElementById('bookingStatusAction').value = 'refund';
            
            // Update modal appearance for refund processing
            const modalHeader = document.getElementById('bookingStatusModalHeader');
            const modalIcon = document.getElementById('bookingStatusIcon');
            const modalMessage = document.getElementById('bookingStatusMessage');
            const confirmBtn = document.getElementById('bookingStatusConfirmBtn');
            
            modalHeader.className = 'modal-header bg-warning text-dark';
            modalIcon.className = 'bi bi-cash-coin text-warning';
            modalMessage.innerHTML = `Process this refund request?<br><br>
                <div class="alert alert-warning mb-3" style="text-align: left;">
                    <strong><i class="bi bi-chat-left-quote me-2"></i>Reason for Refund Request:</strong><br>
                    <em>${refundReason}</em>
                </div>
                <small class="text-muted">This will:<br>• Change booking status to <strong>CANCELLED</strong><br>• Change payment status to <strong>REFUNDED</strong></small>`;
            confirmBtn.className = 'btn btn-warning';
            confirmBtn.textContent = 'Process Refund';
            
            // Show confirmation modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingStatusModal')) || 
                         new bootstrap.Modal(document.getElementById('bookingStatusModal'));
            modal.show();
        }
        
        // Check if booking was cancelled by user and disable edit buttons (only for completed refunds)
        document.addEventListener('DOMContentLoaded', function() {
            allBookingsData.forEach(booking => {
                // Only disable edit button for COMPLETED refunds (cancelled status)
                // Pending refund requests should still be editable
                if (booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1) {
                    // Find and disable edit button for this booking
                    const editBtn = document.querySelector(`button[data-bs-target="#editModal${booking.bookingID}"]`);
                    if (editBtn) {
                        editBtn.disabled = true;
                        editBtn.classList.remove('btn-outline-secondary');
                        editBtn.classList.add('btn-outline-secondary', 'opacity-50');
                        editBtn.title = 'Cannot edit - Refunded by user';
                        editBtn.innerHTML = '<i class="bi bi-lock"></i>';
                    }
                }
            });

            // Initialize bookings pagination after disabling edit buttons
            try {
                applyBookingsPagination();
            } catch (e) {
                // ignore if pagination elements are missing or AdminPagination not loaded yet
            }
        });

        // Bookings pagination using shared AdminPagination
        const bookingsPerPage = 7;
        let bookingsCurrentPage = 1;

        function getBookingRows() {
            return Array.from(document.querySelectorAll('#manageBookingsTableBody tr'));
        }

        function applyBookingsPagination() {
            const rows = getBookingRows();
            const total = rows.length;
            const totalPages = Math.max(1, Math.ceil(total / bookingsPerPage));

            if (bookingsCurrentPage < 1) bookingsCurrentPage = 1;
            if (bookingsCurrentPage > totalPages) bookingsCurrentPage = totalPages;

            const start = (bookingsCurrentPage - 1) * bookingsPerPage;
            const end = Math.min(start + bookingsPerPage, total);

            rows.forEach((r, idx) => r.style.display = (idx >= start && idx < end) ? '' : 'none');

            const startDisplay = total > 0 ? start + 1 : 0;
            AdminPagination.updatePaginationInfo(startDisplay, end, total, {
                topStartId: 'bookingsShowingStart',
                topEndId: 'bookingsShowingEnd',
                topTotalId: 'bookingsTotal',
                bottomStartId: 'bookingsShowingStartBottom',
                bottomEndId: 'bookingsShowingEndBottom',
                bottomTotalId: 'bookingsTotalBottom'
            });

            AdminPagination.generatePaginationControls(totalPages, bookingsCurrentPage, 'bookingsPaginationControls', 'bookingsPaginationControlsBottom', 'goToBookingsPage');
        }

        function goToBookingsPage(page) {
            const rows = getBookingRows();
            const totalPages = Math.max(1, Math.ceil(rows.length / bookingsPerPage));
            if (page < 1 || page > totalPages) return;
            bookingsCurrentPage = page;
            applyBookingsPagination();
        }
        
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
    
    <script>
        // Override confirmBookingStatusChange AFTER admin.js loads to handle refund action
        confirmBookingStatusChange = function() {
            const bookingID = document.getElementById('bookingStatusBookingID').value;
            const action = document.getElementById('bookingStatusAction').value;
            
            // Hide confirmation modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingStatusModal'));
            if (modal) modal.hide();
            
            // Show loading state immediately
            showBookingLoading(action === 'refund' ? 'refund' : action);

            const formData = new FormData();
            formData.append('bookingID', bookingID);
            
            if (action === 'refund') {
                // For refund, we edit the status
                formData.append('bookingAction', 'edit');
                formData.append('newStatus', 'cancelled');
                formData.append('newPaymentStatus', 'refunded');
            } else {
                // For regular actions (confirm, cancel, complete)
                formData.append('bookingAction', action);
            }

            fetch('php/booking_status.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    showBookingResult(data.success, data.message);
                    if (data.success) {
                        const resultModalEl = document.getElementById('bookingResultModal');
                        if (resultModalEl) resultModalEl.setAttribute('data-reload', 'true');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    showBookingResult(false, 'Error parsing server response.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showBookingResult(false, 'An error occurred: ' + error.message);
            });
        };
    </script>
</body>
</html>