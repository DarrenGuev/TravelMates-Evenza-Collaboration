<?php
require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$packageFilter = isset($_GET['package']) ? $_GET['package'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$filterDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

$query = "SELECT 
            r.reservationId,
            r.userId,
            r.eventId,
            r.packageId,
            r.reservationDate,
            r.startTime,
            r.endTime,
            r.totalAmount,
            r.status,
            r.createdAt,
            r.userCancelled,
            u.fullName AS customerName,
            u.email AS customerEmail,
            e.title AS eventTitle,
            e.venue AS eventVenue,
            p.packageName,
            p.price AS packagePrice
          FROM reservations r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN events e ON r.eventId = e.eventId
          INNER JOIN packages p ON r.packageId = p.packageId
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($packageFilter)) {
    $query .= " AND p.packageName LIKE ?";
    $params[] = $packageFilter . '%';
    $types .= 's';
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $query .= " AND LOWER(r.status) = ?";
    $params[] = strtolower($statusFilter);
    $types .= 's';
}

if (!empty($dateFilter)) {
    $query .= " AND DATE(r.reservationDate) = ?";
    $params[] = $dateFilter;
    $types .= 's';
} elseif (!empty($filterDate)) {
    $query .= " AND DATE(r.reservationDate) = ?";
    $params[] = $filterDate;
    $types .= 's';
} else {
    $query .= " AND DATE(r.reservationDate) >= CURDATE()";
}

$countQuery = "SELECT COUNT(*) as total FROM reservations r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN events e ON r.eventId = e.eventId
          INNER JOIN packages p ON r.packageId = p.packageId
          WHERE 1=1";

$countParams = [];
$countTypes = '';

if (!empty($packageFilter)) {
    $countQuery .= " AND p.packageName LIKE ?";
    $countParams[] = $packageFilter . '%';
    $countTypes .= 's';
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $countQuery .= " AND LOWER(r.status) = ?";
    $countParams[] = strtolower($statusFilter);
    $countTypes .= 's';
}

if (!empty($dateFilter)) {
    $countQuery .= " AND DATE(r.reservationDate) = ?";
    $countParams[] = $dateFilter;
    $countTypes .= 's';
} elseif (!empty($filterDate)) {
    $countQuery .= " AND DATE(r.reservationDate) = ?";
    $countParams[] = $filterDate;
    $countTypes .= 's';
} else {
    $countQuery .= " AND DATE(r.reservationDate) >= CURDATE()";
}

$totalCount = 0;
$countStmt = mysqli_prepare($conn, $countQuery);
if ($countStmt) {
    if (!empty($countParams)) {
        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    if ($countRow = mysqli_fetch_assoc($countResult)) {
        $totalCount = $countRow['total'];
    }
    mysqli_stmt_close($countStmt);
}

$totalPages = ceil($totalCount / $perPage);

// Calculate Total Revenue from all reservations (respecting filters, ignoring pagination)
$revenueQuery = "SELECT 
            COALESCE(SUM(r.totalAmount), 0) as totalRevenue
          FROM reservations r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN events e ON r.eventId = e.eventId
          INNER JOIN packages p ON r.packageId = p.packageId
          WHERE 1=1";

$revenueParams = [];
$revenueTypes = '';

if (!empty($packageFilter)) {
    $revenueQuery .= " AND p.packageName LIKE ?";
    $revenueParams[] = $packageFilter . '%';
    $revenueTypes .= 's';
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $revenueQuery .= " AND LOWER(r.status) = ?";
    $revenueParams[] = strtolower($statusFilter);
    $revenueTypes .= 's';
}

if (!empty($dateFilter)) {
    $revenueQuery .= " AND DATE(r.reservationDate) = ?";
    $revenueParams[] = $dateFilter;
    $revenueTypes .= 's';
} elseif (!empty($filterDate)) {
    $revenueQuery .= " AND DATE(r.reservationDate) = ?";
    $revenueParams[] = $filterDate;
    $revenueTypes .= 's';
}
// Note: No default date filter for revenue - show all revenue to match Admin Overview

// Only include completed reservations for revenue calculation (exclude pending and confirmed - only count fully completed)
$revenueQuery .= " AND LOWER(r.status) = 'completed'";

$totalRevenue = 0;
if (!empty($revenueParams)) {
    $revenueStmt = mysqli_prepare($conn, $revenueQuery);
    if ($revenueStmt) {
        mysqli_stmt_bind_param($revenueStmt, $revenueTypes, ...$revenueParams);
        if (mysqli_stmt_execute($revenueStmt)) {
            $revenueResult = mysqli_stmt_get_result($revenueStmt);
            if ($revenueRow = mysqli_fetch_assoc($revenueResult)) {
                $totalRevenue = floatval($revenueRow['totalRevenue']);
            }
            mysqli_free_result($revenueResult);
        }
        mysqli_stmt_close($revenueStmt);
    }
} else {
    $revenueResult = mysqli_query($conn, $revenueQuery);
    if ($revenueResult) {
        if ($revenueRow = mysqli_fetch_assoc($revenueResult)) {
            $totalRevenue = floatval($revenueRow['totalRevenue']);
        }
        mysqli_free_result($revenueResult);
    }
}

// Calculate Revenue by Package Tier from all reservations (respecting filters, ignoring pagination)
$packageRevenueQuery = "SELECT 
            CASE 
                WHEN p.packageName LIKE 'Bronze%' THEN 'Bronze'
                WHEN p.packageName LIKE 'Silver%' THEN 'Silver'
                WHEN p.packageName LIKE 'Gold%' THEN 'Gold'
                ELSE 'Unknown'
            END as packageTier,
            COALESCE(SUM(r.totalAmount), 0) as revenue
          FROM reservations r
          INNER JOIN users u ON r.userId = u.userid
          INNER JOIN events e ON r.eventId = e.eventId
          INNER JOIN packages p ON r.packageId = p.packageId
          WHERE 1=1";

$packageRevenueParams = [];
$packageRevenueTypes = '';

if (!empty($packageFilter)) {
    $packageRevenueQuery .= " AND p.packageName LIKE ?";
    $packageRevenueParams[] = $packageFilter . '%';
    $packageRevenueTypes .= 's';
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $packageRevenueQuery .= " AND LOWER(r.status) = ?";
    $packageRevenueParams[] = strtolower($statusFilter);
    $packageRevenueTypes .= 's';
}

if (!empty($dateFilter)) {
    $packageRevenueQuery .= " AND DATE(r.reservationDate) = ?";
    $packageRevenueParams[] = $dateFilter;
    $packageRevenueTypes .= 's';
} elseif (!empty($filterDate)) {
    $packageRevenueQuery .= " AND DATE(r.reservationDate) = ?";
    $packageRevenueParams[] = $filterDate;
    $packageRevenueTypes .= 's';
}
// Note: No default date filter for revenue - show all revenue to match Admin Overview

// Only include completed reservations for revenue calculation (exclude pending and confirmed - only count fully completed)
$packageRevenueQuery .= " AND LOWER(r.status) = 'completed'";
$packageRevenueQuery .= " GROUP BY packageTier";

$revenueByPackage = [
    'Bronze' => 0,
    'Silver' => 0,
    'Gold' => 0
];

if (!empty($packageRevenueParams)) {
    $packageRevenueStmt = mysqli_prepare($conn, $packageRevenueQuery);
    if ($packageRevenueStmt) {
        mysqli_stmt_bind_param($packageRevenueStmt, $packageRevenueTypes, ...$packageRevenueParams);
        if (mysqli_stmt_execute($packageRevenueStmt)) {
            $packageRevenueResult = mysqli_stmt_get_result($packageRevenueStmt);
            while ($packageRevenueRow = mysqli_fetch_assoc($packageRevenueResult)) {
                $tier = $packageRevenueRow['packageTier'];
                if (isset($revenueByPackage[$tier])) {
                    $revenueByPackage[$tier] = floatval($packageRevenueRow['revenue']);
                }
            }
            mysqli_free_result($packageRevenueResult);
        }
        mysqli_stmt_close($packageRevenueStmt);
    }
} else {
    $packageRevenueResult = mysqli_query($conn, $packageRevenueQuery);
    if ($packageRevenueResult) {
        while ($packageRevenueRow = mysqli_fetch_assoc($packageRevenueResult)) {
            $tier = $packageRevenueRow['packageTier'];
            if (isset($revenueByPackage[$tier])) {
                $revenueByPackage[$tier] = floatval($packageRevenueRow['revenue']);
            }
        }
        mysqli_free_result($packageRevenueResult);
    }
}

$query .= " ORDER BY r.reservationDate ASC, r.createdAt ASC LIMIT " . intval($perPage) . " OFFSET " . intval($offset);

$reservations = [];

// Execute query with pagination
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $packageTier = 'Unknown';
                if (!empty($row['packageName'])) {
                    $packageTier = str_replace(' Package', '', $row['packageName']);
                }
                $row['packageTier'] = $packageTier;
                
                $reservations[] = $row;
            }
            
            mysqli_free_result($result);
        } else {
            $error_message = 'Error fetching reservations: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = 'Error preparing query: ' . mysqli_error($conn);
    }
} else {
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $packageTier = 'Unknown';
            if (!empty($row['packageName'])) {
                $packageTier = str_replace(' Package', '', $row['packageName']);
            }
            $row['packageTier'] = $packageTier;
            
                $reservations[] = $row;
        }
        mysqli_free_result($result);
    } else {
        $error_message = 'Error fetching reservations: ' . mysqli_error($conn);
    }
}

$groupedReservations = [];
foreach ($reservations as $reservation) {
    $reservationDate = isset($reservation['reservationDate']) ? date('Y-m-d', strtotime($reservation['reservationDate'])) : 'Unknown Date';
    $dateFormatted = date('F j, Y', strtotime($reservation['reservationDate']));
    
    if (!isset($groupedReservations[$dateFormatted])) {
        $groupedReservations[$dateFormatted] = [
            'dateKey' => $reservationDate,
            'reservations' => []
        ];
    }
    $groupedReservations[$dateFormatted]['reservations'][] = $reservation;
}

uksort($groupedReservations, function($a, $b) {
    return strtotime($a) - strtotime($b);
});
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
    <title>Reservations Management - EVENZA Admin</title>
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
        .date-group-header {
            background-color: #F9F7F2;
            padding: 1rem 1.5rem;
            border-left: 4px solid #4A5D4A;
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1A1A1A;
        }
        .reservation-item {
            background-color: #FFFFFF;
            border: 1px solid rgba(74, 93, 74, 0.1);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: none !important;
        }
        .reservation-item:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        .status-toggle-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .status-toggle-btn {
            padding: 0.5rem 1rem;
            border: 1px solid;
            background-color: transparent;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-toggle-btn:hover:not(.active) {
            opacity: 0.8;
        }
        .status-toggle-btn.active {
            color: #FFFFFF !important;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .status-toggle-btn.active i {
            color: #FFFFFF !important;
        }
        /* Pending - Amber/Orange */
        .status-pending {
            color: rgba(217, 119, 6, 0.6);
            border-color: rgba(217, 119, 6, 0.4);
        }
        .status-pending.active {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }
        /* Confirmed - EVENZA Green */
        .status-confirmed {
            color: rgba(74, 93, 78, 0.6);
            border-color: rgba(74, 93, 78, 0.4);
        }
        .status-confirmed.active {
            background-color: #4A5D4E;
            border-color: #4A5D4E;
        }
        /* Cancelled - Soft Red */
        .status-cancelled {
            color: rgba(220, 38, 38, 0.6);
            border-color: rgba(220, 38, 38, 0.4);
        }
        .status-cancelled.active {
            background-color: #ef4444;
            border-color: #ef4444;
        }
        /* Completed - Light Blue */
        .status-completed {
            color: rgba(59, 130, 246, 0.6);
            border-color: rgba(59, 130, 246, 0.4);
        }
        .status-completed.active {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .date-group-header {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .date-group-header:hover {
            background-color: #F0EDE5;
            transform: translateX(5px);
        }
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid rgba(74, 93, 74, 0.2);
            background-color: #FFFFFF;
            color: #4A5D4A;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .pagination-btn:hover {
            background-color: #5A6B4F;
            color: #FFFFFF;
            border-color: #5A6B4F;
        }
        .pagination-btn.active {
            background-color: #5A6B4F;
            color: #FFFFFF;
            border-color: #5A6B4F;
        }
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        .empty-state-logo {
            font-family: 'Dancing Script', cursive;
            font-size: 3rem;
            color: #4A5D4A;
            margin-bottom: 1rem;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: rgba(74, 93, 74, 0.3);
            margin-bottom: 1.5rem;
        }
        .empty-state-message {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #4A5D4A;
            margin-bottom: 0.5rem;
        }
        .empty-state-subtext {
            color: #6c757d;
            font-size: 1rem;
        }
        .btn-back-to-all {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.5rem;
            background-color: #5A6B4F;
            color: #FFFFFF;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-back-to-all:hover {
            background-color: #8B7A6B;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
            .filter-section {
                flex-direction: column;
                gap: 1rem;
            }
            .filter-section > div {
                width: 100% !important;
            }
            .p-4[style*="padding: 2rem"] {
                padding: 1rem !important;
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
            .table-responsive {
                font-size: 0.875rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                display: block;
                width: 100%;
            }
            .table-responsive table {
                min-width: 900px;
                width: 100%;
            }
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                white-space: nowrap;
            }
            /* Ensure touch targets are large enough */
            .btn-admin-primary,
            .btn-sm {
                min-height: 44px;
                min-width: 44px;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            /* Status pills - ensure they're touch-friendly */
            .badge {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                min-height: 32px;
                display: inline-flex;
                align-items: center;
            }
            .reservation-item {
                flex-direction: column;
                align-items: flex-start;
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
            .table th,
            .table td {
                font-size: 0.75rem;
                padding: 0.4rem;
            }
            .reservation-item {
                padding: 0.75rem;
            }
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
            .pagination .page-link {
                padding: 0.375rem 0.5rem;
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
                        <a href="admin.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-home"></i></span> 
                            <span style="font-weight: 500;">Dashboard</span>
                        </a>
                        <a href="eventManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-calendar-alt"></i></span> 
                            <span style="font-weight: 500;">Event Management</span>
                        </a>
                        <a href="reservationsManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-clipboard-list"></i></span> 
                            <span>Reservations</span>
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
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">Reservations Management</h4>
                        <div class="text-muted small">View and manage all event reservations</div>
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

            <div class="p-4">
                <div class="admin-card p-4 mb-4">
                    <h5 class="mb-4" style="font-family: 'Playfair Display', serif;">Filter Reservations</h5>
                    <form method="GET" action="reservationsManagement.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="packageFilter" class="form-label fw-semibold">Package Tier</label>
                            <select class="form-select" id="packageFilter" name="package" style="border-radius: 50px; padding: 0.6rem 1.25rem;">
                                <option value="">All Packages</option>
                                <option value="Bronze" <?php echo $packageFilter === 'Bronze' ? 'selected' : ''; ?>>Bronze</option>
                                <option value="Silver" <?php echo $packageFilter === 'Silver' ? 'selected' : ''; ?>>Silver</option>
                                <option value="Gold" <?php echo $packageFilter === 'Gold' ? 'selected' : ''; ?>>Gold</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label fw-semibold">Filter by Status</label>
                            <select class="form-select" id="statusFilter" name="status" style="border-radius: 50px; padding: 0.6rem 1.25rem;">
                                <option value="all" <?php echo empty($statusFilter) || $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label fw-semibold">Filter by Date</label>
                            <input type="date" class="form-control" id="dateFilter" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" style="border-radius: 50px; padding: 0.6rem 1.25rem;">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-admin-primary me-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <?php if (!empty($packageFilter) || !empty($dateFilter) || (!empty($statusFilter) && $statusFilter !== 'all')): ?>
                            <a href="reservationsManagement.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="admin-card p-4 mb-4">
                    <h5 class="mb-4" style="font-family: 'Playfair Display', serif;">Revenue Summary</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-center p-3" style="background-color: #F9F7F2; border-radius: 8px;">
                                <div class="text-muted small mb-1">Total Revenue</div>
                                <div class="h4 mb-0" style="color: #4A5D4A; font-weight: 600;">₱<?php echo number_format($totalRevenue, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3" style="background-color: #F9F7F2; border-radius: 8px;">
                                <div class="text-muted small mb-1">Bronze Package</div>
                                <div class="h5 mb-0" style="color: #4A5D4A; font-weight: 600;">₱<?php echo number_format($revenueByPackage['Bronze'], 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3" style="background-color: #F9F7F2; border-radius: 8px;">
                                <div class="text-muted small mb-1">Silver Package</div>
                                <div class="h5 mb-0" style="color: #4A5D4A; font-weight: 600;">₱<?php echo number_format($revenueByPackage['Silver'], 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3" style="background-color: #F9F7F2; border-radius: 8px;">
                                <div class="text-muted small mb-1">Gold Package</div>
                                <div class="h5 mb-0" style="color: #4A5D4A; font-weight: 600;">₱<?php echo number_format($revenueByPackage['Gold'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="mb-0" style="font-family: 'Playfair Display', serif;">
                                Reservations (<?php echo $totalCount; ?>)
                            </h5>
                            <?php if (!empty($filterDate)): ?>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-filter me-1"></i>Filtered by: <?php echo date('F j, Y', strtotime($filterDate)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($filterDate)): ?>
                            <a href="?<?php echo !empty($packageFilter) ? 'package=' . htmlspecialchars($packageFilter) : ''; ?>" class="btn-back-to-all">
                                <i class="fas fa-arrow-left me-2"></i>Back to All Reservations
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($groupedReservations)): ?>
                    <div class="empty-state">
                        <div class="empty-state-logo">EVENZA</div>
                        <i class="fas fa-clipboard-list empty-state-icon"></i>
                        <div class="empty-state-message">No reservations found</div>
                        <div class="empty-state-subtext">
                            <?php if (!empty($filterDate)): ?>
                                No reservations found for the selected date.
                            <?php else: ?>
                                There are no reservations to display at this time.
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($groupedReservations as $date => $dateData): 
                        $dateKey = $dateData['dateKey'];
                        $dateReservations = $dateData['reservations'];
                    ?>
                    <div class="mb-4">
                        <div class="date-group-header" onclick="filterByDate('<?php echo htmlspecialchars($dateKey, ENT_QUOTES); ?>')" title="Click to filter by this date">
                            <i class="fas fa-calendar-day me-2"></i><?php echo htmlspecialchars($date); ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($dateReservations); ?> reservation(s)</span>
                        </div>
                        
                        <?php foreach ($dateReservations as $reservation): ?>
                        <div class="reservation-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-2" style="font-family: 'Playfair Display', serif;">
                                        <?php echo htmlspecialchars($reservation['eventTitle'] ?? 'Unknown Event'); ?>
                                    </h6>
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-user me-1"></i>
                                        <strong>Customer:</strong> <?php echo htmlspecialchars($reservation['customerName'] ?? 'N/A'); ?>
                                        <span class="ms-2 text-muted">(<?php echo htmlspecialchars($reservation['customerEmail'] ?? 'N/A'); ?>)</span>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php 
                                        $timeDisplay = 'N/A';
                                        if (!empty($reservation['startTime']) && !empty($reservation['endTime'])) {
                                            $timeDisplay = formatTime12Hour($reservation['startTime'] . ' - ' . $reservation['endTime']);
                                        } elseif (!empty($reservation['eventTime'])) {
                                            $timeDisplay = formatTime12Hour($reservation['eventTime']);
                                        }
                                        echo htmlspecialchars($timeDisplay); 
                                        ?>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($reservation['eventVenue'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="mb-2">
                                        <?php 
                                        $tier = strtolower($reservation['packageTier'] ?? '');
                                        $badgeClass = 'package-badge';
                                        if (in_array($tier, ['bronze', 'silver', 'gold'])) {
                                            $badgeClass .= ' ' . $tier;
                                        }
                                        ?>
                                        <span class="<?php echo $badgeClass; ?>">
                                            <i class="fas fa-box me-1"></i>
                                            <?php echo htmlspecialchars($reservation['packageName'] ?? ($reservation['packageTier'] ?? 'N/A') . ' Package'); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        <strong>Revenue:</strong> ₱<?php echo number_format($reservation['totalAmount'] ?? 0, 2); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Reservation Status</label>
                                        <?php if (isset($reservation['userCancelled']) && $reservation['userCancelled']): ?>
                                            <div class="alert alert-warning small mb-2">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>User Cancelled:</strong> This reservation was cancelled by the user. Status cannot be modified.
                                            </div>
                                        <?php endif; ?>
                                        <div class="status-toggle-group">
                                            <button type="button" 
                                                    class="status-toggle-btn status-pending <?php echo (isset($reservation['status']) && strtolower($reservation['status']) === 'pending') ? 'active' : ''; ?>"
                                                    <?php if (isset($reservation['userCancelled']) && $reservation['userCancelled']): ?>disabled title="Cannot modify: User cancelled this reservation"<?php endif; ?>
                                                    onclick="updateReservationStatus('<?php echo htmlspecialchars($reservation['reservationId'], ENT_QUOTES); ?>', 'pending')">
                                                <i class="fas fa-clock me-1"></i> Pending
                                            </button>
                                            <button type="button" 
                                                    class="status-toggle-btn status-confirmed <?php echo (isset($reservation['status']) && strtolower($reservation['status']) === 'confirmed') ? 'active' : ''; ?>"
                                                    <?php if (isset($reservation['userCancelled']) && $reservation['userCancelled']): ?>disabled title="Cannot modify: User cancelled this reservation"<?php endif; ?>
                                                    onclick="updateReservationStatus('<?php echo htmlspecialchars($reservation['reservationId'], ENT_QUOTES); ?>', 'confirmed')">
                                                <i class="fas fa-check-circle me-1"></i> Confirmed
                                            </button>
                                            <button type="button" 
                                                    class="status-toggle-btn status-cancelled <?php echo (isset($reservation['status']) && strtolower($reservation['status']) === 'cancelled') ? 'active' : ''; ?>"
                                                    <?php if (isset($reservation['userCancelled']) && $reservation['userCancelled']): ?>disabled title="Cannot modify: User cancelled this reservation"<?php endif; ?>
                                                    onclick="updateReservationStatus('<?php echo htmlspecialchars($reservation['reservationId'], ENT_QUOTES); ?>', 'cancelled')">
                                                <i class="fas fa-times-circle me-1"></i> Cancelled
                                            </button>
                                            <button type="button" 
                                                    class="status-toggle-btn status-completed <?php echo (isset($reservation['status']) && strtolower($reservation['status']) === 'completed') ? 'active' : ''; ?>"
                                                    <?php if (isset($reservation['userCancelled']) && $reservation['userCancelled']): ?>disabled title="Cannot modify: User cancelled this reservation"<?php endif; ?>
                                                    onclick="updateReservationStatus('<?php echo htmlspecialchars($reservation['reservationId'], ENT_QUOTES); ?>', 'completed')"
                                                    title="Mark as Completed (set automatically after PayPal payment)">
                                                <i class="fas fa-credit-card me-1"></i> Completed
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-id-badge me-1"></i>
                                        Reservation ID: <?php echo htmlspecialchars($reservation['reservationId'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-calendar me-1"></i>
                                        Booked: <?php echo date('M j, Y', strtotime($reservation['reservationDate'] ?? 'now')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filterDate) ? '&filter_date=' . htmlspecialchars($filterDate) : ''; ?><?php echo !empty($packageFilter) ? '&package=' . htmlspecialchars($packageFilter) : ''; ?>" class="pagination-btn">Prev</a>
                        <?php else: ?>
                            <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?page=1<?php echo !empty($filterDate) ? '&filter_date=' . htmlspecialchars($filterDate) : ''; ?><?php echo !empty($packageFilter) ? '&package=' . htmlspecialchars($packageFilter) : ''; ?>" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($filterDate) ? '&filter_date=' . htmlspecialchars($filterDate) : ''; ?><?php echo !empty($packageFilter) ? '&package=' . htmlspecialchars($packageFilter) : ''; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($filterDate) ? '&filter_date=' . htmlspecialchars($filterDate) : ''; ?><?php echo !empty($packageFilter) ? '&package=' . htmlspecialchars($packageFilter) : ''; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filterDate) ? '&filter_date=' . htmlspecialchars($filterDate) : ''; ?><?php echo !empty($packageFilter) ? '&package=' . htmlspecialchars($packageFilter) : ''; ?>" class="pagination-btn">Next</a>
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

    <div class="toast-container">
        <div id="feedbackToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Message will be inserted here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Show feedback toast
        function showFeedback(message, type = 'info') {
            const toast = document.getElementById('feedbackToast');
            const toastMessage = document.getElementById('toastMessage');
            const toastHeader = toast.querySelector('.toast-header');
            
            toastMessage.textContent = message;
            
            // Update icon based on type
            const icon = toastHeader.querySelector('i');
            if (type === 'success') {
                icon.className = 'fas fa-check-circle me-2 text-success';
            } else if (type === 'error') {
                icon.className = 'fas fa-exclamation-circle me-2 text-danger';
            } else {
                icon.className = 'fas fa-info-circle me-2';
            }
            
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 4000
            });
            bsToast.show();
        }

        // Update reservation status
        function filterByDate(dateKey) {
            // Build URL with filter_date parameter and preserve other filters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('filter_date', dateKey);
            urlParams.set('page', '1'); // Reset to first page when filtering
            window.location.href = '?' + urlParams.toString();
        }

        function updateReservationStatus(reservationId, newStatus) {
            // Disable all status buttons for this reservation while updating
            const allButtons = document.querySelectorAll(`[onclick*="'${reservationId}'"]`);
            allButtons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            });
            
            // Show processing feedback
            showFeedback('Updating reservation status...', 'info');
            
            // Make AJAX call to update status in database
            fetch('/evenza/admin/process/update/updateReservationStatus.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reservationId=' + encodeURIComponent(reservationId) + '&status=' + encodeURIComponent(newStatus)
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Expected JSON but got: ' + text.substring(0, 100));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Capitalize first letter for display
                    const displayStatus = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    showFeedback('Reservation status updated to ' + displayStatus + ' successfully!', 'success');
                    
                    // Update button states immediately (case-insensitive comparison)
                    allButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        
                        // Check if button text contains the status (case-insensitive)
                        const btnText = btn.textContent.toLowerCase().trim();
                        if (btnText.includes(newStatus.toLowerCase())) {
                            btn.classList.add('active');
                        }
                    });
                    
                    // Reload after a short delay to reflect server-side changes
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    // Re-enable buttons on error
                    allButtons.forEach(btn => {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    });
                    showFeedback('Error updating status: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                // Re-enable buttons on error
                allButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
                showFeedback('Error updating status: ' + error.message, 'error');
            });
        }

        // Show feedback on page load if there's a message in URL
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const messageType = urlParams.get('type') || 'success';
        if (message) {
            showFeedback(decodeURIComponent(message), messageType);
        }
    </script>
</body>

</html>

