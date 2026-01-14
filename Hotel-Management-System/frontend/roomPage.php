<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once DBCONNECT_PATH . '/connect.php';
require_once CLASSES_PATH . '/autoload.php';

$userModel = new User();
$roomModel = new Room();
$roomTypeModel = new RoomType();

$roomID = isset($_GET['roomID']) ? (int) $_GET['roomID'] : 0;

if ($roomID <= 0) {
    header('Location: rooms.php');
    exit;
}

$room = $roomModel->find($roomID);

if (!$room) {
    header('Location: rooms.php');
    exit;
}

$roomTypeName = 'Standard';
if (isset($room['roomTypeID']) && $room['roomTypeID']) {
    $roomType = $roomTypeModel->find((int)$room['roomTypeID']);
    $roomTypeName = $roomType && isset($roomType['roomType']) ? $roomType['roomType'] : 'Standard';
}

$featuresData = $roomModel->getFeatures($roomID);
$features = [];
foreach ($featuresData as $feature) {
    $features[] = $feature['featureName'];
}

$userData = null;
if (isset($_SESSION['userID'])) {
    $userID = (int) $_SESSION['userID'];
    $user = $userModel->find($userID);
    if ($user) {
        $userData = [
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'email' => $user['email'],
            'phoneNumber' => $user['phoneNumber'],
            'fullName' => $user['firstName'] . ' ' . $user['lastName']
        ];
    }
}

$roomName = htmlspecialchars($room['roomName']);
$basePrice = $room['base_price'];
$capacity = (int) $room['capacity'];
$quantity = (int) $room['quantity'];
$imagePath = htmlspecialchars($room['imagePath']);
?>
<!doctype html>
<html lang="en">

<?php $title = $roomName . " "; ?>
<?php include INCLUDES_PATH . '/head.php'; ?>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container pt-5">
        <div class="row py-2 mb-3">
            <div class="col-12 pt-5">
                <div class="mt-3">
                    <a href="<?php echo FRONTEND_URL; ?>/rooms.php" class="text-black text-decoration-none">
                        <i class="bi bi-arrow-left-circle"></i> Back to Rooms
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!--room image at details -->
            <div class="col-12 col-xl-7 mb-4">
                <div class="position-relative rounded-3 overflow-hidden mb-4">
                    <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo $imagePath; ?>"
                        alt="<?php echo $roomName; ?>"
                        class="img-fluid w-100 rounded-3" style="max-height: 500px; object-fit: cover;">
                </div>

                <h2 class="fw-bold mb-3"><?php echo $roomName; ?></h2>
                <p class="text-secondary fst-italic mb-3">
                    <?php echo htmlspecialchars($roomTypeName); ?> Room • Max <?php echo $capacity; ?> Guests
                </p>
                <h4 class="text-warning fw-bold mb-4">₱<?php echo number_format($basePrice, 2); ?> <span class="fs-6 text-muted fw-normal">/ night</span></h4>

                <div class="mb-4">
                    <h5 class="fw-semibold mb-3">Room Details</h5>
                    <div class="row">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="border rounded p-3 text-center h-100">
                                <i class="bi bi-building-fill-check fs-4 text-warning"></i>
                                <p class="small text-muted mb-0 mt-2">Type</p>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($roomTypeName); ?></p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="border rounded p-3 text-center h-100">
                                <i class="bi bi-people fs-4 text-warning"></i>
                                <p class="small text-muted mb-0 mt-2">Capacity</p>
                                <p class="fw-semibold mb-0"><?php echo $capacity; ?> Guests</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="border rounded p-3 text-center h-100">
                                <i class="bi bi-door-open fs-4 text-warning"></i>
                                <p class="small text-muted mb-0 mt-2">Available</p>
                                <p class="fw-semibold mb-0"><?php echo $quantity; ?> Rooms</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="border rounded p-3 text-center h-100">
                                <i class="bi bi-currency-exchange fs-4 text-warning"></i>
                                <p class="small text-muted mb-0 mt-2">Price</p>
                                <p class="fw-semibold mb-0">₱<?php echo number_format($basePrice, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-semibold mb-3">Features</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($features)) {
                            foreach ($features as $featureName) { ?>
                                <span class="badge bg-dark"><?php echo htmlspecialchars($featureName); ?></span>
                            <?php }
                        } else { ?>
                            <span class="text-muted">No features listed</span>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!--booking form -->
            <div class="col-12 col-xl-5 mb-5">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>Book This Room</h5>
                    </div>
                    <div class="card-body">
                        <form id="bookingForm" action="php/process_booking.php" method="POST">
                            <input type="hidden" name="roomID" value="<?php echo $roomID; ?>">
                            <input type="hidden" name="totalPrice" id="totalPriceInput" value="<?php echo $basePrice; ?>">
                            <input type="hidden" name="paymentMethod" id="paymentMethodInput" value="">

                            <p class="fw-bold mb-2">Guest Information</p>
                            <?php if ($userData): ?>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <input type="text" name="firstName" id="firstName"
                                            class="form-control mb-2"
                                            placeholder="First name"
                                            value="<?php echo htmlspecialchars($userData['firstName'] ?? '', ENT_QUOTES); ?>"
                                            required>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <input type="text" name="lastName" id="lastName"
                                            class="form-control mb-2"
                                            placeholder="Last name"
                                            value="<?php echo htmlspecialchars($userData['lastName'] ?? '', ENT_QUOTES); ?>"
                                            required>
                                    </div>
                                </div>
                                <input type="email" name="email" class="form-control mb-2"
                                    placeholder="Email"
                                    value="<?php echo htmlspecialchars($userData['email'] ?? '', ENT_QUOTES); ?>"
                                    required>
                                <input type="tel" name="phoneNumber"
                                    class="form-control mb-2"
                                    placeholder="Phone Number (7-12 digits, optional +country)"
                                    value="<?php echo htmlspecialchars($userData['phoneNumber'] ?? '', ENT_QUOTES); ?>"
                                    pattern="^\+?[0-9]{7,12}$"
                                    title="Enter 7 to 12 digits (optional leading +country code)"
                                    inputmode="tel" maxlength="13"
                                    oninput="this.value = this.value.replace(/(?!^\+)[^0-9]/g, '').replace(/(?!^)\+/g, '')"
                                    required>
                                <div class="invalid-feedback">Please enter a valid phone number (7-12 digits).</div>
                            <?php else: ?>
                                <div class="alert alert-warning small">
                                    <i class="bi bi-info-circle me-1"></i>Please <a href="<?php echo FRONTEND_URL; ?>/login.php">log in</a> to book a room. Fields are disabled until you sign in.
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <input type="text" name="firstName" id="firstName"
                                            disabled class="form-control mb-2"
                                            placeholder="First name" required>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <input type="text" name="lastName" id="lastName"
                                            disabled class="form-control mb-2"
                                            placeholder="Last name" required>
                                    </div>
                                </div>
                                <input type="email" class="form-control mb-2" placeholder="Email" disabled>
                                <input type="tel" class="form-control mb-2"
                                    placeholder="Phone Number (7-12 digits, optional +country)"
                                    disabled pattern="^\+?[0-9]{7,12}$"
                                    title="Enter 7 to 12 digits (optional leading +country code)"
                                    inputmode="tel" maxlength="13">
                            <?php endif; ?>

                            <hr class="my-3">

                            <p class="fw-bold mb-2">Booking Details</p>
                            <div class="row">
                                <div class="col-6">
                                    <label for="checkIn" class="form-label mb-0">Check-in</label>
                                    <input type="date" name="checkInDate" id="checkIn"
                                        class="form-control mb-2" <?php echo !$userData ? 'disabled' : ''; ?> required>
                                </div>
                                <div class="col-6">
                                    <label for="checkOut" class="form-label mb-0">Check-out</label>
                                    <input type="date" name="checkOutDate" id="checkOut"
                                        class="form-control mb-2" <?php echo !$userData ? 'disabled' : ''; ?> required>
                                </div>
                            </div>
                            <div class="alert alert-danger py-2 mb-2" id="dateError" style="display: none;">
                                <small><i class="bi bi-exclamation-triangle me-1"></i><span id="dateErrorMessage"></span></small>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="guests" class="form-label mb-0">Guests</label>
                                    <input type="number" name="numberOfGuests" id="guests"
                                        class="form-control" min="1" max="<?php echo $capacity; ?>" value="1"
                                        <?php echo !$userData ? 'disabled' : ''; ?> required>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="bg-body-tertiary rounded p-3 mb-3">
                                <p class="fw-bold text-secondary mb-2">Booking Summary</p>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Room:</span>
                                    <span class="fw-semibold"><?php echo $roomName; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Dates:</span>
                                    <span id="summaryDates">-</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Duration:</span>
                                    <span id="summaryNights">-</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Guests:</span>
                                    <span id="summaryGuests">1</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Total:</span>
                                    <span class="fw-bold text-warning fs-5">₱<span id="summaryTotal"><?php echo number_format($basePrice, 2); ?></span></span>
                                </div>
                            </div>
                        </form>

                        <?php if (!$userData) : ?>
                            <button type="button" class="btn btn-warning w-100" disabled title="Please log in to proceed">
                                <i class="bi bi-lock me-2"></i>Proceed to Payment
                            </button>
                        <?php else : ?>
                            <button type="button" class="btn btn-warning w-100" onclick="openPaymentModal()">
                                <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--payment modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closePaymentModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h6 class="text-muted">Total Amount</h6>
                        <h2 class="text-warning fw-bold">₱<span id="paymentTotal">0.00</span></h2>
                    </div>

                    <h6 class="fw-bold mb-3">Select Payment Method</h6>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary payment-method-btn"
                            data-method="paypal" onclick="selectPayment('paypal')">
                            <i class="bi bi-paypal me-2"></i>PayPal
                        </button>
                    </div>

                    <!--payment details section (hidden by default) -->
                    <div id="paymentDetails" class="mt-4" style="display: none;">
                        <hr>
                        <div id="paypalDetails" class="payment-detail" style="display: none;">
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle me-1"></i>You will be redirected to PayPal to complete your secure online payment.
                            </div>
                        </div>

                        <button type="button" class="btn btn-warning w-100 mt-3" onclick="confirmPayment()">
                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="<?php echo JS_URL; ?>/showAlert.js"></script>
    <script>
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
        const roomID = <?php echo $roomID; ?>;
        const basePrice = <?php echo $basePrice; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            setupBookingCalculation();
        });

        function setupBookingCalculation() {
            const checkIn = document.getElementById('checkIn');
            const checkOut = document.getElementById('checkOut');
            const guests = document.getElementById('guests');

            if (checkIn && checkOut && guests) {
                const today = new Date().toISOString().split('T')[0];
                checkIn.min = today;
                checkOut.min = today;

                checkIn.addEventListener('change', () => {
                    if (checkIn.value) {
                        const checkInDate = new Date(checkIn.value);
                        checkInDate.setDate(checkInDate.getDate() + 1);
                        checkOut.min = checkInDate.toISOString().split('T')[0];

                        if (checkOut.value && new Date(checkOut.value) <= new Date(checkIn.value)) {
                            checkOut.value = '';
                        }
                    }
                    updateSummary();
                });
                checkOut.addEventListener('change', () => updateSummary());
                guests.addEventListener('change', () => updateSummary());
            }
        }

        function updateSummary() {
            const checkIn = document.getElementById('checkIn').value;
            const checkOut = document.getElementById('checkOut').value;
            const guests = document.getElementById('guests').value;
            const errorDiv = document.getElementById('dateError');
            const errorMsg = document.getElementById('dateErrorMessage');

            if (errorDiv) errorDiv.style.display = 'none';

            if (checkIn && checkOut) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);
                const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

                if (checkOutDate <= checkInDate) {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Check-out date must be after check-in date.';
                        errorDiv.style.display = 'block';
                    }
                    document.getElementById('summaryDates').textContent = '-';
                    document.getElementById('summaryNights').textContent = '-';
                    document.getElementById('summaryTotal').textContent = '0.00';
                    document.getElementById('totalPriceInput').value = '0';
                    return;
                }

                if (nights > 0) {
                    const total = basePrice * nights;
                    document.getElementById('summaryDates').textContent =
                        checkInDate.toLocaleDateString() + ' - ' + checkOutDate.toLocaleDateString();
                    document.getElementById('summaryNights').textContent = nights + ' night(s)';
                    document.getElementById('summaryGuests').textContent = guests;
                    document.getElementById('summaryTotal').textContent = total.toLocaleString('en-PH', {
                        minimumFractionDigits: 2
                    });
                    document.getElementById('totalPriceInput').value = total;
                } else {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Invalid date range. Please select valid check-in and check-out dates.';
                        errorDiv.style.display = 'block';
                    }
                }
            }
        }

        let selectedPaymentMethod = null;

        function openPaymentModal() {
            const form = document.getElementById('bookingForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const checkIn = document.getElementById('checkIn').value;
            const checkOut = document.getElementById('checkOut').value;
            const errorDiv = document.getElementById('dateError');
            const errorMsg = document.getElementById('dateErrorMessage');

            if (checkIn && checkOut) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);

                if (checkOutDate <= checkInDate) {
                    if (errorDiv && errorMsg) {
                        errorMsg.textContent = 'Check-out date must be after check-in date. Please correct your dates before proceeding.';
                        errorDiv.style.display = 'block';
                    }
                    errorDiv.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
            }

            const total = document.getElementById('totalPriceInput').value;

            if (!total || parseFloat(total) <= 0) {
                if (errorDiv && errorMsg) {
                    errorMsg.textContent = 'Please select valid dates to calculate the total price.';
                    errorDiv.style.display = 'block';
                }
                return;
            }

            document.getElementById('paymentTotal').textContent =
                parseFloat(total).toLocaleString('en-PH', {
                    minimumFractionDigits: 2
                });

            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        function closePaymentModal() {
            selectedPaymentMethod = null;
            document.querySelectorAll('.payment-detail').forEach(el => {
                el.style.display = 'none';
            });
            const paymentContainerHide = document.getElementById('paymentDetails');
            if (paymentContainerHide) paymentContainerHide.style.display = 'none';
            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
            });
        }

        function selectPayment(method) {
            selectedPaymentMethod = method;
            const input = document.getElementById('paymentMethodInput');
            if (input) input.value = method;

            document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));

            const chosenBtn = document.querySelector('.payment-method-btn[data-method="' + method + '"]');
            if (chosenBtn) chosenBtn.classList.add('active');

            document.querySelectorAll('.payment-detail').forEach(el => el.style.display = 'none');
            const paymentContainer = document.getElementById('paymentDetails');
            if (paymentContainer) paymentContainer.style.display = 'block';
            const detail = document.getElementById(method + 'Details');
            if (detail) detail.style.display = 'block';
        }

        function confirmPayment() {
            if (!selectedPaymentMethod) {
                showAlert('Please choose a payment method to continue with your booking.', 'warning', 'Payment Method Required');
                return;
            }

            const btn = (event && event.target) ? event.target : null;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }

            if (selectedPaymentMethod === 'paypal') {
                const total = document.getElementById('totalPriceInput').value;
                const form = document.getElementById('bookingForm');
                if (!form) {
                    showAlert('We encountered a technical issue loading the booking form. Please refresh the page and try again.', 'danger', 'Form Error');
                    if (btn) btn.disabled = false;
                    return;
                }

                const fd = new FormData(form);
                fd.append('ajax', '1');
                fd.set('paymentMethod', 'paypal');

                fetch('<?php echo FRONTEND_URL; ?>/php/process_booking.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                }).then(r => r.json()).then(data => {
                    if (data && data.success && data.bookingID) {
                        const url = '<?php echo BASE_URL; ?>/integrations/paypal/create_order.php?roomID=' + encodeURIComponent(roomID) + '&amount=' + encodeURIComponent(total) + '&bookingID=' + encodeURIComponent(data.bookingID);
                        window.location.href = url;
                    } else {
                        showAlert('Unable to process your booking at this time. Your information has been saved, but we couldn\'t connect to PayPal. Please try again or contact support if the issue persists.', 'danger', 'Booking Failed');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'Confirm Payment';
                        }
                    }
                }).catch(err => {
                    console.error(err);
                    showAlert('A network error occurred while processing your booking. Please check your internet connection and try again. If the problem continues, please contact our support team.', 'danger', 'Connection Error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Confirm Payment';
                    }
                });
                return;
            }

            setTimeout(() => {
                document.getElementById('bookingForm').submit();
            }, 800);
        }
    </script>
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
</body>

</html>