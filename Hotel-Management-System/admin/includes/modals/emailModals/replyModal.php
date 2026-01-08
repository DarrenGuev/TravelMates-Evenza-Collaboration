<!-- Reply Email Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-reply me-2"></i>Reply to Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reply_email">
                    <input type="hidden" name="email_id" id="replyEmailId">
                    <div class="mb-3">
                        <label class="form-label">Replying to:</label>
                        <input type="text" class="form-control" id="replyToAddress" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject:</label>
                        <input type="text" class="form-control" id="replySubject" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Reply</label>
                        <textarea name="reply_body" class="form-control" rows="8" placeholder="Type your reply here..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
