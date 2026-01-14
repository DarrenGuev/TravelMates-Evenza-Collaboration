<!-- Delete Room Confirmation Modal -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h1 class="modal-title fs-5" id="deleteRoomModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Room
                </h1>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center mb-2">Are you sure you want to delete this room?</p>
                <p class="text-center fw-bold fs-5 text-danger" id="deleteRoomName"></p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Warning:</strong> This action cannot be undone. The room and its associated image will be permanently removed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <form method="POST" id="deleteRoomForm" class="d-inline">
                    <input type="hidden" id="deleteRoomID" name="deleteID" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Room
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openDeleteRoomModal(roomID, roomName) {
    document.getElementById('deleteRoomID').value = roomID;
    document.getElementById('deleteRoomName').textContent = roomName;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteRoomModal'));
    deleteModal.show();
}
</script>
