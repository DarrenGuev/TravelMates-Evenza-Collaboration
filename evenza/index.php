<?php 
session_start(); 
require_once 'core/connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVENZA - Premium Event Reservation & Ticketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reviews-section {
            background: linear-gradient(135deg, #F9F7F2 0%, #FFFFFF 100%);
            padding: 5rem 0;
        }
        .review-card {
            background: #FFFFFF;
            border-radius: 15px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(74, 93, 74, 0.1);
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        .review-user-info {
            flex: 1;
        }
        .review-user-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-charcoal);
            margin-bottom: 0.5rem;
        }
        .review-event-name {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: var(--accent-olive);
            font-weight: 500;
        }
        .review-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }
        .review-rating i {
            color: #FFD700;
            font-size: 1rem;
        }
        .review-rating i.far {
            color: #E0E0E0;
        }
        .review-comment {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark-gray);
            line-height: 1.6;
            margin-bottom: 1rem;
            font-style: italic;
        }
        .review-date {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            color: var(--text-dark-gray);
            opacity: 0.6;
        }
        .reviews-empty {
            text-align: center;
            padding: 3rem 0;
            color: var(--text-dark-gray);
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="navbar navbar-expand-lg navbar-light fixed-top luxury-nav">
        <div class="container">
            <a class="navbar-brand luxury-logo" href="index.php"><img src="assets/images/evenzaLogo.png" alt="EVENZA" class="evenza-logo-img"><span class="visually-hidden">EVENZA</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user/pages/events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user/pages/about.php">About</a>
                    </li>
                    <li class="nav-item nav-divider">
                        <span class="nav-separator"></span>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/pages/profile.php">My Profile</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link btn-register" href="user/process/logout.php?type=user">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-login" href="user/pages/login.php">Login</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link btn-register" href="user/pages/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-8 col-xl-7">
                    <div class="hero-content">
                        <div class="hero-logo-wrap mb-3">
                            <img src="assets/images/evenzaLogo.png" alt="EVENZA" class="hero-logo img-fluid">
                        </div>
                        <h1 class="visually-hidden">EVENZA</h1>
                        <p class="hero-subtitle">Reserve Hotel-Hosted Events with Ease</p>
                        <div class="hero-buttons mt-4">
                            <a href="user/pages/events.php" class="btn btn-primary-luxury btn-lg">Explore Events</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="featured-events py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Featured Events</h2>
                <p class="section-subtitle">Curated selections for the discerning attendee</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="luxury-card event-card">
                        <div class="event-image position-relative">
                            <img src="assets/images/event_images/galaEvening.jpg" class="card-img-top featured-event-image" alt="Gala Evening">
                        </div>
                        <div class="event-content p-4">
                            <h3 class="event-title">Gala Evening</h3>

                            <p class="event-description">An elegant evening of fine dining and entertainment in an exclusive setting.</p>
                            <?php $link = isset($_SESSION['user_id']) ? 'user/pages/reservation.php?eventId=1' : 'user/pages/login.php?redirect=' . urlencode('user/pages/reservation.php?eventId=1'); ?>
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-primary-luxury mt-3">Reserve Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="luxury-card event-card">
                        <div class="event-image position-relative">
                            <img src="assets/images/event_images/wineTasting.jpg" class="card-img-top featured-event-image" alt="Wine Tasting">
                        </div>
                        <div class="event-content p-4">
                            <h3 class="event-title">Wine Tasting</h3>

                            <p class="event-description">Discover rare vintages in an intimate tasting experience.</p>
                            <?php $link = isset($_SESSION['user_id']) ? 'user/pages/reservation.php?eventId=2' : 'user/pages/login.php?redirect=' . urlencode('user/pages/reservation.php?eventId=2'); ?>
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-primary-luxury mt-3">Reserve Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="luxury-card event-card">
                        <div class="event-image position-relative">
                            <img src="assets/images/event_images/artExhibition.jpg" class="card-img-top featured-event-image" alt="Art Exhibition">
                        </div>
                        <div class="event-content p-4">
                            <h3 class="event-title">Art Exhibition</h3>

                            <p class="event-description">Private viewing of contemporary masterpieces.</p>
                            <?php $link = isset($_SESSION['user_id']) ? 'user/pages/reservation.php?eventId=3' : 'user/pages/login.php?redirect=' . urlencode('user/pages/reservation.php?eventId=3'); ?>
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-primary-luxury mt-3">Reserve Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="reviews-section">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">What Our Guests Say</h2>
                <p class="section-subtitle">Real experiences from our valued customers</p>
            </div>
            <div class="row g-4">
                <?php
                // Fetch recent reviews from database
                $reviewsQuery = "SELECT r.reviewId, r.rating, r.comment, r.createdAt,
                                       u.fullName AS userName,
                                       e.title AS eventName
                                FROM reviews r
                                INNER JOIN users u ON r.userId = u.userId
                                LEFT JOIN events e ON r.eventId = e.eventId
                                ORDER BY r.createdAt DESC
                                LIMIT 3";
                
                $reviewsResult = mysqli_query($conn, $reviewsQuery);
                $reviews = [];
                
                if ($reviewsResult && mysqli_num_rows($reviewsResult) > 0) {
                    while ($row = mysqli_fetch_assoc($reviewsResult)) {
                        $reviews[] = $row;
                    }
                }
                
                if (empty($reviews)):
                ?>
                    <div class="col-12">
                        <div class="reviews-empty">
                            <i class="fas fa-comments fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>No reviews yet. Be the first to share your experience!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="col-md-4">
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-user-info">
                                        <div class="review-user-name"><?php echo htmlspecialchars($review['userName']); ?></div>
                                        <?php if (!empty($review['eventName'])): ?>
                                            <div class="review-event-name"><?php echo htmlspecialchars($review['eventName']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php
                                    $rating = intval($review['rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                <div class="review-comment">
                                    "<?php echo htmlspecialchars($review['comment']); ?>"
                                </div>
                                <?php endif; ?>
                                <div class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['createdAt'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="cta-section py-5">
        <div class="container">
            <div class="luxury-card cta-card text-center p-5">
                <h2 class="cta-title mb-3">Ready to Reserve Your Experience?</h2>
                <p class="cta-subtitle mb-4">Join our exclusive community and gain access to premium events worldwide.</p>
                <a href="user/pages/register.php" class="btn btn-primary-luxury btn-lg">Create Account</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="hotel-partners-section py-5">
        <div class="container">
            <div class="partners-header text-center mb-5">
                <h2 class="partners-title">Our Featured Hotel Partners</h2>
                <p class="partners-subtitle">Experience elegance at our premium partner locations</p>
            </div>
            
            <div class="row align-items-stretch hotel-partners-row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="hotel-image-container">
                        <div class="hotel-image-placeholder">
                            <img src="assets/images/travelmatesPhoto.jpg" alt="TravelMates Hotel" class="hotel-image">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="luxury-card hotel-card p-5">
                        <h3 class="hotel-name mb-3">TravelMates Hotel</h3>
                        <p class="hotel-description mb-4">
                            TravelMates is a web-based booking system designed to automate and simplify hotel operations, particularly room reservations. The system allows customers to view available rooms, make bookings online, and receive booking confirmations, while enabling hotel staff and administrators to manage reservations efficiently.
                        </p>
                        <div class="hotel-highlights mb-4">
                            <span class="highlight-badge">5-Star Luxury</span>
                            <span class="highlight-badge">Premium Venues</span>
                            <span class="highlight-badge">Expert Service</span>
                        </div>
                        <a href="#" class="btn btn-primary-luxury btn-lg">View Partnership</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="luxury-footer py-5">
        <div class="container">
            <div>
                <div>
                    <h5 class="footer-logo">EVENZA</h5>
                    <p class="footer-text">EVENZA is a premier event reservation platform dedicated to seamless experiences. Elevate your occasions with our curated venues and sophisticated planning tools.</p>
                </div>
                <div>
                    <h6 class="footer-heading">Contact Info</h6>
                    <p class="footer-text">
                        Email: <a href="mailto:evenzacompany@gmail.com">evenzacompany@gmail.com</a><br>
                        Phone: 09916752007<br>
                        Address: Ambulong, Tanauan City, Batangas.
                    </p>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="text-center">
                <p class="footer-copyright">&copy; 2026 EVENZA</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>