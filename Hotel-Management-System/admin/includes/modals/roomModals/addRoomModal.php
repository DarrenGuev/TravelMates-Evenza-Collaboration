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
                                <?php foreach ($roomTypes as $type) { ?>
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
                                                    <div class="col-12">
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
                                            <?php foreach ($featureCategories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['categoryName']); ?>" <?php echo $cat['categoryName'] === 'General' ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['categoryName']); ?>
                                                </option>
                                            <?php endforeach; ?>
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
