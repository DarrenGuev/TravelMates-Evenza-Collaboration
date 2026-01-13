<?php
require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Build base query for counting
$countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$countParams = [];
$countTypes = '';

if (!empty($searchQuery)) {
    $countQuery .= " AND (fullName LIKE ? OR email LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countTypes .= 'ss';
}

if (!empty($roleFilter) && $roleFilter !== 'all') {
    $countQuery .= " AND role = ?";
    $countParams[] = $roleFilter;
    $countTypes .= 's';
}

// Get total count
$totalCount = 0;
if (!empty($countParams)) {
    $countStmt = mysqli_prepare($conn, $countQuery);
    if ($countStmt) {
        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
        if (mysqli_stmt_execute($countStmt)) {
            $countResult = mysqli_stmt_get_result($countStmt);
            if ($countRow = mysqli_fetch_assoc($countResult)) {
                $totalCount = intval($countRow['total']);
            }
            mysqli_free_result($countResult);
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

// Main query with pagination
$query = "SELECT userid, firstName, lastName, fullName, email, phone, role FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($searchQuery)) {
    $query .= " AND (fullName LIKE ? OR email LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if (!empty($roleFilter) && $roleFilter !== 'all') {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

$query .= " ORDER BY userid ASC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$users = [];

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => $row['userid'],
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName'],
                    'fullName' => $row['fullName'],
                    'email' => $row['email'],
                    'mobile' => !empty($row['phone']) ? formatPhoneNumber($row['phone']) : 'N/A',
                    'role' => ucfirst(strtolower($row['role'])) 
                ];
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
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
    <title>Users - EVENZA Admin</title>
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
            overflow-x: hidden;
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
        
        /* Filter Header Section */
        .filter-header-section {
            margin-bottom: 30px;
        }
        
        .filter-section {
            display: flex;
            align-items: end;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        /* Users Table Card */
        .users-table-card {
            overflow: hidden;
            min-height: auto;
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
            white-space: normal;
            word-wrap: break-word;
        }
        .table tbody tr {
            background-color: transparent;
        }
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4A5D4A 0%, #6B7F5A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .role-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .role-admin {
            background-color: rgba(5, 150, 105, 0.15);
            color: #059669;
            border: 1px solid #059669;
        }
        .role-client {
            background-color: rgba(107, 114, 128, 0.15);
            color: #6b7280;
            border: 1px solid #6b7280;
        }
        .search-input {
            border: 1px solid rgba(74, 93, 74, 0.2);
            border-radius: 10px;
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            height: 42px;
        }
        .search-input:focus {
            border-color: #5A6B4F;
            box-shadow: 0 0 0 0.2rem rgba(90, 107, 79, 0.15);
            outline: none;
        }
        .role-filter-select {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 0.6rem 1.25rem;
            padding-right: 2.5rem;
            font-size: 0.9rem;
            background-color: #FFFFFF;
            color: #1A1A1A;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            height: 42px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
            background-image: none;
        }
        .role-filter-select::-ms-expand {
            display: none;
        }
        .role-filter-select:focus {
            border-color: #5A6B4F;
            box-shadow: 0 0 0 0.2rem rgba(90, 107, 79, 0.15);
            outline: none;
        }
        .role-filter-select option {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            padding: 0.5rem;
        }
        .role-filter-wrapper {
            position: relative;
        }
         .role-filter-wrapper .fa-chevron-down {
             position: absolute;
             right: 15px;
             top: 50%;
             transform: translateY(-50%);
             color: #6c757d;
             pointer-events: none;
             z-index: 5;
         }
         .pagination-wrapper {
             display: flex;
             justify-content: center;
             align-items: center;
             margin-top: 2rem;
             gap: 0.5rem;
             flex-wrap: wrap;
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
             font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
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
         .pagination-btn:disabled,
         .pagination-btn[style*="opacity: 0.5"] {
             opacity: 0.5;
             cursor: not-allowed;
         }
         
         .btn-add-user {
            background-color: #5A6B4F;
            border-color: #5A6B4F;
            color: #FFFFFF;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn-add-user:hover {
            background-color: #8B7A6B;
            border-color: #8B7A6B;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .role-badge {
            border-radius: 50px;
        }
        .action-btn {
            background: rgba(74, 93, 74, 0.08);
            border: none;
            color: #4A5D4A;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }
        .action-btn:hover {
            background: rgba(74, 93, 74, 0.15);
            color: #3a4a3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .action-btn.text-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .action-btn.text-danger:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #c82333;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .form-check-input:checked {
            background-color: #4A5D4A;
            border-color: #4A5D4A;
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
                gap: 15px;
            }
            .filter-section > div {
                width: 100% !important;
                min-width: 100% !important;
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
                min-width: 700px;
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
            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: none;
            }
            .search-input,
            .role-filter-select {
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
                        <a href="eventManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-calendar-alt"></i></span> 
                            <span style="font-weight: 500;">Event Management</span>
                        </a>
                        <a href="reservationsManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3" style="transition: all 0.3s ease; color: rgba(26, 26, 26, 0.7); text-decoration: none; border-left: 3px solid transparent;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-clipboard-list"></i></span> 
                            <span style="font-weight: 500;">Reservations</span>
                        </a>
                        <a href="userManagement.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-users"></i></span> 
                            <span>Users</span>
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
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">Users</h4>
                        <div class="text-muted small">View user accounts and information</div>
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

            <div class="p-4" style="padding: 2rem !important; width: 100%; overflow-x: hidden; box-sizing: border-box;">
                <!-- Controls Section -->
                <!-- Search & Filter Bar -->
                <div class="admin-card p-4 mb-4 filter-header-section">
                    <div class="filter-section d-flex align-items-end flex-wrap" style="gap: 20px;">
                        <!-- Search Input -->
                        <div class="flex-grow-1" style="min-width: 200px;">
                            <label class="form-label fw-semibold" style="color: #1A1A1A;">Search</label>
                            <div class="position-relative">
                                <i class="fas fa-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 10;"></i>
                                <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" style="padding-left: 45px; border-radius: 10px;">
                            </div>
                        </div>
                        
                        <!-- Role Filter -->
                        <div style="min-width: 200px;">
                            <label for="roleFilter" class="form-label fw-semibold" style="color: #1A1A1A;">Filter by Role</label>
                            <div class="role-filter-wrapper">
                                <select id="roleFilter" class="form-select role-filter-select">
                                    <option value="all" <?php echo (empty($roleFilter) || $roleFilter === 'all') ? 'selected' : ''; ?>>All Roles</option>
                                    <option value="admin" <?php echo ($roleFilter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?php echo ($roleFilter === 'user' || $roleFilter === 'client') ? 'selected' : ''; ?>>User</option>
                                </select>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="admin-card p-4 users-table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0" style="font-family: 'Playfair Display', serif;">
                            All Users 
                            <span class="badge bg-light text-dark ms-2" style="font-size: 0.9rem; padding: 0.4rem 0.8rem; border-radius: 50px;">
                                <?php echo $totalCount; ?>
                            </span>
                        </h5>
                    </div>
                    <div class="table-responsive" style="overflow-x: auto; width: 100%;">
                        <table class="table align-middle" style="width: 100%; table-layout: auto;">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-users fa-2x mb-3 d-block"></i>
                                        No users found.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($user['fullName'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['fullName']); ?></div>
                                                <div class="text-muted small">ID: <?php echo htmlspecialchars($user['id']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($user['mobile'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo strtolower($user['role']) === 'admin' ? 'role-admin' : 'role-client'; ?>">
                                            <i class="fas <?php echo strtolower($user['role']) === 'admin' ? 'fa-shield-alt' : 'fa-user'; ?> me-1"></i>
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php
                        $queryParams = [];
                        if (!empty($searchQuery)) {
                            $queryParams[] = 'search=' . urlencode($searchQuery);
                        }
                        if (!empty($roleFilter) && $roleFilter !== 'all') {
                            $queryParams[] = 'role=' . urlencode($roleFilter);
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

        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const usersTableBody = document.getElementById('usersTableBody');
        
        // Role filter change event - instant filtering
        if (roleFilter) {
            roleFilter.addEventListener('change', function() {
                performSearch();
            });
        }

         function performSearch() {
             const searchQuery = searchInput.value.trim();
             const roleValue = roleFilter.value;
             
             // Reset to page 1 when filtering
             const url = new URL(window.location.href);
             if (searchQuery) {
                 url.searchParams.set('search', searchQuery);
             } else {
                 url.searchParams.delete('search');
             }
             if (roleValue && roleValue !== 'all') {
                 url.searchParams.set('role', roleValue);
             } else {
                 url.searchParams.delete('role');
             }
             url.searchParams.set('page', '1'); // Reset to first page
             window.location.href = url.toString();
         }

        function updateUsersTable(users) {
            if (users.length === 0) {
                usersTableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="fas fa-users fa-2x mb-3 d-block"></i>
                            No users found.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            users.forEach(user => {
                const roleClass = (user.role.toLowerCase() === 'admin') ? 'role-admin' : 'role-client';
                const roleIcon = (user.role.toLowerCase() === 'admin') ? 'fa-shield-alt' : 'fa-user';
                const fullName = user.fullName || (user.firstName + ' ' + user.lastName);
                const initial = fullName.charAt(0).toUpperCase();
                
                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">${initial}</div>
                                <div>
                                    <div class="fw-semibold">${escapeHtml(fullName)}</div>
                                    <div class="text-muted small">ID: ${escapeHtml(user.id)}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>${escapeHtml(user.email)}</div>
                        </td>
                        <td>
                            <div>${escapeHtml(user.mobile || 'N/A')}</div>
                        </td>
                        <td>
                            <span class="role-badge ${roleClass}">
                                <i class="fas ${roleIcon} me-1"></i>
                                ${escapeHtml(user.role)}
                            </span>
                        </td>
                    </tr>
                `;
            });
            usersTableBody.innerHTML = html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const messageType = urlParams.get('type') || 'success';
        if (message) {
            showFeedback(decodeURIComponent(message), messageType);
        }
    </script>
</body>

</html>

