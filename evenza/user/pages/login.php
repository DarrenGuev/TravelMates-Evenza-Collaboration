<?php
session_start();
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$error = '';
$redirect = '';
if (isset($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
}
if (isset($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
}

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['user_id'])) {
    if (!empty($redirect) && strpos($redirect, 'http') === false && strpos($redirect, '//') === false) {
        header('Location: ' . $redirect);
    } else {
        header('Location: profile.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (($passwordValidation = validatePassword($password)) !== true) {
        $error = $passwordValidation;
    } else {
        $query = "SELECT userId, firstName, lastName, fullName, email, phone, password, role FROM users WHERE LOWER(email) = LOWER(?) AND (role IS NULL OR role != 'Admin')";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user) {
                if (strtolower($user['role'] ?? '') === 'admin') {
                    $error = 'Admin accounts must use the admin login page. Please visit the admin login page to access your account.';
                } else {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['userId'] = $user['userId'];
                        $_SESSION['firstName'] = $user['firstName'];
                        $_SESSION['role'] = $user['role'];
                        
                        $_SESSION['user_id'] = $user['userId'];
                        $_SESSION['user_name'] = $user['fullName'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_mobile'] = $user['phone'] ?? '';
                        $_SESSION['user_role'] = $user['role'] ?? 'user';

                        if (!empty($redirect) && strpos($redirect, 'http') === false && strpos($redirect, '//') === false) {
                            header('Location: ' . $redirect);
                        } else {
                            header('Location: ../../index.php');
                        }
                        exit;
                    } else {
                        $error = 'Invalid email or password';
                    }
                }
            } else {
                $adminCheckQuery = "SELECT userId, role FROM users WHERE LOWER(email) = LOWER(?) AND role = 'Admin'";
                $adminCheckStmt = mysqli_prepare($conn, $adminCheckQuery);
                if ($adminCheckStmt) {
                    mysqli_stmt_bind_param($adminCheckStmt, "s", $email);
                    mysqli_stmt_execute($adminCheckStmt);
                    $adminCheckResult = mysqli_stmt_get_result($adminCheckStmt);
                    $adminUser = mysqli_fetch_assoc($adminCheckResult);
                    mysqli_stmt_close($adminCheckStmt);
                    
                    if ($adminUser) {
                        $error = 'Admin accounts must use the admin login page. Please visit the admin login page to access your account.';
                    } else {
                        $error = 'Invalid email or password';
                    }
                } else {
                    $error = 'Invalid email or password';
                }
            }
        } else {
            $error = 'Database connection error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $activePage = 'login'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="login-page-section py-5 mt-5">
        <div class="container">
            <div class="page-header mb-5 text-center">
                <h1 class="page-title">Login to EVENZA</h1>
                <p class="page-subtitle">Sign in to manage your account and reservations</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="luxury-card p-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form id="loginForm" method="post" action="" novalidate>
                            <div class="form-group mb-4">
                                <label for="email" class="form-label">Email Address <span class="required-asterisk">*</span></label>
                                <input id="email" name="email" type="email" class="form-control luxury-input" required placeholder="you@example.com">
                                <div class="error-message" id="emailError"></div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="password" class="form-label">Password <span class="required-asterisk">*</span></label>
                                <div class="password-input-wrapper" style="position: relative;">
                                    <input id="password" name="password" type="password" class="form-control luxury-input" required placeholder="Enter your password" style="padding-right: 70px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password', 'toggle_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #4A5D4E; cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.875rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-weight: 500; z-index: 5;">
                                        <span id="toggle_password">Show</span>
                                    </button>
                                </div>
                                <div class="error-message" id="passwordError"></div>
                                <small class="password-requirement-text">Password must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.</small>
                            </div>

                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <button type="submit" class="btn btn-primary-luxury">Login</button>
                                <a href="forgot-password.php" class="text-muted small">Forgot Password?</a>
                            </div>

                            <p class="text-center mb-0">Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal for Form Validation -->
    <div class="modal fade" id="loginErrorModal" tabindex="-1" aria-labelledby="loginErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-exclamation-circle custom-alert-icon error-icon"></i>
                    </div>
                    <h5 class="custom-alert-title">Form Incomplete</h5>
                    <p class="custom-alert-message" id="loginErrorModalMessage">Please fill in all required fields before submitting.</p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn custom-alert-btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/login.js"></script>
    <style>
        .register-link {
            color: #6B7F5A;
            text-decoration: underline;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .register-link:hover {
            color: #5A6B4F;
            text-decoration: underline;
        }
        .login-page-section .luxury-card {
            border-radius: 20px;
        }
    </style>
    <script>
        function togglePassword(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = 'Hide';
            } else {
                input.type = 'password';
                toggle.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
