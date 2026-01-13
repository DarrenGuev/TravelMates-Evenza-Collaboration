<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-labelledby="manageCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h1 class="modal-title fs-5" id="manageCategoriesModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Manage Categories
                </h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Edit or delete feature categories. Categories with features cannot be deleted.</p>
                
                <div class="list-group">
                    <?php 
                    // Fetch all categories with feature counts using models
                    $catListForModal = $categoryModel->getAll('categoryName');
                    // Count features per category
                    $featureCounts = [];
                    foreach ($allFeaturesList as $feat) {
                        $catName = $feat['categoryName'] ?? 'General';
                        $featureCounts[$catName] = ($featureCounts[$catName] ?? 0) + 1;
                    }
                    
                    foreach ($catListForModal as $catRow) { 
                        $catID = $catRow['categoryID'];
                        $catName = $catRow['categoryName'];
                        $catCount = $featureCounts[$catName] ?? 0;
                    ?>
                        <div class="list-group-item" id="categoryItem<?php echo $catID; ?>">
                            <!-- Display Mode -->
                            <div class="d-flex justify-content-between align-items-center" id="displayMode<?php echo $catID; ?>">
                                <div>
                                    <i class="bi bi-tag-fill me-2 text-primary"></i>
                                    <span id="catName<?php echo $catID; ?>"><?php echo htmlspecialchars($catName); ?></span>
                                    <span class="badge <?php echo $catCount > 0 ? 'bg-warning text-dark' : 'bg-success'; ?> ms-2">
                                        <?php echo $catCount; ?> feature(s)
                                    </span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="enableCategoryEditMode('<?php echo $catID; ?>', '<?php echo htmlspecialchars($catName, ENT_QUOTES); ?>')" 
                                            title="Edit Name">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($catCount == 0) { ?>
                                        <button type="button" class="btn btn-danger" title="Delete" 
                                                onclick="confirmDeleteCategory(<?php echo $catID; ?>, '<?php echo htmlspecialchars($catName, ENT_QUOTES); ?>')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                        <form method="POST" id="deleteCategoryForm<?php echo $catID; ?>" style="display:none;">
                                            <input type="hidden" name="deleteCategoryID" value="<?php echo $catID; ?>">
                                            <input type="hidden" name="delete_category" value="1">
                                        </form>
                                    <?php } else { ?>
                                        <button type="button" class="btn btn-outline-secondary" disabled title="Cannot delete - has <?php echo $catCount; ?> feature(s)">
                                            <i class="bi bi-lock"></i> Protected
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                            <!-- Edit Mode (hidden by default) -->
                            <div class="d-none" id="editMode<?php echo $catID; ?>">
                                <form method="POST" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="categoryID" value="<?php echo $catID; ?>">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                        <input type="text" class="form-control" name="newCategoryName" 
                                            id="editInput<?php echo $catID; ?>" 
                                            value="<?php echo htmlspecialchars($catName); ?>" required>
                                    </div>
                                    <button type="submit" name="rename_category" class="btn btn-success btn-sm" title="Save Changes">
                                        <i class="bi bi-check-lg"></i> Save
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="cancelCategoryEditMode('<?php echo $catID; ?>')" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
                <div class="alert alert-warning small mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Note:</strong> Categories with existing features are protected. Reassign or delete those features first to delete a category.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
