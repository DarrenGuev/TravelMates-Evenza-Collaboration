<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="php/update_role.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="userID" id="editUserID">
                    <p>User: <strong id="editUserName"></strong></p>
                    <div class="mb-3">
                        <label for="newRole" class="form-label">Select Role</label>
                        <select class="form-select" name="role" id="newRole" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
