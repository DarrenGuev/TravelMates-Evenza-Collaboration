<?php
session_start();

require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="en">

<?php $title = "Forgot Password "; ?>
<?php include INCLUDES_PATH . '/head.php'; ?>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <div class="forgot-page d-flex align-items-center justify-content-center">
        <div class="container position-relative" style="z-index: 1;">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="glass border border-secondary border-opacity-25 rounded-4 shadow-lg p-4 p-lg-5 my-5">
                        <h2 class="fs-3 fw-semibold text-white text-center mb-2">Forgot Password</h2>
                        <p class="text-white-50 text-center mb-4">Reset your password using your phone number</p>

                        <div class="step-indicator">
                            <div class="step-dot active" id="dot1"></div>
                            <div class="step-dot" id="dot2"></div>
                            <div class="step-dot" id="dot3"></div>
                        </div>

                        <div id="alertContainer">
                            <!-- just for alrt contauner -->
                        </div>

                        <!--step 1-->
                        <div class="step active" id="step1">
                            <h5 class="text-white mb-3 text-center">Enter your account details</h5>
                            <p class="text-white-50 small text-center mb-4">We'll send a verification code to your phone number</p>
                            <form id="phoneForm">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0" style="border: 1px solid rgba(255, 255, 255, 0.2);">
                                            <i class="bi bi-person text-white-50"></i>
                                        </span>
                                        <input type="text"
                                            class="form-control form-control-lg form-control-glass rounded-end-3 border-start-0 py-3"
                                            id="username" name="username"
                                            placeholder="Enter your username"
                                            autocomplete="username"
                                            required>
                                    </div>
                                    <small class="text-white-50 d-block mt-2">Enter your account username</small>
                                </div>
                                <div class="mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0" style="border: 1px solid rgba(255, 255, 255, 0.2);">
                                            <i class="bi bi-phone text-white-50"></i>
                                        </span>
                                        <input type="tel"
                                            class="form-control form-control-lg form-control-glass rounded-end-3 border-start-0 py-3"
                                            id="phoneNumber" name="phoneNumber"
                                            placeholder="e.g., +639123456789"
                                            pattern="^\+?[0-9]{7,15}$"
                                            inputmode="tel"
                                            oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                                            required>
                                    </div>
                                    <small class="text-white-50 d-block mt-2">Enter the phone number linked to your account</small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" id="sendOtpBtn"
                                        class="btn btn-glass btn-lg text-white fw-semibold rounded-3 py-3">
                                        <span class="btn-text">Send OTP</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!--step 2-->
                        <div class="step" id="step2">
                            <h5 class="text-white mb-3 text-center">Enter Verification Code</h5>
                            <p class="text-white-50 small text-center mb-4">A 6-digit code was sent to <span id="maskedPhone"></span></p>
                            <form id="otpForm">
                                <div class="d-flex justify-content-center gap-2 mb-4">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="0" inputmode="numeric">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="1" inputmode="numeric">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="2" inputmode="numeric">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="3" inputmode="numeric">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="4" inputmode="numeric">
                                    <input type="text" class="form-control form-control-glass otp-input rounded-3" maxlength="1" data-index="5" inputmode="numeric">
                                </div>
                                <input type="hidden" id="otpCode" name="otpCode">
                                <div class="text-center mb-4">
                                    <span class="timer-text">Resend code in <span id="timer">60</span>s</span>
                                    <button type="button" id="resendBtn" class="resend-btn d-none">Resend Code</button>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" id="verifyOtpBtn"
                                        class="btn btn-glass btn-lg text-white fw-semibold rounded-3 py-3">
                                        <span class="btn-text">Verify Code</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!--step 3-->
                        <div class="step" id="step3">
                            <h5 class="text-white mb-3 text-center">Create New Password</h5>
                            <p class="text-white-50 small text-center mb-4">Enter your new password below</p>
                            <form id="passwordForm">
                                <div class="mb-3 position-relative">
                                    <input type="password"
                                        class="form-control form-control-lg form-control-glass rounded-3 py-3"
                                        id="newPassword" name="newPassword"
                                        placeholder="New Password"
                                        minlength="6"
                                        required
                                        style="padding-right: 2.5rem;">
                                    <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3 text-white toggle-password"
                                        data-target="newPassword" style="cursor: pointer;"></i>
                                </div>
                                <div class="mb-4 position-relative">
                                    <input type="password"
                                        class="form-control form-control-lg form-control-glass rounded-3 py-3"
                                        id="confirmPassword" name="confirmPassword"
                                        placeholder="Confirm Password"
                                        minlength="6"
                                        required
                                        style="padding-right: 2.5rem;">
                                    <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3 text-white toggle-password"
                                        data-target="confirmPassword" style="cursor: pointer;"></i>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" id="resetPasswordBtn"
                                        class="btn btn-glass btn-lg text-white fw-semibold rounded-3 py-3">
                                        <span class="btn-text">Reset Password</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <p class="text-center text-white-50 small mb-0 mt-4">
                            Remember your password?
                            <a href="<?php echo FRONTEND_URL; ?>/login.php"
                                class="text-warning text-decoration-none fw-semibold">Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let username = '';
        let phoneNumber = '';
        let timerInterval = null;

        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }

        function goToStep(stepNumber) {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.getElementById(`step${stepNumber}`).classList.add('active');

            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                dot.classList.remove('active', 'completed');
                if (index + 1 < stepNumber) {
                    dot.classList.add('completed');
                } else if (index + 1 === stepNumber) {
                    dot.classList.add('active');
                }
            });
        }

        function setButtonLoading(button, isLoading) {
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.spinner-border');

            if (isLoading) {
                btnText.classList.add('d-none');
                spinner.classList.remove('d-none');
                button.disabled = true;
            } else {
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
                button.disabled = false;
            }
        }

        //mask phoneNumber for display
        function maskPhone(phone) {
            if (phone.length < 4) return phone;
            return phone.slice(0, -4).replace(/./g, '*') + phone.slice(-4);
        }

        function startTimer() {
            let seconds = 60;
            const timerEl = document.getElementById('timer');
            const timerText = document.querySelector('.timer-text');
            const resendBtn = document.getElementById('resendBtn');

            timerText.classList.remove('d-none');
            resendBtn.classList.add('d-none');

            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(() => {
                seconds--;
                timerEl.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(timerInterval);
                    timerText.classList.add('d-none');
                    resendBtn.classList.remove('d-none');
                }
            }, 1000);
        }

        //send OTP
        document.getElementById('phoneForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('sendOtpBtn');
            username = document.getElementById('username').value.trim();
            phoneNumber = document.getElementById('phoneNumber').value.trim();

            if (!username) {
                showAlert('danger', 'Please enter your username.');
                return;
            }

            if (!phoneNumber) {
                showAlert('danger', 'Please enter your phone number.');
                return;
            }

            setButtonLoading(btn, true);

            try {
                const response = await fetch(`${BASE_URL}/frontend/php/forgot_password_send_otp.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        phoneNumber
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('maskedPhone').textContent = maskPhone(phoneNumber);
                    goToStep(2);
                    startTimer();
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred. Please try again.');
                console.error(error);
            } finally {
                setButtonLoading(btn, false);
            }
        });

        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                //this will combine all the inputs into hidden field
                let otp = '';
                otpInputs.forEach(inp => otp += inp.value);
                document.getElementById('otpCode').value = otp;
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                pastedData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });
                document.getElementById('otpCode').value = pastedData;
                if (pastedData.length > 0) {
                    otpInputs[Math.min(pastedData.length, otpInputs.length - 1)].focus();
                }
            });
        });

        //resend OTP
        document.getElementById('resendBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const response = await fetch(`${BASE_URL}/frontend/php/forgot_password_send_otp.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        phoneNumber
                    })
                });

                const data = await response.json();

                if (data.success) {
                    startTimer();
                    showAlert('success', 'A new verification code has been sent.');
                    //this will clear the otp input
                    otpInputs.forEach(input => input.value = '');
                    document.getElementById('otpCode').value = '';
                } else {
                    showAlert('danger', data.message);
                }
            } catch (error) {
                showAlert('danger', 'Failed to resend code. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Resend Code';
            }
        });

        // Step 2: Verify OTP
        document.getElementById('otpForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('verifyOtpBtn');
            const otpCode = document.getElementById('otpCode').value;

            if (otpCode.length !== 6) {
                showAlert('danger', 'Please enter the complete 6-digit code.');
                return;
            }

            setButtonLoading(btn, true);

            try {
                const response = await fetch(`${BASE_URL}/frontend/php/forgot_password_verify_otp.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        phoneNumber,
                        otpCode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (timerInterval) clearInterval(timerInterval);
                    goToStep(3);
                    showAlert('success', 'Phone number verified successfully.');
                } else {
                    showAlert('danger', data.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred. Please try again.');
                console.error(error);
            } finally {
                setButtonLoading(btn, false);
            }
        });

        // Step 3: Reset Password
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('resetPasswordBtn');
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword.length < 6) {
                showAlert('danger', 'Password must be at least 6 characters long.');
                return;
            }

            if (newPassword !== confirmPassword) {
                showAlert('danger', 'Passwords do not match.');
                return;
            }

            setButtonLoading(btn, true);

            try {
                const response = await fetch(`${BASE_URL}/frontend/php/forgot_password_reset.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        phoneNumber,
                        newPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = `${BASE_URL}/frontend/login.php?success=Password reset successful. Please login with your new password.`;
                    }, 2000);
                } else {
                    showAlert('danger', data.message);
                }
            } catch (error) {
                showAlert('danger', 'An error occurred. Please try again.');
                console.error(error);
            } finally {
                setButtonLoading(btn, false);
            }
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>

</html>