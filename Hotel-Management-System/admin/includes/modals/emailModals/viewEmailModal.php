<!-- View Email Modal -->
<div class="modal fade" id="viewEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEmailSubject">Email Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 pb-3 border-bottom">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>From:</strong> <span id="viewEmailFrom"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>To:</strong> <span id="viewEmailTo"></span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <strong>Date:</strong> <span id="viewEmailDate"></span>
                    </div>
                </div>
                <div class="email-body-content" id="viewEmailBody">
                    <!-- Email body will be inserted here -->
                </div>
            </div>
            <div class="modal-footer" id="viewEmailFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="replyBtn" data-bs-toggle="modal" data-bs-target="#replyModal">
                    <i class="bi bi-reply me-2"></i>Reply
                </button>
            </div>
        </div>
    </div>
</div>
