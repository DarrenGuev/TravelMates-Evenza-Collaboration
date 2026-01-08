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
                                            <?php foreach ($featureCategories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['categoryName']); ?>" <?php echo $cat['categoryName'] === 'General' ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['categoryName']); ?>
                                                </option>
                                            <?php endforeach; ?>
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
