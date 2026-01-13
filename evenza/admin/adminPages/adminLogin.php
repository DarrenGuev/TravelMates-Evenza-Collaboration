<?php
session_start();
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$error = '';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
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
        $query = "SELECT userId, fullName, email, password, role FROM users WHERE LOWER(email) = LOWER(?) AND role = 'Admin'";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($admin) {
                if (password_verify($password, $admin['password'])) {
                    if (strtolower($admin['role'] ?? '') === 'admin') {
                        $_SESSION['admin_id'] = $admin['userId'];
                        $_SESSION['admin_name'] = $admin['fullName'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['user_role'] = 'Admin';
                        header('Location: admin.php');
                        exit;
                    } else {
                        $error = 'This account does not have admin privileges. Please use the regular login page.';
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $userCheckQuery = "SELECT userId, role FROM users WHERE LOWER(email) = LOWER(?) AND (role IS NULL OR role != 'Admin')";
                $userCheckStmt = mysqli_prepare($conn, $userCheckQuery);
                if ($userCheckStmt) {
                    mysqli_stmt_bind_param($userCheckStmt, "s", $email);
                    mysqli_stmt_execute($userCheckStmt);
                    $userCheckResult = mysqli_stmt_get_result($userCheckStmt);
                    $regularUser = mysqli_fetch_assoc($userCheckResult);
                    mysqli_stmt_close($userCheckStmt);
                    
                    if ($regularUser) {
                        $error = 'Regular user accounts must use the user login page. Please visit the login page to access your account.';
                    } else {
                        $error = 'Invalid email or password.';
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        } else {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-wrapper {
            min-height: 100vh;
            background-color: #F9F7F2;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .admin-login-card {
            background-color: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            max-width: 450px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .admin-login-wrapper {
                padding: 1rem;
            }
            .admin-login-card {
                padding: 2rem;
            }
        }
        @media (max-width: 576px) {
            .admin-login-wrapper {
                padding: 0.5rem;
            }
            .admin-login-card {
                padding: 1.5rem;
            }
        }
        .admin-login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .admin-login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: #1A1A1A;
            margin-bottom: 0.5rem;
        }
        .admin-login-header p {
            color: rgba(26, 26, 26, 0.7);
            font-size: 0.95rem;
        }
        .admin-login-logo {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .admin-login-logo img {
            height: 50px;
            width: auto;
        }
        .btn-admin-login {
            background-color: #4A5D4A;
            border-color: #4A5D4A;
            color: #FFFFFF;
            font-weight: 500;
            padding: 0.75rem 2rem;
            width: 100%;
        }
        .btn-admin-login:hover {
            background-color: #3a4a3a;
            border-color: #3a4a3a;
            color: #FFFFFF;
        }
        .form-label {
            font-weight: 500;
            color: #1A1A1A;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid rgba(74, 93, 74, 0.2);
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #4A5D4A;
            box-shadow: 0 0 0 0.2rem rgba(74, 93, 74, 0.15);
        }
        .password-input-wrapper {
            position: relative;
        }
        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .password-toggle-btn:hover {
            color: #4A5D4A;
        }
        .password-toggle-btn:focus {
            outline: none;
        }
    </style>
</head>
<body>
    <div class="admin-login-wrapper">
        <div class="admin-login-card">
            <div class="admin-login-logo">
                <img src="../../assets/images/evenzaLogo.png" alt="EVENZA">
            </div>
            
            <div class="admin-login-header">
                <h1>Admin Login</h1>
                <p>Access the EVENZA Admin Dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" name="email" type="email" class="form-control" required placeholder="admin@evenza.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <input id="password" name="password" type="password" class="form-control" required placeholder="Enter your password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password', 'toggle_password')">
                            <span id="toggle_password">Show</span>
                        </button>
                    </div>
                    <small class="text-muted">Password must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.</small>
                </div>

                <button type="submit" class="btn btn-admin-login">Login</button>
            </form>

            <div class="text-center mt-4">
                <a href="adminForgotPassword.php" class="text-muted small text-decoration-none d-block mb-2">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            if (!form) return;

            function validatePassword(password) {
                if (password.length < 8) {
                    return 'Password must be at least 8 characters long.';
                }
                if (!/[A-Z]/.test(password)) {
                    return 'Password must contain at least one uppercase letter.';
                }
                if (!/[a-z]/.test(password)) {
                    return 'Password must contain at least one lowercase letter.';
                }
                if (!/[0-9]/.test(password)) {
                    return 'Password must contain at least one number.';
                }
                return true;
            }

            form.addEventListener('submit', function (e) {
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                let err = '';

                if (!email.value.trim()) {
                    err = 'Please enter your email address.';
                } else if (!password.value) {
                    err = 'Please enter your password.';
                } else {
                    const passwordValidation = validatePassword(password.value);
                    if (passwordValidation !== true) {
                        err = passwordValidation;
                    }
                }

                if (err) {
                    e.preventDefault();
                    alert(err);
                    (err === 'Please enter your email address.') ? email.focus() : password.focus();
                }
            });
        });

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

