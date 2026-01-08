document.getElementById('bookingSelect').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const phone = selectedOption.getAttribute('data-phone');
    if (phone) {
        document.getElementById('phoneNumber').value = phone;
    }
});

document.getElementById('smsMessage').addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;

    if (this.value !== 'Your Booking is Approved.') {
        this.setCustomValidity('Invalid message');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
const smsForm = document.querySelector('#sendSmsModal form');
smsForm.addEventListener('submit', function (event) {
    const phoneInput = document.getElementById('phoneNumber');
    const messageInput = document.getElementById('smsMessage');
    let isValid = true;

    // Phone validation (numbers only)
    if (!/^\d+$/.test(phoneInput.value)) {
        phoneInput.setCustomValidity('Numbers only');
        isValid = false;
    } else {
        phoneInput.setCustomValidity('');
    }

    // Message validation (exact match)
    if (messageInput.value !== 'Your Booking is Approved.') {
        messageInput.setCustomValidity('Invalid message');
        isValid = false;
    } else {
        messageInput.setCustomValidity('');
    }

    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }

    this.classList.add('was-validated');
}, false);