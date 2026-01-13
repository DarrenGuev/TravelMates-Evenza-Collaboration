<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

Auth::requireAdmin('../frontend/login.php');

require_once SMS_PATH . '/SmsService.php';

$bookingModel = new Booking();
$smsService = new SmsService();
$stats = $smsService->getStatistics();

$alertMessage = null;
$alertType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_sms') {
        $phoneNumber = trim($_POST['phone_number']);
        $message = trim($_POST['message']);
        $bookingId = !empty($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;

        if (empty($phoneNumber) || !preg_match('/^\d+$/', $phoneNumber)) {
            $alertMessage = 'Please enter a valid phone number (numbers only).';
            $alertType = 'danger';
        } elseif ($message !== 'Your Booking is Approved.') {
            $alertMessage = 'You must enter "Your Booking is Approved." as the message.';
            $alertType = 'danger';
        } else {
            $result = $smsService->sendCustomSms($phoneNumber, $message, $bookingId);
            if ($result['success']) {
                $alertMessage = 'SMS sent successfully!';
                $alertType = 'success';
            } else {
                $alertMessage = 'Failed to send SMS: ' . $result['error'];
                $alertType = 'danger';
            }
        }
        $stats = $smsService->getStatistics();
    }
}

$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['direction'])) {
    $filters['direction'] = $_GET['direction'];
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get logs
$logs = $smsService->getSmsLogs($filters, $limit, $offset);
$totalCount = $smsService->getTotalCount($filters);
$totalPages = ceil($totalCount / $limit);

// Get bookings for dropdown using Booking model
$pendingAndConfirmedBookings = $bookingModel->getByStatusWithDetails([Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED]);
$bookings = array_slice($pendingAndConfirmedBookings, 0, 50);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - SMS Dashboard</title>
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
            <div class="col-12 col-lg-10 p-3 p-lg-4">
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <div class="mb-3 mb-md-0">
                                <h2 class="fw-bold mb-1">SMS Dashboard</h2>
                                <p class="text-muted mb-0">Manage SMS notifications and view message logs</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo BASE_URL; ?>/integrations/sms/test_connection.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-gear me-1"></i>Test Connection
                                </a>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
                                    <i class="bi bi-send me-1"></i>Send SMS
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($alertMessage): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($alertMessage); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-send-check display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo $stats['total_sent']; ?></h3>
                                <small>Total Sent</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-inbox display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo $stats['total_received']; ?></h3>
                                <small>Total Received</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-danger text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-x-circle display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo $stats['total_failed']; ?></h3>
                                <small>Failed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body text-center py-3">
                                <i class="bi bi-calendar-check display-6"></i>
                                <h3 class="fw-bold mt-2 mb-0"><?php echo $stats['today_sent']; ?></h3>
                                <small>Sent Today</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body py-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="sent" <?php echo (isset($_GET['status']) && $_GET['status'] === 'sent') ? 'selected' : ''; ?>>Sent</option>
                                    <option value="failed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                    <option value="received" <?php echo (isset($_GET['status']) && $_GET['status'] === 'received') ? 'selected' : ''; ?>>Received</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small mb-1">Direction</label>
                                <select name="direction" class="form-select form-select-sm">
                                    <option value="">All Directions</option>
                                    <option value="outgoing" <?php echo (isset($_GET['direction']) && $_GET['direction'] === 'outgoing') ? 'selected' : ''; ?>>Outgoing</option>
                                    <option value="incoming" <?php echo (isset($_GET['direction']) && $_GET['direction'] === 'incoming') ? 'selected' : ''; ?>>Incoming</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <a href="smsDashboard.php" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SMS Logs Table -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>SMS Logs</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Phone Number</th>
                                        <th>Message</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Direction</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                                No SMS logs found
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr class="sms-log-item">
                                                <td><small class="text-muted">#<?php echo $log['id']; ?></small></td>
                                                <td><code><?php echo htmlspecialchars($log['phone_number']); ?></code></td>
                                                <td>
                                                    <span class="message-preview" title="<?php echo htmlspecialchars($log['message']); ?>">
                                                        <?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?>
                                                        <?php echo strlen($log['message']) > 50 ? '...' : ''; ?>
                                                    </span>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['message_type']); ?></span></td>
                                                <td>
                                                    <?php
                                                    $statusClass = match ($log['status']) {
                                                        'sent' => 'badge-sent',
                                                        'failed' => 'badge-failed',
                                                        'received' => 'badge-received',
                                                        default => 'badge-pending'
                                                    };
                                                    ?>
                                                    <span class="text-dark badge <?php echo $statusClass; ?>"><?php echo ucfirst($log['status']); ?></span>
                                                </td>
                                                <td>
                                                    <i class="bi <?php echo $log['direction'] === 'outgoing' ? 'bi-arrow-up-right text-success' : 'bi-arrow-down-left text-primary'; ?>"></i>
                                                    <?php echo ucfirst($log['direction']); ?>
                                                </td>
                                                <td><small><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></small></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewLogModal<?php echo $log['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <?php 
                                            // INCLUDED MODAL inside the loop
                                            include ADMIN_INCLUDES_PATH . '/modals/smsModals/viewLogModal.php'; 
                                            ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards View -->
                        <div class="d-md-none">
                            <?php if (empty($logs)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No SMS logs found
                                </div>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <div class="border-bottom p-3 sms-log-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <code class="small"><?php echo htmlspecialchars($log['phone_number']); ?></code>
                                                <small class="text-muted ms-2">#<?php echo $log['id']; ?></small>
                                            </div>
                                            <?php
                                            $statusClass = match ($log['status']) {
                                                'sent' => 'badge-sent',
                                                'failed' => 'badge-failed',
                                                'received' => 'badge-received',
                                                default => 'badge-pending'
                                            };
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($log['status']); ?></span>
                                        </div>
                                        <p class="mb-2 small"><?php echo htmlspecialchars(substr($log['message'], 0, 80)); ?><?php echo strlen($log['message']) > 80 ? '...' : ''; ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi <?php echo $log['direction'] === 'outgoing' ? 'bi-arrow-up-right' : 'bi-arrow-down-left'; ?> me-1"></i>
                                                <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                                            </small>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewLogModal<?php echo $log['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $_GET['status'] ?? ''; ?>&direction=<?php echo $_GET['direction'] ?? ''; ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $_GET['status'] ?? ''; ?>&direction=<?php echo $_GET['direction'] ?? ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $_GET['status'] ?? ''; ?>&direction=<?php echo $_GET['direction'] ?? ''; ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2 mb-0">
                                Showing <?php echo count($logs); ?> of <?php echo $totalCount; ?> records
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals included from external file -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/smsModals/sendSmsModal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="javascript/sms.js"></script>
</body>

</html>