<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();
require_once '../auth/adminAuth.php';
require_once '../../../core/connect.php';
require_once '../../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';

// Build query with filters
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

$query .= " ORDER BY userid ASC";

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
} else {
    $result = mysqli_query($conn, $query);
    if ($result) {
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
}

ob_clean();
echo json_encode(['success' => true, 'users' => $users]);
ob_end_flush();
exit;

