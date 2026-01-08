<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

// Check if user is admin
Auth::requireAdmin('../frontend/login.php');

// Include SMS Service
require_once SMS_PATH . '/SmsService.php';

// Include Email Service for booking receipts
require_once GMAIL_PATH . '/EmailService.php';

// Initialize models
$bookingModel = new Booking();
$userModel = new User();

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookingAction'])) {
    $bookingID = (int)$_POST['bookingID'];
    $action = $_POST['bookingAction'];
    
    // Get booking details for SMS
    $bookingDetails = $bookingModel->getById($bookingID);
    if ($bookingDetails) {
        // Get user details
        $bookingUser = $userModel->find($bookingDetails['userID']);
        $phoneNumber = $bookingDetails['phoneNumber'] ?? '';
        $checkInDate = $bookingDetails['checkInDate'] ?? '';
        $customerName = trim(($bookingUser['firstName'] ?? '') . ' ' . ($bookingUser['lastName'] ?? ''));
        
        if ($action === 'confirm') {
            if ($bookingModel->confirm($bookingID)) {
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
                
                header("Location: admin.php?success=Booking confirmed successfully!");
                exit();
            }
        } elseif ($action === 'cancel') {
            if ($bookingModel->cancel($bookingID)) {
                // Send SMS notification
                if (!empty($phoneNumber)) {
                    try {
                        $smsService = new SmsService();
                        $smsService->sendBookingCancelledSms($bookingID, $phoneNumber, $customerName);
                    } catch (Exception $e) {
                        error_log('SMS Error: ' . $e->getMessage());
                    }
                }
                header("Location: admin.php?success=Booking cancelled successfully!");
                exit();
            }
        }
    }
}

// Fetch customers (users with role 'user')
$customersData = $userModel->getAllCustomers();

// Fetch all bookings with details
$allBookingsData = $bookingModel->getAllWithDetails();

// Fetch confirmed bookings (confirmed + completed)
$confirmedBookingsData = $bookingModel->getConfirmedBookings();

// Fetch pending bookings
$pendingBookingsData = $bookingModel->getPendingBookings();

// Fetch completed bookings
$completedBookingsData = $bookingModel->getCompletedBookings();

$countAllBookings = count($allBookingsData);
$countCustomers = count($customersData);
$countConfirmed = count($confirmedBookingsData);
$countPending = count($pendingBookingsData);
$countCompleted = count($completedBookingsData);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/css/admin.css">
    <style>
        .stat-card {
            cursor: pointer;
        }
        .stat-card.active {
            border: 3px solid #212529 !important;
        }
    </style>
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include ADMIN_INCLUDES_PATH . '/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-12 col-lg-10 p-3 p-lg-4">
                <!-- Page Header -->
                <div class="page-header">
                    <h2>Admin Dashboard</h2>
                    <p>Welcome back, <?php echo htmlspecialchars(Auth::getCurrentUserName()); ?>!</p>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 mb-4">
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-primary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countAllBookings; ?></h3>
                                <small>All Reservations</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-warning h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-people display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countCustomers; ?></h3>
                                <small>Customers</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-success h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countConfirmed; ?></h3>
                                <small>Confirmed</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-danger h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-clock display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countPending; ?></h3>
                                <small>Pending</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-info h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-flag display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countCompleted; ?></h3>
                                <small>Completed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="card" id="tableSection">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="adminTabs">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-reservations" href="#" onclick="switchTable('reservations'); return false;">Reservations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-customers" href="#" onclick="switchTable('customers'); return false;">Customers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-confirmed" href="#" onclick="switchTable('confirmed'); return false;">Confirmed</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-pending" href="#" onclick="switchTable('pending'); return false;">Pending</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-completed" href="#" onclick="switchTable('completed'); return false;">Completed</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body position-relative">
                        <!-- Table Loader (Hidden by default) -->
                        <div id="table-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 justify-content-center align-items-center" 
                             style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px); z-index: 10; border-radius: inherit;">
                            <div class="text-center">
                                <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-white fw-semibold">Loading...</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr id="tableHeaders">
                                        <!-- Headers will be populated by JS -->
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <!-- Data will be populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div class="modal fade" id="viewBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Booking Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="php/update_role.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="userID" id="editUserID">
                        <p>User: <strong id="editUserName"></strong></p>
                        <div class="mb-3">
                            <label for="newRole" class="form-label">Select Role</label>
                            <select class="form-select" name="role" id="newRole" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="php/delete_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="userID" id="deleteUserID">
                        <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                        <p class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        const customersData = <?php echo json_encode($customersData); ?>;
        const allBookingsData = <?php echo json_encode($allBookingsData); ?>;
        const confirmedBookingsData = <?php echo json_encode($confirmedBookingsData); ?>;
        const pendingBookingsData = <?php echo json_encode($pendingBookingsData); ?>;
        const completedBookingsData = <?php echo json_encode($completedBookingsData); ?>;

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'confirmed': '<span class="badge bg-success">Confirmed</span>',
                'cancelled': '<span class="badge bg-danger">Cancelled</span>',
                'completed': '<span class="badge bg-success">Confirmed</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
        }

        function getPaymentBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'paid': '<span class="badge bg-success">Paid</span>',
                'refunded': '<span class="badge bg-secondary">Refunded</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
        }

        function getBookingActions(booking) {
            let actions = `<button class="btn btn-sm btn-outline-primary me-1" onclick="viewBooking(${booking.bookingID})">
                <i class="bi bi-eye"></i>
            </button>`;
            
            if (booking.bookingStatus === 'pending') {
                actions += `
                <form method="POST" class="d-inline">
                    <input type="hidden" name="bookingID" value="${booking.bookingID}">
                    <input type="hidden" name="bookingAction" value="confirm">
                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Approve">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="bookingID" value="${booking.bookingID}">
                    <input type="hidden" name="bookingAction" value="cancel">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>`;
            }
            return actions;
        }

        // Store all bookings for modals
        const allBookingsMap = {};
        allBookingsData.forEach(b => allBookingsMap[b.bookingID] = b);

        function viewBooking(bookingID) {
            const booking = allBookingsMap[bookingID];
            if (!booking) return;
            
            const checkIn = new Date(booking.checkInDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const checkOut = new Date(booking.checkOutDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Guest Information</h6>
                        <p><strong>Name:</strong> ${booking.fullName}</p>
                        <p><strong>Email:</strong> ${booking.email}</p>
                        <p><strong>Phone:</strong> ${booking.phoneNumber}</p>
                        <p><strong>Guests:</strong> ${booking.numberOfGuests}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Booking Information</h6>
                        <p><strong>Room:</strong> ${booking.roomName} (${booking.roomType})</p>
                        <p><strong>Check-in:</strong> ${checkIn}</p>
                        <p><strong>Check-out:</strong> ${checkOut}</p>
                        <p><strong>Total:</strong> ₱${parseFloat(booking.totalPrice).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                        <p><strong>Payment:</strong> ${booking.paymentMethod ? booking.paymentMethod.replace('_', ' ') + ' ' + getPaymentBadge(booking.paymentStatus) : getPaymentBadge(booking.paymentStatus)}</p>
                        <p><strong>Status:</strong> ${getStatusBadge(booking.bookingStatus)}</p>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('viewBookingModal')).show();
        }

        function openEditRoleModal(userID, userName, currentRole) {
            document.getElementById('editUserID').value = userID;
            document.getElementById('editUserName').textContent = userName;
            document.getElementById('newRole').value = currentRole;
            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
        }

        function openDeleteModal(userID, userName) {
            document.getElementById('deleteUserID').value = userID;
            document.getElementById('deleteUserName').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        // Table configurations
        const tableConfigs = {
            reservations: {
                headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
                getData: () => allBookingsData,
                renderRow: (booking, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${booking.firstName} ${booking.lastName}</strong><br><small class="text-muted">${booking.userEmail}</small></td>
                        <td>${booking.roomName}<br><small class="text-muted">${booking.roomType}</small></td>
                        <td>${booking.checkInDate}</td>
                        <td>${booking.checkOutDate}</td>
                        <td>₱${parseFloat(booking.totalPrice).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td>${getStatusBadge(booking.bookingStatus)}</td>
                        <td>${booking.paymentMethod ? booking.paymentMethod.replace('_', ' ') + ' ' + getPaymentBadge(booking.paymentStatus) : getPaymentBadge(booking.paymentStatus)}</td>
                        <td>${getBookingActions(booking)}</td>
                    </tr>
                `
            },
            customers: {
                headers: ['#', 'Name', 'Email', 'Username', 'Phone', 'Member Since', 'Role', 'Actions'],
                getData: () => customersData,
                renderRow: (user, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${user.firstName} ${user.lastName}</td>
                        <td>${user.email}</td>
                        <td>${user.username}</td>
                        <td>${user.phoneNumber || 'N/A'}</td>
                        <td>${user.created_at}</td>
                        <td><span class="badge bg-${user.role === 'admin' ? 'danger' : 'primary'}">${user.role}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openEditRoleModal(${user.userID}, '${user.firstName} ${user.lastName}', '${user.role}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal(${user.userID}, '${user.firstName} ${user.lastName}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `
            },
            confirmed: {
                headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
                getData: () => confirmedBookingsData,
                renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
            },
            pending: {
                headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
                getData: () => pendingBookingsData,
                renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
            },
            completed: {
                headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
                getData: () => completedBookingsData,
                renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
            }
        };

        function switchTable(tableType) {
            // Show loader
            const loader = document.getElementById('table-loader');
            loader.classList.remove('d-none');
            loader.classList.add('d-flex');

            // Use setTimeout to simulate loading and allow the loader to render
            setTimeout(() => {
                const config = tableConfigs[tableType];
                if (!config) {
                    loader.classList.add('d-none');
                    loader.classList.remove('d-flex');
                    return;
                }

                document.getElementById('tableHeaders').innerHTML = config.headers.map(h => `<th>${h}</th>`).join('');
                const data = config.getData();
                if (data.length > 0) {
                    document.getElementById('tableBody').innerHTML = data.map((item, index) => config.renderRow(item, index)).join('');
                } else {
                    document.getElementById('tableBody').innerHTML = `
                        <tr>
                            <td colspan="${config.headers.length}" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <h5>No Data Found</h5>
                                    <p class="mb-0">There are no records to display in this section.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }

                document.querySelectorAll('.stat-card').forEach(card => card.classList.remove('active'));
                document.querySelector(`.stat-card[data-table="${tableType}"]`)?.classList.add('active');

                document.querySelectorAll('#adminTabs .nav-link').forEach(tab => tab.classList.remove('active'));
                document.getElementById('tab-' + tableType)?.classList.add('active');

                // Hide loader
                loader.classList.remove('d-flex');
                loader.classList.add('d-none');
            }, 300); // Adjust timeout as needed
        }
        document.addEventListener('DOMContentLoaded', () => switchTable('reservations'));
    </script>
</body>
</html>