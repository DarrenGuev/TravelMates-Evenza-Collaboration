<?php
session_start();

// Include configuration file
require_once __DIR__ . '/config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

// Load recent customer feedbacks for the homepage
$feedbackQuery = "SELECT f.userName AS username, f.comments AS userReview, f.rating, r.roomName, f.submittedAt FROM feedback f LEFT JOIN rooms r ON f.roomID = r.roomID ORDER BY f.submittedAt DESC LIMIT 8";
$feedbackResult = $conn->query($feedbackQuery);
$reviewsArray = [];
if ($feedbackResult && $feedbackResult->num_rows > 0) {
    while ($row = $feedbackResult->fetch_assoc()) {
        $reviewsArray[] = $row;
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates</title>
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/logo/logoW.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
</head>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <div id="home" class="position-relative d-flex align-items-center justify-content-center"
        style="min-height: 100vh; background: url('images/loginRegisterImg/img.jpg') center/cover no-repeat fixed;">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-black opacity-50"></div>
        <div class="container position-relative">
            <div class="row justify-content-center pt-5">
                <div class="col-12 col-md-10 col-lg-8 text-black text-center">
                    <h1 class="fw-bold mb-3" style="color: white; text-shadow: 0 4px 15px rgba(0,0,0,0.9);">
                        Welcome to
                    </h1>
                    <h1 class="display-1 fw-bold mb-3" style="color: white; text-shadow: 0 4px 15px rgba(0,0,0,0.9);">
                        TravelMates Hotel
                    </h1>
                    <p class="lead fs-4 mb-5 opacity-75" style="color: white; text-shadow: 0 4px 15px rgba(0,0,0,0.9);">
                        travelmits // aayusin pa to
                    </p>
                    <a href="<?php echo FRONTEND_URL; ?>/rooms.php" class="btn btn-warning btn-lg">Book Now</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col">
                <h2 class="mt-5 pt-4 mb-2 text-center fw-bold h-font">OUR ROOMS</h2>
                <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;"></div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row" id="ourRoomsContainer">

        </div>
    </div>
    <div class="col-lg-12 text-center mt-4 mb-5">
        <a href="<?php echo FRONTEND_URL; ?>/rooms.php" class="btn btn-sm btn-outline-dark rounded-0 fw-bold shadow-none">More Rooms
            >>></a>
    </div>

    <div class="body bg-body-tertiary pb-5">
        <div class="container mb-5">
            <div class="row align-items-center py-2">
                <div class="col-12 text-black text-md-start">
                    <h2 class="mt-5 mb-2 text-center fw-bold h-font">GALLERY</h2>
                    <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;"></div>
                </div>
            </div>

            <!--bento box-->
            <div class="container-fluid px-0">
                <div class="row g-3">
                    <!--left large-->
                    <div class="col-12 col-md-8">
                        <div id="galleryCarousel" class="carousel slide h-100" data-bs-ride="carousel" data-bs-interval="2000">
                            <div class="carousel-inner h-100 rounded-3">
                                <div class="carousel-item active h-100">
                                    <div class="ratio ratio-4x3 h-100">
                                        <img src="<?php echo IMAGES_URL; ?>/rooms/basic.jpeg" class="d-block w-100 object-fit-cover"
                                            alt="Gallery 1">
                                    </div>
                                </div>
                                <div class="carousel-item h-100">
                                    <div class="ratio ratio-4x3 h-100">
                                        <img src="<?php echo IMAGES_URL; ?>/rooms/special(1).jpg" class="d-block w-100 object-fit-cover"
                                            alt="Gallery 2">
                                    </div>
                                </div>
                                <div class="carousel-item h-100">
                                    <div class="ratio ratio-4x3 h-100">
                                        <img src="<?php echo IMAGES_URL; ?>/rooms/twin_room.jpeg" class="d-block w-100 object-fit-cover"
                                            alt="Gallery 3">
                                    </div>
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                    <!--right column with 2 stacked images-->
                    <div class="col-12 col-md-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                                    <img src="<?php echo IMAGES_URL; ?>/rooms/special(2).jpeg" alt="Gallery 2"
                                        class="img-fluid object-fit-cover">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                                    <img src="<?php echo IMAGES_URL; ?>/rooms/family_rooms/F(1).jpg" alt="Gallery 3"
                                        class="img-fluid object-fit-cover">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--bottom row with 3 images-->
                <div class="row g-3 mt-0">
                    <div class="col-12 col-md-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                            <img src="<?php echo IMAGES_URL; ?>/rooms/family_rooms/F(3).jpeg" alt="Gallery 4"
                                class="img-fluid object-fit-cover">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                            <img src="<?php echo IMAGES_URL; ?>/rooms/single_rooms/(1).jpg" alt="Gallery 5"
                                class="img-fluid object-fit-cover">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden">
                            <img src="<?php echo IMAGES_URL; ?>/rooms/single_rooms/(2).jpg" alt="Gallery 6"
                                class="img-fluid object-fit-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col">
                <h2 class="mt-5 pt-4 mb-2 text-center fw-bold h-font">CUSTOMER REVIEWS</h2>
                <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;"></div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-dark rounded-circle me-3 d-none d-md-block" id="prevReview"
                onclick="changeReviewPage(-1)">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="row flex-grow-1" id="reviewsContainer">
                <?php if (count($reviewsArray) > 0): ?>
                    <script>
                        var allReviews = <?php echo json_encode($reviewsArray); ?>;
                        var currentPage = 0;
                        var reviewsPerPage = 3;

                        function renderReviews() {
                            var container = document.getElementById('reviewsContainer');
                            container.innerHTML = '';
                            var start = currentPage * reviewsPerPage;
                            var end = Math.min(start + reviewsPerPage, allReviews.length);

                            if (allReviews.length === 0) {
                                container.innerHTML = '<div class="col-12"><div class="alert alert-info mb-0">No customer reviews yet. Be the first to <a href="<?php echo FRONTEND_URL; ?>/userFeedback.php">leave a review</a>.</div></div>';
                                return;
                            }

                            for (var i = start; i < end; i++) {
                                var review = allReviews[i];
                                var stars = '';
                                for (var j = 0; j < parseInt(review.rating); j++) {
                                    stars += '<i class="bi bi-star-fill text-warning"></i>';
                                }
                                var date = new Date(review.submittedAt);
                                var options = {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                };
                                var formattedDate = new Date(review.submittedAt).toLocaleDateString();
                                var roomInfo = review.roomName ? ' for ' + escapeHtml(review.roomName) : '';
                                var reviewText = review.userReview || '';
                                var seeMore = '<button class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#fullReviewModal" onclick="showFullReview(\'' + encodeURIComponent(review.username) + '\', \'' + encodeURIComponent(reviewText) + '\', ' + review.rating + ', \'' + formattedDate + '\', \'' + encodeURIComponent(review.roomName || '') + '\')">see more...</button>';
                                var finalReviewHtml = '';

                                if (reviewText.length > 30) {
                                    var truncated = reviewText.slice(0, 30);
                                    finalReviewHtml = escapeHtml(truncated).replace(/\n/g, '<br>') + seeMore;
                                } else {
                                    finalReviewHtml = escapeHtml(reviewText).replace(/\n/g, '<br>');
                                }

                                container.innerHTML += `
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title fw-bold h-font">
                                                        ${escapeHtml(review.username)}
                                                    </h5>
                                                    ${stars}
                                                    <p class="mt-3 card-subtitle mb-2 text-muted">
                                                        Reviewed on ${formattedDate}${roomInfo}
                                                    </p>
                                                    <h6 class="card-text">
                                                        ${finalReviewHtml}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                            }

                            document.getElementById('prevReview').disabled = currentPage === 0;
                            document.getElementById('nextReview').disabled = end >= allReviews.length;
                        }

                        function escapeHtml(text) {
                            if (!text) return '';
                            var div = document.createElement('div');
                            div.textContent = text;
                            return div.innerHTML;
                        }

                        function changeReviewPage(direction) {
                            var totalPages = Math.ceil(allReviews.length / reviewsPerPage);
                            currentPage += direction;
                            if (currentPage < 0) currentPage = 0;
                            if (currentPage >= totalPages) currentPage = totalPages - 1;
                            renderReviews();
                        }

                        document.addEventListener('DOMContentLoaded', function() {
                            renderReviews();
                        });
                    </script>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">No customer reviews yet. Be the first to <a
                                href="<?php echo FRONTEND_URL; ?>/userFeedback.php">leave a review</a>.</div>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn btn-outline-dark rounded-circle ms-3 d-none d-md-block" id="nextReview"
                onclick="changeReviewPage(1)">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="row mt-3 justify-content-center">
            <div class="col-6 d-flex justify-content-center">
                <button class="btn btn-outline-dark rounded-circle me-3 d-block d-md-none"
                    id="prevReview" onclick="changeReviewPage(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
            <div class="col-6 d-flex justify-content-center">
                <button class="btn btn-outline-dark rounded-circle ms-3 d-block d-md-none"
                    id="nextReview" onclick="changeReviewPage(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>


        <div class="col-lg-12 text-center my-4 mb-5">
            <a href="<?php echo FRONTEND_URL; ?>/userFeedback.php" class="btn btn-warning shadow-none">
                <i class="bi bi-pencil-square me-2"></i>Add Your Review
            </a>
        </div>
    </div>
    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="<?php echo JS_URL; ?>/ourRooms.js"></script>
    <script>
        var ourRoomsContainer = document.getElementById("ourRoomsContainer");
        for (var i = 0; i < ourRooms.length; i++) {
            ourRoomsContainer.innerHTML += `
            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3 pb-4">
                <div class="card h-100 bg-transparent shadow rounded-3">
                    <div class="ratio ratio-4x3 overflow-hidden rounded-top-3">
                        <img src="<?php echo IMAGES_URL; ?>/rooms/` + ourRooms[i].images + `" alt="..." class="img-fluid">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">` + ourRooms[i].name + `</h5>
                        <p class="card-text">` + ourRooms[i].description + `</p>
                    </div>
                </div>
            </div>
      `;
        }

        function changeMode() {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);

            document.querySelectorAll('#mode i, #mode-lg i').forEach(icon => {
                icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            });

            // Update logos
            const logoPath = newTheme === 'dark' ? '<?php echo IMAGES_URL; ?>/logo/logoW.png' : '<?php echo IMAGES_URL; ?>/logo/logoB.png';
            document.querySelectorAll('#site-logo, #footer-logo').forEach(function(logo) {
                logo.src = logoPath;
            });

            document.querySelectorAll('.text-black, .text-white').forEach(element => {
                element.classList.toggle('text-black');
                element.classList.toggle('text-white');
            });

            document.querySelectorAll('.btn-outline-dark, .btn-outline-light').forEach(element => {
                element.classList.toggle('btn-outline-dark');
                element.classList.toggle('btn-outline-light');
            });

            const aboutSection = document.querySelector('#about-section');
            if (aboutSection) {
                if (isDark) {
                    aboutSection.style.background = "linear-gradient(rgba(245, 240, 230, 0.85), rgba(245, 240, 230, 0.85)), url('images/loginRegisterImg/img.jpg') center/cover no-repeat";
                } else {
                    aboutSection.style.background = "linear-gradient(rgba(30, 30, 30, 0.9), rgba(30, 30, 30, 0.9)), url('images/loginRegisterImg/img.jpg') center/cover no-repeat";
                }
            }
        }

        function showFullReview(username, reviewText, rating, date, roomName) {
            document.getElementById('modalReviewUsername').textContent = decodeURIComponent(username);
            document.getElementById('modalReviewText').innerHTML = decodeURIComponent(reviewText).replace(/\n/g, '<br>');
            document.getElementById('modalReviewDate').textContent = date;

            var roomInfo = decodeURIComponent(roomName);
            document.getElementById('modalReviewRoom').textContent = roomInfo ? ' for ' + roomInfo : '';

            var starsHtml = '';
            for (var i = 0; i < rating; i++) {
                starsHtml += '<i class="bi bi-star-fill text-warning"></i>';
            }
            document.getElementById('modalReviewStars').innerHTML = starsHtml;
        }
    </script>

    <!-- Full Review Modal -->
    <div class="modal fade" id="fullReviewModal" tabindex="-1" aria-labelledby="fullReviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold h-font" id="fullReviewModalLabel">Customer Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 class="fw-bold h-font" id="modalReviewUsername"></h5>
                    <div id="modalReviewStars" class="mb-2"></div>
                    <p class="text-muted mb-3">
                        <small>Reviewed on <span id="modalReviewDate"></span><span id="modalReviewRoom"></span></small>
                    </p>
                    <p id="modalReviewText" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>