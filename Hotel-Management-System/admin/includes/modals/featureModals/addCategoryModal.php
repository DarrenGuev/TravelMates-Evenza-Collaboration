<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="addCategoryModalLabel">
                    <i class="bi bi-tags me-2"></i>Add New Category
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="newCategory" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="newCategory" name="newCategory" 
                            placeholder="e.g., Kitchen, Outdoor, Safety" required>
                        <small class="text-muted">Enter a unique name for the new category</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_category" class="btn btn-success">
                            <i class="bi bi-plus-lg me-1"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
