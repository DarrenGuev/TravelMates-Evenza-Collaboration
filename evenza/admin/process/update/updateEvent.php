<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();

$bufferBeforeConnect = ob_get_contents();
ob_clean();

require_once '../../../core/connect.php';

$bufferAfterConnect = ob_get_contents();
if (!empty(trim($bufferAfterConnect))) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check your database configuration.']);
    exit;
}

if (!isset($conn) || !$conn) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

if (mysqli_connect_errno()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . mysqli_connect_error()]);
    exit;
}

if (!isset($_SESSION['admin_id']) && !(isset($_SESSION['user_id']) && isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin'))) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$eventId = isset($_POST['eventId']) ? intval($_POST['eventId']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$venue = isset($_POST['venue']) ? trim($_POST['venue']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$imagePath = isset($_POST['imagePath']) ? trim($_POST['imagePath']) : '';

// Package data
$bronzePrice = isset($_POST['bronzePrice']) ? floatval($_POST['bronzePrice']) : null;
$silverPrice = isset($_POST['silverPrice']) ? floatval($_POST['silverPrice']) : null;
$goldPrice   = isset($_POST['goldPrice'])   ? floatval($_POST['goldPrice'])   : null;

$bronzeInclusions = isset($_POST['bronzeInclusions']) ? trim($_POST['bronzeInclusions']) : '';
$silverInclusions = isset($_POST['silverInclusions']) ? trim($_POST['silverInclusions']) : '';
$goldInclusions   = isset($_POST['goldInclusions'])   ? trim($_POST['goldInclusions'])   : '';

// Helper to normalize multiline inclusions into arrays
$toInclusionArray = function($text) {
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $clean = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }
    return $clean;
};

$bronzeList = $toInclusionArray($bronzeInclusions);
$silverList = $toInclusionArray($silverInclusions);
$goldList   = $toInclusionArray($goldInclusions);

if (empty($title)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if (empty($category)) {
    $category = 'Uncategorized';
}

// Validate package inputs
if ($bronzePrice === null || $silverPrice === null || $goldPrice === null ||
    $bronzePrice < 0 || $silverPrice < 0 || $goldPrice < 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Package prices are required and must be zero or greater.']);
    exit;
}

if (empty($bronzeList) || empty($silverList) || empty($goldList)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please provide inclusions for Bronze, Silver, and Gold packages.']);
    exit;
}

error_log("Updating event ID: $eventId, Category: $category");

try {
    mysqli_begin_transaction($conn);

    if ($eventId > 0) {
        $checkQuery = "SELECT eventId FROM events WHERE eventId = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "i", $eventId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $eventExists = mysqli_fetch_assoc($checkResult);
            mysqli_stmt_close($checkStmt);
            
            if (!$eventExists) {
                mysqli_rollback($conn);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit;
            }
        }
        
        $query = "UPDATE events SET title = ?, category = ?, venue = ?, description = ?, imagePath = ? WHERE eventId = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssi", $title, $category, $venue, $description, $imagePath, $eventId);
        } else {
            mysqli_rollback($conn);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement: ' . mysqli_error($conn)]);
            exit;
        }
    } else {
        $query = "INSERT INTO events (title, category, venue, description, imagePath) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $title, $category, $venue, $description, $imagePath);
        } else {
            mysqli_rollback($conn);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to prepare insert statement: ' . mysqli_error($conn)]);
            exit;
        }
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

$bufferContent = ob_get_contents();
if (!empty(trim($bufferContent))) {
    error_log('Unexpected output in buffer before JSON: ' . substr($bufferContent, 0, 200));
    ob_clean();
}

$response = null;
if ($stmt) {
    if (mysqli_stmt_execute($stmt)) {
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        $newEventId = $eventId;

        if ($eventId == 0 && $affectedRows > 0) {
            $newEventId = mysqli_insert_id($conn);
        }

        // Helper to upsert package prices (global table)
        $updatePackagePrice = function($tierName, $price) use ($conn) {
            $pkgName = ucfirst($tierName) . ' Package';
            // upsert by tier name (unique)
            $update = "UPDATE packages SET price = ? WHERE LOWER(packageName) = LOWER(?)";
            $stmt = mysqli_prepare($conn, $update);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ds", $price, $pkgName);
                mysqli_stmt_execute($stmt);
                $rows = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                if ($rows <= 0) {
                    $insert = "INSERT INTO packages (packageName, price) VALUES (?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)";
                    $insStmt = mysqli_prepare($conn, $insert);
                    if ($insStmt) {
                        mysqli_stmt_bind_param($insStmt, "sd", $pkgName, $price);
                        mysqli_stmt_execute($insStmt);
                        mysqli_stmt_close($insStmt);
                    } else {
                        throw new Exception('Failed to prepare package insert statement.');
                    }
                }
            } else {
                throw new Exception('Failed to prepare package update statement.');
            }
        };

        // Update/insert package prices
        try {
            $updatePackagePrice('bronze', $bronzePrice);
            $updatePackagePrice('silver', $silverPrice);
            $updatePackagePrice('gold', $goldPrice);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        // Maintain package inclusions (per event)
        if ($newEventId > 0) {
            // Clear existing inclusions for updates
            $deleteInc = mysqli_prepare($conn, "DELETE FROM package_inclusions WHERE eventId = ?");
            if ($deleteInc) {
                mysqli_stmt_bind_param($deleteInc, "i", $newEventId);
                mysqli_stmt_execute($deleteInc);
                mysqli_stmt_close($deleteInc);
            }

            $insertInc = mysqli_prepare($conn, "INSERT INTO package_inclusions (eventId, packageTier, inclusionText, displayOrder) VALUES (?, ?, ?, ?)");
            if ($insertInc) {
                $order = 1;
                $packages = [
                    ['Bronze', $bronzeList],
                    ['Silver', $silverList],
                    ['Gold', $goldList]
                ];
                foreach ($packages as $pkg) {
                    [$tier, $list] = $pkg;
                    foreach ($list as $incText) {
                        mysqli_stmt_bind_param($insertInc, "issi", $newEventId, $tier, $incText, $order);
                        if (!mysqli_stmt_execute($insertInc)) {
                            mysqli_stmt_close($insertInc);
                            mysqli_rollback($conn);
                            ob_end_clean();
                            echo json_encode(['success' => false, 'message' => 'Failed to save package inclusions.']);
                            exit;
                        }
                        $order++;
                    }
                }
                mysqli_stmt_close($insertInc);
            } else {
                mysqli_rollback($conn);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to prepare inclusions statement: ' . mysqli_error($conn)]);
                exit;
            }
        }

        mysqli_commit($conn);

        if ($eventId > 0) {
            $verifyQuery = "SELECT category FROM events WHERE eventId = ?";
            $verifyStmt = mysqli_prepare($conn, $verifyQuery);
            $verifiedCategory = $category;
            if ($verifyStmt) {
                mysqli_stmt_bind_param($verifyStmt, "i", $eventId);
                mysqli_stmt_execute($verifyStmt);
                $verifyResult = mysqli_stmt_get_result($verifyStmt);
                $updatedEvent = mysqli_fetch_assoc($verifyResult);
                mysqli_stmt_close($verifyStmt);
                if ($updatedEvent && isset($updatedEvent['category'])) {
                    $verifiedCategory = trim($updatedEvent['category']);
                    error_log("Verified category for event $eventId: $verifiedCategory");
                }
            }
            
            $response = json_encode([
                'success' => true, 
                'message' => 'Event updated successfully',
                'affectedRows' => $affectedRows,
                'category' => $verifiedCategory,
                'eventId' => $eventId
            ]);
        } else {
            if ($affectedRows > 0) {
                $response = json_encode([
                    'success' => true, 
                    'message' => 'Event added successfully',
                    'affectedRows' => $affectedRows,
                    'eventId' => $newEventId
                ]);
            } else {
                $response = json_encode(['success' => false, 'message' => 'Failed to insert event. No rows were added.']);
            }
        }
    } else {
        $errorMsg = mysqli_error($conn);
        mysqli_rollback($conn);
        $response = json_encode(['success' => false, 'message' => 'Database error: ' . $errorMsg]);
    }
    mysqli_stmt_close($stmt);
} else {
    $errorMsg = mysqli_error($conn);
    mysqli_rollback($conn);
    $response = json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $errorMsg]);
}

if ($response === null || $response === false) {
    $response = json_encode(['success' => false, 'message' => 'Unknown error occurred']);
}

$testParse = json_decode($response);
if ($testParse === null && json_last_error() !== JSON_ERROR_NONE) {
    $response = json_encode(['success' => false, 'message' => 'Error generating response']);
}

ob_end_clean();

if (!headers_sent()) {
    header('Content-Type: application/json', true);
}

echo $response;

if (ob_get_level() > 0) {
    ob_end_flush();
}

exit;
