function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning text-dark">Pending</span>',
        'confirmed': '<span class="badge bg-success">Confirmed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'completed': '<span class="badge bg-success">Confirmed</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function getPaymentBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning text-dark">Pending</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'refunded': '<span class="badge bg-secondary">Refunded</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function getBookingActions(booking) {
    let actions = `<button class="btn btn-outline-primary" onclick="viewBooking(${booking.bookingID})" title="View Details">
                <i class="bi bi-eye"></i>
            </button>`;

    // Check if this is a refund request (pending with cancelledByUser flag)
    const isRefundRequest = booking.bookingStatus === 'pending' && booking.cancelledByUser == 1;
    
    // Check if this was refunded (cancelled by user)
    const isRefunded = booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1;

    if (isRefunded) {
        // Show disabled lock icon for refunded bookings
        actions += `<button class="btn btn-outline-secondary" disabled title="Refunded - Cannot edit">
                <i class="bi bi-lock"></i>
            </button>`;
    } else if (isRefundRequest) {
        // Show approve/reject buttons for refund requests
        actions += `
            <button class="btn btn-outline-success" onclick="updateBookingStatus(${booking.bookingID}, 'confirm')" title="Keep Booking (Reject Refund)">
                <i class="bi bi-check-circle"></i>
            </button>
            <button class="btn btn-outline-danger" onclick="updateBookingStatus(${booking.bookingID}, 'cancel')" title="Approve Refund (Cancel Booking)">
                <i class="bi bi-cash-coin"></i>
            </button>`;
    } else if (booking.bookingStatus === 'pending') {
        // Regular pending booking (not refund request)
        actions += `
                <button type="button" class="btn btn-outline-success" title="Approve" onclick="updateBookingStatus(${booking.bookingID}, 'confirm')">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" class="btn btn-outline-danger" title="Reject" onclick="updateBookingStatus(${booking.bookingID}, 'cancel')">
                    <i class="bi bi-x-lg"></i>
                </button>`;
    } else if (booking.bookingStatus === 'confirmed') {
        actions += `
                <button class="btn btn-outline-info" onclick="updateBookingStatus(${booking.bookingID}, 'complete')" title="Mark as Completed">
                    <i class="bi bi-flag"></i>
                </button>`;
    }
    
    // Add Edit/Pencil button (opens the inline modal which is not easily replicated here without full HTML structure, 
    // but in admin.php we don't have the inline edit modals pre-rendered for all rows usually if loaded dynamically.
    // However, looking at manage_bookings.php, it uses a modal per row.
    // In admin.php we are rendering rows via JS. We can't easily open a PHP-generated modal that doesn't exist.
    // We will omit the edit button for now or implement a JS-based edit modal later if requested.
    // If you want the exact look, the edit button is:
    /*
    actions += `
            <button class="btn btn-outline-secondary" title="Edit Booking">
                <i class="bi bi-pencil"></i>
            </button>`;
    */
    
    return actions;
}

// Modal instances
let bookingStatusModal = null;
let bookingResultModal = null;

// Initialize modals after DOM is ready
function initBookingModals() {
    const statusModalEl = document.getElementById('bookingStatusModal');
    const resultModalEl = document.getElementById('bookingResultModal');
    
    if (statusModalEl) {
        bookingStatusModal = new bootstrap.Modal(statusModalEl);
    }
    if (resultModalEl) {
        bookingResultModal = new bootstrap.Modal(resultModalEl);
        
        // Reload page when result modal is closed after success
        resultModalEl.addEventListener('hidden.bs.modal', function () {
            const shouldReload = resultModalEl.getAttribute('data-reload') === 'true';
            if (shouldReload) {
                location.reload();
            }
        });
    }
}

function updateBookingStatus(bookingID, action) {
    // Store booking info for confirmation
    document.getElementById('bookingStatusBookingID').value = bookingID;
    document.getElementById('bookingStatusAction').value = action;
    
    // Update modal appearance based on action
    const modalHeader = document.getElementById('bookingStatusModalHeader');
    const modalIcon = document.getElementById('bookingStatusIcon');
    const modalMessage = document.getElementById('bookingStatusMessage');
    const confirmBtn = document.getElementById('bookingStatusConfirmBtn');
    
    if (action === 'confirm') {
        modalHeader.className = 'modal-header bg-success text-white';
        modalIcon.className = 'bi bi-check-circle-fill text-success';
        modalMessage.textContent = 'Are you sure you want to confirm this booking?';
        confirmBtn.className = 'btn btn-success';
        confirmBtn.textContent = 'Confirm Booking';
    } else if (action === 'complete') {
        modalHeader.className = 'modal-header bg-info text-white';
        modalIcon.className = 'bi bi-flag-fill';
        modalMessage.textContent = 'Are you sure you want to mark this booking as completed?';
        confirmBtn.className = 'btn btn-info text-white';
        confirmBtn.textContent = 'Complete Booking';
    } else {
        modalHeader.className = 'modal-header bg-danger text-white';
        modalIcon.className = 'bi bi-x-circle-fill text-danger';
        modalMessage.textContent = 'Are you sure you want to cancel this booking?';
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.textContent = 'Cancel Booking';
    }
    
    bookingStatusModal.show();
}

function confirmBookingStatusChange() {
    const bookingID = document.getElementById('bookingStatusBookingID').value;
    const action = document.getElementById('bookingStatusAction').value;
    
    // Hide confirmation modal
    bookingStatusModal.hide();
    
    // Show loading state immediately
    showBookingLoading(action);

    const formData = new FormData();
    formData.append('bookingID', bookingID);
    formData.append('bookingAction', action);

    fetch('php/booking_status.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            showBookingResult(data.success, data.message);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            showBookingResult(false, 'Error parsing server response.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showBookingResult(false, 'An error occurred: ' + error.message);
    });
}

function showBookingLoading(action) {
    const resultModalEl = document.getElementById('bookingResultModal');
    const modalHeader = document.getElementById('bookingResultModalHeader');
    const modalIcon = document.getElementById('bookingResultIcon');
    const modalMessage = document.getElementById('bookingResultMessage');
    const modalLabel = document.getElementById('bookingResultModalLabel');
    const okBtn = document.getElementById('bookingResultOkBtn');
    
    // Set loading state
    modalHeader.className = 'modal-header bg-primary text-white';
    modalIcon.className = '';
    modalIcon.innerHTML = '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Loading...</span></div>';
    modalLabel.textContent = 'Processing';
    
    let msg = 'Processing...';
    if (action === 'confirm') msg = 'Confirming booking...';
    else if (action === 'cancel') msg = 'Cancelling booking...';
    else if (action === 'complete') msg = 'Completing booking...';
    
    modalMessage.textContent = msg;
    okBtn.style.display = 'none';
    resultModalEl.setAttribute('data-reload', 'false');
    
    // Prevent closing while loading
    resultModalEl.setAttribute('data-bs-backdrop', 'static');
    resultModalEl.setAttribute('data-bs-keyboard', 'false');
    
    bookingResultModal.show();
}

function showBookingResult(success, message) {
    const resultModalEl = document.getElementById('bookingResultModal');
    const modalHeader = document.getElementById('bookingResultModalHeader');
    const modalIcon = document.getElementById('bookingResultIcon');
    const modalMessage = document.getElementById('bookingResultMessage');
    const modalLabel = document.getElementById('bookingResultModalLabel');
    const okBtn = document.getElementById('bookingResultOkBtn');
    
    // Reset icon element (remove spinner)
    modalIcon.innerHTML = '';
    
    if (success) {
        modalHeader.className = 'modal-header bg-success text-white';
        modalIcon.className = 'bi bi-check-circle-fill text-success';
        modalLabel.textContent = 'Success';
        resultModalEl.setAttribute('data-reload', 'true');
    } else {
        modalHeader.className = 'modal-header bg-danger text-white';
        modalIcon.className = 'bi bi-x-circle-fill text-danger';
        modalLabel.textContent = 'Error';
        resultModalEl.setAttribute('data-reload', 'false');
    }
    
    // Show OK button and allow closing
    okBtn.style.display = 'block';
    resultModalEl.removeAttribute('data-bs-backdrop');
    resultModalEl.removeAttribute('data-bs-keyboard');
    
    modalMessage.textContent = message;
}

// Store all bookings for modals
const allBookingsMap = {};
allBookingsData.forEach(b => allBookingsMap[b.bookingID] = b);

function viewBooking(bookingID) {
    const booking = allBookingsMap[bookingID];
    if (!booking) return;

    const checkIn = new Date(booking.checkInDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    const checkOut = new Date(booking.checkOutDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

    // Check if this is a pending refund request
    const isRefundRequest = booking.bookingStatus === 'pending' && booking.cancelledByUser == 1;
    
    // Check if this is a completed refund
    const isRefunded = booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1;
    
    let refundRequestSection = '';
    if (isRefundRequest) {
        refundRequestSection = `
            <div class="alert alert-warning border-warning mt-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Refund Request - Pending Approval</h6>
                <p class="mb-1"><strong>Status:</strong> User has requested a refund for this confirmed booking</p>
                <p class="mb-0"><strong>Action Required:</strong> Please review and approve/process the refund request</p>
                <p class="mb-0 mt-2"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>Use the action buttons below to approve (Cancel booking) or keep the booking confirmed</small></p>
            </div>
        `;
    } else if (isRefunded) {
        refundRequestSection = `
            <div class="alert alert-info border-info mt-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-check-circle me-2"></i>Refund Processed</h6>
                <p class="mb-0"><strong>Status:</strong> This booking was cancelled and refunded as per user request</p>
            </div>
        `;
    }

    document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Guest Information</h6>
                        <p><strong>Name:</strong> ${booking.fullName}</p>
                        <p><strong>Email:</strong> ${booking.email}</p>
                        <p><strong>Phone:</strong> ${booking.phoneNumber}</p>
                        <p><strong>Guests:</strong> ${booking.numberOfGuests}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Booking Information</h6>
                        <p><strong>Room:</strong> ${booking.roomName} (${booking.roomType})</p>
                        <p><strong>Check-in:</strong> ${checkIn}</p>
                        <p><strong>Check-out:</strong> ${checkOut}</p>
                        <p><strong>Total:</strong> ₱${parseFloat(booking.totalPrice).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                        <p><strong>Payment:</strong> ${booking.paymentMethod ? booking.paymentMethod.replace('_', ' ') + ' ' + getPaymentBadge(booking.paymentStatus) : getPaymentBadge(booking.paymentStatus)}</p>
                        <p><strong>Status:</strong> ${getStatusBadge(booking.bookingStatus)}</p>
                    </div>
                </div>
                ${refundRequestSection}
            `;
    new bootstrap.Modal(document.getElementById('viewBookingModal')).show();
}

function openEditRoleModal(userID, userName, currentRole) {
    document.getElementById('editUserID').value = userID;
    document.getElementById('editUserName').textContent = userName;
    document.getElementById('newRole').value = currentRole;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}

function openDeleteModal(userID, userName) {
    document.getElementById('deleteUserID').value = userID;
    document.getElementById('deleteUserName').textContent = userName;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

// Table configurations
const tableConfigs = {
    reservations: {
        headers: ['ID', 'Guest', 'Room', 'Dates', 'Total', 'Payment', 'Status', 'Actions'],
        getData: () => allBookingsData,
        renderRow: (booking, index) => {
            const checkIn = new Date(booking.checkInDate);
            const checkOut = new Date(booking.checkOutDate);
            const dateStr = checkIn.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' + 
                          checkOut.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Check if this is a refund request
            const isRefundRequest = booking.bookingStatus === 'pending' && booking.cancelledByUser == 1;
            // Check if this is a completed refund
            const isRefunded = booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1;
            
            // Generate status badge
            let statusBadge;
            if (isRefundRequest) {
                statusBadge = `<button class="badge bg-warning text-dark border-0" onclick="processRefund(${booking.bookingID})" title="Click to approve refund and cancel booking" style="cursor: pointer;"><i class="bi bi-clock-history me-1"></i>Process Refund</button>`;
            } else if (isRefunded) {
                statusBadge = `<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Refunded</span>`;
            } else {
                statusBadge = getStatusBadge(booking.bookingStatus);
            }
            
            // Generate action buttons
            let actionButtons = `<button class="btn btn-outline-primary" onclick="viewBooking(${booking.bookingID})" title="View Details"><i class="bi bi-eye"></i></button>`;
            
            if (booking.bookingStatus === 'pending') {
                actionButtons += `
                    <button class="btn btn-outline-success" onclick="updateBookingStatus(${booking.bookingID}, 'confirm')" title="Approve"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-outline-danger" onclick="updateBookingStatus(${booking.bookingID}, 'cancel')" title="Reject"><i class="bi bi-x-lg"></i></button>`;
            }
            
            if (booking.bookingStatus === 'confirmed') {
                actionButtons += `<button class="btn btn-outline-info" onclick="updateBookingStatus(${booking.bookingID}, 'complete')" title="Mark as Completed"><i class="bi bi-flag"></i></button>`;
            }
            
            // Add edit button (will be disabled for refunded bookings via JS)
            actionButtons += `<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal${booking.bookingID}" title="Edit Booking" id="editBtn${booking.bookingID}"><i class="bi bi-pencil"></i></button>`;
            
            return `
                    <tr>
                        <td><strong>#${booking.bookingID}</strong></td>
                        <td>
                            <div>
                                <strong>${booking.firstName} ${booking.lastName}</strong>
                                <br><small class="text-muted">${booking.userEmail}</small>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>${booking.roomName}</strong>
                                <br><small class="text-muted">${booking.roomType}</small>
                            </div>
                        </td>
                        <td><small>${dateStr}</small></td>
                        <td><strong>₱${parseFloat(booking.totalPrice).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></td>
                        <td>
                            ${getPaymentBadge(booking.paymentStatus)}
                            <br><small class="text-muted">
                                ${booking.paymentMethod ? 
                                    (booking.paymentMethod.toLowerCase() === 'paypal' ? '<i class="bi bi-paypal me-1"></i>PayPal' : booking.paymentMethod.replace('_', ' ')) : 
                                    (booking.paymentStatus === 'paid' ? '<i class="bi bi-paypal me-1"></i>PayPal' : '-')
                                }
                            </small>
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                ${actionButtons}
                            </div>
                            
                            <!-- Edit Details Inline Modal -->
                            <div class="modal fade" id="editModal${booking.bookingID}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Booking #${booking.bookingID}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form onsubmit="return submitEditForm(this)">
                                            <div class="modal-body">
                                                <input type="hidden" name="bookingID" value="${booking.bookingID}">
                                                <input type="hidden" name="bookingAction" value="edit">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Guest</label>
                                                    <p class="form-control-plaintext">${booking.firstName} ${booking.lastName}</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Room</label>
                                                    <p class="form-control-plaintext">${booking.roomName} (${booking.roomType})</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="newStatus${booking.bookingID}" class="form-label fw-bold">Booking Status</label>
                                                    <select class="form-select" id="newStatus${booking.bookingID}" name="newStatus" required>
                                                        <option value="pending" ${booking.bookingStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="confirmed" ${booking.bookingStatus === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                                        <option value="cancelled" ${booking.bookingStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                                        <option value="completed" ${booking.bookingStatus === 'completed' ? 'selected' : ''}>Completed</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="newPaymentStatus${booking.bookingID}" class="form-label fw-bold">Payment Status</label>
                                                    <select class="form-select" id="newPaymentStatus${booking.bookingID}" name="newPaymentStatus" required>
                                                        <option value="pending" ${booking.paymentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="paid" ${booking.paymentStatus === 'paid' ? 'selected' : ''}>Paid</option>
                                                        <option value="refunded" ${booking.paymentStatus === 'refunded' ? 'selected' : ''}>Refunded</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="alert alert-info small mb-0">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Use this to correct accidental status changes. Note: SMS notifications are not sent when editing.
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
        }
    },
    users: {
        headers: ['#', 'Name', 'Email', 'Username', 'Phone', 'Member Since', 'Role', 'Actions'],
        getData: () => usersData,
        renderRow: (user, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${user.firstName} ${user.lastName}</td>
                        <td>${user.email}</td>
                        <td>${user.username}</td>
                        <td>${user.phoneNumber || 'N/A'}</td>
                        <td>${user.created_at}</td>
                        <td><span class="badge bg-${user.role === 'admin' ? 'danger' : 'primary'}">${user.role}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openEditRoleModal(${user.userID}, '${user.firstName} ${user.lastName}', '${user.role}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal(${user.userID}, '${user.firstName} ${user.lastName}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `
    },
    confirmed: {
        headers: ['ID', 'Guest', 'Room', 'Dates', 'Total', 'Payment', 'Status', 'Actions'],
        getData: () => confirmedBookingsData,
        renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
    },
    pending: {
        headers: ['ID', 'Guest', 'Room', 'Dates', 'Total', 'Payment', 'Status', 'Actions'],
        getData: () => pendingBookingsData,
        renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
    },
    completed: {
        headers: ['ID', 'Guest', 'Room', 'Dates', 'Total', 'Payment', 'Status', 'Actions'],
        getData: () => completedBookingsData,
        renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
    }
};

function switchTable(tableType) {
    // Show loader
    const loader = document.getElementById('table-loader');
    loader.classList.remove('d-none');
    loader.classList.add('d-flex');

    // Use setTimeout to simulate loading and allow the loader to render
    setTimeout(() => {
        const config = tableConfigs[tableType];
        if (!config) {
            loader.classList.add('d-none');
            loader.classList.remove('d-flex');
            return;
        }

        document.getElementById('tableHeaders').innerHTML = config.headers.map(h => `<th>${h}</th>`).join('');
        const data = config.getData();
        if (data.length > 0) {
            document.getElementById('tableBody').innerHTML = data.map((item, index) => config.renderRow(item, index)).join('');
        } else {
            document.getElementById('tableBody').innerHTML = `
                        <tr>
                            <td colspan="${config.headers.length}" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <h5>No Data Found</h5>
                                    <p class="mb-0">There are no records to display in this section.</p>
                                </div>
                            </td>
                        </tr>
                    `;
        }



        document.querySelectorAll('#adminTabs .nav-link').forEach(tab => tab.classList.remove('active'));
        document.getElementById('tab-' + tableType)?.classList.add('active');

        // Hide loader
        loader.classList.remove('d-flex');
        loader.classList.add('d-none');
    }, 300); // Adjust timeout as needed
}
document.addEventListener('DOMContentLoaded', () => {
    initBookingModals();
    switchTable('reservations');
});