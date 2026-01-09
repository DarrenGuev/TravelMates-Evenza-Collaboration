<!-- Booking Status Confirmation Modal -->
<div class="modal fade" id="bookingStatusModal" tabindex="-1" aria-labelledby="bookingStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="bookingStatusModalHeader">
                <h5 class="modal-title" id="bookingStatusModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi" id="bookingStatusIcon" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center mb-0" id="bookingStatusMessage">Are you sure you want to proceed?</p>
                <input type="hidden" id="bookingStatusBookingID">
                <input type="hidden" id="bookingStatusAction">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="bookingStatusConfirmBtn" onclick="confirmBookingStatusChange()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Booking Status Result Modal -->
<div class="modal fade" id="bookingResultModal" tabindex="-1" aria-labelledby="bookingResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="bookingResultModalHeader">
                <h5 class="modal-title" id="bookingResultModalLabel">Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi" id="bookingResultIcon" style="font-size: 3rem;"></i>
                </div>
                <p class="text-center mb-0" id="bookingResultMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="bookingResultOkBtn" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
