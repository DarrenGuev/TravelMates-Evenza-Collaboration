<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="send_sms">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-send me-2"></i>Send SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Booking (Optional)</label>
                        <select name="booking_id" class="form-select" id="bookingSelect">
                            <option value="">-- Select a booking --</option>
                            <?php foreach ($bookings as $booking): ?>
                                <option value="<?php echo $booking['bookingID']; ?>" data-phone="<?php echo htmlspecialchars($booking['phoneNumber']); ?>">
                                    #<?php echo $booking['bookingID']; ?> - <?php echo htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone_number" id="phoneNumber" class="form-control" placeholder="e.g., 09171234567 or 639171234567" required pattern="\d+">
                        <div class="invalid-feedback">Please enter a valid phone number (numbers only).</div>
                        <div class="form-text">Enter Philippine mobile number format</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="smsMessage" class="form-control" rows="4" required placeholder="Enter your message here..."></textarea>
                        <div class="invalid-feedback">You must enter "Your Booking is Approved."</div>
                        <div class="form-text"><span id="charCount">0</span> characters</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
