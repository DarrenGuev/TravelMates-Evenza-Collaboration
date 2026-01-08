<!-- Add Room Type Modal -->
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