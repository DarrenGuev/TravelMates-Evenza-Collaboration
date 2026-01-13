<?php
require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$stats = [
    'totalRevenue' => 0,
    'totalTicketsSold' => 0,
    'activeEvents' => 0,
    'newUsers' => 0,
    'averageRating' => 0,
    'topEvents' => [],
    'recentActivity' => [],
    'revenueTrend' => 0
];

try {
    $revenueQuery = "SELECT COALESCE(SUM(totalAmount), 0) as totalRevenue 
                     FROM reservations 
                     WHERE LOWER(status) = 'completed'";
    $revenueResult = mysqli_query($conn, $revenueQuery);
    if ($revenueResult) {
        $revenueRow = mysqli_fetch_assoc($revenueResult);
        $stats['totalRevenue'] = floatval($revenueRow['totalRevenue'] ?? 0);
        mysqli_free_result($revenueResult);
    } else {
        error_log("Revenue query error: " . mysqli_error($conn));
    }

    $ticketsQuery = "SELECT COUNT(*) as totalTickets FROM reservations";
    $ticketsResult = mysqli_query($conn, $ticketsQuery);
    if ($ticketsResult) {
        $ticketsRow = mysqli_fetch_assoc($ticketsResult);
        $stats['totalTicketsSold'] = intval($ticketsRow['totalTickets'] ?? 0);
        mysqli_free_result($ticketsResult);
    } else {
        error_log("Tickets query error: " . mysqli_error($conn));
    }

    $activeEventsQuery = "SELECT COUNT(DISTINCT eventId) as activeEvents 
                          FROM reservations 
                          WHERE LOWER(status) IN ('pending', 'confirmed', 'completed')";
    $activeEventsResult = mysqli_query($conn, $activeEventsQuery);
    if ($activeEventsResult) {
        $activeEventsRow = mysqli_fetch_assoc($activeEventsResult);
        $stats['activeEvents'] = intval($activeEventsRow['activeEvents'] ?? 0);
        mysqli_free_result($activeEventsResult);
    } else {
        error_log("Active events query error: " . mysqli_error($conn));
    }

    $newUsersQuery = "SELECT COUNT(*) as newUsers 
                      FROM users 
                      WHERE (LOWER(role) != 'admin' OR role IS NULL)";
    $newUsersResult = mysqli_query($conn, $newUsersQuery);
    if ($newUsersResult) {
        $newUsersRow = mysqli_fetch_assoc($newUsersResult);
        $stats['newUsers'] = intval($newUsersRow['newUsers'] ?? 0);
        mysqli_free_result($newUsersResult);
    } else {
        error_log("New users query error: " . mysqli_error($conn));
    }

    $averageRatingQuery = "SELECT COALESCE(AVG(rating), 0) as averageRating 
                           FROM reviews";
    $averageRatingResult = mysqli_query($conn, $averageRatingQuery);
    if ($averageRatingResult) {
        $averageRatingRow = mysqli_fetch_assoc($averageRatingResult);
        $stats['averageRating'] = floatval($averageRatingRow['averageRating'] ?? 0);
        mysqli_free_result($averageRatingResult);
    } else {
        error_log("Average rating query error: " . mysqli_error($conn));
    }

    $topEventsQuery = "
        SELECT 
            e.eventId,
            e.title,
            COUNT(CASE WHEN LOWER(r.status) IN ('completed', 'confirmed', 'pending') THEN r.reservationId END) as packagesReserved,
            COALESCE(SUM(CASE WHEN LOWER(r.status) = 'completed' THEN r.totalAmount ELSE 0 END), 0) as revenue
        FROM events e
        LEFT JOIN reservations r ON e.eventId = r.eventId AND LOWER(r.status) != 'cancelled'
        GROUP BY e.eventId, e.title
        ORDER BY packagesReserved DESC, revenue DESC
        LIMIT 5
    ";
    $topEventsResult = mysqli_query($conn, $topEventsQuery);
    if ($topEventsResult) {
        while ($row = mysqli_fetch_assoc($topEventsResult)) {
            $stats['topEvents'][] = [
                'eventId' => intval($row['eventId']),
                'title' => $row['title'],
                'packagesReserved' => intval($row['packagesReserved']),
                'revenue' => floatval($row['revenue'])
            ];
        }
        mysqli_free_result($topEventsResult);
    } else {
        error_log("Top events query error: " . mysqli_error($conn));
    }

    $recentActivityQuery = "
        SELECT 
            r.reservationId,
            r.createdAt,
            r.reservationDate,
            r.startTime,
            r.endTime,
            r.totalAmount,
            u.fullName as userName,
            e.title as eventName,
            p.packageName
        FROM reservations r
        LEFT JOIN users u ON r.userId = u.userId
        LEFT JOIN events e ON r.eventId = e.eventId
        LEFT JOIN packages p ON r.packageId = p.packageId
        ORDER BY r.reservationDate DESC, r.createdAt DESC
        LIMIT 3
    ";
    $recentActivityResult = mysqli_query($conn, $recentActivityQuery);
    if ($recentActivityResult) {
        while ($row = mysqli_fetch_assoc($recentActivityResult)) {
            $stats['recentActivity'][] = [
                'userName' => $row['userName'] ?? 'Unknown User',
                'eventName' => $row['eventName'] ?? 'Unknown Event',
                'packageName' => $row['packageName'] ?? 'N/A',
                'createdAt' => $row['createdAt'],
                'reservationDate' => $row['reservationDate'] ?? null,
                'startTime' => $row['startTime'] ?? null,
                'endTime' => $row['endTime'] ?? null,
                'totalAmount' => floatval($row['totalAmount'] ?? 0)
            ];
        }
        mysqli_free_result($recentActivityResult);
    } else {
        error_log("Recent activity query error: " . mysqli_error($conn));
    }

    $lastMonthQuery = "
        SELECT COALESCE(SUM(totalAmount), 0) as lastMonthRevenue 
        FROM reservations 
        WHERE LOWER(status) = 'completed'
        AND MONTH(createdAt) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        AND YEAR(createdAt) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    ";
    $lastMonthResult = mysqli_query($conn, $lastMonthQuery);
    $lastMonthRevenue = 0;
    if ($lastMonthResult) {
        $lastMonthRow = mysqli_fetch_assoc($lastMonthResult);
        $lastMonthRevenue = floatval($lastMonthRow['lastMonthRevenue'] ?? 0);
        mysqli_free_result($lastMonthResult);
    }

    $currentMonthQuery = "
        SELECT COALESCE(SUM(totalAmount), 0) as currentMonthRevenue 
        FROM reservations 
        WHERE LOWER(status) = 'completed'
        AND MONTH(createdAt) = MONTH(NOW())
        AND YEAR(createdAt) = YEAR(NOW())
    ";
    $currentMonthResult = mysqli_query($conn, $currentMonthQuery);
    $currentMonthRevenue = 0;
    if ($currentMonthResult) {
        $currentMonthRow = mysqli_fetch_assoc($currentMonthResult);
        $currentMonthRevenue = floatval($currentMonthRow['currentMonthRevenue'] ?? 0);
        mysqli_free_result($currentMonthResult);
    }

    if ($lastMonthRevenue > 0) {
        $stats['revenueTrend'] = round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
    } else {
        $stats['revenueTrend'] = $currentMonthRevenue > 0 ? 100 : 0;
    }

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats['error'] = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>EVENZA Admin Dashboard</title>
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
        .stat-number { 
            font-size: 1.8rem; 
            font-weight: 700; 
            font-family: 'Playfair Display', serif;
            color: #1A1A1A;
        }
        .stat-label { 
            color: rgba(26, 26, 26, 0.7); 
            font-size: 0.9rem; 
            font-weight: 500;
        }
        .table-sm td, .table-sm th { 
            padding: 0.75rem; 
        }
        .activity-item { 
            border-left: 3px solid rgba(74, 93, 74, 0.2); 
            padding-left: 0.75rem; 
            margin-bottom: 0.75rem;
            border-radius: 8px;
            padding: 0.75rem;
            background-color: rgba(249, 247, 242, 0.5);
        }
        #recentActivity {
            overflow: visible;
            max-height: none;
        }
        .view-all-link {
            color: #5A6B4F;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .view-all-link:hover {
            color: #8B7A6B;
            text-decoration: underline;
        }
        .admin-card {
            background-color: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(74, 93, 74, 0.05);
            padding: 24px;
            padding-bottom: 24px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .top-events-card .admin-card {
            height: auto;
        }
        .dashboard-grid {
            display: flex;
            align-items: stretch;
            gap: 24px;
            margin-bottom: 2rem;
        }
        .top-events-card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .recent-activity-card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .top-events-card table {
            margin-bottom: 0 !important;
        }
        .top-events-card table td {
            padding: 18px 10px;
        }
        .top-events-card table th {
            padding: 18px 10px;
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
        .trend-indicator {
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .trend-up {
            color: rgba(26, 26, 26, 0.7);
        }
        .trend-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background-color: #d1e7dd;
            color: #0f5132;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        @media (max-width: 1024px) {
            .dashboard-grid {
                flex-direction: column;
                gap: 20px;
            }
            .top-events-card,
            .recent-activity-card {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        .admin-sidebar a:not(.active):hover {
            background: rgba(74, 93, 74, 0.05) !important;
            color: #4A5D4A !important;
            border-left-color: rgba(74, 93, 74, 0.3) !important;
            transform: translateX(5px);
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
            .row.g-3 > [class*="col-"] {
                margin-bottom: 1rem;
            }
        }
        /* Dynamic Analytics Grid */
        @media (max-width: 599px) {
            /* Single column on mobile */
            .row.g-3 > [class*="col-"] {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 1rem;
            }
        }
        
        @media (min-width: 600px) and (max-width: 1024px) {
            /* 2-column grid on tablet */
            .row.g-3 > [class*="col-"] {
                flex: 0 0 calc(50% - 0.75rem);
                max-width: calc(50% - 0.75rem);
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
            .stat-number {
                font-size: clamp(1.3rem, 4vw, 1.8rem);
            }
            .stat-label {
                font-size: clamp(0.8rem, 2vw, 0.9rem);
            }
            .admin-card {
                padding: 20px;
                border-radius: 20px;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .card-header h5 {
                font-size: clamp(1rem, 3vw, 1.25rem);
            }
            .table-responsive {
                font-size: 0.875rem;
            }
            .table th,
            .table td {
                padding: 0.5rem;
            }
            /* Ensure dashboard grid stacks vertically */
            .dashboard-grid {
                flex-direction: column;
                gap: 20px;
            }
            .top-events-card,
            .recent-activity-card {
                flex: 0 0 100%;
                max-width: 100%;
            }
            /* Fix padding - remove excess white space */
            .top-events-card .admin-card {
                height: auto;
                padding-bottom: 24px;
            }
        }
        @media (max-width: 576px) {
            .admin-top-nav {
                padding: 0.5rem;
            }
            .admin-top-nav > div {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .stat-number {
                font-size: 1.1rem;
            }
            .row.g-3 {
                margin: 0;
            }
            .row.g-3 > [class*="col-"] {
                padding: 0.5rem;
            }
            .btn-admin-primary {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            .table th,
            .table td {
                font-size: 0.75rem;
                padding: 0.4rem;
            }
            .activity-item {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <div class="d-flex flex-column admin-sidebar p-4" style="background: linear-gradient(180deg, #FFFFFF 0%, #F9F7F2 100%);">
            <div class="d-flex align-items-center mb-5" style="padding: 1rem 0;">
                <div class="luxury-logo">
                    <img src="../../assets/images/evenzaLogo.png" alt="EVENZA" class="evenza-logo-img" style="max-width: 180px;">
                </div>
            </div>
            <div class="mb-4">
                <div style="background: transparent; box-shadow: none; border: none;">
                    <div class="d-flex flex-column gap-2">
                        <a href="admin.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-home"></i></span> 
                            <span>Dashboard</span>
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
                        <a href="reviewsManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-star"></i></span>
                            <span style="font-weight: 500;">Reviews & Feedback</span>
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
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">Dashboard</h4>
                        <div class="text-muted small">Overview of activity and performance</div>
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
                <?php if (isset($stats['error'])): ?>
                    <div class="alert alert-danger">
                        <strong>Error loading dashboard data:</strong> <?php echo htmlspecialchars($stats['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                if (isset($_GET['debug'])) {
                    echo '<div class="alert alert-info"><pre>' . print_r($stats, true) . '</pre></div>';
                }
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg col-md-4">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Total Revenue</div>
                                <div class="stat-number">₱ <span id="totalRevenue"><?php echo isset($stats['totalRevenue']) ? number_format($stats['totalRevenue'], 2) : '0.00'; ?></span></div>
                                <div class="trend-indicator trend-up mt-2">
                                    <span class="trend-icon"><i class="fas fa-arrow-up"></i></span> <span id="revenueTrend"><?php echo isset($stats['revenueTrend']) ? number_format($stats['revenueTrend'], 1) : '0.0'; ?>%</span> since last month
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg col-md-4">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Total Packages Reserved</div>
                                <div class="stat-number" id="ticketsSold"><?php echo isset($stats['totalTicketsSold']) ? number_format($stats['totalTicketsSold']) : '0'; ?></div>
                                <div class="text-muted small mt-2">All-time</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg col-md-4">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Active Events</div>
                                <div class="stat-number" id="activeEvents"><?php echo isset($stats['activeEvents']) ? $stats['activeEvents'] : '0'; ?></div>
                                <div class="text-muted small mt-2">Events accepting reservations</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg col-md-4">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">New User Sign-ups</div>
                                <div class="stat-number" id="newUsers"><?php echo isset($stats['newUsers']) ? $stats['newUsers'] : '0'; ?></div>
                                <div class="text-muted small mt-2">Last 30 days</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg col-md-4">
                        <div class="admin-card p-4 h-100">
                            <div class="d-flex flex-column">
                                <div class="stat-label mb-2">Average Rating</div>
                                <div class="stat-number" id="averageRating">
                                    <?php 
                                    $avgRating = isset($stats['averageRating']) ? floatval($stats['averageRating']) : 0;
                                    echo number_format($avgRating, 1);
                                    ?>
                                    <span style="font-size: 1.5rem; color: #ffc107; margin-left: 0.25rem;">
                                        <i class="fas fa-star"></i>
                                    </span>
                                </div>
                                <div class="text-muted small mt-2">From all reviews</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="top-events-card">
                        <div class="admin-card">
                            <div class="card-header">
                                <div>
                                    <h5 class="mb-1" style="font-family: 'Playfair Display', serif;">Top Performing Events</h5>
                                    <div class="text-muted small">Top 5 events by total packages reserved</div>
                                </div>
                                <div class="text-muted small">Updated just now</div>
                            </div>
                            <div class="card-content">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr style="border-bottom: 2px solid rgba(74, 93, 74, 0.1);">
                                            <th style="font-weight: 600; color: #1A1A1A; font-family: 'Playfair Display', serif;">Event Name</th>
                                            <th style="font-weight: 600; color: #1A1A1A;">Reservations</th>
                                            <th style="font-weight: 600; color: #1A1A1A;">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody id="topEventsBody">
                                        <?php if (empty($stats['topEvents'])): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No events with reservations yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($stats['topEvents'] as $event): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <div class="fw-semibold" style="font-family: 'Playfair Display', serif;"><?php echo htmlspecialchars($event['title']); ?></div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo number_format($event['packagesReserved']); ?></td>
                                                    <td>₱ <?php echo number_format($event['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="recent-activity-card">
                        <div class="admin-card">
                            <div class="card-header">
                                <div>
                                    <h5 class="mb-1" style="font-family: 'Playfair Display', serif;">Recent Activity</h5>
                                    <div class="text-muted small">Latest reservations</div>
                                </div>
                                <a href="reservationsManagement.php" class="view-all-link">View all</a>
                            </div>
                            <div class="card-content">
                                <div id="recentActivity">
                                <?php if (empty($stats['recentActivity'])): ?>
                                    <div class="text-center text-muted">No recent activity</div>
                                <?php else: ?>
                                    <?php foreach ($stats['recentActivity'] as $activity): 
                                        // Format date
                                        $reservationDate = !empty($activity['reservationDate']) ? $activity['reservationDate'] : $activity['createdAt'];
                                        $dateTime = new DateTime($reservationDate);
                                        $formattedDate = $dateTime->format('M j, Y');
                                        
                                        // Format time in 12-hour format
                                        $timeDisplay = 'N/A';
                                        if (!empty($activity['startTime']) && !empty($activity['endTime'])) {
                                            $timeDisplay = formatTime12Hour($activity['startTime'] . ' - ' . $activity['endTime']);
                                        } elseif (!empty($activity['startTime'])) {
                                            $timeDisplay = formatTime12Hour($activity['startTime']);
                                        }
                                    ?>
                                        <div class="mb-3 activity-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="fw-semibold"><?php echo htmlspecialchars($activity['userName']); ?></div>
                                                <div class="text-muted small text-end">
                                                    <div><?php echo htmlspecialchars($formattedDate); ?></div>
                                                    <?php if ($timeDisplay !== 'N/A'): ?>
                                                        <div class="mt-1"><?php echo htmlspecialchars($timeDisplay); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-muted small">
                                                Reserved <?php echo htmlspecialchars($activity['packageName']); ?> — <?php echo htmlspecialchars($activity['eventName']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
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
