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
    </script>

    <script src="javascript/admin.js"></script>
</body>
</html>