<?php
header('Content-Type: application/json');
session_start();
require_once '../../../core/connect.php';
require_once '../auth/adminAuth.php';

if (!isset($_SESSION['admin_id']) && !(isset($_SESSION['user_id']) && isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin'))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stats = [];

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
        $stats['totalRevenue'] = 0;
    }

    $ticketsQuery = "SELECT COUNT(*) as totalTickets FROM reservations";
    $ticketsResult = mysqli_query($conn, $ticketsQuery);
    if ($ticketsResult) {
        $ticketsRow = mysqli_fetch_assoc($ticketsResult);
        $stats['totalTicketsSold'] = intval($ticketsRow['totalTickets'] ?? 0);
        mysqli_free_result($ticketsResult);
    } else {
        $stats['totalTicketsSold'] = 0;
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
        $stats['activeEvents'] = 0;
    }

    $newUsersQuery = "SELECT COUNT(*) as newUsers 
                      FROM users 
                      WHERE (role != 'Admin' OR role IS NULL)";
    $newUsersResult = mysqli_query($conn, $newUsersQuery);
    if ($newUsersResult) {
        $newUsersRow = mysqli_fetch_assoc($newUsersResult);
        $stats['newUsers'] = intval($newUsersRow['newUsers'] ?? 0);
        mysqli_free_result($newUsersResult);
    } else {
        $stats['newUsers'] = 0;
    }

    $topEventsQuery = "
        SELECT 
            e.eventId,
            e.title,
            COUNT(r.reservationId) as ticketsSold,
            COALESCE(SUM(r.totalAmount), 0) as revenue,
            COUNT(r.reservationId) as capacity
        FROM events e
        LEFT JOIN reservations r ON e.eventId = r.eventId AND LOWER(r.status) != 'cancelled'
        GROUP BY e.eventId, e.title
        ORDER BY ticketsSold DESC, revenue DESC
        LIMIT 5
    ";
    $topEventsResult = mysqli_query($conn, $topEventsQuery);
    $topEvents = [];
    if ($topEventsResult) {
        while ($row = mysqli_fetch_assoc($topEventsResult)) {
            $topEvents[] = [
                'eventId' => intval($row['eventId']),
                'title' => $row['title'],
                'ticketsSold' => intval($row['ticketsSold']),
                'revenue' => floatval($row['revenue']),
                'capacity' => intval($row['capacity'])
            ];
        }
        mysqli_free_result($topEventsResult);
    }
    $stats['topEvents'] = $topEvents;

    $recentActivityQuery = "
        SELECT 
            r.reservationId,
            r.createdAt,
            r.totalAmount,
            u.fullName as userName,
            e.title as eventName,
            p.packageName
        FROM reservations r
        LEFT JOIN users u ON r.userId = u.userId
        LEFT JOIN events e ON r.eventId = e.eventId
        LEFT JOIN packages p ON r.packageId = p.packageId
        ORDER BY r.createdAt DESC
        LIMIT 5
    ";
    $recentActivityResult = mysqli_query($conn, $recentActivityQuery);
    $recentActivity = [];
    if ($recentActivityResult) {
        while ($row = mysqli_fetch_assoc($recentActivityResult)) {
            $recentActivity[] = [
                'userName' => $row['userName'] ?? 'Unknown User',
                'eventName' => $row['eventName'] ?? 'Unknown Event',
                'packageName' => $row['packageName'] ?? 'N/A',
                'createdAt' => $row['createdAt'],
                'totalAmount' => floatval($row['totalAmount'] ?? 0)
            ];
        }
        mysqli_free_result($recentActivityResult);
    }
    $stats['recentActivity'] = $recentActivity;

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
        $revenueTrend = (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        $stats['revenueTrend'] = round($revenueTrend, 1);
    } else {
        $stats['revenueTrend'] = $currentMonthRevenue > 0 ? 100 : 0;
    }

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard statistics: ' . $e->getMessage()
    ]);
}
?>

