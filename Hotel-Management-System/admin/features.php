<?php
// Include configuration file
require_once __DIR__ . '/../config.php';

// Include class autoloader
require_once __DIR__ . '/../classes/autoload.php';
Auth::startSession();
Auth::requireAdmin('../frontend/login.php');

$featureModel = new Feature();
$categoryModel = new FeatureCategory();

// Handle form submissions and store messages in session
if (isset($_POST['add_feature'])) {
    $featureName = trim($_POST['featureName']);
    $categoryID = isset($_POST['categoryID']) ? (int)$_POST['categoryID'] : 0;

    if (!empty($featureName) && $categoryID > 0) {
        $result = $featureModel->addFeature($featureName, $categoryID);
        Auth::setAlert($result['success'] ? 'success' : 'danger', $result['message']);
    } else {
        Auth::setAlert('danger', 'Feature name and category are required');
    }
    header("Location: features.php");
    exit();
}

if (isset($_POST['deleteFeatureId'])) {
    $deleteFeatureId = (int)$_POST['deleteFeatureId'];
    $result = $featureModel->deleteFeature($deleteFeatureId);
    Auth::setAlert($result['success'] ? 'success' : 'danger', $result['message']);
    header("Location: features.php");
    exit();
}

if (isset($_POST['update_feature'])) {
    $featureId = (int)$_POST['featureId'];
    $featureName = trim($_POST['editFeatureName']);
    $categoryID = isset($_POST['editCategoryID']) ? (int)$_POST['editCategoryID'] : 0;

    if ($featureId && !empty($featureName) && $categoryID > 0) {
        $result = $featureModel->updateFeature($featureId, $featureName, $categoryID);
        Auth::setAlert($result['success'] ? 'success' : 'danger', $result['message']);
    } else {
        Auth::setAlert('danger', 'Feature name and category are required');
    }
    header("Location: features.php");
    exit();
}

// Handle adding new category
if (isset($_POST['add_category'])) {
    $newCategory = trim($_POST['newCategory']);
    $result = $categoryModel->addCategory($newCategory);
    Auth::setAlert($result['success'] ? 'success' : ($result['message'] === "Category \"{$newCategory}\" already exists!" ? 'warning' : 'danger'), $result['message']);
    header("Location: features.php");
    exit();
}

// Handle deleting category
if (isset($_POST['delete_category'])) {
    $deleteCategoryID = (int)$_POST['deleteCategoryID'];
    $result = $categoryModel->deleteCategory($deleteCategoryID);
    $alertType = $result['success'] ? 'success' : (strpos($result['message'], 'Cannot delete') !== false ? 'warning' : 'danger');
    Auth::setAlert($alertType, $result['message']);
    header("Location: features.php");
    exit();
}

// Handle renaming category
if (isset($_POST['rename_category'])) {
    $categoryID = (int)$_POST['categoryID'];
    $newCategoryName = trim($_POST['newCategoryName']);
    $result = $categoryModel->renameCategory($categoryID, $newCategoryName);
    $alertType = $result['success'] ? 'success' : (strpos($result['message'], 'already exists') !== false ? 'warning' : 'danger');
    Auth::setAlert($alertType, $result['message']);
    header("Location: features.php");
    exit();
}

// Get all features and categories
$features = $featureModel->getAllOrdered();
$categoryList = $categoryModel->getAllOrdered();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Features Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/css/admin.css">
</head>

<body class="bg-light">
    <?php include INCLUDES_PATH . '/loader.php'; ?>

    <!-- Alert Message Container -->
    <?php $alert = Auth::getAlert(); if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
            role="alert" style="z-index: 99999; max-width: 600px; width: calc(100% - 2rem);" id="autoAlert">
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include ADMIN_INCLUDES_PATH . '/sidebar.php'; ?>

            <div class="col-12 col-lg-10 p-3 p-lg-4">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Room Features</h2>
                        <p>Manage room features and amenities</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeatureModal">
                        <i class="bi bi-plus-lg me-2"></i>Add Feature
                    </button>
                </div>

                <!-- Category Filter Tabs -->
                <div class="card mb-4">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" id="categoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-category="All" type="button" role="tab" onclick="filterFeatures('All')">
                                    All Features
                                </button>
                            </li>
                            <?php 
                            // Get categories with feature counts using Feature model
                            $allFeaturesList = $featureModel->getAllOrdered();
                            $categoriesWithCount = [];
                            foreach ($allFeaturesList as $feat) {
                                $cat = $feat['categoryName'] ?? 'General';
                                $categoriesWithCount[$cat] = ($categoriesWithCount[$cat] ?? 0) + 1;
                            }
                            
                            foreach ($categoryList as $cat) { 
                                $catName = $cat['categoryName'];
                                $count = $categoriesWithCount[$catName] ?? 0;
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-category="<?php echo htmlspecialchars($catName, ENT_QUOTES); ?>" type="button" role="tab" onclick="filterFeatures(this.getAttribute('data-category'))">
                                        <?php echo htmlspecialchars($catName); ?>
                                        <span class="badge bg-secondary ms-1"><?php echo $count; ?></span>
                                    </button>
                                </li>
                            <?php } ?>
                            <li class="nav-item">
                                <button class="nav-link text-success" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add Category
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-danger" type="button" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
                                    <i class="bi bi-pencil-square me-1"></i>Manage Categories
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-body">
                        <!-- Pagination Info & Controls -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted" id="paginationInfo">
                                Showing <span id="showingStart">1</span>-<span id="showingEnd">7</span> of <span id="totalFeatures">0</span> features
                            </div>
                            <nav aria-label="Feature pagination">
                                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                    <!-- Pagination buttons will be generated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Category</th>
                                        <th class="text-center">Feature Name</th>
                                        <th class="text-center" style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="featuresTableBody">
                                    <?php foreach ($features as $row) { ?>
                                        <tr data-category="<?php echo htmlspecialchars($row['categoryName'] ?? 'General', ENT_QUOTES); ?>">
                                            <td class="text-center"><?php echo (int)$row['featureId']; ?></td>
                                            <td class="text-center"><span class="badge bg-info"><?php echo htmlspecialchars($row['categoryName'] ?? 'General'); ?></span></td>
                                            <td class="text-center"><?php echo htmlspecialchars($row['featureName']); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editFeatureModal<?php echo (int)$row['featureId']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteFeature(<?php echo (int)$row['featureId']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Hidden delete form -->
                                                <form method="POST" id="deleteFeatureForm<?php echo (int)$row['featureId']; ?>" style="display:none;">
                                                    <input type="hidden" name="deleteFeatureId" value="<?php echo (int)$row['featureId']; ?>">
                                                </form>

                                                <!-- INCLUDED MODAL inside loop -->
                                                <?php include ADMIN_INCLUDES_PATH . '/modals/featureModals/editFeatureModal.php'; ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <!-- Empty State Row (hidden by default, shown when no features in category) -->
                                    <tr id="noFeaturesRow" style="display: none;">
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                <h5>No Features Found</h5>
                                                <p class="mb-0" id="noFeaturesMessage">There are no features in this category yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Bottom Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted" id="paginationInfoBottom">
                                Showing <span id="showingStartBottom">1</span>-<span id="showingEndBottom">7</span> of <span id="totalFeaturesBottom">0</span> features
                            </div>
                            <nav aria-label="Feature pagination bottom">
                                <ul class="pagination pagination-sm mb-0" id="paginationControlsBottom">
                                    <!-- Pagination buttons will be generated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals included from external files -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/featureModals/addFeatureModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/featureModals/addCategoryModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/featureModals/manageCategoriesModal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo JS_URL; ?>/autoDismiss.js"></script>
    <script src="javascript/features.js"></script>
</body>

</html>
