<!-- Edit Feature Modal -->
<div class="modal fade" id="editFeatureModal<?php echo (int)$row['featureId']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><i class="bi bi-pencil me-2"></i>Edit Feature</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                <form method="POST">
                    <input type="hidden" name="featureId" value="<?php echo (int)$row['featureId']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="editCategory" required>
                            <?php foreach ($categoryList as $cat) { ?>
                                <option value="<?php echo htmlspecialchars($cat['categoryName']); ?>" <?php echo ($row['category'] == $cat['categoryName']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Feature Name</label>
                        <input class="form-control" type="text" name="editFeatureName" value="<?php echo htmlspecialchars($row['featureName']); ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_feature" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
