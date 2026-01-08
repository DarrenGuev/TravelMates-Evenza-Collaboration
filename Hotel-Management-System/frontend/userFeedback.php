<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

$roomsQuery = "SELECT rooms.roomID, rooms.roomName, roomTypes.roomType FROM rooms 
                INNER JOIN roomTypes ON rooms.roomTypeId = roomTypes.roomTypeID 
                ORDER BY roomTypes.roomType, rooms.roomName";
$roomsResult = $conn->query($roomsQuery);

// Get success/error messages
$successMsg = $_SESSION['feedback_success'] ?? null;
$errorMsg = $_SESSION['feedback_error'] ?? null;
unset($_SESSION['feedback_success'], $_SESSION['feedback_error']);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Customer Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.25rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input:checked~label {
            color: #ffc107;
        }

        .star-rating label:before {
            content: '★';
        }
    </style>
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <!-- Success/Error Alerts -->
                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $errorMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-chat-heart me-2"></i>Share Your Experience</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">We value your feedback! Please take a moment to share your experience with us.</p>

                        <form action="php/submit_feedback.php" method="POST" id="feedbackForm">
                            <!-- User Name -->
                            <div class="mb-3">
                                <label for="userName" class="form-label">
                                    <i class="bi bi-person-fill me-1"></i>Your Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="userName" name="userName"
                                    placeholder="Enter your full name" required maxlength="300">
                            </div>



                            <!-- Room Selection -->
                            <div class="mb-3">
                                <label for="roomID" class="form-label">
                                    <i class="bi bi-door-open-fill me-1"></i>Room Stayed <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="roomID" name="roomID" required>
                                    <option value="" selected disabled>Select the room you stayed in</option>
                                    <?php
                                    if ($roomsResult && $roomsResult->num_rows > 0) {
                                        $currentType = '';
                                        while ($room = $roomsResult->fetch_assoc()) {
                                            // Add optgroup for room types
                                            if ($currentType !== $room['roomType']) {
                                                if ($currentType !== '') {
                                                    echo '</optgroup>';
                                                }
                                                $currentType = $room['roomType'];
                                                echo '<optgroup label="' . htmlspecialchars($currentType) . '">';
                                            }
                                            echo '<option value="' . $room['roomID'] . '">' . htmlspecialchars($room['roomName']) . '</option>';
                                        }
                                        if ($currentType !== '') {
                                            echo '</optgroup>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Star Rating -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-star-fill me-1"></i>Your Rating <span class="text-danger">*</span>
                                </label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required>
                                    <label for="star5" title="5 stars - Excellent"></label>
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4" title="4 stars - Very Good"></label>
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3" title="3 stars - Good"></label>
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2" title="2 stars - Fair"></label>
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1" title="1 star - Poor"></label>
                                </div>
                                <div class="form-text" id="ratingText">Click to rate your experience</div>
                            </div>

                            <!-- Comments -->
                            <div class="mb-4">
                                <label for="comments" class="form-label">
                                    <i class="bi bi-pencil-fill me-1"></i>Your Comments <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="comments" name="comments" rows="5"
                                    placeholder="Tell us about your stay... What did you enjoy? What could be improved?"
                                    required minlength="10"></textarea>
                                <div class="form-text">Minimum 10 characters</div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill me-2"></i>Submit Feedback
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info"><i class="bi bi-info-circle-fill me-2"></i>Why Your Feedback Matters</h6>
                        <p class="card-text small text-muted mb-0">
                            Your reviews help us improve our services and assist other travelers in making informed decisions.
                            All feedback is carefully reviewed by our team.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        const ratingLabels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        document.querySelectorAll('.star-rating input').forEach(input => {
            input.addEventListener('change', function() {
                const ratingText = document.getElementById('ratingText');
                ratingText.textContent = ratingLabels[this.value - 1] + ' (' + this.value + ' star' + (this.value > 1 ? 's' : '') + ')';
                ratingText.classList.remove('text-muted');
                ratingText.classList.add('text-warning', 'fw-bold');
            });
        });

        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.querySelector('input[name="rating"]:checked');
            if (!rating) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
                return false;
            }
        });
    </script>
</body>

</html>