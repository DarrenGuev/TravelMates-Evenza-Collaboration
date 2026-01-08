<?php
$isLoggedIn = isset($_SESSION['userID']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn && isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<link rel="stylesheet" href="<?php echo CSS_URL; ?>/navbar.css">
<nav class="navbar navbar-expand-lg fixed-top glass bg-body-tertiary shadow animate-nav border-bottom">
    <div class="container-fluid px-3 mx-3 px-md-5">
        <a class="navbar-brand fw-bold fs-3" href="<?php echo BASE_URL; ?>/index.php"><img id="site-logo"
                src="<?php echo IMAGES_URL; ?>/logo/logoB.png" style="width: 120px;" alt="logo"></a>

        <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
            <div class="navbar-nav">
                <a class="nav-link small text-body me-5" href="<?php echo BASE_URL; ?>/index.php"><i
                        class="bi bi-house-fill me-2"></i>HOME</a>
                <a class="nav-link small text-body me-5" href="<?php echo FRONTEND_URL; ?>/rooms.php"><i
                        class="bi bi-door-open me-2"></i>ROOMS</a>
                <!-- <a class="nav-link small text-body me-5" href="<?php echo FRONTEND_URL; ?>/events.php"><i
                        class="bi bi-calendar-event me-2"></i>EVENTS</a> -->
                <a class="nav-link small text-body me-5" href="<?php echo FRONTEND_URL; ?>/about.php"><i
                        class="bi bi-info-circle me-2"></i>ABOUT</a>

                <div class="d-flex d-lg-none mt-3">
                    <?php if ($isLoggedIn){ ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-dark dropdown-toggle me-2" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
                                <?php if ($userRole === 'admin') { ?>
                                    <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/admin.php"><i class="bi bi-speedometer2 me-2"></i>Admin Side</a></li>
                                <?php } else { ?>
                                    <li><a class="dropdown-item" href="<?php echo FRONTEND_URL; ?>/bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                                <?php } ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo FRONTEND_URL; ?>/php/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php } else { ?>
                        <button class="btn btn-outline-dark me-2"
                            onclick="location.href='<?php echo FRONTEND_URL; ?>/login.php'">Login</button>
                    <?php } ?>
                    <div class="vr mx-2"></div>
                    <button class="nav-link small text-body ms-2 border-0 bg-transparent" id="mode" type="button"
                        onclick="changeMode()"><i class="bi bi-moon-fill"></i></button>
                </div>
            </div>
        </div>

        <div class="d-none d-lg-flex align-items-center ms-auto">
            <?php if ($isLoggedIn){ ?>
                <div class="dropdown">
                    <button class="btn btn-outline-dark dropdown-toggle me-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if ($userRole === 'admin') { ?>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/admin.php"><i class="bi bi-speedometer2 me-2"></i>Admin Side</a></li>
                        <?php } else { ?>
                            <li><a class="dropdown-item" href="<?php echo FRONTEND_URL; ?>/bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                        <?php } ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo FRONTEND_URL; ?>/php/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php } else { ?>
                <button class="btn btn-outline-dark me-2"
                    onclick="location.href='<?php echo FRONTEND_URL; ?>/login.php'">Login</button>
            <?php } ?>
            <div class="vr mx-2"></div>
            <button class="nav-link small text-body ms-2 border-0 bg-transparent d-none d-lg-inline" id="mode-lg"
                type="button" onclick="changeMode()"><i class="bi bi-moon-fill"></i></button>
        </div>
    </div>
</nav>
<script>
    window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
</script>
<script src="<?php echo JS_URL; ?>/changeMode.js"></script>