<?php

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';
require_once __DIR__ . '/../../classes/autoload.php';

header('Content-Type: application/json');

$featureModel = new Feature();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $featureName = isset($_POST['featureName']) ? trim($_POST['featureName']) : '';
    $categoryID = isset($_POST['categoryID']) ? (int)$_POST['categoryID'] : 0;
    
    if (empty($featureName)) {
        echo json_encode(['success' => false, 'error' => 'Feature name is required']);
        exit;
    }
    
    if ($categoryID <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid category is required']);
        exit;
    }
    
    // Check if feature already exists
    $existingFeature = $featureModel->findByName($featureName);
    
    if ($existingFeature) {
        echo json_encode([
            'success' => false, 
            'error' => 'Feature already exists',
            'featureId' => $existingFeature['featureId'],
            'featureName' => $featureName,
            'categoryID' => $existingFeature['categoryID']
        ]);
        exit;
    }
    
    // Insert new feature with categoryID using model
    $result = $featureModel->addFeature($featureName, $categoryID);
    
    if ($result['success'] && $result['id']) {
        echo json_encode([
            'success' => true,
            'featureId' => $result['id'],
            'featureName' => $featureName,
            'categoryID' => $categoryID,
            'message' => 'Feature added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['message'] ?? 'Failed to add feature']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
