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
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'General';
    
    if (empty($featureName)) {
        echo json_encode(['success' => false, 'error' => 'Feature name is required']);
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
            'category' => $existingFeature['category']
        ]);
        exit;
    }
    
    // Insert new feature with category using model
    $result = $featureModel->addFeature($featureName, $category);
    
    if ($result['success'] && $result['id']) {
        echo json_encode([
            'success' => true,
            'featureId' => $result['id'],
            'featureName' => $featureName,
            'category' => $category,
            'message' => 'Feature added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['message'] ?? 'Failed to add feature']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
