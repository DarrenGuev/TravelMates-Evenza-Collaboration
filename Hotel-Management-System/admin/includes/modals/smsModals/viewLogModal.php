<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal<?php echo $log['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SMS Details #<?php echo $log['id']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Phone Number</label>
                    <p class="mb-0"><?php echo htmlspecialchars($log['phone_number']); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Message</label>
                    <p class="mb-0 bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($log['message'])); ?></p>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0"><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($log['status']); ?></span></p>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Direction</label>
                        <p class="mb-0"><?php echo ucfirst($log['direction']); ?></p>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-bold">Date</label>
                    <p class="mb-0"><?php echo date('F d, Y H:i:s', strtotime($log['created_at'])); ?></p>
                </div>
                <?php if ($log['booking_id']): ?>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Booking ID</label>
                        <p class="mb-0">#<?php echo $log['booking_id']; ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($log['status'] === 'failed' && !empty($log['response'])): ?>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Error Details</label>
                        <pre class="bg-dark text-light p-2 rounded small"><?php echo htmlspecialchars($log['response']); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
