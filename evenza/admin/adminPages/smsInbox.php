<?php
require_once '../process/auth/adminAuth.php';
require_once '../../core/connect.php';
require_once '../../includes/helpers.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

$unreadQuery = "SELECT COUNT(*) as unread_count FROM sms_messages WHERE is_read = 0 AND (raw_data IS NULL OR raw_data NOT LIKE ?)";
$unreadStmt = mysqli_prepare($conn, $unreadQuery);
$unreadCount = 0;
if ($unreadStmt) {
    $sentPattern = '%"type":"sent"%';
    mysqli_stmt_bind_param($unreadStmt, "s", $sentPattern);
    mysqli_stmt_execute($unreadStmt);
    $unreadResult = mysqli_stmt_get_result($unreadStmt);
    if ($unreadRow = mysqli_fetch_assoc($unreadResult)) {
        $unreadCount = $unreadRow['unread_count'] ?? 0;
    }
    mysqli_stmt_close($unreadStmt);
}

$countQuery = "SELECT COUNT(*) as total FROM sms_messages WHERE raw_data IS NULL OR raw_data NOT LIKE ?";
$countStmt = mysqli_prepare($conn, $countQuery);
$totalCount = 0;
if ($countStmt) {
    $sentPattern = '%"type":"sent"%';
    mysqli_stmt_bind_param($countStmt, "s", $sentPattern);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    if ($countRow = mysqli_fetch_assoc($countResult)) {
        $totalCount = $countRow['total'] ?? 0;
    }
    mysqli_stmt_close($countStmt);
}

$query = "SELECT sms_id, phone AS phone, message_body, received_at, is_read, created_at 
          FROM sms_messages 
          WHERE raw_data IS NULL OR raw_data NOT LIKE ? 
          ORDER BY received_at DESC, created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
$smsMessages = [];

if ($stmt) {
    $sentPattern = '%"type":"sent"%';
    mysqli_stmt_bind_param($stmt, "sii", $sentPattern, $perPage, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $smsMessages[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$totalPages = ceil($totalCount / $perPage);
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
    <title>SMS Inbox - EVENZA Admin</title>
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
        .sms-message-item {
            background-color: #FFFFFF;
            border: 1px solid rgba(74, 93, 74, 0.1);
            border-radius: 20px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }
        .sms-message-item.unread {
            background-color: #F0F7F0;
            border-left: 4px solid #4A5D4A;
            font-weight: 600;
        }
        .sms-message-item:last-of-type {
            margin-bottom: 0;
        }
        
        /* Pagination Styling - Matching User Management */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
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
            background-color: #E0E0E0;
            color: #4A5D4A;
            border-color: #E0E0E0;
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
        .sms-phone {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #4A5D4A;
            margin-bottom: 0.5rem;
        }
        .sms-body {
            color: #1A1A1A;
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }
        .sms-date {
            color: rgba(26, 26, 26, 0.6);
            font-size: 0.875rem;
        }
        .unread-badge {
            display: inline-block;
            background-color: #4A5D4A;
            color: #FFFFFF;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
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
        }
        
        @media (max-width: 768px) {
            .admin-top-nav {
                padding: 0.75rem 1rem;
                flex-wrap: wrap;
            }
            .admin-top-nav h4 {
                font-size: clamp(1.1rem, 4vw, 1.5rem);
            }
            .sms-message-item {
                padding: 1rem;
            }
            .sms-phone {
                font-size: clamp(1rem, 3vw, 1.1rem);
            }
            .empty-state-message {
                font-size: clamp(1.25rem, 4vw, 1.5rem);
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
                        <a href="smsInbox.php" class="d-flex align-items-center py-3 px-3 rounded-3 active" style="background: linear-gradient(135deg, rgba(90, 107, 79, 0.15) 0%, rgba(90, 107, 79, 0.08) 100%); color: #5A6B4F; font-weight: 600; text-decoration: none; border-left: 3px solid #5A6B4F;">
                            <span class="me-3" style="width: 24px; text-align: center;"><i class="fas fa-sms"></i></span> 
                            <span>SMS Inbox</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="unread-badge"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
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
                        <h4 class="mb-0" style="font-family: 'Playfair Display', serif;">SMS Inbox</h4>
                        <div class="text-muted small">View and manage incoming SMS messages</div>
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
                <div class="admin-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <h5 class="mb-0" style="font-family: 'Playfair Display', serif;">Incoming Messages</h5>
                            <span class="badge bg-light text-dark" style="font-size: 0.9rem; padding: 0.4rem 0.8rem; border-radius: 50px; background-color: #f0f0f0 !important; font-weight: 600;">
                                <?php echo $totalCount; ?>
                            </span>
                        </div>
                        <?php if ($unreadCount > 0): ?>
                            <div class="text-muted small">
                                <span class="text-danger"><?php echo $unreadCount; ?> unread</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($smsMessages)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-state-message">No messages yet</div>
                            <div class="empty-state-subtext">Incoming SMS messages will appear here</div>
                        </div>
                    <?php else: ?>
                        <div class="sms-messages-list">
                            <?php foreach ($smsMessages as $sms): ?>
                                <div class="sms-message-item <?php echo $sms['is_read'] == 0 ? 'unread' : ''; ?>" 
                                     data-sms-id="<?php echo $sms['sms_id']; ?>"
                                     onclick="markAsRead(<?php echo $sms['sms_id']; ?>)">
                                    <div class="sms-phone">
                                        <i class="fas fa-phone me-2"></i>
                                        <?php echo htmlspecialchars(formatPhoneNumber($sms['phone'])); ?>
                                        <?php if ($sms['is_read'] == 0): ?>
                                            <span class="unread-badge">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sms-body">
                                        <?php echo nl2br(htmlspecialchars($sms['message_body'])); ?>
                                    </div>
                                    <div class="sms-date">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('F d, Y g:i A', strtotime($sms['received_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrapper">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">Prev</a>
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
                                <a href="?page=1" class="pagination-btn">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-btn" style="border: none; cursor: default;">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.js"></script>
    <script>
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

        function markAsRead(smsId) {
            fetch('/evenza/admin/process/sms/markSMSRead.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'smsId=' + encodeURIComponent(smsId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messageItem = document.querySelector(`[data-sms-id="${smsId}"]`);
                    if (messageItem) {
                        messageItem.classList.remove('unread');
                        const badge = messageItem.querySelector('.unread-badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error marking as read:', error);
            });
        }
    </script>
</body>
</html>

