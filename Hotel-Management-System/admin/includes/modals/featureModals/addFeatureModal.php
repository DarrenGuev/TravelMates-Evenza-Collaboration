<!-- Add Feature Modal -->
<div class="modal fade" id="addFeatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"><i class="bi bi-plus-circle me-2"></i>Add Feature</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="categoryID" required>
                            <option value="" selected disabled>-- Select Category --</option>
                            <?php foreach ($categoryList as $cat) { ?>
                                <option value="<?php echo (int)$cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Feature Name</label>
                        <input class="form-control" type="text" name="featureName" placeholder="e.g., Free Wi-Fi" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_feature" class="btn btn-primary">Save Feature</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
