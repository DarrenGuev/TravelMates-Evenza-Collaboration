function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning text-dark">Pending</span>',
        'confirmed': '<span class="badge bg-success">Confirmed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'completed': '<span class="badge bg-info">Completed</span>'
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
    else if (action === 'refund') msg = 'Processing refund...';
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
                statusBadge = `<span class="badge bg-warning text-dark" title="User has requested a refund"><i class="bi bi-clock-history me-1"></i>Refund Requested</span>`;
            } else if (isRefunded) {
                statusBadge = `<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelled</span>`;
            } else {
                statusBadge = getStatusBadge(booking.bookingStatus);
            }
            
            // Generate action buttons
            let actionButtons = `<button class="btn btn-outline-primary" onclick="viewBooking(${booking.bookingID})" title="View Details"><i class="bi bi-eye"></i></button>`;
            
            if (isRefundRequest) {
                actionButtons += `<button class="btn btn-outline-warning" onclick="processRefund(${booking.bookingID})" title="Process Refund"><i class="bi bi-cash-coin"></i></button>`;
            } else if (booking.bookingStatus === 'pending') {
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
                                <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-scrollable">
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
        getData: () => allBookingsData.filter(b => b.bookingStatus === 'confirmed'),
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

// Pagination for admin tables
const adminItemsPerPage = 7;
const adminCurrentPage = {
    reservations: 1,
    users: 1,
    confirmed: 1,
    pending: 1,
    completed: 1
};

function getAdminRows() {
    return Array.from(document.querySelectorAll('#tableBody tr'));
}

function applyAdminPagination(tableType) {
    const rows = getAdminRows();
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / adminItemsPerPage));

    if (!adminCurrentPage[tableType] || adminCurrentPage[tableType] < 1) adminCurrentPage[tableType] = 1;
    if (adminCurrentPage[tableType] > totalPages) adminCurrentPage[tableType] = totalPages;

    const start = (adminCurrentPage[tableType] - 1) * adminItemsPerPage;
    const end = Math.min(start + adminItemsPerPage, total);

    rows.forEach((row, idx) => {
        row.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // Update pagination info (top and bottom)
    const startDisplay = total > 0 ? start + 1 : 0;
    document.getElementById('adminShowingStart').textContent = startDisplay;
    document.getElementById('adminShowingEnd').textContent = end;
    document.getElementById('adminTotal').textContent = total;

    document.getElementById('adminShowingStartBottom').textContent = startDisplay;
    document.getElementById('adminShowingEndBottom').textContent = end;
    document.getElementById('adminTotalBottom').textContent = total;

    // Generate pagination controls
    const html = generateAdminPaginationHTML(totalPages, tableType);
    document.getElementById('adminPaginationControls').innerHTML = html;
    document.getElementById('adminPaginationControlsBottom').innerHTML = html;
}

function generateAdminPaginationHTML(totalPages, tableType) {
    const currentPage = adminCurrentPage[tableType] || 1;
    if (totalPages <= 1) return '';

    let html = '';

    // Previous
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToAdminPage('${tableType}', ${currentPage - 1}); return false;" aria-label="Previous">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>`;

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    if (endPage - startPage < maxVisiblePages - 1) startPage = Math.max(1, endPage - maxVisiblePages + 1);

    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToAdminPage('${tableType}', 1); return false;">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="goToAdminPage('${tableType}', ${i}); return false;">${i}</a></li>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToAdminPage('${tableType}', ${totalPages}); return false;">${totalPages}</a></li>`;
    }

    // Next
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToAdminPage('${tableType}', ${currentPage + 1}); return false;" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>`;

    return html;
}

function goToAdminPage(tableType, page) {
    const rows = getAdminRows();
    const totalPages = Math.max(1, Math.ceil(rows.length / adminItemsPerPage));
    if (page < 1 || page > totalPages) return;
    adminCurrentPage[tableType] = page;
    applyAdminPagination(tableType);
}

function switchTable(tableType) {
    // Reset page to 1 when switching tables
    if (adminCurrentPage.hasOwnProperty(tableType)) adminCurrentPage[tableType] = 1;
    // Show loader (if present)
    const loader = document.getElementById('table-loader');
    if (loader) {
        loader.classList.remove('d-none');
        loader.classList.add('d-flex');
    }

    // Use setTimeout to simulate loading and allow the loader to render
    setTimeout(() => {
        const config = tableConfigs[tableType];
        if (!config) {
            if (loader) {
                loader.classList.remove('d-flex');
                loader.classList.add('d-none');
            }
            return;
        }

        // Required DOM elements for admin table
        const headersEl = document.getElementById('tableHeaders');
        const bodyEl = document.getElementById('tableBody');

        // If elements are not present on the current page, hide loader and exit gracefully
        if (!headersEl || !bodyEl) {
            if (loader) {
                loader.classList.remove('d-flex');
                loader.classList.add('d-none');
            }
            return;
        }

        headersEl.innerHTML = config.headers.map(h => `<th>${h}</th>`).join('');
        const data = config.getData();
        if (data && data.length > 0) {
            bodyEl.innerHTML = data.map((item, index) => config.renderRow(item, index)).join('');
        } else {
            bodyEl.innerHTML = `
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

        // Hide loader (if present)
        if (loader) {
            loader.classList.remove('d-flex');
            loader.classList.add('d-none');
        }
        // Apply pagination for the current table
        try {
            applyAdminPagination(tableType);
        } catch (e) {
            // If pagination elements are not present on the page, ignore
            // console.warn('Pagination not applied:', e);
        }
    }, 300); // Adjust timeout as needed
}
document.addEventListener('DOMContentLoaded', () => {
    initBookingModals();
    // Only initialize admin table on pages that include the admin table elements
    if (document.getElementById('tableHeaders') && document.getElementById('tableBody')) {
        switchTable('reservations');
    }
});

// Process refund function
        function processRefund(bookingID) {
            // Find booking data from allBookingsData
            const booking = allBookingsData.find(b => b.bookingID == bookingID);
            const refundReason = booking && booking.refundReason ? booking.refundReason : 'No reason provided';
            
            document.getElementById('bookingStatusBookingID').value = bookingID;
            document.getElementById('bookingStatusAction').value = 'refund';

            const modalHeader = document.getElementById('bookingStatusModalHeader');
            const modalIcon = document.getElementById('bookingStatusIcon');
            const modalMessage = document.getElementById('bookingStatusMessage');
            const confirmBtn = document.getElementById('bookingStatusConfirmBtn');

            modalHeader.className = 'modal-header bg-warning text-dark';
            modalIcon.className = 'bi bi-cash-coin text-warning';
            modalMessage.innerHTML = `Process this refund request?<br><br>
                <div class="alert alert-warning mb-3" style="text-align: left;">
                    <strong><i class="bi bi-chat-left-quote me-2"></i>Reason for Refund Request:</strong><br>
                    <em>${refundReason}</em>
                </div>
                <small class="text-muted">This will:<br>• Change booking status to <strong>CANCELLED</strong><br>• Change payment status to <strong>REFUNDED</strong></small>`;
            confirmBtn.className = 'btn btn-warning';
            confirmBtn.textContent = 'Process Refund';

            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingStatusModal')) ||
                new bootstrap.Modal(document.getElementById('bookingStatusModal'));
            modal.show();
        }

        // Override confirmBookingStatusChange to handle refund action
        confirmBookingStatusChange = function() {
            const bookingID = document.getElementById('bookingStatusBookingID').value;
            const action = document.getElementById('bookingStatusAction').value;

            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingStatusModal'));
            if (modal) modal.hide();

            showBookingLoading(action === 'refund' ? 'refund' : action);

            const formData = new FormData();
            formData.append('bookingID', bookingID);

            if (action === 'refund') {
                formData.append('bookingAction', 'edit');
                formData.append('newStatus', 'cancelled');
                formData.append('newPaymentStatus', 'refunded');
            } else {
                formData.append('bookingAction', action);
            }

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
                        if (data.success) {
                            const resultModalEl = document.getElementById('bookingResultModal');
                            if (resultModalEl) resultModalEl.setAttribute('data-reload', 'true');
                        }
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
        };

        // submitEditForm function for inline modals
        function submitEditForm(form) {
            const formData = new FormData(form);
            const modalEl = form.closest('.modal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            showBookingLoading('edit');

            fetch('php/booking_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    showBookingResult(data.success, data.message);
                    if (data.success) {
                        const resultModalEl = document.getElementById('bookingResultModal');
                        if (resultModalEl) resultModalEl.setAttribute('data-reload', 'true');
                    }
                })
                .catch(err => {
                    showBookingResult(false, 'Error: ' + err.message);
                });
            return false;
        }

        // Disable edit buttons for refunded bookings after table renders
        const originalSwitchTable = switchTable;
        switchTable = function(tableType) {
            originalSwitchTable(tableType);

            // After table renders, disable edit buttons for refunded bookings
            if (tableType === 'reservations' || tableType === 'confirmed' || tableType === 'pending' || tableType === 'completed') {
                setTimeout(() => {
                    allBookingsData.forEach(booking => {
                        if (booking.bookingStatus === 'cancelled' && booking.cancelledByUser == 1) {
                            const editBtn = document.getElementById(`editBtn${booking.bookingID}`);
                            if (editBtn) {
                                editBtn.disabled = true;
                                editBtn.classList.add('opacity-50');
                                editBtn.title = 'Cannot edit - Refunded by user';
                                editBtn.innerHTML = '<i class="bi bi-lock"></i>';
                            }
                        }
                    });
                }, 350);
            }
        };