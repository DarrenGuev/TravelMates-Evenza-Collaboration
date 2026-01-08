<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../dbconnect/connect.php';
require_once __DIR__ . '/../classes/autoload.php';

Auth::startSession();
Auth::requireAdmin('../frontend/login.php');

$roomModel = new Room();
$roomTypeModel = new RoomType();
$featureModel = new Feature();

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
                                        <td class="text-center">
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

                                            <div class="modal fade" id="editModal<?php echo $row['roomID']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['roomID']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-xl">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h1 class="modal-title fs-5" id="editModalLabel<?php echo $row['roomID']; ?>"><i class="bi bi-pencil-square me-2"></i>Edit Room Details</h1>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="roomID" value="<?php echo $row['roomID']; ?>">
                                                                <div class="row align-items-center">
                                                                    <div class="col-12 col-lg-4 mb-3">
                                                                        <label for="editRoomName<?php echo $row['roomID']; ?>" class="form-label">Room Name</label>
                                                                        <input id="editRoomName<?php echo $row['roomID']; ?>" class="form-control" type="text" name="editRoomName" value="<?php echo htmlspecialchars($row['roomName']); ?>" required>
                                                                    </div>
                                                                    <div class="col-6 col-lg-4 mb-3">
                                                                        <label for="editRoomTypeId<?php echo $row['roomID']; ?>" class="form-label">Room Type</label>
                                                                        <select id="editRoomTypeId<?php echo $row['roomID']; ?>" class="form-select" name="editRoomTypeId" required>
                                                                            <?php
                                                                            foreach ($roomTypes as $type) {
                                                                                $selected = ($type['roomTypeID'] == $row['roomTypeId']) ? 'selected' : '';
                                                                            ?>
                                                                                <option value="<?php echo $type['roomTypeID']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($type['roomType']); ?></option>
                                                                            <?php } ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-6 col-md-6 col-lg-4 mb-3">
                                                                        <label for="editCapacity<?php echo $row['roomID']; ?>" class="form-label">Guest Capacity</label>
                                                                        <input id="editCapacity<?php echo $row['roomID']; ?>" class="form-control" type="number" name="editCapacity" value="<?php echo $row['capacity']; ?>" required>
                                                                    </div>
                                                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                                                        <label for="editQuantity<?php echo $row['roomID']; ?>" class="form-label">Quantity of Rooms</label>
                                                                        <input id="editQuantity<?php echo $row['roomID']; ?>" class="form-control" type="number" name="editQuantity" value="<?php echo $row['quantity']; ?>" required>
                                                                    </div>
                                                                    <div class="col-6 col-md-6 col-lg-3 mb-3">
                                                                        <label for="editBasePrice<?php echo $row['roomID']; ?>" class="form-label">Price (₱)</label>
                                                                        <input id="editBasePrice<?php echo $row['roomID']; ?>" class="form-control" type="number" step="0.01" name="editBasePrice" value="<?php echo $row['base_price']; ?>" required>
                                                                    </div>
                                                                    <div class="col-12 col-lg-6 mb-3">
                                                                        <label for="editRoomImage<?php echo $row['roomID']; ?>" class="form-label">Room Image</label>
                                                                        <input id="editRoomImage<?php echo $row['roomID']; ?>" class="form-control" type="file" name="editRoomImage" accept="image/*">
                                                                        <?php if (!empty($row['imagePath'])) { ?>
                                                                            <div class="mt-2">
                                                                                <small class="text-muted">Current image:</small>
                                                                                <img src="<?php echo ADMIN_URL; ?>/assets/<?php echo htmlspecialchars($row['imagePath']); ?>" class="img-thumbnail ms-2" style="max-width: 100px; max-height: 60px;">
                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Room Features</label>
                                                                        <div id="editRoomFeaturesContainer<?php echo $row['roomID']; ?>">
                                                                            <div class="row justify-content-center">
                                                                                <?php foreach ($featuresByCategory as $category => $categoryFeatures) { ?>
                                                                                <div class="col-12 col-md-6 col-lg-4 mb-3">
                                                                                    <h6 class="text-muted border-bottom pb-1"><i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($category); ?></h6>
                                                                                    <div class="row">
                                                                                        <?php foreach ($categoryFeatures as $feature) {
                                                                                            $checked = in_array($feature['featureId'], $roomFeatureIds) ? 'checked' : '';
                                                                                        ?>
                                                                                            <div class="col-12 col-md-6 col-lg-4">
                                                                                                <div class="form-check">
                                                                                                    <input class="form-check-input" type="checkbox" name="editFeatures[]" value="<?php echo $feature['featureId']; ?>" id="editFeature<?php echo $row['roomID'] . '_' . $feature['featureId']; ?>" <?php echo $checked; ?>>
                                                                                                    <label class="form-check-label" for="editFeature<?php echo $row['roomID'] . '_' . $feature['featureId']; ?>">
                                                                                                        <?php echo htmlspecialchars($feature['featureName']); ?>
                                                                                                    </label>
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php } ?>
                                                                                    </div>
                                                                                </div>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row justify-content-center">
                                                                            <div class="col-12 col-lg-6 m-3 border-top pt-3">
                                                                                <label class="form-label text-muted small">Add Custom Feature</label>
                                                                                <div class="input-group">
                                                                                    <select class="form-select" id="customFeatureCategoryInputEdit<?php echo $row['roomID']; ?>" style="max-width: 140px;">
                                                                                        <option value="Beds">Beds</option>
                                                                                        <option value="Rooms">Rooms</option>
                                                                                        <option value="Bathroom">Bathroom</option>
                                                                                        <option value="Amenities">Amenities</option>
                                                                                        <option value="Entertainment">Entertainment</option>
                                                                                        <option value="General" selected>General</option>
                                                                                    </select>
                                                                                    <input type="text" class="form-control" id="customFeatureInputEdit<?php echo $row['roomID']; ?>" placeholder="Enter new feature name">
                                                                                    <button type="button" class="btn btn-outline-success" onclick="addCustomFeature('editRoomFeaturesContainer<?php echo $row['roomID']; ?>', 'customFeatureInputEdit<?php echo $row['roomID']; ?>', 'editFeatures[]', '<?php echo $row['roomID']; ?>', 'customFeatureCategoryInputEdit<?php echo $row['roomID']; ?>')">
                                                                                        <i class="bi bi-plus-lg"></i> Add
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                            <button type="submit" name="update_room" class="btn btn-primary">Save Changes</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addRoomModalLabel"><i class="bi bi-plus-circle me-2"></i>Add New Room</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                            <div class="modal-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row align-items-center">
                                        <div class="col-12 col-lg-4 mb-3">
                                            <label for="roomName" class="form-label">Room Name</label>
                                            <input id="roomName" class="form-control" type="text" name="roomName" placeholder="e.g., Deluxe Suite" required>
                                        </div>
                                        <div class="col-6 col-lg-4 mb-3">
                                            <label for="roomTypeId" class="form-label">Room Type</label>
                                            <select id="roomTypeId" class="form-select" name="roomTypeId" required>
                                                <option value="" selected disabled>-- Select Room Type --</option>
                                                <?php
                                                foreach ($roomTypes as $type) {
                                                ?>
                                                    <option value="<?php echo $type['roomTypeID']; ?>"><?php echo htmlspecialchars($type['roomType']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-6 col-lg-4 mb-3">
                                            <label for="capacity" class="form-label">Guest Capacity</label>
                                            <input id="capacity" class="form-control" type="number" name="capacity" placeholder="number of max capacity" required>
                                        </div>
                                        <div class="col-6 col-md-6 col-lg-3 mb-3">
                                            <label for="quantity" class="form-label">Quantity of Rooms</label>
                                            <input id="quantity" class="form-control" type="number" name="quantity" placeholder="number of rooms" required>
                                        </div>
                                        <div class="col-6 col-md-6 col-lg-3 mb-3">
                                            <label for="base_price" class="form-label">Price (₱)</label>
                                            <input id="base_price" class="form-control" type="number" step="0.01" name="base_price" placeholder="e.g., 1500.00" required>
                                        </div>
                                        <div class="col-12 col-lg-6 mb-3">
                                            <label for="roomImage" class="form-label">Room Image</label>
                                            <input id="roomImage" class="form-control" type="file" name="roomImage" accept="image/*">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Room Features</label>
                                            <div id="addRoomFeaturesContainer">
                                                <div class="row justify-content-center">
                                                    <?php foreach ($featuresByCategory as $category => $categoryFeatures) { ?>
                                                    <div class="col-12 col-md-6 col-lg-4 mb-3">
                                                        <h6 class="text-muted border-bottom pb-1"><i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($category); ?></h6>
                                                        <div class="row">
                                                            <?php foreach ($categoryFeatures as $feature) { ?>
                                                                <div class="col-12 col-md-6 col-lg-4">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $feature['featureId']; ?>" id="feature<?php echo $feature['featureId']; ?>">
                                                                        <label class="form-check-label" for="feature<?php echo $feature['featureId']; ?>">
                                                                            <?php echo htmlspecialchars($feature['featureName']); ?>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                </div>
                                            </div>
                                            <div class="row justify-content-center">
                                                <div class="col-12 col-lg-6 m-3 border-top pt-3">
                                                <label class="form-label text-muted small">Add Custom Feature</label>
                                                <div class="input-group">
                                                    <select class="form-select" id="customFeatureCategoryInput" style="max-width: 140px;">
                                                        <option value="Beds">Beds</option>
                                                        <option value="Rooms">Rooms</option>
                                                        <option value="Bathroom">Bathroom</option>
                                                        <option value="Amenities">Amenities</option>
                                                        <option value="Entertainment">Entertainment</option>
                                                        <option value="General" selected>General</option>
                                                    </select>
                                                    <input type="text" class="form-control" id="customFeatureInput" placeholder="Enter new feature name">
                                                    <button type="button" class="btn btn-outline-success" onclick="addCustomFeature('addRoomFeaturesContainer', 'customFeatureInput', 'features[]', null, 'customFeatureCategoryInput')">
                                                        <i class="bi bi-plus-lg"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="add_room" class="btn btn-primary">Save Room</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

    <!-- Add Room Type Modal - Add this before </body> -->
    <div class="modal fade" id="addRoomTypeModal" tabindex="-1" aria-labelledby="addRoomTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addRoomTypeModalLabel">
                        <i class="bi bi-tags me-2"></i>Manage Room Types
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Add New Room Type Form -->
                    <form method="POST" class="mb-4">
                        <label for="newRoomType" class="form-label">Add New Room Type</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newRoomType" name="newRoomType" 
                                   placeholder="e.g., Presidential, Economy, VIP" required>
                            <button type="submit" name="add_room_type" class="btn btn-success">
                                <i class="bi bi-plus-lg me-1"></i>Add
                            </button>
                        </div>
                        <small class="text-muted">Enter a unique name for the new room type</small>
                    </form>

                    <!-- Existing Room Types List -->
                    <h6 class="border-bottom pb-2 mb-3">Existing Room Types</h6>
                    <div class="list-group">
                        <?php 
                        foreach ($roomTypes as $type) { 
                            // Count rooms using this type
                            $roomCount = $roomModel->countByType($type['roomTypeID']);
                        ?>
                            <div class="list-group-item" id="roomTypeItem<?php echo $type['roomTypeID']; ?>">
                                <!-- Display Mode -->
                                <div class="d-flex justify-content-between align-items-center" id="displayMode<?php echo $type['roomTypeID']; ?>">
                                    <div>
                                        <i class="bi bi-tag-fill me-2 text-primary"></i>
                                        <span id="typeName<?php echo $type['roomTypeID']; ?>"><?php echo htmlspecialchars($type['roomType']); ?></span>
                                        <span class="badge bg-secondary ms-2"><?php echo $roomCount; ?> room(s)</span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="enableEditMode(<?php echo $type['roomTypeID']; ?>, '<?php echo htmlspecialchars($type['roomType'], ENT_QUOTES); ?>')" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($roomCount == 0) { ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room type?');">
                                                <input type="hidden" name="deleteRoomTypeID" value="<?php echo $type['roomTypeID']; ?>">
                                                <button type="submit" name="delete_room_type" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Cannot delete - <?php echo $roomCount; ?> room(s) using this type">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>
                                <!-- Edit Mode (hidden by default) -->
                                <div class="d-none" id="editMode<?php echo $type['roomTypeID']; ?>">
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="updateRoomTypeID" value="<?php echo $type['roomTypeID']; ?>">
                                        <input type="text" class="form-control form-control-sm" name="updateRoomTypeName" 
                                               id="editInput<?php echo $type['roomTypeID']; ?>" 
                                               value="<?php echo htmlspecialchars($type['roomType']); ?>" required>
                                        <button type="submit" name="update_room_type" class="btn btn-success btn-sm" title="Save">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEditMode(<?php echo $type['roomTypeID']; ?>)" title="Cancel">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Note:</strong> Room types with existing rooms cannot be deleted. You must first reassign or delete those rooms.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete/Edit Room Type Modal -->
    <div class="modal fade" id="deleteRoomTypeModal" tabindex="-1" aria-labelledby="deleteRoomTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h1 class="modal-title fs-5" id="deleteRoomTypeModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Edit Room Types
                    </h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Select a room type to edit its name or delete it (if no rooms are assigned).</p>
                    
                    <div class="list-group">
                        <?php 
                        foreach ($roomTypes as $type) { 
                            // Count rooms using this type
                            $roomCount = $roomModel->countByType($type['roomTypeID']);
                        ?>
                            <div class="list-group-item" id="deleteModalTypeItem<?php echo $type['roomTypeID']; ?>">
                                <!-- Display Mode -->
                                <div class="d-flex justify-content-between align-items-center" id="deleteDisplayMode<?php echo $type['roomTypeID']; ?>">
                                    <div>
                                        <i class="bi bi-tag-fill me-2 text-primary"></i>
                                        <span id="deleteTypeName<?php echo $type['roomTypeID']; ?>"><?php echo htmlspecialchars($type['roomType']); ?></span>
                                        <span class="badge <?php echo $roomCount > 0 ? 'bg-warning text-dark' : 'bg-success'; ?> ms-2">
                                            <?php echo $roomCount; ?> room(s)
                                        </span>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="enableDeleteModalEditMode(<?php echo $type['roomTypeID']; ?>, '<?php echo htmlspecialchars($type['roomType'], ENT_QUOTES); ?>')" title="Edit Name">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($roomCount == 0) { ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete the room type: <?php echo htmlspecialchars($type['roomType'], ENT_QUOTES); ?>?');">
                                                <input type="hidden" name="deleteRoomTypeID" value="<?php echo $type['roomTypeID']; ?>">
                                                <button type="submit" name="delete_room_type" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Cannot delete - has <?php echo $roomCount; ?> room(s)">
                                                <i class="bi bi-lock"></i> Protected
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>
                                <!-- Edit Mode (hidden by default) -->
                                <div class="d-none" id="deleteEditMode<?php echo $type['roomTypeID']; ?>">
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="updateRoomTypeID" value="<?php echo $type['roomTypeID']; ?>">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <input type="text" class="form-control" name="updateRoomTypeName" 
                                                   id="deleteEditInput<?php echo $type['roomTypeID']; ?>" 
                                                   value="<?php echo htmlspecialchars($type['roomType']); ?>" required>
                                        </div>
                                        <button type="submit" name="update_room_type" class="btn btn-success btn-sm" title="Save Changes">
                                            <i class="bi bi-check-lg"></i> Save
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cancelDeleteModalEditMode(<?php echo $type['roomTypeID']; ?>)" title="Cancel">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> Deleting a room type is permanent. Room types with assigned rooms are protected and cannot be deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-dismiss alert after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const autoAlert = document.getElementById('autoAlert');
            if (autoAlert) {
                setTimeout(function() {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(autoAlert);
                    bsAlert.close();
                }, 3000);
            }
        });

        // Room Type Edit Functions for Add Room Type Modal
        function enableEditMode(typeId, currentName) {
            document.getElementById('displayMode' + typeId).classList.add('d-none');
            document.getElementById('editMode' + typeId).classList.remove('d-none');
            const input = document.getElementById('editInput' + typeId);
            input.value = currentName;
            input.focus();
            input.select();
        }
        
        function cancelEditMode(typeId) {
            document.getElementById('displayMode' + typeId).classList.remove('d-none');
            document.getElementById('editMode' + typeId).classList.add('d-none');
        }
        
        // Room Type Edit Functions for Delete/Edit Room Type Modal
        function enableDeleteModalEditMode(typeId, currentName) {
            document.getElementById('deleteDisplayMode' + typeId).classList.add('d-none');
            document.getElementById('deleteEditMode' + typeId).classList.remove('d-none');
            const input = document.getElementById('deleteEditInput' + typeId);
            input.value = currentName;
            input.focus();
            input.select();
        }
        
        function cancelDeleteModalEditMode(typeId) {
            document.getElementById('deleteDisplayMode' + typeId).classList.remove('d-none');
            document.getElementById('deleteEditMode' + typeId).classList.add('d-none');
        }

        // Pagination variables
        const roomsPerPage = 7;
        let currentPage = 1;
        let currentFilter = 'All';

        function filterRooms(roomType) {
            currentFilter = roomType;
            currentPage = 1; // Reset to first page when filter changes
            
            // Update active tab
            document.querySelectorAll('#roomTabs .nav-link').forEach(tab => {
                tab.classList.remove('active');
            });
            const activeTab = document.querySelector(`#roomTabs [data-room-type="${roomType}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }

            applyPagination();
        }

        function getFilteredRows() {
            const tableRows = document.querySelectorAll('#roomsTableBody tr');
            const filtered = [];
            tableRows.forEach(row => {
                if (currentFilter === 'All' || row.dataset.roomType === currentFilter) {
                    filtered.push(row);
                }
            });
            return filtered;
        }

        function applyPagination() {
            const filteredRows = getFilteredRows();
            const totalRooms = filteredRows.length;
            const totalPages = Math.ceil(totalRooms / roomsPerPage);
            
            // Ensure current page is valid
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;
            
            const startIndex = (currentPage - 1) * roomsPerPage;
            const endIndex = Math.min(startIndex + roomsPerPage, totalRooms);
            
            // Hide all rows first
            document.querySelectorAll('#roomsTableBody tr').forEach(row => {
                row.style.display = 'none';
            });
            
            // Show only rows for current page
            filteredRows.forEach((row, index) => {
                if (index >= startIndex && index < endIndex) {
                    row.style.display = '';
                }
            });
            
            // Update pagination info (top and bottom)
            updatePaginationInfo(startIndex + 1, endIndex, totalRooms);
            
            // Generate pagination controls
            generatePaginationControls(totalPages);
        }

        function updatePaginationInfo(start, end, total) {
            // Top pagination info
            document.getElementById('showingStart').textContent = total > 0 ? start : 0;
            document.getElementById('showingEnd').textContent = end;
            document.getElementById('totalRooms').textContent = total;
            
            // Bottom pagination info
            document.getElementById('showingStartBottom').textContent = total > 0 ? start : 0;
            document.getElementById('showingEndBottom').textContent = end;
            document.getElementById('totalRoomsBottom').textContent = total;
        }

        function generatePaginationControls(totalPages) {
            const paginationHTML = generatePaginationHTML(totalPages);
            document.getElementById('paginationControls').innerHTML = paginationHTML;
            document.getElementById('paginationControlsBottom').innerHTML = paginationHTML;
        }

        function generatePaginationHTML(totalPages) {
            if (totalPages <= 1) {
                return '';
            }
            
            let html = '';
            
            // Previous button
            html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;" aria-label="Previous">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>`;
            
            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
            
            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            // First page and ellipsis
            if (startPage > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="goToPage(1); return false;">1</a>
                </li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                </li>`;
            }
            
            // Last page and ellipsis
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a>
                </li>`;
            }
            
            // Next button
            html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>`;
            
            return html;
        }

        function goToPage(page) {
            const filteredRows = getFilteredRows();
            const totalPages = Math.ceil(filteredRows.length / roomsPerPage);
            
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            applyPagination();
        }

        document.addEventListener('DOMContentLoaded', function() {
            filterRooms('All');
        });

        // Function to add custom feature via AJAX
        function addCustomFeature(containerId, inputId, checkboxName, roomId = null, categorySelectId = null) {
            const input = document.getElementById(inputId);
            const featureName = input.value.trim();
            let category = 'General';
            if (categorySelectId) {
                const categorySelect = document.getElementById(categorySelectId);
                if (categorySelect) {
                    category = categorySelect.value;
                }
            }

            if (!featureName) {
                alert('Please enter a feature name');
                input.focus();
                return;
            }

            // Create FormData
            const formData = new FormData();
            formData.append('featureName', featureName);
            formData.append('category', category);

            // Send AJAX request
            fetch('php/add_feature.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addFeatureCheckboxToCategory(containerId, data.featureId, data.featureName, data.category, checkboxName, roomId, true);
                        addFeatureToAllContainers(data.featureId, data.featureName, data.category, containerId);
                        input.value = '';
                        showToast('Feature "' + data.featureName + '" added to ' + data.category + ' successfully!', 'success');
                    } else if (data.error === 'Feature already exists') {
                        const existingCheckbox = document.querySelector('#' + containerId + ' input[value="' + data.featureId + '"]');
                        if (existingCheckbox) {
                            existingCheckbox.checked = true;
                            showToast('Feature already exists. It has been selected.', 'info');
                        } else {
                            addFeatureCheckboxToCategory(containerId, data.featureId, featureName, data.category || 'General', checkboxName, roomId, true);
                            showToast('Feature already exists. It has been added and selected.', 'info');
                        }
                        input.value = '';
                    } else {
                        showToast('Error: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while adding the feature', 'danger');
                });
        }

        function addFeatureCheckboxToCategory(containerId, featureId, featureName, category, checkboxName, roomId = null, isChecked = false) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const existingCheckbox = container.querySelector('input[value="' + featureId + '"]');
            if (existingCheckbox) {
                if (isChecked) existingCheckbox.checked = true;
                return;
            }

            // Find existing category section by looking for h6 with matching text
            let categorySection = null;
            const allSections = container.querySelectorAll('.mb-3');
            allSections.forEach(section => {
                const h6 = section.querySelector('h6');
                if (h6 && h6.textContent.trim() === category) {
                    categorySection = section;
                }
            });

            if (!categorySection) {
                // Create new category section only if it doesn't exist
                categorySection = document.createElement('div');
                categorySection.className = 'col-12 col-md-6 col-lg-4 mb-3';
                categorySection.innerHTML = `
                    <h6 class="text-muted border-bottom pb-1"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(category)}</h6>
                    <div class="row category-features"></div>
                `;
                
                // Insert before the "Add Custom Feature" section if it exists
                const customFeatureSection = container.querySelector('.border-top.pt-3')?.closest('.row.justify-content-center') 
                    || container.querySelector('.mt-3.border-top');
                if (customFeatureSection) {
                    customFeatureSection.parentNode.insertBefore(categorySection, customFeatureSection);
                } else {
                    // For add modal, insert into the row container
                    const rowContainer = container.querySelector('.row.justify-content-center');
                    if (rowContainer) {
                        rowContainer.appendChild(categorySection);
                    } else {
                        container.appendChild(categorySection);
                    }
                }
            }

            // Find the features row within the category section
            let featuresRow = categorySection.querySelector('.category-features') || categorySection.querySelector('.row');
            if (!featuresRow) {
                featuresRow = document.createElement('div');
                featuresRow.className = 'row category-features';
                categorySection.appendChild(featuresRow);
            }

            const checkboxId = roomId ? 'editFeature' + roomId + '_' + featureId : 'feature' + featureId;
            const colDiv = document.createElement('div');
            colDiv.className = 'col-6';
            colDiv.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="${checkboxName}" value="${featureId}" id="${checkboxId}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="${checkboxId}">
                        ${escapeHtml(featureName)}
                    </label>
                </div>
            `;

            featuresRow.appendChild(colDiv);
        }

        function addFeatureCheckbox(containerId, featureId, featureName, checkboxName, roomId = null, isChecked = false) {
            addFeatureCheckboxToCategory(containerId, featureId, featureName, 'General', checkboxName, roomId, isChecked);
        }

        function addFeatureToAllContainers(featureId, featureName, category, excludeContainerId) {
            if (excludeContainerId !== 'addRoomFeaturesContainer') {
                addFeatureCheckboxToCategory('addRoomFeaturesContainer', featureId, featureName, category, 'features[]', null, false);
            }

            document.querySelectorAll('[id^="editRoomFeaturesContainer"]').forEach(container => {
                if (container.id !== excludeContainerId) {
                    const roomId = container.id.replace('editRoomFeaturesContainer', '');
                    addFeatureCheckboxToCategory(container.id, featureId, featureName, category, 'editFeatures[]', roomId, false);
                }
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.cssText = 'z-index: 99999; max-width: 600px; width: calc(100% - 2rem);';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>

</html>