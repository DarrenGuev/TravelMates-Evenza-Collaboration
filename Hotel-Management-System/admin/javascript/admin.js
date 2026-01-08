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
                <form method="POST" class="d-inline">
                    <input type="hidden" name="bookingID" value="${booking.bookingID}">
                    <input type="hidden" name="bookingAction" value="confirm">
                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Approve">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="bookingID" value="${booking.bookingID}">
                    <input type="hidden" name="bookingAction" value="cancel">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>`;
    }
    return actions;
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
document.addEventListener('DOMContentLoaded', () => switchTable('reservations'));