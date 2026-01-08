<?php
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Register</title>
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/flag.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
            position: relative;
            overflow: hidden;
        }

        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo BASE_URL; ?>/images/loginRegisterImg/img.jpg') center center/cover no-repeat;
            filter: blur(5px);
            z-index: 0;
        }


        .divider-vertical {
            width: 5px;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .form-control-glass {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #fff !important;
        }

        .form-control-glass::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control-glass:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 153, 0, 0.5) !important;
            box-shadow: 0 0 15px rgba(255, 153, 0, 0.2) !important;
        }

        .btn-glass {
            background: linear-gradient(135deg, #ff9900 0%, #ff6600 100%);
        }
    </style>
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>

    <div class="login-page d-flex align-items-center justify-content-center py-5">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="glass border border-secondary border-opacity-25 rounded-4 shadow-lg p-4 p-lg-5">
                        <div class="row align-items-stretch" style="min-height: 500px;">
                            <div
                                class="col-12 col-md-5 d-flex flex-column justify-content-between mb-4 mb-md-0 pe-md-4">
                                <div>
                                    <div class="mt-2">
                                        <a href="<?php echo BASE_URL; ?>/index.php"
                                            class="text-white-50 small mb-0 text-decoration-underline">&lt;-- Back to
                                            Home Page</a>
                                    </div>
                                    <h1 class="display-4 fw-bold text-white mb-2 mt-5">Join</h1>
                                    <h1 class="display-4 fw-bold text-white mb-4">Us Today!</h1>
                                    <p class="text-white-50 fs-6">
                                        Create your account and unlock exclusive deals,
                                        easy bookings, and personalized travel experiences.
                                        Your adventure starts here.
                                    </p>
                                </div>
                                <a class="navbar-brand fw-bold fs-3" href="<?php echo BASE_URL; ?>/index.php"><img
                                        id="site-logo" src="<?php echo IMAGES_URL; ?>/logo/logoW.png"
                                        style="width: 120px;" alt="logo"></a>
                            </div>

                            <div class="col-auto d-none d-md-flex align-items-center px-4">
                                <div class="divider-vertical h-75"></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="d-flex flex-column h-100 justify-content-center">
                                    <h2 class="fs-3 fw-semibold text-white text-center mb-4">Register</h2>

                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($_GET['error']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="php/register_process.php" method="POST" class="needs-validation"
                                        novalidate>
                                        <div class="row">
                                            <div class="col-12 col-sm-6 mb-3">
                                                <input id="firstname" name="firstname" type="text"
                                                    class="form-control form-control-lg form-control-glass rounded-3"
                                                    placeholder="First Name" required>
                                                <div class="invalid-feedback">Please provide your first name.</div>
                                            </div>
                                            <div class="col-12 col-sm-6 mb-3">
                                                <input id="lastname" name="lastname" type="text"
                                                    class="form-control form-control-lg form-control-glass rounded-3"
                                                    placeholder="Last Name" required>
                                                <div class="invalid-feedback">Please provide your last name.</div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <input id="email" name="email" type="email"
                                                class="form-control form-control-lg form-control-glass rounded-3"
                                                placeholder="Email Address" required>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                        <div class="mb-3">
                                            <input id="username" name="username" type="text"
                                                class="form-control form-control-lg form-control-glass rounded-3"
                                                placeholder="Username (3-15 letters, numbers, or _ )"
                                                pattern="[A-Za-z0-9_]{3,15}" title="3-15 letters, numbers or underscore"
                                                required>
                                            <div class="invalid-feedback">Please choose a username (3-15 letters,
                                                numbers, or _).</div>
                                        </div>
                                        <div class="mb-3">
                                            <input id="phone" name="phone" type="tel"
                                                class="form-control form-control-lg form-control-glass rounded-3"
                                                placeholder="Phone Number (7-15 digits)" pattern="[0-9]{7,15}"
                                                title="Enter 7 to 15 digits" required>
                                            <div class="invalid-feedback">Please enter a valid phone number (7-15
                                                digits).</div>
                                        </div>
                                        <div class="mb-3 position-relative">
                                            <input id="password" name="password" type="password"
                                                class="form-control form-control-lg form-control-glass rounded-3"
                                                placeholder="Password (min 8 chars)" minlength="8" pattern=".{8,}"
                                                title="Minimum 8 characters" required style="padding-right: 2.5rem;">
                                            <i class="bi bi-eye position-absolute end-0 translate-middle-y me-3 text-white"
                                                id="togglePassword" style="cursor: pointer; top: 24px;"></i>
                                            <div class="invalid-feedback">Please provide a password (minimum 8
                                                characters).</div>
                                        </div>
                                        <div class="mb-4 position-relative">
                                            <input id="confirmPassword" name="confirm_password" type="password"
                                                class="form-control form-control-lg form-control-glass rounded-3"
                                                placeholder="Confirm Password" minlength="8" required
                                                style="padding-right: 2.5rem;">
                                            <i class="bi bi-eye position-absolute end-0 translate-middle-y me-3 text-white"
                                                id="toggleConfirmPassword" style="cursor: pointer; top: 24px;"></i>
                                            <div class="invalid-feedback" id="confirmFeedback">Passwords must match.
                                            </div>
                                        </div>

                                        <div class="d-grid mb-3">
                                            <button type="submit"
                                                class="btn btn-glass btn-lg text-white fw-semibold rounded-3 py-3">Register</button>
                                        </div>
                                    </form>

                                    <p class="text-center text-white-50 small mb-0">
                                        Already have an account?
                                        <a href="<?php echo FRONTEND_URL; ?>/login.php"
                                            class="text-warning text-decoration-none fw-semibold">Login</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        (function () {
            'use strict'

            const togglePassword = document.querySelector('#togglePassword');
            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
            const password = document.querySelector('#password');
            const confirmPassword = document.querySelector('#confirmPassword');

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function () {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }

            if (toggleConfirmPassword && confirmPassword) {
                toggleConfirmPassword.addEventListener('click', function () {
                    const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPassword.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }

            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (password && confirmPassword) {
                        if (password.value !== confirmPassword.value) {
                            confirmPassword.setCustomValidity('Passwords do not match');
                            document.getElementById('confirmFeedback').textContent = 'Passwords do not match.';
                        } else {
                            confirmPassword.setCustomValidity('');
                            document.getElementById('confirmFeedback').textContent = 'Passwords must match.';
                        }
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>