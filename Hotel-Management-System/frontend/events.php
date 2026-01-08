<?php
require_once __DIR__ . '/../config.php';
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
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <div class="container pt-5" id="eventsContainer">
        <div class="row">
            <div class="col">
                <h2 class="mt-5 pt-5 mb-2 text-center fw-bold h-font">EVENTS</h2>
                <div class="mx-auto mt-3 mb-5" style="width: 80px; height: 4px; background-color: #FF9900;"></div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col">
                <!-- API para sa events nina jana dito ilalagay -->
            </div>
        </div>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script>
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
    </script>
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
    <script>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>