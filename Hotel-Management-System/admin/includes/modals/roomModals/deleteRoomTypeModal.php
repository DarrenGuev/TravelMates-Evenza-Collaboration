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
