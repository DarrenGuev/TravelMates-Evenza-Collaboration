<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Login</title>
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
            background: rgba(226, 226, 226, 0.1) !important;
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

        /* White Google button: solid white, black text, no hover effect */
        .btn-google-white {
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: none !important;
        }

        .btn-google-white:active {
            background: #ffffff !important;
            color: #000000 !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
            box-shadow: none !important;
            transform: none !important;
        }
    </style>
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <div class="login-page d-flex align-items-center justify-content-center">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="glass border border-secondary border-opacity-25 rounded-4 shadow-lg p-4 p-lg-5">
                        <div class="row align-items-stretch" style="min-height: 500px;">
                            <!-- left Side-->
                            <div
                                class="col-12 col-md-6 d-flex flex-column justify-content-between mb-4 mb-md-0 pe-md-5">
                                <div>
                                    <div class="mt-2">
                                        <a href="<?php echo BASE_URL; ?>/index.php"
                                            class="text-white-50 small mb-0 text-decoration-underline">&lt;-- Back to
                                            Home Page</a>
                                    </div>
                                    <h1 class="display-4 fw-bold text-white mb-2 mt-5">Welcome</h1>
                                    <h1 class="display-4 fw-bold text-white mb-4">Back!</h1>
                                    <p class="text-white-50 fs-6">
                                        Experience comfort and luxury at TravelMates Hotel.
                                        Your perfect getaway awaits. Book your stay and create
                                        unforgettable memories with us.
                                    </p>
                                </div>
                                <a class="navbar-brand fw-bold fs-3" href="<?php echo BASE_URL; ?>/index.php"><img
                                        id="site-logo" src="<?php echo IMAGES_URL; ?>/logo/logoW.png"
                                        style="width: 120px;" alt="logo"></a>
                            </div>

                            <!-- divider -->
                            <div class="col-auto d-none d-md-flex align-items-center px-4">
                                <div class="divider-vertical h-75"></div>
                            </div>

                            <!-- right Side -->
                            <div class="col-12 col-md-5">
                                <div class="d-flex flex-column h-100 justify-content-center">
                                    <h2 class="fs-3 fw-semibold text-white text-center mb-4">Login</h2>

                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($_GET['error']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- (social buttons moved below the form) -->

                                    <?php if (isset($_GET['success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($_GET['success']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="php/login_process.php" method="POST">
                                        <div class="mb-3">
                                            <input type="text"
                                                class="form-control form-control-lg form-control-glass rounded-3 py-3"
                                                name="username" placeholder="Username" required>
                                        </div>
                                        <div class="mb-4 position-relative">
                                            <input type="password"
                                                class="form-control form-control-lg form-control-glass rounded-3 py-3"
                                                name="password" placeholder="Password" required id="password"
                                                style="padding-right: 2.5rem;">
                                            <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3 text-white"
                                                id="togglePassword" style="cursor: pointer;"></i>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit"
                                                class="btn btn-glass btn-lg text-white fw-semibold rounded-3 py-3">Login</button>
                                        </div>
                                    </form>

                                    <div class="d-flex align-items-center my-3">
                                        <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.12);" />
                                        <span class="mx-3 text-white-50 small">Sign in via</span>
                                        <hr class="flex-grow-1" style="border-color: rgba(255,255,255,0.12);" />
                                    </div>
                                    <div class="mb-3">
                                        <a href="<?php echo BASE_URL; ?>/integrations/gmail/googleLogin.php"
                                            class="btn btn-google-white w-100 d-flex align-items-center justify-content-center py-2">
                                            <svg class="me-2" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                <path d="M21.6 12.227c0-.766-.069-1.5-.198-2.227H12v4.217h5.597c-.242 1.308-.98 2.418-2.09 3.168v2.633h3.376c1.977-1.821 3.117-4.5 3.117-7.791z" fill="#4285F4"/>
                                                <path d="M12 22c2.7 0 4.972-.9 6.63-2.445l-3.376-2.633C14.9 17.1 13.55 17.6 12 17.6c-2.26 0-4.174-1.52-4.852-3.56H3.604v2.813C5.26 19.86 8.36 22 12 22z" fill="#34A853"/>
                                                <path d="M7.148 13.999a4.999 4.999 0 010-3.998V7.188H3.604A9.998 9.998 0 0012 2c2.7 0 4.972.9 6.63 2.445l-3.376 2.633C14.9 6.9 13.55 6.4 12 6.4c-2.26 0-4.174 1.52-4.852 3.56z" fill="#FBBC05"/>
                                                <path d="M12 6.4c1.55 0 2.9.5 3.854 1.678L19.23 5.445C17.972 4.018 15.7 3 12 3 8.36 3 5.26 5.14 3.604 7.999l3.544 2.001C7.826 8.02 9.74 6.5 12 6.5z" fill="#EA4335"/>
                                            </svg>
                                            <p class="m-0 fw-normal">Continue with Google</p>
                                        </a>
                                    </div>

                                    <p class="text-center text-white-50 small mb-0">
                                        Don't have an account?
                                        <a href="<?php echo FRONTEND_URL; ?>/register.php"
                                            class="text-warning text-decoration-none fw-semibold">Register</a>
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
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>

</html>