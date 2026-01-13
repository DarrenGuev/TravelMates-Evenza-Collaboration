<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../dbconnect/connect.php';
require_once __DIR__ . '/../classes/autoload.php';

Auth::startSession();
Auth::requireAdmin('../frontend/login.php');

$roomModel = new Room();
$roomTypeModel = new RoomType();
$featureModel = new Feature();
$featureCategoryModel = new FeatureCategory();
$featureCategories = $featureCategoryModel->getAllOrdered();

$imageUpload = new ImageUpload('assets/');

if (isset($_POST['add_room'])) {
    $roomName = trim($_POST['roomName']);
    $roomTypeId = (int)$_POST['roomTypeId'];
    $capacity = (int)$_POST['capacity'];
    $quantity = (int)$_POST['quantity'];
    $basePrice = (float)$_POST['base_price'];
    $selectedFeatures = isset($_POST['features']) ? $_POST['features'] : [];

    // Handle image upload using ImageUpload class
    $uploadResult = $imageUpload->upload('roomImage');
    $fileName = $uploadResult['fileName'];

    if (!$uploadResult['success'] && isset($_FILES['roomImage']) && $_FILES['roomImage']['error'] !== UPLOAD_ERR_NO_FILE) {
        Auth::setAlert('danger', 'Image upload failed: ' . htmlspecialchars($uploadResult['error']));
    } elseif (!empty($roomName) && !empty($roomTypeId)) {
        $roomData = [
            'roomName' => $roomName,
            'roomTypeId' => $roomTypeId,
            'capacity' => $capacity,
            'quantity' => $quantity,
            'base_price' => $basePrice,
            'imagePath' => $fileName
        ];
        
        $newRoomID = $roomModel->addRoom($roomData);
        
        if ($newRoomID) {
            $roomModel->setFeatures($newRoomID, $selectedFeatures);
            Auth::setAlert('success', 'Room Added Successfully!');
        } else {
            Auth::setAlert('danger', 'Error adding room.');
        }
    }
    header("Location: rooms.php");
    exit();
}

if (isset($_POST['deleteID'])) {
    $deleteID = (int)$_POST['deleteID'];
    
    // Get and delete the image
    $imagePath = $roomModel->getImagePath($deleteID);
    if ($imagePath) {
        $imageUpload->delete($imagePath);
    }

    if ($roomModel->deleteRoom($deleteID)) {
        Auth::setAlert('success', 'Room deleted successfully!');
    } else {
        Auth::setAlert('danger', 'Error deleting room.');
    }
    header("Location: rooms.php");
    exit();
}

if (isset($_POST['update_room'])) {
    $roomID = (int)$_POST['roomID'];
    $roomName = trim($_POST['editRoomName']);
    $roomTypeId = (int)$_POST['editRoomTypeId'];
    $capacity = (int)$_POST['editCapacity'];
    $quantity = (int)$_POST['editQuantity'];
    $basePrice = (float)$_POST['editBasePrice'];
    $selectedFeatures = isset($_POST['editFeatures']) ? $_POST['editFeatures'] : [];

    if (!empty($roomID) && !empty($roomName) && !empty($roomTypeId) && is_numeric($capacity) && is_numeric($quantity) && is_numeric($basePrice)) {
        $updateData = [
            'roomName' => $roomName,
            'roomTypeId' => $roomTypeId,
            'capacity' => $capacity,
            'quantity' => $quantity,
            'base_price' => $basePrice
        ];

        if (isset($_FILES['editRoomImage']) && $_FILES['editRoomImage']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $imageUpload->upload('editRoomImage');

            if ($uploadResult['success']) {
                // Delete old image
                $oldImagePath = $roomModel->getImagePath($roomID);
                if ($oldImagePath) {
                    $imageUpload->delete($oldImagePath);
                }
                $updateData['imagePath'] = $uploadResult['fileName'];
            } else {
                Auth::setAlert('warning', 'Image upload failed: ' . htmlspecialchars($uploadResult['error']) . '. Other details were updated.');
            }
        }

        if ($roomModel->updateRoom($roomID, $updateData)) {
            $roomModel->setFeatures($roomID, $selectedFeatures);
            if (!Auth::hasAlert()) {
                Auth::setAlert('success', 'Room updated successfully.');
            }
        } else {
            Auth::setAlert('danger', 'Error updating room.');
        }
    }
    header("Location: rooms.php");
    exit();
}

// Handle adding new room type
if (isset($_POST['add_room_type'])) {
    $newRoomType = trim($_POST['newRoomType']);
    $result = $roomTypeModel->addRoomType($newRoomType);
    $alertType = $result['success'] ? 'success' : (strpos($result['message'], 'already exists') !== false ? 'warning' : 'danger');
    Auth::setAlert($alertType, $result['message']);
    header("Location: rooms.php");
    exit();
}

// Handle deleting room type
if (isset($_POST['delete_room_type'])) {
    $deleteTypeID = (int)$_POST['deleteRoomTypeID'];
    $result = $roomTypeModel->deleteRoomType($deleteTypeID);
    $alertType = $result['success'] ? 'success' : (strpos($result['message'], 'Cannot delete') !== false ? 'warning' : 'danger');
    Auth::setAlert($alertType, $result['message']);
    header("Location: rooms.php");
    exit();
}

// Handle updating room type name
if (isset($_POST['update_room_type'])) {
    $updateTypeID = (int)$_POST['updateRoomTypeID'];
    $newTypeName = trim($_POST['updateRoomTypeName']);
    $result = $roomTypeModel->updateRoomType($updateTypeID, $newTypeName);
    $alertType = $result['success'] ? 'success' : (strpos($result['message'], 'already exists') !== false ? 'warning' : 'danger');
    Auth::setAlert($alertType, $result['message']);
    header("Location: rooms.php");
    exit();
}

// Get data using model classes
$rooms = $roomModel->getAllWithType();
$roomTypes = $roomTypeModel->getAllOrdered();
$featuresByCategory = $featureModel->getAllGroupedByCategory();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates - Rooms Management</title>
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
                        <h2>Rooms Management</h2>
                        <p>Manage hotel rooms and their details</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="bi bi-plus-lg me-2"></i>Add Room
                    </button>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" id="roomTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-room-type="All" type="button" role="tab" onclick="filterRooms('All')">
                                    All Rooms
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-room-type="Basic" type="button" role="tab" onclick="filterRooms('Basic')">
                                    Basic
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-room-type="Family" type="button" role="tab" onclick="filterRooms('Family')">
                                    Family
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-room-type="Suite" type="button" role="tab" onclick="filterRooms('Suite')">
                                    Suite
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-room-type="Deluxe" type="button" role="tab" onclick="filterRooms('Deluxe')">
                                    Deluxe
                                </button>
                            </li>
                            <?php 
                            // Dynamically add tabs for any additional room types beyond the default ones
                            $defaultTypes = ['Basic', 'Family', 'Suite', 'Deluxe'];
                            foreach ($roomTypes as $type) {
                                if (!in_array($type['roomType'], $defaultTypes)) { ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-room-type="<?php echo htmlspecialchars($type['roomType']); ?>" type="button" role="tab" onclick="filterRooms('<?php echo htmlspecialchars($type['roomType']); ?>')">
                                            <?php echo htmlspecialchars($type['roomType']); ?>
                                        </button>
                                    </li>
                            <?php }
                            }
                            ?>
                            <li class="nav-item">
                                <button class="nav-link text-success" type="button" data-bs-toggle="modal" data-bs-target="#addRoomTypeModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add Room Type
                                </button>
                            </li>
                            <li>
                                <button class="nav-link text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteRoomTypeModal">
                                    <i class="bi bi-dash-circle me-1"></i>Edit room types
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Pagination Info & Controls -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted" id="paginationInfo">
                                Showing <span id="showingStart">1</span>-<span id="showingEnd">7</span> of <span id="totalRooms">0</span> rooms
                            </div>
                            <nav aria-label="Room pagination">
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
                                        <th class="text-center">Room Type</th>
                                        <th class="text-center">Room Name</th>
                                        <th class="text-center">Max Occupancy</th>
                                        <th class="text-center">Features</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-center">Image</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="roomsTableBody">
                                <?php foreach ($rooms as $row) {
                                    // Get room features using model
                                    $roomFeatures = $roomModel->getFeatures($row['roomID']);
                                    $roomFeatureNames = array_column($roomFeatures, 'featureName');
                                    $roomFeatureIds = array_column($roomFeatures, 'featureId');
                                ?>
                                    <tr data-room-type="<?php echo htmlspecialchars($row['roomTypeName'], ENT_QUOTES); ?>">
                                        <td class="text-center"><?php echo $row['roomID'] ?></td>
                                        <td class="text-center"><span class="badge bg-info"><?php echo $row['roomTypeName'] ?></span></td>
                                        <td class="text-center"><?php echo $row['roomName'] ?></td>
                                        <td class="text-center"><?php echo $row['capacity'] ?> guests</td>
                                        <td class="text-start justify-content-evenly">
                                            <?php if (!empty($roomFeatureNames)) {
                                                foreach ($roomFeatureNames as $featureName) { ?>
                                                    <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($featureName); ?></span>
                                                <?php }
                                            } else { ?>
                                                <span class="text-muted">No features</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center"><strong>₱<?php echo number_format($row['base_price'], 2) ?></strong></td>
                                        <td class="text-center"><?php echo $row['quantity'] ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($row['imagePath'])) { ?>
                                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo $row['imagePath']; ?>" class="rounded" style="width:100px; height:60px; object-fit:cover;">
                                            <?php } else { ?>
                                                <span class="text-muted">No image</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['roomID']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                                    <input type="hidden" value="<?php echo $row['roomID'] ?>" name="deleteID">
                                                    <button class="btn btn-outline-danger btn-sm" type="submit">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <?php 
                                            // THIS MUST BE INSIDE THE LOOP
                                            include ADMIN_INCLUDES_PATH . '/modals/roomModals/editRoomModal.php'; 
                                            ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Bottom Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted" id="paginationInfoBottom">
                            Showing <span id="showingStartBottom">1</span>-<span id="showingEndBottom">7</span> of <span id="totalRoomsBottom">0</span> rooms
                        </div>
                        <nav aria-label="Room pagination bottom">
                            <ul class="pagination pagination-sm mb-0" id="paginationControlsBottom">
                                <!-- Pagination buttons will be generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Static Modals included from external files -->
    <?php include ADMIN_INCLUDES_PATH . '/modals/roomModals/addRoomModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/roomModals/addRoomTypeModal.php'; ?>
    <?php include ADMIN_INCLUDES_PATH . '/modals/roomModals/deleteRoomTypeModal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="javascript/pagination.js"></script>
    <script src="<?php echo JS_URL; ?>/showAlert.js"></script>
    <script src="<?php echo JS_URL; ?>/autoDismiss.js"></script>
    <script src="javascript/rooms.js"></script>
</body>

</html>