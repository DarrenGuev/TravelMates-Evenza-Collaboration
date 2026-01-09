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
    let actions = `<button class="btn btn-sm btn-outline-primary me-1" onclick="viewBooking(${booking.bookingID})">
                <i class="bi bi-eye"></i>
            </button>`;

    if (booking.bookingStatus === 'pending') {
        actions += `
                <button type="button" class="btn btn-sm btn-outline-success me-1" title="Approve" onclick="updateBookingStatus(${booking.bookingID}, 'confirm')">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Reject" onclick="updateBookingStatus(${booking.bookingID}, 'cancel')">
                    <i class="bi bi-x-lg"></i>
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
    modalMessage.textContent = action === 'confirm' ? 'Confirming booking...' : 'Cancelling booking...';
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
        headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
        getData: () => allBookingsData,
        renderRow: (booking, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${booking.firstName} ${booking.lastName}</strong><br><small class="text-muted">${booking.userEmail}</small></td>
                        <td>${booking.roomName}<br><small class="text-muted">${booking.roomType}</small></td>
                        <td>${booking.checkInDate}</td>
                        <td>${booking.checkOutDate}</td>
                        <td>₱${parseFloat(booking.totalPrice).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                        <td>${getStatusBadge(booking.bookingStatus)}</td>
                        <td>${booking.paymentMethod ? booking.paymentMethod.replace('_', ' ') + ' ' + getPaymentBadge(booking.paymentStatus) : getPaymentBadge(booking.paymentStatus)}</td>
                        <td>${getBookingActions(booking)}</td>
                    </tr>
                `
    },
    customers: {
        headers: ['#', 'Name', 'Email', 'Username', 'Phone', 'Member Since', 'Role', 'Actions'],
        getData: () => customersData,
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
        headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
        getData: () => confirmedBookingsData,
        renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
    },
    pending: {
        headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
        getData: () => pendingBookingsData,
        renderRow: (booking, index) => tableConfigs.reservations.renderRow(booking, index)
    },
    completed: {
        headers: ['#', 'Guest', 'Room', 'Check-In', 'Check-Out', 'Total', 'Status', 'Payment', 'Actions'],
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

        document.querySelectorAll('.stat-card').forEach(card => card.classList.remove('active'));
        document.querySelector(`.stat-card[data-table="${tableType}"]`)?.classList.add('active');

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