<?php
session_start();
require_once '../../core/connect.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';

    $phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
    if (preg_match('/^\+639/', $phoneNumber)) {
        $phoneNumber = '0' . substr($phoneNumber, 3);
    }

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phoneNumber) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^09[0-9]{9}$/', $phoneNumber)) {
        $error = 'Please enter a valid Philippine phone number (09XX XXX XXXX).';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $checkQuery = "SELECT userId FROM users WHERE email = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $email);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_fetch_assoc($result)) {
                $error = 'Email already registered';
                mysqli_stmt_close($checkStmt);
            } else {
                mysqli_stmt_close($checkStmt);
                
                $fullName = trim($firstName . ' ' . $lastName);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $insertQuery = "INSERT INTO users (firstName, lastName, fullName, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
                $insertStmt = mysqli_prepare($conn, $insertQuery);
                
                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, "ssssss", $firstName, $lastName, $fullName, $email, $phoneNumber, $hashedPassword);
                    
                    if (mysqli_stmt_execute($insertStmt)) {
                        $userId = mysqli_insert_id($conn);
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_name'] = $fullName;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_mobile'] = $phoneNumber;
                        $_SESSION['user_role'] = 'user';
                        
                        mysqli_stmt_close($insertStmt);
                        header('Location: profile.php');
                        exit;
                    } else {
                        $error = 'Failed to create account. Please try again.';
                    }
                    mysqli_stmt_close($insertStmt);
                } else {
                    $error = 'Database error. Please try again later.';
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
    <title>Register - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
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
            color: #4A5D4E;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .password-toggle-btn:hover {
            color: #3A4D3E;
        }
        .password-toggle-btn:focus {
            outline: none;
        }
        .login-link {
            color: #6B7F5A;
            text-decoration: underline;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .login-link:hover {
            color: #5A6B4F;
            text-decoration: underline;
        }
        .register-page-section .luxury-card {
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <?php $activePage = 'register'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="register-page-section py-5 mt-5">
        <div class="container">
            <div class="page-header mb-5 text-center">
                <h1 class="page-title">Create an EVENZA Account</h1>
                <p class="page-subtitle">Join us to discover and book amazing events</p>
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
                        <form id="registerForm" method="post" action="">
                            <div class="form-group mb-4">
                                <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input id="firstName" name="firstName" type="text" class="form-control luxury-input" required placeholder="Enter your first name">
                            </div>

                            <div class="form-group mb-4">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input id="lastName" name="lastName" type="text" class="form-control luxury-input" required placeholder="Enter your last name">
                            </div>

                            <div class="form-group mb-4">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input id="email" name="email" type="email" class="form-control luxury-input" required placeholder="you@example.com">
                            </div>

                            <div class="form-group mb-4">
                                <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input id="phoneNumber" name="phoneNumber" type="tel" class="form-control luxury-input" required placeholder="09XX XXX XXXX" pattern="^(09|\+639)[0-9]{9}$" maxlength="13">
                                <small class="form-text text-muted">Format: 09XX XXX XXXX or +63 9XX XXX XXXX</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="password-input-wrapper position-relative">
                                    <input id="password" name="password" type="password" class="form-control luxury-input" required placeholder="Enter your password" minlength="6" style="padding-right: 70px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #4A5D4E; cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.875rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-weight: 500; z-index: 5;">
                                        <span id="passwordToggleText">Show</span>
                                    </button>
                                </div>
                                <div class="error-message" id="passwordError"></div>
                                <small class="password-requirement-text">Password must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="password-input-wrapper position-relative">
                                    <input id="confirmPassword" name="confirmPassword" type="password" class="form-control luxury-input" required placeholder="Confirm your password" minlength="6" style="padding-right: 70px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirmPassword')" aria-label="Toggle password visibility" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #4A5D4E; cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.875rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-weight: 500; z-index: 5;">
                                        <span id="confirmPasswordToggleText">Show</span>
                                    </button>
                                </div>
                                <div class="error-message" id="confirmPasswordError"></div>
                            </div>

                            <button type="submit" class="btn btn-primary-luxury w-100 mb-4">Register</button>

                            <p class="text-center mb-0">Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/login.js"></script>
    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleText = document.getElementById(fieldId + 'ToggleText');
            
            if (field.type === 'password') {
                field.type = 'text';
                if (toggleText) toggleText.textContent = 'Hide';
            } else {
                field.type = 'password';
                if (toggleText) toggleText.textContent = 'Show';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phoneNumber');
            
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.startsWith('63') && value.length > 2) {
                    value = '0' + value.substring(2);
                }
                
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                if (value.length > 4) {
                    value = value.substring(0, 4) + ' ' + value.substring(4);
                }
                if (value.length > 8) {
                    value = value.substring(0, 8) + ' ' + value.substring(8);
                }
                
                e.target.value = value;
            });

            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const phoneValue = phoneInput.value.replace(/\s/g, '');
                const phonePattern = /^(09|\+639)[0-9]{9}$/;
                
                const requiredFields = document.querySelectorAll('#registerForm [required]');
                let hasErrors = false;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        hasErrors = true;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!phonePattern.test(phoneValue)) {
                    e.preventDefault();
                    phoneInput.classList.add('is-invalid');
                    phoneInput.setCustomValidity('Please enter a valid Philippine phone number (09XX XXX XXXX)');
                    phoneInput.reportValidity();
                    return false;
                } else {
                    phoneInput.classList.remove('is-invalid');
                    phoneInput.setCustomValidity('');
                }
                
                if (hasErrors) {
                    e.preventDefault();
                    return false;
                }
            });

            phoneInput.addEventListener('input', function() {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            });
            
            const allInputs = document.querySelectorAll('#registerForm input, #registerForm select');
            allInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });
        });
    </script>
</body>
</html>
