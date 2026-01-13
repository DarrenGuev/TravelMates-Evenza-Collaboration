<?php
session_start();
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$error = '';
$success = '';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (($passwordValidation = validatePassword($newPassword)) !== true) {
        $error = $passwordValidation;
    } else {
        $checkQuery = "SELECT userId, email, role FROM users WHERE LOWER(email) = LOWER(?) AND role = 'Admin'";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $email);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $admin = mysqli_fetch_assoc($checkResult);
            mysqli_stmt_close($checkStmt);
            
            if (!$admin) {
                $error = 'No admin account found with this email address. Only admin accounts can reset passwords here.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $userId = $admin['userId'];
                
                $updateQuery = "UPDATE users SET password = ? WHERE userId = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $userId);
                    if (mysqli_stmt_execute($updateStmt)) {
                        $success = 'Your admin password has been reset successfully. You can now login with your new password.';
                    } else {
                        $error = 'Failed to update password. Please try again.';
                    }
                    mysqli_stmt_close($updateStmt);
                } else {
                    $error = 'Database error. Please try again.';
                }
            }
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Forgot Password - EVENZA</title>
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
                <h1>Admin Password Reset</h1>
                <p>Reset password for admin accounts only</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="text-center mt-4">
                    <a href="adminLogin.php" class="btn btn-admin-login">Go to Admin Login</a>
                </div>
            <?php else: ?>
                <form method="post" action="" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" name="email" type="email" class="form-control" required placeholder="admin@evenza.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="password-input-wrapper">
                            <input id="new_password" name="new_password" type="password" class="form-control" required placeholder="Enter new password">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password', 'toggle_new_password')">
                                <span id="toggle_new_password">Show</span>
                            </button>
                        </div>
                        <small class="text-muted">Must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.</small>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input id="confirm_password" name="confirm_password" type="password" class="form-control" required placeholder="Confirm new password">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', 'toggle_confirm_password')">
                                <span id="toggle_confirm_password">Show</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-admin-login">Reset Password</button>
                </form>

                <div class="text-center mt-4">
                    <a href="adminLogin.php" class="text-muted small text-decoration-none">← Back to Admin Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

