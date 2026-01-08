<?php
/**
 * Email Dashboard - Admin Panel
 * 
 * Provides interface for viewing inbox, composing emails, and replying to user messages.
 */

session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Check if user is admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/login.php?error=Access denied");
    exit();
}

require_once GMAIL_PATH . '/EmailService.php';
require_once GMAIL_PATH . '/config.php';

$emailService = new EmailService();
$stats = $emailService->getStatistics();
$configStatus = get_gmail_config_status();

// Handle manual actions
$alertMessage = null;
$alertType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_email') {
        $to = trim($_POST['to_email']);
        $subject = trim($_POST['subject']);
        $body = trim($_POST['body']);
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = 'Please enter a valid email address.';
            $alertType = 'danger';
        } elseif (empty($subject)) {
            $alertMessage = 'Please enter a subject.';
            $alertType = 'danger';
        } elseif (empty($body)) {
            $alertMessage = 'Please enter a message body.';
            $alertType = 'danger';
        } else {
            $result = $emailService->sendEmail($to, $subject, $body);
            if ($result['success']) {
                $alertMessage = 'Email sent successfully!';
                $alertType = 'success';
            } else {
                $alertMessage = 'Failed to send email: ' . ($result['error'] ?? 'Unknown error');
                $alertType = 'danger';
            }
        }
        $stats = $emailService->getStatistics();
    } elseif ($_POST['action'] === 'sync_emails') {
        $result = $emailService->fetchEmails('INBOX', 50, 0);
        if ($result['success']) {
            $alertMessage = 'Emails synced successfully! Found ' . count($result['emails']) . ' emails.';
            $alertType = 'success';
        } else {
            $alertMessage = 'Failed to sync emails: ' . ($result['error'] ?? 'Unknown error');
            $alertType = 'danger';
        }
        $stats = $emailService->getStatistics();
    } elseif ($_POST['action'] === 'reply_email') {
        $emailId = (int)$_POST['email_id'];
        $replyBody = trim($_POST['reply_body']);
        
        if (empty($replyBody)) {
            $alertMessage = 'Please enter a reply message.';
            $alertType = 'danger';
        } else {
            $result = $emailService->replyToEmail($emailId, $replyBody);
            if ($result['success']) {
                $alertMessage = 'Reply sent successfully!';
                $alertType = 'success';
            } else {
                $alertMessage = 'Failed to send reply: ' . ($result['error'] ?? 'Unknown error');
                $alertType = 'danger';
            }
        }
    }
}

// Fetch emails from database for display
$filters = [];
if (!empty($_GET['direction'])) {
    $filters['direction'] = $_GET['direction'];
}
if (isset($_GET['is_read']) && $_GET['is_read'] !== '') {
    $filters['is_read'] = (int)$_GET['is_read'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$emailsResult = $emailService->getEmailsFromDatabase($filters, $limit, $offset);
$emails = $emailsResult['emails'] ?? [];
$totalEmails = $emailsResult['total'] ?? 0;
$totalPages = ceil($totalEmails / $limit);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Email Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body class="bg-light">
    <?php include INCLUDES_PATH . '/loader.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include ADMIN_INCLUDES_PATH . '/sidebar.php'; ?>
            <!-- Main Content -->
            <div class="col-lg-10 p-4">
                <!-- Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h2 class="fw-bold mb-1">Email Dashboard</h2>
                        <p class="text-muted mb-0">Manage emails and communicate with guests</p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="sync_emails">
                            <button type="submit" class="btn btn-outline-primary" <?php echo !$configStatus['is_authenticated'] ? 'disabled' : ''; ?>>
                                <i class="bi bi-arrow-repeat me-2"></i>Sync Inbox
                            </button>
                        </form>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal" <?php echo !$configStatus['is_authenticated'] ? 'disabled' : ''; ?>>
                            <i class="bi bi-pencil-square me-2"></i>Compose
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($alertMessage): ?>
                    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($alertMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Configuration Status -->
                <?php if (!$configStatus['oauth_configured']): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Gmail API not configured.</strong> Please set up your Google Cloud credentials in the .env file.
                        <a href="#" data-bs-toggle="modal" data-bs-target="#setupModal">View setup instructions</a>
                    </div>
                <?php elseif (!$configStatus['is_authenticated']): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Gmail not connected.</strong> You need to authorize the application to access your Gmail account.
                        <a href="<?php echo BASE_URL; ?>/integrations/gmail/googleLogin.php" class="btn btn-primary btn-sm ms-3">Connect Gmail Account</a>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-envelope-arrow-up display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['total_sent']); ?></h3>
                                <small>Total Sent</small>
                            </div>
                        </div>
                    </div>
                        <div class="col-6 col-md-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-envelope-arrow-down display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['total_received']); ?></h3>
                                <small>Total Received</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-envelope-exclamation display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['unread']); ?></h3>
                                <small>Unread</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-send display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['sent_today']); ?></h3>
                                <small>Sent Today</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-secondary text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-inbox display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['received_today']); ?></h3>
                                <small>Received Today</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-danger text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-x-circle display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo number_format($stats['failed']); ?></h3>
                                <small>Failed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email List Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-0"><i class="bi bi-inbox me-2"></i>Email Messages</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="GET" class="d-flex gap-2">
                                            <select name="direction" class="form-select form-select-sm">
                                                <option value="">All Emails</option>
                                                <option value="inbound" <?php echo ($_GET['direction'] ?? '') === 'inbound' ? 'selected' : ''; ?>>Received</option>
                                                <option value="outbound" <?php echo ($_GET['direction'] ?? '') === 'outbound' ? 'selected' : ''; ?>>Sent</option>
                                            </select>
                                            <select name="is_read" class="form-select form-select-sm">
                                                <option value="">All Status</option>
                                                <option value="0" <?php echo isset($_GET['is_read']) && $_GET['is_read'] === '0' ? 'selected' : ''; ?>>Unread</option>
                                                <option value="1" <?php echo isset($_GET['is_read']) && $_GET['is_read'] === '1' ? 'selected' : ''; ?>>Read</option>
                                            </select>
                                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($emails)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                                        <p class="text-muted mt-3">No emails found</p>
                                        <?php if ($configStatus['smtp_configured']): ?>
                                            <p class="text-muted">Click "Sync Inbox" to fetch emails from Gmail</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($emails as $email): ?>
                                            <div class="list-group-item email-list-item <?php echo !$email['is_read'] && $email['direction'] === 'inbound' ? 'unread' : ''; ?>" 
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#viewEmailModal"
                                                 data-email-id="<?php echo $email['id']; ?>"
                                                 data-email-from="<?php echo htmlspecialchars($email['from_email']); ?>"
                                                 data-email-to="<?php echo htmlspecialchars($email['to_email']); ?>"
                                                 data-email-subject="<?php echo htmlspecialchars($email['subject']); ?>"
                                                 data-email-date="<?php echo $email['email_date'] ?? $email['created_at']; ?>"
                                                 data-email-direction="<?php echo $email['direction']; ?>">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!$email['is_read'] && $email['direction'] === 'inbound'): ?>
                                                                <span class="badge bg-primary me-2">New</span>
                                                            <?php endif; ?>
                                                            <span class="badge bg-<?php echo $email['direction'] === 'inbound' ? 'success' : 'info'; ?> badge-direction me-2">
                                                                <?php echo $email['direction'] === 'inbound' ? 'Received' : 'Sent'; ?>
                                                            </span>
                                                            <span class="text-truncate">
                                                                <?php echo htmlspecialchars($email['direction'] === 'inbound' ? $email['from_email'] : $email['to_email']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-sm-12 mb-2 mb-md-0">
                                                        <div class="fw-medium text-truncate"><?php echo htmlspecialchars($email['subject'] ?: '(No Subject)'); ?></div>
                                                        <div class="email-preview"><?php echo htmlspecialchars(substr($email['body'] ?? '', 0, 100)); ?></div>
                                                    </div>
                                                    <div class="col-md-2 col-sm-6">
                                                        <span class="email-date"><?php echo date('M d, Y H:i', strtotime($email['email_date'] ?? $email['created_at'])); ?></span>
                                                    </div>
                                                    <div class="col-md-1 col-sm-6 text-end">
                                                        <span class="badge bg-<?php echo $email['status'] === 'sent' || $email['status'] === 'received' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($email['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($totalPages > 1): ?>
                                        <div class="card-footer bg-white">
                                            <nav>
                                                <ul class="pagination justify-content-center mb-0">
                                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">Previous</a>
                                                    </li>
                                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">Next</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals included from external files -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/emailModals/composeModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/emailModals/viewEmailModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/emailModals/replyModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/emailModals/setupModal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="javascript/email.js"></script>
</body>

</html>