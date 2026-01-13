document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const emailEl = document.getElementById('email');
  const passwordEl = document.getElementById('password');

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

  // Clear errors when user starts typing
  if (emailEl) {
    emailEl.addEventListener('input', function() {
      clearError(this, document.getElementById('emailError'));
    });
  }
  if (passwordEl) {
    passwordEl.addEventListener('input', function() {
      clearError(this, document.getElementById('passwordError'));
    });
  }

  // Password validation function
  function validatePassword(password) {
    if (password.length < 8) {
      return 'Password must be at least 8 characters long.';
    }
    if (!/[A-Z]/.test(password)) {
      return 'Password must contain at least one uppercase letter.';
    }
    if (!/[a-z]/.test(password)) {
      return 'Password must contain at least one lowercase letter.';
    }
    if (!/[0-9]/.test(password)) {
      return 'Password must contain at least one number.';
    }
    return true;
  }

  form.addEventListener('submit', function (e) {
    // Clear all previous errors
    if (emailEl) clearError(emailEl, document.getElementById('emailError'));
    if (passwordEl) clearError(passwordEl, document.getElementById('passwordError'));

    const email = emailEl ? emailEl.value.trim() : '';
    const password = passwordEl ? passwordEl.value : '';
    let hasErrors = false;
    let errorMessages = [];

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

    // Validate password
    if (!password) {
      showError(passwordEl, document.getElementById('passwordError'), 'Please enter your password');
      hasErrors = true;
      errorMessages.push('Password');
    } else {
      const passwordValidation = validatePassword(password);
      if (passwordValidation !== true) {
        showError(passwordEl, document.getElementById('passwordError'), passwordValidation);
        hasErrors = true;
        errorMessages.push('Password');
      }
    }

    if (hasErrors) {
      e.preventDefault();
      
      // Show error modal
      const errorModal = document.getElementById('loginErrorModal');
      const errorMessageEl = document.getElementById('loginErrorModalMessage');
      if (errorModal && errorMessageEl) {
        const message = errorMessages.length > 0 
          ? `Please complete the following required fields: ${errorMessages.join(', ')}.`
          : 'Please fill in all required fields before submitting.';
        errorMessageEl.textContent = message;
        const bsModal = new bootstrap.Modal(errorModal);
        bsModal.show();
      }
      
      // Scroll to first error and focus
      if (emailEl && emailEl.classList.contains('is-invalid')) {
        emailEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        emailEl.focus();
      } else if (passwordEl && passwordEl.classList.contains('is-invalid')) {
        passwordEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        passwordEl.focus();
      }
      
      return false;
    }
  });
});
