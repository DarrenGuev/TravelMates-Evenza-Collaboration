<?php
session_start();

require_once __DIR__ . '/../config.php';
?>


<!doctype html>
<html lang="en">

<?php $title = "About Us "; ?>
<?php include INCLUDES_PATH . '/head.php'; ?>

<body>
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <div class="container pt-5" id="about">
        <div class="row">
            <div class="col">
                <h2 class="mt-5 pt-5 mb-2 text-center fw-bold h-font">ABOUT US</h2>
                <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;">
                </div>
            </div>
        </div>
    </div>

    <div id="about-section" class="container-fluid py-5 shadow-sm"
        style="background: linear-gradient(rgba(245, 240, 230, 0.85), rgba(245, 240, 230, 0.85)), url('../images/loginRegisterImg/img.jpg') center/cover no-repeat;">
        <div class="row justify-content-center g-4">
            <div class="col-12 col-lg-5">
                <div
                    class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start text-center text-sm-start">
                    <div class="rounded-5 overflow-hidden border border-3 border-secondary flex-shrink-0 mb-3 mb-sm-0"
                        style="width: 200px; height: 200px;">
                        <img src="<?php echo IMAGES_URL; ?>/loginRegisterImg/img.jpg" alt="..."
                            class="img-fluid object-fit-cover w-100 h-100">
                    </div>
                    <div class="ms-sm-4">
                        <h5 class="fw-bold text-uppercase text-secondary mb-3" style="letter-spacing: 2px;">A
                            Little
                            About Us</h5>
                        <p class="text-muted mb-0">TravelMates is a web-based Hotel-Management-System designed to automate and
                            simplify hotel operations, particularly room reservations. The system allows customers to
                            view available rooms, make bookings online, and receive booking confirmations, while
                            enabling hotel staff and administrators to manage reservations efficiently.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div
                    class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start text-center text-sm-start">
                    <div class="rounded-5 overflow-hidden border border-3 border-secondary flex-shrink-0 mb-3 mb-sm-0"
                        style="width: 200px; height: 200px;">
                        <img src="<?php echo IMAGES_URL; ?>/loginRegisterImg/evenzalogo.png" alt="..."
                            class="img-fluid object-fit-cover w-100 h-100" style="filter: grayscale(100%);">
                    </div>
                    <div class="ms-sm-4">
                        <h5 class="fw-bold text-uppercase text-secondary mb-3" style="letter-spacing: 2px;">Our
                            Collaborator</h5>
                        <p class="text-muted mb-0">EVENZA is a premium event reservation and ticketing platform focused
                            on delivering seamless and well-organized hotel-hosted events. We aim to connect guests with
                            carefully curated experiences through a secure and user-friendly digital system.

                            By combining modern technology with professional event management, EVENZA helps organizers
                            efficiently manage reservations, service packages, and guest experiences while ensuring
                            convenience and reliability for every attendee.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row" id="membersContainer">

        </div>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script src="<?php echo JS_URL; ?>/members.js"></script>
    <script>
        var memberContainer = document.getElementById("membersContainer");
        for (var i = 0; i < members.length; i++) {
            memberContainer.innerHTML += `
            <div class="col-12 col-sm-6 col-md-4 col-lg pb-4 text-center">
                <div class="card h-100 bg-transparent border-0 rounded-3 align-items-center">
                    <div class="rounded-circle overflow-hidden border border-3 border-secondary flex-shrink-0 mb-3 mb-sm-0"
                        style="width: 200px; height: 200px;">
                        <img src="<?php echo IMAGES_URL; ?>/members-img/` + members[i].images + `" alt="..."
                            class="img-fluid object-fit-cover w-100 h-100">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">` + members[i].name + `</h5>
                    </div>
                </div>
            </div>
      `;
        }
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';

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
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>