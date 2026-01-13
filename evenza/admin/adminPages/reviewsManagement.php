<?php
require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

// Filtering and pagination
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT 
            r.reviewId,
            r.reservationId,
            r.userId,
            r.eventId,
            r.rating,
            r.comment,
            r.createdAt,
            u.fullName AS userName,
            u.email AS userEmail,
            res.reservationCode,
            res.reservationDate,
            e.title AS eventTitle,
            e.venue AS eventVenue
          FROM reviews r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN reservations res ON r.reservationId = res.reservationId
          LEFT JOIN events e ON r.eventId = e.eventId
          WHERE 1=1";

$params = [];
$types = '';

if ($ratingFilter > 0 && $ratingFilter <= 5) {
    $query .= " AND r.rating = ?";
    $params[] = $ratingFilter;
    $types .= 'i';
}

if (!empty($searchQuery)) {
    $query .= " AND (u.fullName LIKE ? OR u.email LIKE ? OR res.reservationCode LIKE ? OR r.comment LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ssss';
}

$query .= " ORDER BY r.createdAt DESC";

// Count query for pagination
$countQuery = "SELECT COUNT(*) as total
          FROM reviews r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN reservations res ON r.reservationId = res.reservationId
          LEFT JOIN events e ON r.eventId = e.eventId
          WHERE 1=1";

if ($ratingFilter > 0 && $ratingFilter <= 5) {
    $countQuery .= " AND r.rating = ?";
}

if (!empty($searchQuery)) {
    $countQuery .= " AND (u.fullName LIKE ? OR u.email LIKE ? OR res.reservationCode LIKE ? OR r.comment LIKE ?)";
}

$totalCount = 0;
$countParams = [];
$countTypes = '';

if ($ratingFilter > 0 && $ratingFilter <= 5) {
    $countParams[] = $ratingFilter;
    $countTypes .= 'i';
}

if (!empty($searchQuery)) {
    $searchParam = '%' . $searchQuery . '%';
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countTypes .= 'ssss';
}

if (!empty($countParams)) {
    $countStmt = mysqli_prepare($conn, $countQuery);
    if ($countStmt) {
        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        if ($countRow = mysqli_fetch_assoc($countResult)) {
            $totalCount = intval($countRow['total']);
        }
        mysqli_stmt_close($countStmt);
    }
} else {
    $countResult = mysqli_query($conn, $countQuery);
    if ($countResult) {
        $countRow = mysqli_fetch_assoc($countResult);
        $totalCount = intval($countRow['total']);
        mysqli_free_result($countResult);
    }
}

$totalPages = ceil($totalCount / $perPage);

// Fetch reviews
$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$reviews = [];
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $reviews[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
}

// Get statistics
$statsQuery = "SELECT 
                COUNT(*) as totalReviews,
                AVG(rating) as averageRating
              FROM reviews";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
mysqli_free_result($statsResult);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Reviews & Feedback Management - EVENZA Admin</title>
    <style>
        .admin-wrapper { 
            min-height: 100vh; 
            background: linear-gradient(135deg, #F9F7F2 0%, #F5F3ED 100%);
        }
        .admin-sidebar { 
            width: 240px; 
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            background: linear-gradient(180deg, #FFFFFF 0%, #F9F7F2 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        .admin-content {
            margin-left: 240px;
            width: calc(100% - 240px);
        }
        .admin-top-nav {
            background-color: #FFFFFF;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid rgba(74, 93, 74, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        .admin-card {
            background-color: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(74, 93, 74, 0.05);
        }
        .btn-admin-primary {
            background-color: #5A6B4F;
            border-color: #5A6B4F;
            color: #FFFFFF;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-admin-primary:hover {
            background-color: #8B7A6B;
            border-color: #8B7A6B;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .btn-admin-primary.btn-sm {
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
        }
        .review-card {
            background-color: #FFFFFF;
            border: 1px solid rgba(74, 93, 74, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .review-card:last-of-type {
            margin-bottom: 0;
        }
        .star-rating-display {
            color: #FFD700;
        }
        .stat-label {
            font-size: 0.95rem;
            color: rgba(26, 26, 26, 0.7);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1A1A1A;
            font-family: 'Playfair Display', serif;
        }
        
        /* Rating Filter Pill Styling */
        .rating-filter-pill {
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            border: 2px solid #E8E4DC;
            background-color: #FFFFFF;
            color: #1A1A1A;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        
        .rating-filter-pill:hover {
            background-color: #F5F5F5;
            border-color: #D4D4D4;
        }
        
        .rating-filter-pill.active {
            background-color: #4A5D4E;
            border-color: #4A5D4E;
            color: #FFFFFF;
        }
        
        .rating-filter-pill.active:hover {
            background-color: #5A6B5A;
            border-color: #5A6B5A;
        }
        
        .rating-filter-pill.active i.fa-star {
            color: #FFFFFF !important;
        }
        
        /* Pagination Styling - Matching User Management */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .pagination-btn {
            min-width: 35px;
            width: 35px;
            height: 35px;
            padding: 0;
            border: 1px solid #E0E0E0;
            background-color: #FFFFFF;
            color: #4A5D4A;
            border-radius: 50%;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .pagination-btn:hover:not(:disabled):not([style*="opacity"]) {
            background-color: #5A6B4F;
            color: #FFFFFF;
            border-color: #5A6B4F;
        }
        .pagination-btn.active {
            background-color: #4A5D4E;
            color: #FFFFFF;
            border-color: #4A5D4E;
        }
        .pagination-btn:disabled,
        .pagination-btn[style*="opacity: 0.5"] {
            opacity: 0.5;
            cursor: not-allowed;
        }
        /* Prev/Next buttons - wider for text, use Sans-Serif with bolder weight */
        .pagination-wrapper > a:first-child,
        .pagination-wrapper > span:first-child,
        .pagination-wrapper > a:last-child,
        .pagination-wrapper > span:last-child {
            min-width: auto;
            width: auto;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 700;
        }
        
        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        @media (max-width: 1023px) { 
            .admin-sidebar { 
                width: 280px; 
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }
            .admin-sidebar.show {
                left: 0;
            }
            .admin-content {
                margin-left: 0;
                width: 100%;
            }
            .admin-wrapper {
                flex-direction: column;
            }
            .stat-number {
                font-size: clamp(1.3rem, 4vw, 1.8rem);
            }
        }
        
        @media (max-width: 768px) {
            .admin-top-nav {
                padding: 0.75rem 1rem;
                flex-wrap: wrap;
            }
            .admin-top-nav h4 {
                font-size: clamp(1.1rem, 4vw, 1.5rem);
            }
            .stat-label {
                font-size: clamp(0.8rem, 2vw, 0.95rem);
            }
            .review-card {
                padding: 1rem;
            }
            .rating-filter-pill {
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
            }
            .pagination-wrapper {
                gap: 0.35rem;
            }
            .pagination-btn {
                min-width: 32px;
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }
            .pagination-wrapper > a:first-child,
            .pagination-wrapper > span:first-child,
            .pagination-wrapper > a:last-child,
            .pagination-wrapper > span:last-child {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .row.g-3 > [class*="col-"] {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <div class="d-flex flex-column admin-sidebar p-4">
            <div class="d-flex align-items-center mb-5" style="padding: 1rem 0;">
                <div class="luxury-logo">
                    <img src="../../assets/images/evenzaLogo.png" alt="EVENZA" class="evenza-logo-img" style="max-width: 180px;">
                </div>
            </div>
            <div class="mb-4">
                <div style="background: transparent; box-shadow: none; border: none;">
                    <div class="d-flex flex-column gap-2">
                        <a href="admin.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-home"></i></span> 
                            <span style="font-weight: 500;">Dashboard</span>
                        </a>
                        <a href="eventManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-calendar-alt"></i></span> 
                            <span style="font-weight: 500;">Event Management</span>
                        </a>
                        <a href="reservationsManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-clipboard-list"></i></span>
                            <span style="font-weight: 500;">Reservations</span>
                        </a>
                        <a href="userManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-users"></i></span> 
                            <span style="font-weight: 500;">Users</span>
                        </a>
                        <a href="reviewsManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-star"></i></span>
                            <span>Reviews & Feedback</span>
                        </a>
                        <a href="smsInbox.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-sms"></i></span> 
                            <span style="font-weight: 500;">SMS Inbox</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-fill admin-content">
            <div class="admin-top-nav d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3 d-xl-none">
                        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; padding: 0.5rem 0.75rem;">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    <div>
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">Reviews & Feedback</h4>
                        <div class="text-muted small">Manage user reviews and feedback</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-user text-muted"></i>
                        </div>
                    </div>
                    <a href="../../user/process/logout.php?type=admin" class="btn btn-admin-primary btn-sm">Logout</a>
                </div>
            </div>

            <div class="p-4" style="padding: 2rem !important;">
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-6">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Total Reviews</div>
                                <div class="stat-number"><?php echo number_format($stats['totalReviews'] ?? 0); ?></div>
                                <div class="text-muted small mt-2">All-time reviews</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Average Rating</div>
                                <div class="stat-number">
                                    <?php echo number_format($stats['averageRating'] ?? 0, 1); ?>/5
                                    <span style="font-size: 1.5rem; color: #FFD700; margin-left: 0.25rem; vertical-align: middle;">
                                        <i class="fas fa-star"></i>
                                    </span>
                                </div>
                                <div class="text-muted small mt-2">Overall rating</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="admin-card mb-4">
                    <div class="p-4">
                        <form method="GET" action="" id="reviewFilterForm" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1A1A1A;">Search</label>
                                <input type="text" class="form-control" name="search" id="reviewSearchInput" placeholder="Name, email, or comment..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="border-radius: 50px; padding: 0.6rem 1.25rem; border: 1px solid rgba(74, 93, 74, 0.2);">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-2" style="color: #1A1A1A;">Filter by Rating</label>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 0 ? 'active' : ''; ?>" data-rating="0">
                                        All Ratings
                                    </button>
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 5 ? 'active' : ''; ?>" data-rating="5">
                                        5 <i class="fas fa-star" style="color: #FFD700; font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 4 ? 'active' : ''; ?>" data-rating="4">
                                        4 <i class="fas fa-star" style="color: #FFD700; font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 3 ? 'active' : ''; ?>" data-rating="3">
                                        3 <i class="fas fa-star" style="color: #FFD700; font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 2 ? 'active' : ''; ?>" data-rating="2">
                                        2 <i class="fas fa-star" style="color: #FFD700; font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="rating-filter-pill <?php echo $ratingFilter == 1 ? 'active' : ''; ?>" data-rating="1">
                                        1 <i class="fas fa-star" style="color: #FFD700; font-size: 0.85rem;"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="rating" id="ratingFilter" value="<?php echo $ratingFilter; ?>">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="admin-card">
                    <div class="p-4">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-star text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">No reviews found</p>
                            </div>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h5 class="mb-0" style="font-family: 'Inter', sans-serif; font-weight: 600; color: #1A1A1A;"><?php echo htmlspecialchars($review['userName']); ?></h5>
                                        </div>
                                        <div class="mb-2">
                                            <?php
                                            $rating = intval($review['rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star star-rating-display"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-muted"></i>';
                                                }
                                            }
                                            ?>
                                            <span class="ms-2" style="color: #6c757d; font-weight: 600;"><?php echo $rating; ?>/5</span>
                                        </div>
                                        <p class="text-muted mb-2" style="font-size: 0.875rem;">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($review['userEmail']); ?>
                                            <span class="ms-3"><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($review['createdAt'])); ?></span>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($review['comment'])): ?>
                                    <div class="mb-3 p-3" style="background: #F9F7F2; border-radius: 15px; border-left: 3px solid #5A6B4F;">
                                        <p class="mb-0" style="color: #1A1A1A; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="pt-3" style="border-top: 1px solid rgba(74, 93, 74, 0.1);">
                                    <div class="text-muted small">
                                        <span><i class="fas fa-ticket-alt me-1"></i>Reservation: <strong style="color: #5A6B4F;"><?php echo htmlspecialchars($review['reservationCode'] ?? 'N/A'); ?></strong></span>
                                        <?php if (!empty($review['eventTitle'])): ?>
                                            <span class="ms-3"><i class="fas fa-calendar-alt me-1"></i><?php echo htmlspecialchars($review['eventTitle']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrapper">
                                <?php
                                $queryParams = [];
                                if (!empty($searchQuery)) {
                                    $queryParams[] = 'search=' . urlencode($searchQuery);
                                }
                                if ($ratingFilter > 0 && $ratingFilter <= 5) {
                                    $queryParams[] = 'rating=' . $ratingFilter;
                                }
                                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                                ?>
                                
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="pagination-btn">Prev</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                                <?php endif; ?>
                                
                                <?php
                                // Calculate page range to show (max 5 page numbers)
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                // Adjust if we're near the start
                                if ($page <= 3) {
                                    $startPage = 1;
                                    $endPage = min(5, $totalPages);
                                }
                                
                                // Adjust if we're near the end
                                if ($page >= $totalPages - 2) {
                                    $startPage = max(1, $totalPages - 4);
                                    $endPage = $totalPages;
                                }
                                
                                // Show first page if not in range
                                if ($startPage > 1): ?>
                                    <a href="?page=1<?php echo $queryString; ?>" class="pagination-btn">1</a>
                                    <?php if ($startPage > 2): ?>
                                        <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="pagination-btn">Next</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle rating filter pill clicks
        document.querySelectorAll('.rating-filter-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                // Remove active class from all pills
                document.querySelectorAll('.rating-filter-pill').forEach(p => p.classList.remove('active'));
                // Add active class to clicked pill
                this.classList.add('active');
                // Update hidden input value
                const ratingValue = this.getAttribute('data-rating');
                document.getElementById('ratingFilter').value = ratingValue === '0' ? '' : ratingValue;
                // Submit form immediately
                document.getElementById('reviewFilterForm').submit();
            });
        });
        
        // Sidebar toggle for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('adminSidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                sidebar.classList.toggle('show');
                if (overlay) {
                    overlay.classList.toggle('show');
                }
                // Prevent body scroll when sidebar is open
                if (sidebar.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 1024 && sidebar && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        toggleSidebar();
                    }
                }
            });
        });
    </script>
</body>
</html>


