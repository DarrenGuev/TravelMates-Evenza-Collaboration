<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';

function getEventImagePath($imagePath) {
    $imageDir = '../../assets/images/event_images/';
    $placeholder = $imageDir . 'placeholder.jpg';

    if (empty($imagePath)) {
        return $placeholder;
    }

    $imagePath = ltrim($imagePath, '/\\');

    if (strpos($imagePath, '../../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../../assets/images/event_images/'));
    }
    if (strpos($imagePath, '../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../assets/images/event_images/'));
    }
    if (strpos($imagePath, 'assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('assets/images/event_images/'));
    }

    $filename = basename($imagePath);
    $filename = str_replace(['/', '\\'], '', $filename);
    $imagePath = $imageDir . $filename;

    $fullPath = realpath(__DIR__ . '/' . $imagePath);
    if ($fullPath && file_exists($fullPath)) {
        return $imagePath;
    }

    return $placeholder;
}

$eventsData = [];
$query = "SELECT * FROM events ORDER BY eventId DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $imagePath = isset($row['imagePath']) ? $row['imagePath'] : '';
        $imageName = !empty($imagePath) ? basename($imagePath) : '';
        
        $eventId = isset($row['eventId']) ? $row['eventId'] : 0;
        
        $eventsData[$eventId] = [
            'eventId' => $eventId,
            'id' => $eventId,
            'name' => isset($row['title']) ? $row['title'] : '',
            'title' => isset($row['title']) ? $row['title'] : '',
            'category' => (isset($row['category']) && $row['category'] !== null && trim($row['category']) !== '') ? trim($row['category']) : '',
            'status' => 'Active', 
            'image' => $imageName,
            'imagePath' => $imagePath,
            'venue' => isset($row['venue']) ? $row['venue'] : ''
        ];
    }
    mysqli_free_result($result);
} else {
    $eventsData = [];
}

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredEvents = $eventsData;
if (!empty($searchQuery)) {
    $filteredEvents = array_filter($eventsData, function($event) use ($searchQuery) {
        return stripos($event['name'], $searchQuery) !== false || 
               stripos($event['title'], $searchQuery) !== false ||
               stripos($event['category'], $searchQuery) !== false ||
               stripos($event['venue'], $searchQuery) !== false;
    });
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
    <title>Event Management - EVENZA Admin</title>
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
        
        /* Add Event Modal Button Styles */
        .btn-cancel-event {
            background-color: #FFFFFF;
            color: #4A5D4E;
            border: 1px solid #4A5D4E;
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .btn-cancel-event:hover {
            background-color: #F9F7F2;
            color: #4A5D4E;
            border-color: #4A5D4E;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(74, 93, 78, 0.2);
        }
        
        .btn-save-event {
            background-color: #4A5D4E;
            color: #FFFFFF;
            border: none;
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .btn-save-event:hover {
            background-color: #5A6B4F;
            color: #FFFFFF;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(74, 93, 78, 0.3);
        }
        
        .table {
            margin-bottom: 0;
        }
        .table thead {
            background: linear-gradient(135deg, rgba(74, 93, 74, 0.05) 0%, rgba(74, 93, 74, 0.02) 100%);
        }
        .table th {
            font-weight: 600;
            color: #1A1A1A;
            border-bottom: 2px solid rgba(74, 93, 74, 0.15);
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
            padding: 18px 1rem;
            border-bottom: 1px solid rgba(74, 93, 74, 0.08);
        }
        .table tbody tr {
            background-color: transparent;
        }
        .event-thumbnail {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            box-shadow: 0 2px 4px rgba(21, 87, 36, 0.2);
        }
        .status-inactive {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            box-shadow: 0 2px 4px rgba(114, 28, 36, 0.2);
        }
        
        /* Package Inclusions Button */
        .btn-package-inclusions {
            border: 1px solid #4A5D4E;
            color: #4A5D4E;
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-package-inclusions:hover {
            background-color: #4A5D4E;
            color: #FFFFFF;
            border-color: #4A5D4E;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(74, 93, 78, 0.2);
        }
        
        .btn-package-inclusions:active {
            transform: translateY(0);
        }
        /* Actions column container */
        .actions-cell {
            width: 120px;
            text-align: center;
        }
        
        .actions-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .action-btn {
            background: rgba(0, 0, 0, 0.05);
            border: none;
            color: #4A5D4A;
            padding: 0.5rem;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        /* Edit button - blue hover */
        .action-btn:not(.text-danger):hover {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            transform: translateY(-1px);
        }
        
        /* Delete button - red/pink hover */
        .action-btn.text-danger {
            background: rgba(0, 0, 0, 0.05);
            color: #dc3545;
        }
        
        .action-btn.text-danger:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            transform: translateY(-1px);
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        #confirmDeleteBtn:hover {
            background-color: #c82333 !important;
            border-color: #c82333 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .modal-footer .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
                min-width: 800px;
                width: 100%;
            }
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                white-space: nowrap;
            }
            .table img {
                width: 40px;
                height: 40px;
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
            .table th:nth-child(1),
            .table td:nth-child(1) {
                display: none;
            }
            .search-input {
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
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
                        <a href="eventManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-calendar-alt"></i></span> 
                            <span>Event Management</span>
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

        <!-- Content -->
        <div class="flex-fill admin-content">
            <!-- Top Navigation Bar -->
            <div class="admin-top-nav d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3 d-xl-none">
                        <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; padding: 0.5rem 0.75rem;">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    <div>
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">Event Management</h4>
                        <div class="text-muted small">Manage all events and their details</div>
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
                <!-- Controls Section -->
                <div class="admin-card p-4 mb-4" style="padding: 2rem !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1" style="font-family: 'Playfair Display', serif; color: #1A1A1A;">Event Management</h4>
                            <p class="text-muted mb-0 small">Search and manage all events</p>
                        </div>
                        <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus me-2"></i> Add New Event
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <form method="GET" action="eventManagement.php" class="d-flex gap-2">
                                <div class="position-relative flex-grow-1">
                                    <i class="fas fa-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 10;"></i>
                                    <input type="text" 
                                           class="form-control ps-5" 
                                           id="searchBar" 
                                           name="search" 
                                           placeholder="Search by event name, category, or venue..." 
                                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                                           style="border-radius: 50px; border: 2px solid rgba(74, 93, 74, 0.1); padding: 0.75rem 1.5rem;">
                                </div>
                                <button type="submit" class="btn btn-admin-primary" style="border-radius: 50px; padding: 0.75rem 2rem;">
                                    <i class="fas fa-search me-2"></i> Search
                                </button>
                                <?php if (!empty($searchQuery)): ?>
                                <a href="eventManagement.php" class="btn btn-outline-secondary" style="border-radius: 50px; padding: 0.75rem 1.5rem;">
                                    <i class="fas fa-times me-2"></i> Clear
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="admin-card p-4" style="padding: 2rem !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0" style="font-family: 'Playfair Display', serif; color: #1A1A1A;">
                            All Events 
                            <span class="badge bg-light text-dark ms-2" style="font-size: 0.9rem; padding: 0.4rem 0.8rem; border-radius: 50px;">
                                <?php echo count($filteredEvents); ?>
                            </span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Venue</th>
                                    <th>PACKAGES</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($filteredEvents)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="py-5">
                                            <div class="mb-4" style="font-size: 4rem; color: rgba(74, 93, 74, 0.2);">
                                                <i class="fas fa-calendar-times"></i>
                                            </div>
                                            <h5 class="mb-2" style="font-family: 'Playfair Display', serif; color: #1A1A1A;">No Events Found</h5>
                                            <p class="text-muted mb-4"><?php echo !empty($searchQuery) ? 'No events match your search criteria.' : 'Get started by adding your first event.'; ?></p>
                                            <?php if (empty($searchQuery)): ?>
                                            <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                                <i class="fas fa-plus me-2"></i> Add Your First Event
                                            </button>
                                            <?php else: ?>
                                            <a href="eventManagement.php" class="btn btn-outline-secondary" style="border-radius: 50px;">
                                                <i class="fas fa-times me-2"></i> Clear Search
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($filteredEvents as $id => $event): 
                                    $imageSrc = getEventImagePath($event['imagePath']);
                                    
                                    if (stripos($event['title'], 'wine') !== false || stripos($event['name'], 'wine') !== false) {
                                        if (file_exists(__DIR__ . '/../../assets/images/event_images/wineCellar.jpg')) {
                                            $imageSrc = '../../assets/images/event_images/wineCellar.jpg';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td style="width: 90px;">
                                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                             alt="<?php echo htmlspecialchars($event['name'] ?? $event['title']); ?>" 
                                             class="event-thumbnail"
                                             onerror="this.src='../../assets/images/event_images/businessInnovation.jpg'">
                                    </td>
                                    <td style="min-width: 200px;">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($event['name'] ?? $event['title']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: linear-gradient(135deg, rgba(74, 93, 74, 0.1) 0%, rgba(74, 93, 74, 0.05) 100%); color: #4A5D4A; padding: 0.5rem 1rem; border-radius: 50px; font-weight: 500;">
                                            <?php echo htmlspecialchars(!empty($event['category']) ? $event['category'] : 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td style="min-width: 250px;">
                                        <div class="text-muted small"><?php echo htmlspecialchars($event['venue']); ?></div>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-package-inclusions" 
                                                data-event-id="<?php echo $event['eventId'] ?? $event['id']; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['name'] ?? $event['title'], ENT_QUOTES); ?>"
                                                onclick="viewPackageInclusions(<?php echo $event['eventId'] ?? $event['id']; ?>, '<?php echo htmlspecialchars($event['name'] ?? $event['title'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-box me-1"></i> Package Inclusions
                                        </button>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="actions-group">
                                            <button class="action-btn" onclick="editEvent(<?php echo $event['eventId'] ?? $event['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn text-danger" onclick="deleteEvent(<?php echo $event['eventId'] ?? $event['id']; ?>, '<?php echo htmlspecialchars($event['name'] ?? $event['title'], ENT_QUOTES); ?>')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container for Feedback Messages -->
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(74, 93, 74, 0.1); padding: 1.5rem;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; color: #dc3545;"></i>
                        <h5 class="modal-title mb-0" id="deleteConfirmModalLabel" style="font-family: 'Playfair Display', serif; color: #1A1A1A;">Confirm Deletion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p class="mb-0" id="deleteConfirmMessage" style="color: rgba(26, 26, 26, 0.8); line-height: 1.6;">
                        <!-- Message will be inserted here -->
                    </p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(74, 93, 74, 0.1); padding: 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px; padding: 0.6rem 1.5rem; font-weight: 500; transition: all 0.3s ease;">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="border-radius: 50px; padding: 0.6rem 1.5rem; font-weight: 600; background-color: #dc3545; border-color: #dc3545; transition: all 0.3s ease;">
                        <i class="fas fa-trash-alt me-2"></i>Delete Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif;" id="addEventModalLabel">Add New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <div class="mb-3">
                            <label for="eventName" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="eventName" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="eventCategory" required>
                                <option value="">Select Category</option>
                                <option value="Business">Business</option>
                                <option value="Weddings">Weddings</option>
                                <option value="Socials">Socials</option>
                                <option value="Workshops">Workshops</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="addEventDescription" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="addEventDescription" rows="4" placeholder="Enter a detailed description of the event..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="addEventVenue" class="form-label">Venue <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addEventVenue" placeholder="e.g., TravelMates Hotel - Main Ballroom" required>
                        </div>
                        <div class="mb-3">
                            <label for="addEventImagePath" class="form-label">Image Path <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addEventImagePath" placeholder="assets/images/event_images/filename.jpg" required>
                            <div class="form-text">Enter the path to the event image (e.g., assets/images/event_images/myevent.jpg)</div>
                        </div>

                        <!-- Package Tiers & Inclusions Section -->
                        <div class="mt-4 pt-3" style="border-top: 1px solid rgba(74, 93, 74, 0.1);">
                            <h6 class="mb-4" style="font-family: 'Playfair Display', serif; font-weight: 600; color: #1A1A1A;">Package Tiers & Inclusions</h6>
                            
                            <!-- Bronze Package -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #B8956A;">Bronze Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="bronzePrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="bronzePrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="bronzeInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="bronzeInclusions" rows="2" placeholder="e.g., 4-hour venue use, basic sound system" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Silver Package -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #D4D4D4;">Silver Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="silverPrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="silverPrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="silverInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="silverInclusions" rows="2" placeholder="e.g., 6-hour venue use, premium sound system, catering" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Gold Package -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="color: #D4AF37;">Gold Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="goldPrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="goldPrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="goldInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="goldInclusions" rows="2" placeholder="e.g., Full-day venue use, premium sound system, catering, decorations" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel-event" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-save-event" onclick="saveNewEvent()">Save Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif;" id="editEventModalLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editEventForm">
                        <input type="hidden" id="editEventId" name="eventId">
                        <div class="mb-3">
                            <label for="editEventTitle" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEventTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEventCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="editEventCategory" required>
                                <option value="">Select Category</option>
                                <option value="Business">Business</option>
                                <option value="Weddings">Weddings</option>
                                <option value="Socials">Socials</option>
                                <option value="Workshops">Workshops</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEventDescription" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="editEventDescription" rows="4" placeholder="Enter a detailed description of the event..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editEventVenue" class="form-label">Venue <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEventVenue" placeholder="e.g., TravelMates Hotel - Main Ballroom" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEventImagePath" class="form-label">Image Path <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEventImagePath" placeholder="assets/images/event_images/filename.jpg" required>
                            <div class="form-text">Enter the path to the event image (e.g., assets/images/event_images/myevent.jpg)</div>
                        </div>

                        <!-- Package Tiers & Inclusions Section (Edit) -->
                        <div class="mt-4 pt-3" style="border-top: 1px solid rgba(74, 93, 74, 0.1);">
                            <h6 class="mb-4" style="font-family: 'Playfair Display', serif; font-weight: 600; color: #1A1A1A;">Package Tiers & Inclusions</h6>
                            
                            <!-- Bronze Package -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #B8956A;">Bronze Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="editBronzePrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="editBronzePrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="editBronzeInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="editBronzeInclusions" rows="2" placeholder="e.g., 4-hour venue use, basic sound system" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Silver Package -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #D4D4D4;">Silver Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="editSilverPrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="editSilverPrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="editSilverInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="editSilverInclusions" rows="2" placeholder="e.g., 6-hour venue use, premium sound system, catering" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Gold Package -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="color: #D4AF37;">Gold Package</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="editGoldPrice" class="form-label small">Price (₱) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="editGoldPrice" placeholder="0.00" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="editGoldInclusions" class="form-label small">Inclusions/Offers <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="editGoldInclusions" rows="2" placeholder="e.g., Full-day venue use, premium sound system, catering, decorations" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel-event" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-save-event" onclick="saveEditedEvent()">Update Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Package Inclusions Modal -->
    <div class="modal fade" id="packageInclusionsModal" tabindex="-1" aria-labelledby="packageInclusionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(74, 93, 74, 0.1);">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif; font-weight: 600; color: #1A1A1A;" id="packageInclusionsModalLabel">Packages for <span id="packageModalEventTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <div id="packageInclusionsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading package details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(74, 93, 74, 0.1);">
                    <button type="button" class="btn btn-cancel-event" data-bs-dismiss="modal">Close</button>
                </div>
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

        let isEditEventMode = false;
        let currentEventId = null;

        // Edit event function
        function editEvent(eventId) {
            isEditEventMode = true;
            currentEventId = eventId;
            
            fetch('/evenza/admin/process/fetch/getEvent.php?eventId=' + eventId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    // Check if response is actually JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error('Invalid response format. Expected JSON but got: ' + text.substring(0, 100));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const event = data.data;
                        document.getElementById('editEventModalLabel').textContent = 'Edit Event';
                        document.getElementById('editEventId').value = event.eventId || eventId;
                        document.getElementById('editEventTitle').value = event.title || '';
                        // Set category - handle case where category might not match dropdown options
                        const categoryValue = (event.category || '').trim();
                        const categorySelect = document.getElementById('editEventCategory');
                        
                        // Try to set the value first
                        categorySelect.value = categoryValue;
                        
                        // If category doesn't match any option, add it as a temporary option
                        if (categoryValue && !Array.from(categorySelect.options).some(opt => opt.value === categoryValue)) {
                            const option = document.createElement('option');
                            option.value = categoryValue;
                            option.textContent = categoryValue;
                            option.selected = true;
                            categorySelect.insertBefore(option, categorySelect.firstChild.nextSibling); // Insert after "Select Category"
                        }
                        document.getElementById('editEventVenue').value = event.venue || '';
                        document.getElementById('editEventDescription').value = event.description || '';
                        document.getElementById('editEventImagePath').value = event.imagePath || '';

                        // Populate package pricing and inclusions
                        const packages = data.packages || {};
                        document.getElementById('editBronzePrice').value = packages.bronze && packages.bronze.price !== null ? packages.bronze.price : '';
                        document.getElementById('editSilverPrice').value = packages.silver && packages.silver.price !== null ? packages.silver.price : '';
                        document.getElementById('editGoldPrice').value = packages.gold && packages.gold.price !== null ? packages.gold.price : '';
                        document.getElementById('editBronzeInclusions').value = packages.bronze && packages.bronze.inclusions ? packages.bronze.inclusions : '';
                        document.getElementById('editSilverInclusions').value = packages.silver && packages.silver.inclusions ? packages.silver.inclusions : '';
                        document.getElementById('editGoldInclusions').value = packages.gold && packages.gold.inclusions ? packages.gold.inclusions : '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
                        modal.show();
                    } else {
                        showFeedback(data.message || 'Failed to load event data.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching event:', error);
                    showFeedback('An error occurred while loading event data: ' + error.message, 'error');
                });
        }

        // Delete event function
        let pendingDeleteEventId = null;
        let pendingDeleteEventName = null;

        function deleteEvent(eventId, eventName) {
            pendingDeleteEventId = eventId;
            pendingDeleteEventName = eventName;
            
            // Set the confirmation message
            document.getElementById('deleteConfirmMessage').textContent = 
                'Are you sure you want to delete "' + eventName + '"? This action cannot be undone.';
            
            // Show the confirmation modal
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        // Handle confirm delete button click
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (pendingDeleteEventId && pendingDeleteEventName) {
                const formData = new FormData();
                formData.append('eventId', pendingDeleteEventId);
                
                // Hide the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                if (modal) {
                    modal.hide();
                }
                
                fetch('/evenza/admin/process/delete/deleteEvent.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFeedback('Event "' + pendingDeleteEventName + '" has been deleted successfully.', 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showFeedback(data.message || 'An error occurred while deleting the event.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFeedback('An error occurred while deleting the event. Please check the console for details.', 'error');
                })
                .finally(() => {
                    // Reset pending delete variables
                    pendingDeleteEventId = null;
                    pendingDeleteEventName = null;
                });
            }
        });

        // Save new event
        function saveNewEvent() {
            const title = document.getElementById('eventName').value.trim();
            const category = document.getElementById('eventCategory').value;
            const description = document.getElementById('addEventDescription').value.trim();
            const venue = document.getElementById('addEventVenue').value.trim();
            const imagePath = document.getElementById('addEventImagePath').value.trim();
            
            // Package data
            const bronzePrice = document.getElementById('bronzePrice').value.trim();
            const bronzeInclusions = document.getElementById('bronzeInclusions').value.trim();
            const silverPrice = document.getElementById('silverPrice').value.trim();
            const silverInclusions = document.getElementById('silverInclusions').value.trim();
            const goldPrice = document.getElementById('goldPrice').value.trim();
            const goldInclusions = document.getElementById('goldInclusions').value.trim();
            
            if (!title || !category || !description || !venue || !imagePath) {
                showFeedback('Please fill in all required fields including Description, Venue, and Image Path.', 'error');
                return;
            }
            
            if (!bronzePrice || !bronzeInclusions || !silverPrice || !silverInclusions || !goldPrice || !goldInclusions) {
                showFeedback('Please fill in all package details (Price and Inclusions for Bronze, Silver, and Gold).', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('eventId', '0');
            formData.append('title', title);
            formData.append('category', category);
            formData.append('venue', venue);
            formData.append('description', description);
            formData.append('imagePath', imagePath);
            formData.append('bronzePrice', bronzePrice);
            formData.append('bronzeInclusions', bronzeInclusions);
            formData.append('silverPrice', silverPrice);
            formData.append('silverInclusions', silverInclusions);
            formData.append('goldPrice', goldPrice);
            formData.append('goldInclusions', goldInclusions);
            
            fetch('/evenza/admin/process/update/updateEvent.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showFeedback('Event "' + title + '" has been added successfully.', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showFeedback(data.message || 'An error occurred while saving the event.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFeedback('An error occurred while saving the event. Please check the console for details.', 'error');
            });
        }

        // Save edited event
        function saveEditedEvent() {
            const eventId = document.getElementById('editEventId').value;
            const title = document.getElementById('editEventTitle').value.trim();
            const category = document.getElementById('editEventCategory').value;
            const venue = document.getElementById('editEventVenue').value.trim();
            const description = document.getElementById('editEventDescription').value.trim();
            const imagePath = document.getElementById('editEventImagePath').value.trim();
            const bronzePrice = document.getElementById('editBronzePrice').value.trim();
            const silverPrice = document.getElementById('editSilverPrice').value.trim();
            const goldPrice = document.getElementById('editGoldPrice').value.trim();
            const bronzeInclusions = document.getElementById('editBronzeInclusions').value.trim();
            const silverInclusions = document.getElementById('editSilverInclusions').value.trim();
            const goldInclusions = document.getElementById('editGoldInclusions').value.trim();
            
            if (!title || !category || !description || !venue || !imagePath) {
                showFeedback('Please fill in all required fields including Description, Venue, and Image Path.', 'error');
                return;
            }

            if (!bronzePrice || !silverPrice || !goldPrice || !bronzeInclusions || !silverInclusions || !goldInclusions) {
                showFeedback('Please fill in all package details (Price and Inclusions for Bronze, Silver, and Gold).', 'error');
                return;
            }
            
            // Debug: Log all values being sent
            console.log('Updating event:', {
                eventId: eventId,
                title: title,
                category: category,
                venue: venue,
                bronzePrice,
                silverPrice,
                goldPrice
            });
            
            const formData = new FormData();
            formData.append('eventId', eventId);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('venue', venue);
            formData.append('description', description);
            formData.append('imagePath', imagePath);
            formData.append('bronzePrice', bronzePrice);
            formData.append('silverPrice', silverPrice);
            formData.append('goldPrice', goldPrice);
            formData.append('bronzeInclusions', bronzeInclusions);
            formData.append('silverInclusions', silverInclusions);
            formData.append('goldInclusions', goldInclusions);
            
            fetch('/evenza/admin/process/update/updateEvent.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // First, get the response text to debug
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if response is empty
                    if (!text || text.trim() === '') {
                        console.error('Empty response from server');
                        throw new Error('Empty response from server. Please check the server logs.');
                    }
                    
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) {
                            throw new Error(data.message || 'Network response was not ok');
                        }
                        return data;
                    } catch (e) {
                        console.error('JSON Parse Error. Response text:', text);
                        console.error('Parse error:', e);
                        throw new Error('Invalid JSON response. Server returned: ' + (text.substring(0, 200) || '(empty)'));
                    }
                });
            })
            .then(data => {
                console.log('Update response:', data);
                if (data.success) {
                    // Log the category that was updated
                    if (data.category) {
                        console.log('Category updated to:', data.category);
                    }
                    showFeedback('Event "' + title + '" has been updated successfully.', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Force a hard reload to ensure fresh data from server
                    // Use location.reload() with forceReload flag for better cache clearing
                    setTimeout(function() {
                        // Clear any cache and reload with timestamp
                        if (window.location.search) {
                            window.location.href = window.location.pathname + '?t=' + new Date().getTime();
                        } else {
                            window.location.href = window.location.pathname + '?t=' + new Date().getTime();
                        }
                    }, 500);
                } else {
                    showFeedback(data.message || 'An error occurred while updating the event.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFeedback('An error occurred while updating the event: ' + error.message, 'error');
            });
        }

        // Show feedback on page load if there's a message in URL
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const messageType = urlParams.get('type') || 'success';
        if (message) {
            showFeedback(decodeURIComponent(message), messageType);
        }

        // View Package Inclusions
        function viewPackageInclusions(eventId, eventTitle) {
            // Set the modal title
            document.getElementById('packageModalEventTitle').textContent = eventTitle;
            
            // Show loading state
            document.getElementById('packageInclusionsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading package details...</p>
                </div>
            `;
            
            // Open the modal
            const modal = new bootstrap.Modal(document.getElementById('packageInclusionsModal'));
            modal.show();
            
            // Fetch package data
            fetch('/evenza/admin/process/fetch/getEvent.php?eventId=' + eventId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.packages) {
                        displayPackageInclusions(data.packages);
                    } else {
                        document.getElementById('packageInclusionsContent').innerHTML = `
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No package information available for this event.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching packages:', error);
                    document.getElementById('packageInclusionsContent').innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-times-circle me-2"></i>
                            Error loading package details. Please try again later.
                        </div>
                    `;
                });
        }

        // Display Package Inclusions
        function displayPackageInclusions(packages) {
            const content = document.getElementById('packageInclusionsContent');
            
            let html = '<div class="row g-4">';
            
            // Bronze Package
            if (packages.bronze) {
                html += `
                    <div class="col-12">
                        <div class="card" style="border: 1px solid rgba(184, 149, 106, 0.3); border-radius: 15px; overflow: hidden;">
                            <div class="card-header" style="background: linear-gradient(135deg, rgba(184, 149, 106, 0.1) 0%, rgba(184, 149, 106, 0.05) 100%); border-bottom: 1px solid rgba(184, 149, 106, 0.2);">
                                <h6 class="mb-0 fw-bold" style="color: #B8956A; font-family: 'Playfair Display', serif;">
                                    <i class="fas fa-box me-2"></i>Bronze Package
                                    ${packages.bronze.price !== null ? `<span class="badge bg-light text-dark ms-2" style="font-size: 0.85rem; padding: 0.3rem 0.7rem; border-radius: 50px;">₱${parseFloat(packages.bronze.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>` : ''}
                                </h6>
                            </div>
                            <div class="card-body" style="padding: 1.5rem;">
                                ${packages.bronze.inclusions ? `
                                    <ul class="list-unstyled mb-0" style="line-height: 1.8;">
                                        ${packages.bronze.inclusions.split('\n').filter(item => item.trim()).map(item => `
                                            <li style="color: #1A1A1A; margin-bottom: 0.5rem;">
                                                <i class="fas fa-check-circle me-2" style="color: #B8956A;"></i>${item.trim()}
                                            </li>
                                        `).join('')}
                                    </ul>
                                ` : '<p class="text-muted mb-0">No inclusions specified.</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Silver Package
            if (packages.silver) {
                html += `
                    <div class="col-12">
                        <div class="card" style="border: 1px solid rgba(212, 212, 212, 0.5); border-radius: 15px; overflow: hidden;">
                            <div class="card-header" style="background: linear-gradient(135deg, rgba(212, 212, 212, 0.1) 0%, rgba(212, 212, 212, 0.05) 100%); border-bottom: 1px solid rgba(212, 212, 212, 0.2);">
                                <h6 class="mb-0 fw-bold" style="color: #8B8B8B; font-family: 'Playfair Display', serif;">
                                    <i class="fas fa-box me-2"></i>Silver Package
                                    ${packages.silver.price !== null ? `<span class="badge bg-light text-dark ms-2" style="font-size: 0.85rem; padding: 0.3rem 0.7rem; border-radius: 50px;">₱${parseFloat(packages.silver.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>` : ''}
                                </h6>
                            </div>
                            <div class="card-body" style="padding: 1.5rem;">
                                ${packages.silver.inclusions ? `
                                    <ul class="list-unstyled mb-0" style="line-height: 1.8;">
                                        ${packages.silver.inclusions.split('\n').filter(item => item.trim()).map(item => `
                                            <li style="color: #1A1A1A; margin-bottom: 0.5rem;">
                                                <i class="fas fa-check-circle me-2" style="color: #8B8B8B;"></i>${item.trim()}
                                            </li>
                                        `).join('')}
                                    </ul>
                                ` : '<p class="text-muted mb-0">No inclusions specified.</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Gold Package
            if (packages.gold) {
                html += `
                    <div class="col-12">
                        <div class="card" style="border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 15px; overflow: hidden;">
                            <div class="card-header" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.05) 100%); border-bottom: 1px solid rgba(212, 175, 55, 0.2);">
                                <h6 class="mb-0 fw-bold" style="color: #D4AF37; font-family: 'Playfair Display', serif;">
                                    <i class="fas fa-box me-2"></i>Gold Package
                                    ${packages.gold.price !== null ? `<span class="badge bg-light text-dark ms-2" style="font-size: 0.85rem; padding: 0.3rem 0.7rem; border-radius: 50px;">₱${parseFloat(packages.gold.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>` : ''}
                                </h6>
                            </div>
                            <div class="card-body" style="padding: 1.5rem;">
                                ${packages.gold.inclusions ? `
                                    <ul class="list-unstyled mb-0" style="line-height: 1.8;">
                                        ${packages.gold.inclusions.split('\n').filter(item => item.trim()).map(item => `
                                            <li style="color: #1A1A1A; margin-bottom: 0.5rem;">
                                                <i class="fas fa-check-circle me-2" style="color: #D4AF37;"></i>${item.trim()}
                                            </li>
                                        `).join('')}
                                    </ul>
                                ` : '<p class="text-muted mb-0">No inclusions specified.</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            
            content.innerHTML = html;
        }
    </script>
</body>

</html>

