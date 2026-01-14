<?php
/**
 * Reusable Admin Header Component
 * 
 * @param string $pageTitle - Main page title (e.g., "Event Management")
 * @param string $pageSubtitle - Subtitle/description (e.g., "Manage all events and their details")
 */
if (!isset($pageTitle) || !isset($pageSubtitle)) {
    // Default values if not provided
    $pageTitle = $pageTitle ?? 'Dashboard';
    $pageSubtitle = $pageSubtitle ?? 'Overview of activity and performance';
}
?>
<!-- Top Navigation Bar -->
<div class="admin-top-nav d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <div class="me-3 d-xl-none">
            <button id="adminSidebarToggle" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; padding: 0.5rem 0.75rem;">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div>
            <h4 class="mb-0 admin-page-title"><?php echo htmlspecialchars($pageTitle); ?></h4>
            <div class="admin-page-subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center admin-user-icon">
                <i class="fas fa-user text-muted"></i>
            </div>
        </div>
        <a href="../../user/process/logout.php?type=admin" class="btn btn-admin-primary btn-sm admin-logout-btn">Logout</a>
    </div>
</div>

<style>
/* Admin Header Styles */
.admin-top-nav {
    background-color: #FFFFFF;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(74, 93, 74, 0.08);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

/* Left section: Hamburger + Title/Subtitle - Left aligned */
.admin-top-nav > div:first-child {
    display: flex;
    align-items: center;
    flex: 1;
}

/* Title and subtitle container - Left aligned */
.admin-top-nav > div:first-child > div:last-child {
    text-align: left;
}

.admin-page-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1A1A1A;
    line-height: 1.2;
    margin-bottom: 0.25rem;
    text-align: left;
}

.admin-page-subtitle {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 0.875rem;
    color: rgba(26, 26, 26, 0.6);
    line-height: 1.4;
    text-align: left;
}

/* Right section: User icon + Logout button - Vertically centered */
.admin-top-nav > div:last-child {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.admin-user-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-logout-btn {
    margin-right: 0;
    white-space: nowrap;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .admin-top-nav {
        padding: 1.25rem 1.5rem;
        flex-wrap: nowrap;
    }
    
    .admin-page-title {
        font-size: clamp(1.1rem, 4vw, 1.5rem);
    }
    
    .admin-page-subtitle {
        font-size: 0.8rem;
    }
    
    .admin-logout-btn {
        margin-right: 0.5rem;
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
    }
    
    .admin-user-icon {
        width: 36px;
        height: 36px;
    }
}

@media (max-width: 576px) {
    .admin-top-nav {
        padding: 1.25rem 1rem;
    }
    
    .admin-top-nav > div:first-child {
        flex: 1;
        min-width: 0;
        align-items: center;
    }
    
    .admin-top-nav > div:last-child {
        flex-shrink: 0;
        align-items: center;
    }
    
    .admin-page-title {
        font-size: clamp(1rem, 4vw, 1.3rem);
    }
    
    .admin-page-subtitle {
        font-size: 0.75rem;
    }
    
    .admin-logout-btn {
        margin-right: 0.5rem;
        padding: 0.45rem 0.75rem;
        font-size: 0.8rem;
    }
    
    .admin-user-icon {
        width: 32px;
        height: 32px;
    }
}
</style>

