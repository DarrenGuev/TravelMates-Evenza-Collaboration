<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();
require_once '../../../core/connect.php';

if (!isset($_SESSION['admin_id']) && !(isset($_SESSION['user_id']) && isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin'))) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

$eventId = isset($_GET['eventId']) ? intval($_GET['eventId']) : 0;

if ($eventId <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    ob_end_flush();
    exit;
}

$query = "SELECT * FROM events WHERE eventId = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    ob_clean();
    
    if ($event) {
        // Fetch package inclusions for this event
        $packages = [
            'bronze' => ['price' => null, 'inclusions' => ''],
            'silver' => ['price' => null, 'inclusions' => ''],
            'gold'   => ['price' => null, 'inclusions' => '']
        ];

        $incQuery = "SELECT packageTier, inclusionText FROM package_inclusions WHERE eventId = ? ORDER BY displayOrder ASC, inclusionId ASC";
        $incStmt = mysqli_prepare($conn, $incQuery);
        if ($incStmt) {
            mysqli_stmt_bind_param($incStmt, "i", $eventId);
            mysqli_stmt_execute($incStmt);
            $incResult = mysqli_stmt_get_result($incStmt);
            while ($row = mysqli_fetch_assoc($incResult)) {
                $tierKey = strtolower($row['packageTier']);
                if (isset($packages[$tierKey])) {
                    if (!isset($packages[$tierKey]['inclusionList'])) {
                        $packages[$tierKey]['inclusionList'] = [];
                    }
                    $packages[$tierKey]['inclusionList'][] = $row['inclusionText'];
                }
            }
            mysqli_stmt_close($incStmt);
        }

        // Fetch package prices (global packages table)
        $priceQuery = "SELECT packageName, price FROM packages WHERE LOWER(packageName) IN ('bronze package','silver package','gold package')";
        $priceResult = mysqli_query($conn, $priceQuery);
        if ($priceResult) {
            while ($row = mysqli_fetch_assoc($priceResult)) {
                $name = strtolower($row['packageName']);
                if (strpos($name, 'bronze') !== false) {
                    $packages['bronze']['price'] = $row['price'];
                } elseif (strpos($name, 'silver') !== false) {
                    $packages['silver']['price'] = $row['price'];
                } elseif (strpos($name, 'gold') !== false) {
                    $packages['gold']['price'] = $row['price'];
                }
            }
            mysqli_free_result($priceResult);
        }

        // Flatten inclusion lists to newline text for the form
        foreach ($packages as $key => $pkg) {
            $list = isset($pkg['inclusionList']) ? $pkg['inclusionList'] : [];
            $packages[$key]['inclusions'] = implode("\n", $list);
        }

        echo json_encode(['success' => true, 'data' => $event, 'packages' => $packages]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

ob_end_flush();
exit;
