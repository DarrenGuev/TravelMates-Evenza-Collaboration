(function() {
    'use strict';

    // -------------------------------
    // Reservation details & PayPal
    // -------------------------------
    let currentReservation = null;
    let paypalButtonsRendered = false;

    function formatTo12Hour(timeStr) {
        if (!timeStr || timeStr === '') return '';
        const timeOnly = timeStr.split(' ')[0];
        const [hours, minutes] = timeOnly.split(':');
        const hour = parseInt(hours, 10);
        const min = minutes || '00';
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
        return `${hour12}:${min} ${period}`;
    }

    function renderPayPalButtons() {
        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded');
            const paymentSection = document.getElementById('paymentSection');
            if (paymentSection) {
                paymentSection.innerHTML = '<div class="alert alert-danger">PayPal is not available. Please refresh the page.</div>';
            }
            return;
        }

        const container = document.getElementById('paypal-button-container-modal');
        if (container) container.innerHTML = '';

        paypal.Buttons({
            style: {
                layout: 'vertical',
                color: 'gold',
                shape: 'rect',
                label: 'paypal',
                height: 45
            },
            createOrder: function() {
                if (!currentReservation) {
                    throw new Error('No reservation selected');
                }

                return fetch('/evenza/user/process/paypal/paypal-create-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        eventId: parseInt(currentReservation.eventId, 10),
                        packageId: parseInt(currentReservation.packageId, 10),
                        amount: parseFloat(currentReservation.totalAmount),
                        reservationId: parseInt(currentReservation.reservationId, 10)
                    })
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned an invalid response. Please try again.');
                        });
                    }
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Failed to create order');
                        }).catch(() => {
                            throw new Error('Failed to process order creation. Please try again.');
                        });
                    }
                    return response.json();
                })
                .then(orderData => orderData.id);
            },
            onApprove: function(data) {
                return fetch('/evenza/user/process/paypal/paypal-capture-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        orderId: data.orderID,
                        reservationId: parseInt(currentReservation.reservationId, 10)
                    })
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned an invalid response. Please try again.');
                        });
                    }
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Failed to capture payment');
                        }).catch(() => {
                            throw new Error('Failed to process payment response. Please try again.');
                        });
                    }
                    return response.json();
                })
                .then(captureData => {
                    if (captureData.status === 'COMPLETED') {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('reservationDetailsModal'));
                        if (modal) modal.hide();
                        
                        // Always construct the redirect URL with the correct full path
                        const baseUrl = window.location.origin + '/TravelMates-Evenza-Collaboration/evenza';
                        const successToken = captureData.successToken || '';
                        const txId = captureData.transactionId || '';
                        
                        // Construct the full URL manually to ensure correct path
                        let redirectUrl = baseUrl + '/user/pages/confirmation.php';
                        const params = [];
                        if (successToken) {
                            params.push('success=' + encodeURIComponent(successToken));
                        }
                        if (txId) {
                            params.push('tx=' + encodeURIComponent(txId));
                        }
                        if (params.length > 0) {
                            redirectUrl += '?' + params.join('&');
                        }
                        
                        console.log('Redirecting to:', redirectUrl);
                        window.location.replace(redirectUrl);
                    } else {
                        throw new Error('Payment was not completed');
                    }
                })
                .catch(error => {
                    if (typeof showCustomModal === 'function') {
                        showCustomModal('Payment failed: ' + error.message, 'error', 'Payment Error');
                    } else {
                        alert('Payment failed: ' + error.message);
                    }
                });
            },
            onCancel: function() {
                console.log('Payment cancelled');
            },
            onError: function(err) {
                console.error('PayPal error:', err);
                if (typeof showCustomModal === 'function') {
                    showCustomModal('An error occurred with PayPal. Please try again.', 'error', 'Payment Error');
                } else {
                    alert('An error occurred with PayPal. Please try again.');
                }
            }
        }).render('#paypal-button-container-modal')
        .then(() => {
            paypalButtonsRendered = true;
        });
    }

    function openReservationDetails(reservation) {
        currentReservation = reservation;

        const modalEventName = document.getElementById('modalEventName');
        if (modalEventName) modalEventName.textContent = reservation.eventName || 'N/A';
        const modalVenue = document.getElementById('modalVenue');
        if (modalVenue) modalVenue.textContent = reservation.venue || 'N/A';
        const modalDate = document.getElementById('modalDate');
        if (modalDate) {
            modalDate.textContent = reservation.date
                ? new Date(reservation.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                : 'N/A';
        }

        let timeDisplay = 'N/A';
        if (reservation.time && reservation.time !== ' - ') {
            const timeParts = reservation.time.split(' - ');
            if (timeParts.length === 2) {
                const startTime = timeParts[0].trim();
                const endTime = timeParts[1].trim();
                const formattedStart = formatTo12Hour(startTime);
                const formattedEnd = formatTo12Hour(endTime);
                timeDisplay = formattedStart && formattedEnd ? `${formattedStart} - ${formattedEnd}` : reservation.time;
            } else {
                timeDisplay = reservation.time;
            }
        }
        const modalTime = document.getElementById('modalTime');
        if (modalTime) modalTime.textContent = timeDisplay;

        const modalPackage = document.getElementById('modalPackage');
        if (modalPackage) modalPackage.textContent = reservation.packageName || 'N/A';
        const modalReservationId = document.getElementById('modalReservationId');
        if (modalReservationId) modalReservationId.textContent = '#' + reservation.reservationId;
        const modalAmount = document.getElementById('modalAmount');
        if (modalAmount) modalAmount.textContent = '₱ ' + parseFloat(reservation.totalAmount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const statusEl = document.getElementById('modalStatus');
        const status = (reservation.status || 'pending').toLowerCase();
        if (statusEl) {
            if (status === 'completed') {
                statusEl.innerHTML = '<span class="badge fs-6 px-3 py-2" style="background-color: #e0f2fe; color: #0284c7; border-radius: 50px;">Completed</span>';
            } else if (status === 'confirmed') {
                statusEl.innerHTML = '<span class="badge fs-6 px-3 py-2" style="background-color: #d1fae5; color: #059669; border-radius: 50px;">Confirmed</span>';
            } else if (status === 'cancelled') {
                statusEl.innerHTML = '<span class="badge fs-6 px-3 py-2" style="background-color: #fee2e2; color: #dc2626; border-radius: 50px;">Cancelled</span>';
            } else if (status === 'paid') {
                statusEl.innerHTML = '<span class="badge fs-6 px-3 py-2" style="background-color: #e0f2fe; color: #0284c7; border-radius: 50px;">Completed</span>';
            } else {
                statusEl.innerHTML = '<span class="badge fs-6 px-3 py-2" style="background-color: #fef3c7; color: #d97706; border-radius: 50px;">Pending</span>';
            }
        }

        const pendingMessage = document.getElementById('pendingMessage');
        const cancelledMessage = document.getElementById('cancelledMessage');
        const paidMessage = document.getElementById('paidMessage');
        const paymentSection = document.getElementById('paymentSection');
        const paymentDeadlineNotice = document.getElementById('paymentDeadlineNotice');
        const cancelReservationSection = document.getElementById('cancelReservationSection');
        if (pendingMessage) pendingMessage.style.display = 'none';
        if (cancelledMessage) cancelledMessage.style.display = 'none';
        if (paidMessage) paidMessage.style.display = 'none';
        if (paymentSection) paymentSection.style.display = 'none';
        if (paymentDeadlineNotice) paymentDeadlineNotice.style.display = 'none';
        if (cancelReservationSection) cancelReservationSection.style.display = 'none';

        const userCancelled = reservation.userCancelled || 0;

        if (status === 'pending') {
            if (pendingMessage) pendingMessage.style.display = 'block';
            if (!userCancelled && cancelReservationSection) {
                cancelReservationSection.style.display = 'block';
            }
        } else if (status === 'cancelled') {
            if (cancelledMessage) cancelledMessage.style.display = 'block';
        } else if (status === 'completed') {
            if (paidMessage) paidMessage.style.display = 'block';
            if (!userCancelled && cancelReservationSection) {
                cancelReservationSection.style.display = 'block';
            }
        } else if (status === 'confirmed') {
            if (paymentSection) paymentSection.style.display = 'block';

            if (reservation.paymentDeadline && paymentDeadlineNotice) {
                const deadline = new Date(reservation.paymentDeadline);
                const now = new Date();
                const daysRemaining = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24));

                if (daysRemaining > 0) {
                    const deadlineDays = document.getElementById('deadlineDays');
                    const deadlineDate = document.getElementById('deadlineDate');
                    if (deadlineDays) deadlineDays.textContent = daysRemaining;
                    if (deadlineDate) {
                        deadlineDate.textContent = deadline.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                    paymentDeadlineNotice.style.display = 'block';
                }
            }

            if (!userCancelled && cancelReservationSection) {
                cancelReservationSection.style.display = 'block';
            }

            if (!paypalButtonsRendered) {
                renderPayPalButtons();
            }
        }

        const modal = new bootstrap.Modal(document.getElementById('reservationDetailsModal'));
        modal.show();
    }

    function showCancelConfirmation() {
        if (!currentReservation) {
            if (typeof showCustomModal === 'function') {
                showCustomModal('No reservation selected', 'error', 'Error');
            } else {
                alert('No reservation selected');
            }
            return;
        }
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelReservationModal'));
        cancelModal.show();
    }

    function confirmCancelReservation() {
        if (!currentReservation) {
            if (typeof showCustomModal === 'function') {
                showCustomModal('No reservation selected', 'error', 'Error');
            } else {
                alert('No reservation selected');
            }
            return;
        }

        const reservationId = currentReservation.reservationId;
        const confirmBtn = document.querySelector('#cancelReservationModal .btn-danger');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Cancelling...';
        }

        fetch('/evenza/user/process/cancellation/cancelReservation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'reservationId=' + encodeURIComponent(reservationId)
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned an invalid response. Please try again.');
                });
            }
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Failed to cancel reservation');
                }).catch(() => {
                    throw new Error('Failed to cancel reservation');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const cancelModal = bootstrap.Modal.getInstance(document.getElementById('cancelReservationModal'));
                if (cancelModal) cancelModal.hide();

                const detailsModal = bootstrap.Modal.getInstance(document.getElementById('reservationDetailsModal'));
                if (detailsModal) detailsModal.hide();

                showSuccessModal('Reservation cancelled successfully');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to cancel reservation');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal(error.message || 'An error occurred while cancelling the reservation. Please try again.');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Yes';
            }
        });
    }

    // Custom Modal Functions
    function showSuccessModal(message) {
        const modal = document.getElementById('successModal');
        const messageElement = document.getElementById('successModalMessage');
        if (messageElement) {
            messageElement.textContent = message;
        }
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    function showErrorModal(message) {
        const modal = document.getElementById('errorModal');
        const messageElement = document.getElementById('errorModalMessage');
        if (messageElement) {
            messageElement.textContent = message;
        }
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    // Expose functions globally for inline handlers
    window.openReservationDetails = openReservationDetails;
    window.showCancelConfirmation = showCancelConfirmation;
    window.confirmCancelReservation = confirmCancelReservation;
    window.showSuccessModal = showSuccessModal;
    window.showErrorModal = showErrorModal;

    // -------------------------------
    // Profile editing (existing)
    // -------------------------------
    // Helper function to show field error
    function showFieldError(fieldId, errorMessageId, message) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(errorMessageId);
        
        if (field) {
            field.classList.add('is-invalid');
        }
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }
    }

    // Helper function to clear field error
    function clearFieldError(fieldId, errorMessageId) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(errorMessageId);
        
        if (field) {
            field.classList.remove('is-invalid');
        }
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('show');
        }
    }

    // Clear errors when user starts typing
    document.addEventListener('DOMContentLoaded', function() {
        const nameField = document.getElementById('editName');
        const emailField = document.getElementById('editEmail');
        const mobileField = document.getElementById('editMobile');
        
        if (nameField) {
            nameField.addEventListener('input', function() {
                clearFieldError('editName', 'editNameError');
            });
        }
        if (emailField) {
            emailField.addEventListener('input', function() {
                clearFieldError('editEmail', 'editEmailError');
            });
        }
        if (mobileField) {
            mobileField.addEventListener('input', function() {
                clearFieldError('editMobile', 'editMobileError');
            });
        }
    });

    window.saveProfile = function() {
        // Clear all previous errors
        clearFieldError('editName', 'editNameError');
        clearFieldError('editEmail', 'editEmailError');
        clearFieldError('editMobile', 'editMobileError');

        const name = document.getElementById('editName').value.trim();
        const email = document.getElementById('editEmail').value.trim();
        const mobile = document.getElementById('editMobile').value.trim();

        let hasErrors = false;

        if (!name) {
            showFieldError('editName', 'editNameError', 'Name is required');
            hasErrors = true;
        }

        if (!email) {
            showFieldError('editEmail', 'editEmailError', 'Email is required');
            hasErrors = true;
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showFieldError('editEmail', 'editEmailError', 'Please enter a valid email address');
                hasErrors = true;
            }
        }

        if (!mobile) {
            showFieldError('editMobile', 'editMobileError', 'Mobile number is required');
            hasErrors = true;
        } else if (mobile.length < 7) {
            showFieldError('editMobile', 'editMobileError', 'Please enter a valid mobile number');
            hasErrors = true;
        }

        if (hasErrors) {
            // Scroll to first error field
            const firstErrorField = document.querySelector('#editProfileForm .is-invalid');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
            return;
        }

        const saveBtn = document.querySelector('#editProfileModal .btn-primary-luxury');
        if (saveBtn) saveBtn.disabled = true;

        const formData = new URLSearchParams();
        formData.append('fullName', name);
        formData.append('email', email);
        formData.append('mobile', mobile);

        fetch('../../user/process/updateProfile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                if (modal) modal.hide();
                
                if (typeof showCustomModal === 'function') {
                    showCustomModal('Profile updated successfully!', 'success', 'Profile Updated');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Profile updated successfully!');
                    window.location.reload();
                }
            } else {
                if (typeof showCustomModal === 'function') {
                    showCustomModal(data.message || 'Failed to update profile.', 'error', 'Update Failed');
                } else {
                    alert(data.message || 'Failed to update profile.');
                }
                if (saveBtn) saveBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Profile update error:', err);
            if (typeof showCustomModal === 'function') {
                showCustomModal('An error occurred while saving your profile. Please try again.', 'error', 'Error');
            } else {
                alert('An error occurred while saving your profile. Please try again.');
            }
            if (saveBtn) saveBtn.disabled = false;
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
    });

})();

