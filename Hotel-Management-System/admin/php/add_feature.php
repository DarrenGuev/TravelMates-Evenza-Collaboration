<?php

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';
require_once __DIR__ . '/../../classes/autoload.php';

header('Content-Type: application/json');

$featureModel = new Feature();
$categoryModel = new FeatureCategory();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $featureName = isset($_POST['featureName']) ? trim($_POST['featureName']) : '';
    //this will support both categoryID (integer) and category (name string)
    $categoryID = 0;
    $categoryName = '';
    
    if (isset($_POST['categoryID']) && (int)$_POST['categoryID'] > 0) {
        $categoryID = (int)$_POST['categoryID'];
        $category = $categoryModel->find($categoryID);
        $categoryName = $category ? $category['categoryName'] : '';
    } elseif (isset($_POST['category']) && !empty(trim($_POST['category']))) {
        $categoryName = trim($_POST['category']);
        $category = $categoryModel->findByName($categoryName);
        if ($category) {
            $categoryID = (int)$category['categoryID'];
        }
    }
    
    if (empty($featureName)) {
        echo json_encode(['success' => false, 'error' => 'Feature name is required']);
        exit;
    }
    
    if ($categoryID <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid category is required']);
        exit;
    }
    
    $existingFeature = $featureModel->findByName($featureName); ///this will check if feature already exists
    
    if ($existingFeature) {
        $existingCategory = $categoryModel->find($existingFeature['categoryID']);
        $existingCategoryName = $existingCategory ? $existingCategory['categoryName'] : 'General';
        
        echo json_encode([
            'success' => false, 
            'error' => 'Feature already exists',
            'featureId' => $existingFeature['featureId'],
            'featureName' => $featureName,
            'categoryID' => $existingFeature['categoryID'],
            'category' => $existingCategoryName
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
            'category' => $categoryName,
            'message' => 'Feature added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['message'] ?? 'Failed to add feature']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
