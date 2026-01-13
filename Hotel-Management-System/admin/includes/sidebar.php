<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['userID']);
$username = $isLoggedIn ? ($_SESSION['firstName'] ?? $_SESSION['username'] ?? 'Admin') : '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="col-lg-2 d-none d-lg-block px-0 sidebar">
    <div class="p-4 text-center">
        <h4 class="text-white fw-bold">TravelMates</h4>
        <small class="text-white-50">Admin Panel</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>" href="admin.php">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
        <a class="nav-link <?php echo $currentPage === 'rooms.php' ? 'active' : ''; ?>" href="rooms.php">
            <i class="bi bi-door-open me-2"></i>Rooms
        </a>
        <a class="nav-link <?php echo $currentPage === 'manage_bookings.php' ? 'active' : ''; ?>" href="manage_bookings.php">
            <i class="bi bi-calendar-check me-2"></i>Bookings
        </a>
        <a class="nav-link <?php echo $currentPage === 'features.php' ? 'active' : ''; ?>" href="features.php">
            <i class="bi bi-star me-2"></i>Features
        </a>
        <a class="nav-link <?php echo $currentPage === 'smsDashboard.php' ? 'active' : ''; ?>" href="smsDashboard.php">
            <i class="bi bi-chat-dots me-2"></i>SMS
        </a>
        <a class="nav-link <?php echo $currentPage === 'emailDashboard.php' ? 'active' : ''; ?>" href="emailDashboard.php">
            <i class="bi bi-envelope me-2"></i>Email
        </a>
        <hr class="text-white-50 mx-3">
        <a class="nav-link text-danger" href="<?php echo FRONTEND_URL; ?>/php/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
    </nav>
</div>

<!-- Mobile Header -->
<div class="col-12 d-lg-none bg-dark py-3">
    <div class="d-flex justify-content-between align-items-center px-3">
        <h5 class="text-white mb-0">TravelMates</h5>
        <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
            <i class="bi bi-list"></i>
        </button>
    </div>
</div>

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header">
        <h5 class="text-white">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column">
            <a class="nav-link text-white <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>" href="admin.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            <a class="nav-link text-white <?php echo $currentPage === 'rooms.php' ? 'active' : ''; ?>" href="rooms.php">
                <i class="bi bi-door-open me-2"></i>Rooms
            </a>
            <a class="nav-link text-white <?php echo $currentPage === 'manage_bookings.php' ? 'active' : ''; ?>" href="manage_bookings.php">
                <i class="bi bi-calendar-check me-2"></i>Bookings
            </a>
            <a class="nav-link text-white <?php echo $currentPage === 'features.php' ? 'active' : ''; ?>" href="features.php">
                <i class="bi bi-star me-2"></i>Features
            </a>
            <a class="nav-link text-white <?php echo $currentPage === 'smsDashboard.php' ? 'active' : ''; ?>" href="smsDashboard.php">
                <i class="bi bi-chat-dots me-2"></i>SMS
            </a>
            <a class="nav-link text-white <?php echo $currentPage === 'emailDashboard.php' ? 'active' : ''; ?>" href="emailDashboard.php">
                <i class="bi bi-envelope me-2"></i>Email
            </a>
            <hr class="text-white-50">
            <a class="nav-link text-danger" href="<?php echo FRONTEND_URL; ?>/php/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </nav>
    </div>
</div>