(function() {
    'use strict';

    const reservationData = window.reservationData || { packages: [], selectedPackageId: null, eventId: null, defaultEvent: { date: '', time: '' } };

    const phFormatter = new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function formatPHP(amount) {
        return '₱ ' + phFormatter.format(amount || 0);
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    function updateReservationSummary() {
        const dateInput = document.getElementById('reservationDate');
        const startTimeValue = (document.getElementById('eventStartTime') || {}).value || '';
        const endTimeValue = (document.getElementById('eventEndTime') || {}).value || '';
        const dateValue = dateInput ? dateInput.value : '';

        const summaryDateEl = document.getElementById('summaryDate');
        const summaryTimeEl = document.getElementById('summaryTimeRange');
        const mobileSummaryDateEl = document.getElementById('mobileSummaryDate');
        const mobileSummaryTimeEl = document.getElementById('mobileSummaryTimeRange');
        
        const formattedDate = dateValue ? formatDate(dateValue) : (reservationData.defaultEvent.date || '');
        if (summaryDateEl) summaryDateEl.textContent = formattedDate;
        if (mobileSummaryDateEl) mobileSummaryDateEl.textContent = formattedDate;
        
        let timeText = '';
        if (startTimeValue && endTimeValue) {
            timeText = `${startTimeValue} - ${endTimeValue}`;
        } else if (startTimeValue) {
            timeText = startTimeValue;
        } else if (endTimeValue) {
            timeText = endTimeValue;
        } else {
            timeText = reservationData.defaultEvent.time || '';
        }
        
        if (summaryTimeEl) summaryTimeEl.textContent = timeText;
        if (mobileSummaryTimeEl) mobileSummaryTimeEl.textContent = timeText;
    }

    function setupPackageSelectionListeners() {
        const packageTiles = document.querySelectorAll('.package-tile');
        if (!packageTiles.length) return;

        // Set initial selected package based on hidden input value
        const initialPackageId = document.getElementById('packageId')?.value;
        if (initialPackageId) {
            packageTiles.forEach(tile => {
                if (tile.getAttribute('data-id') === initialPackageId) {
                    tile.classList.add('selected');
                } else {
                    tile.classList.remove('selected');
                }
            });
        } else {
            packageTiles.forEach(t => t.classList.remove('selected'));
        }

        packageTiles.forEach(tile => {
            const selectTile = () => {
                packageTiles.forEach(t => t.classList.remove('selected'));
                tile.classList.add('selected');

                const packageId = tile.getAttribute('data-id');
                const packageTier = tile.getAttribute('data-tier');
                const packageName = tile.getAttribute('data-name');
                const packagePrice = tile.getAttribute('data-price');

                const idEl = document.getElementById('packageId');
                const tierEl = document.getElementById('packageTier');
                const nameEl = document.getElementById('packageName');
                const priceEl = document.getElementById('packagePrice');
                if (idEl) idEl.value = packageId;
                if (tierEl) tierEl.value = packageTier;
                if (nameEl) nameEl.value = packageName;
                if (priceEl) priceEl.value = packagePrice;

                const summaryPkg = document.getElementById('summaryPackage');
                if (summaryPkg) summaryPkg.textContent = packageName;
                const summaryTotal = document.getElementById('summaryTotal');
                if (summaryTotal) summaryTotal.textContent = formatPHP(parseFloat(packagePrice));
                
                // Update mobile summary
                const mobileSummaryPkg = document.getElementById('mobileSummaryPackage');
                if (mobileSummaryPkg) mobileSummaryPkg.textContent = packageName;
                const mobileSummaryTotal = document.getElementById('mobileSummaryTotal');
                if (mobileSummaryTotal) mobileSummaryTotal.textContent = formatPHP(parseFloat(packagePrice));
            };

            tile.addEventListener('click', selectTile);
            tile.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectTile();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('reservationDate');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }

        const startTimeSelect = document.getElementById('eventStartTime');
        const endTimeSelect = document.getElementById('eventEndTime');

        setupPackageSelectionListeners();

        updateReservationSummary();
        if (dateInput) dateInput.addEventListener('change', updateReservationSummary);
        if (startTimeSelect) startTimeSelect.addEventListener('change', updateReservationSummary);
        if (endTimeSelect) endTimeSelect.addEventListener('change', updateReservationSummary);

        const reservationForm = document.getElementById('reservationForm');
        if (reservationForm) {
            // Helper function to show error
            function showError(field, errorMessageEl, message) {
                field.classList.add('is-invalid');
                if (errorMessageEl) {
                    errorMessageEl.textContent = message;
                    errorMessageEl.classList.add('show');
                }
            }

            // Helper function to clear error
            function clearError(field, errorMessageEl) {
                field.classList.remove('is-invalid');
                if (errorMessageEl) {
                    errorMessageEl.textContent = '';
                    errorMessageEl.classList.remove('show');
                }
            }

            // Clear errors when user starts typing/selecting
            const fullNameEl = document.getElementById('fullName');
            const emailEl = document.getElementById('email');
            const mobileEl = document.getElementById('mobile');
            
            if (fullNameEl) {
                fullNameEl.addEventListener('input', function() {
                    clearError(this, document.getElementById('fullNameError'));
                });
            }
            if (emailEl) {
                emailEl.addEventListener('input', function() {
                    clearError(this, document.getElementById('emailError'));
                });
            }
            if (mobileEl) {
                mobileEl.addEventListener('input', function() {
                    clearError(this, document.getElementById('mobileError'));
                });
            }
            if (dateInput) {
                dateInput.addEventListener('input', function() {
                    clearError(this, document.getElementById('reservationDateError'));
                });
            }
            if (startTimeSelect) {
                startTimeSelect.addEventListener('change', function() {
                    clearError(this, document.getElementById('eventStartTimeError'));
                });
            }
            if (endTimeSelect) {
                endTimeSelect.addEventListener('change', function() {
                    clearError(this, document.getElementById('eventEndTimeError'));
                });
            }
            
            // Clear package error when package is selected
            const packageTiles = document.querySelectorAll('.package-tile');
            packageTiles.forEach(tile => {
                tile.addEventListener('click', function() {
                    const packageErrorEl = document.getElementById('packageError');
                    if (packageErrorEl) {
                        packageErrorEl.textContent = '';
                        packageErrorEl.classList.remove('show');
                    }
                });
            });

            reservationForm.addEventListener('submit', function(e) {
                const fullNameEl = document.getElementById('fullName');
                const emailEl = document.getElementById('email');
                const mobileEl = document.getElementById('mobile');
                const packageId = (document.getElementById('packageId') || {}).value || '';
                
                const fullName = fullNameEl ? fullNameEl.value.trim() : '';
                const email = emailEl ? emailEl.value.trim() : '';
                const mobile = mobileEl ? mobileEl.value.trim() : '';
                const dateValue = dateInput ? dateInput.value : '';
                const startTimeValue = startTimeSelect ? startTimeSelect.value : '';
                const endTimeValue = endTimeSelect ? endTimeSelect.value : '';

                // Clear all previous errors
                const allFields = [
                    { field: fullNameEl, errorId: 'fullNameError' },
                    { field: emailEl, errorId: 'emailError' },
                    { field: mobileEl, errorId: 'mobileError' },
                    { field: dateInput, errorId: 'reservationDateError' },
                    { field: startTimeSelect, errorId: 'eventStartTimeError' },
                    { field: endTimeSelect, errorId: 'eventEndTimeError' }
                ].filter(item => item.field);
                
                allFields.forEach(item => {
                    clearError(item.field, document.getElementById(item.errorId));
                });

                let hasErrors = false;
                let errorMessages = [];

                // Validate full name
                if (!fullName) {
                    showError(fullNameEl, document.getElementById('fullNameError'), 'Please enter your full name');
                    hasErrors = true;
                    errorMessages.push('Full Name');
                }

                // Validate email
                if (!email) {
                    showError(emailEl, document.getElementById('emailError'), 'Please enter your email address');
                    hasErrors = true;
                    errorMessages.push('Email Address');
                } else {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        showError(emailEl, document.getElementById('emailError'), 'Please enter a valid email address');
                        hasErrors = true;
                        errorMessages.push('Email Address');
                    }
                }

                // Validate mobile
                if (!mobile) {
                    showError(mobileEl, document.getElementById('mobileError'), 'Please enter your mobile number');
                    hasErrors = true;
                    errorMessages.push('Mobile Number');
                } else if (mobile.length < 10) {
                    showError(mobileEl, document.getElementById('mobileError'), 'Please enter a valid mobile number');
                    hasErrors = true;
                    errorMessages.push('Mobile Number');
                }

                // Validate package selection
                if (!packageId) {
                    const packageErrorEl = document.getElementById('packageError');
                    if (packageErrorEl) {
                        packageErrorEl.textContent = 'Please select a package';
                        packageErrorEl.classList.add('show');
                    }
                    hasErrors = true;
                    errorMessages.push('Package');
                }

                // Validate date
                if (!dateValue) {
                    showError(dateInput, document.getElementById('reservationDateError'), 'Please select a preferred date');
                    hasErrors = true;
                    errorMessages.push('Preferred Date');
                } else {
                    const selectedDate = new Date(dateValue + 'T00:00:00');
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selectedDate < today) {
                        showError(dateInput, document.getElementById('reservationDateError'), 'Please select a future date');
                        hasErrors = true;
                        errorMessages.push('Preferred Date');
                    }
                }

                // Validate start time
                if (!startTimeValue) {
                    showError(startTimeSelect, document.getElementById('eventStartTimeError'), 'Please select a start time');
                    hasErrors = true;
                    errorMessages.push('Start Time');
                }

                // Validate end time
                if (!endTimeValue) {
                    showError(endTimeSelect, document.getElementById('eventEndTimeError'), 'Please select an end time');
                    hasErrors = true;
                    errorMessages.push('End Time');
                }

                // Validate time order
                if (startTimeValue && endTimeValue) {
                    const timeOrder = ['08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM', '09:00 PM', '10:00 PM', '11:00 PM'];
                    const startIndex = timeOrder.indexOf(startTimeValue);
                    const endIndex = timeOrder.indexOf(endTimeValue);
                    if (startIndex >= endIndex) {
                        showError(endTimeSelect, document.getElementById('eventEndTimeError'), 'Event end time must be after start time');
                        hasErrors = true;
                        errorMessages.push('End Time');
                    }
                }

                if (hasErrors) {
                    e.preventDefault();
                    
                    // Show error modal
                    const errorModal = document.getElementById('reservationErrorModal');
                    const errorMessageEl = document.getElementById('reservationErrorModalMessage');
                    if (errorModal && errorMessageEl) {
                        const message = errorMessages.length > 0 
                            ? `Please complete the following required fields: ${errorMessages.join(', ')}.`
                            : 'Please fill in all required fields before submitting.';
                        errorMessageEl.textContent = message;
                        const bsModal = new bootstrap.Modal(errorModal);
                        bsModal.show();
                    }
                    
                    // Scroll to first error
                    const firstErrorField = allFields.find(item => item.field && item.field.classList.contains('is-invalid'));
                    if (firstErrorField && firstErrorField.field) {
                        firstErrorField.field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstErrorField.field.focus();
                    }
                    
                    return false;
                }
            });
        }
    });

})();

