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

// Initialize models
$bookingModel = new Booking();
$userModel = new User();

// [MODULARIZED] Booking Action Logic moved to: php/booking_status.php
// If you have forms posting to this page for validation, update their action to 'php/booking_status.php'

// Fetch all users (admins and customers)
$customers = $userModel->getAllCustomers();
$admins = $userModel->getAllAdmins();
$usersData = array_merge($admins, $customers);
// Sort by created_at DESC to maintain latest first order
usort($usersData, function($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

// Fetch all bookings with details
$allBookingsData = $bookingModel->getAllWithDetails();

// Fetch confirmed bookings (confirmed + completed)
$confirmedBookingsData = $bookingModel->getConfirmedBookings();

// Fetch pending bookings
$pendingBookingsData = $bookingModel->getPendingBookings();

// Fetch completed bookings
$completedBookingsData = $bookingModel->getCompletedBookings();

$countAllBookings = count($allBookingsData);
$countUsers = count($usersData);
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
                        <div class="card text-bg-primary h-100 stat-card" data-table="reservations" onclick="switchTable('reservations')">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countAllBookings; ?></h3>
                                <small>All Reservations</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-warning h-100 stat-card" data-table="users" onclick="switchTable('users')">
                            <div class="card-body text-center">
                                <i class="bi bi-people display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countUsers; ?></h3>
                                <small>Users</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-success h-100 stat-card" data-table="confirmed" onclick="switchTable('confirmed')">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countConfirmed; ?></h3>
                                <small>Confirmed</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-danger h-100 stat-card" data-table="pending" onclick="switchTable('pending')">
                            <div class="card-body text-center">
                                <i class="bi bi-clock display-6"></i>
                                <h3 class="fw-bold mt-2"><?php echo $countPending; ?></h3>
                                <small>Pending</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-bg-info h-100 stat-card" data-table="completed" onclick="switchTable('completed')">
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
                                <a class="nav-link" id="tab-users" href="#" onclick="switchTable('users'); return false;">Users</a>
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

    <!-- Modals -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/view_booking.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/edit_role.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/delete_user.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/adminDashboardModals/booking_status_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        const usersData = <?php echo json_encode($usersData); ?>;
        const allBookingsData = <?php echo json_encode($allBookingsData); ?>;
        const confirmedBookingsData = <?php echo json_encode($confirmedBookingsData); ?>;
        const pendingBookingsData = <?php echo json_encode($pendingBookingsData); ?>;
        const completedBookingsData = <?php echo json_encode($completedBookingsData); ?>;
    </script>
    <script src="javascript/admin.js"></script>
</body>
</html>