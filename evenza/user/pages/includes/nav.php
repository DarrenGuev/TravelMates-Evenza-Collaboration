<?php
$activePage = $activePage ?? '';
?>
<div class="navbar navbar-expand-lg navbar-light fixed-top luxury-nav">
    <div class="container">
        <a class="navbar-brand luxury-logo" href="../../index.php"><img src="../../assets/images/evenzaLogo.png" alt="EVENZA" class="evenza-logo-img"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link<?php echo $activePage === 'home' ? ' active' : ''; ?>" href="../../index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo $activePage === 'events' ? ' active' : ''; ?>" href="events.php">Events</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link<?php echo $activePage === 'about' ? ' active' : ''; ?>" href="about.php">About</a>
                </li>
                <li class="nav-item nav-divider">
                    <span class="nav-separator"></span>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $activePage === 'profile' ? ' active' : ''; ?>" href="profile.php">My Profile</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-register" href="../process/logout.php?type=user">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn-login<?php echo $activePage === 'login' ? ' active' : ''; ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-register<?php echo $activePage === 'register' ? ' active' : ''; ?>" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
