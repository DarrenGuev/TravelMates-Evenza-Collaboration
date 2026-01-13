document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('forgotPasswordForm');
  if (!form) return;

  const emailEl = document.getElementById('email');
  const newPasswordEl = document.getElementById('new_password');
  const confirmPasswordEl = document.getElementById('confirm_password');

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
  if (newPasswordEl) {
    newPasswordEl.addEventListener('input', function() {
      clearError(this, document.getElementById('newPasswordError'));
    });
  }
  if (confirmPasswordEl) {
    confirmPasswordEl.addEventListener('input', function() {
      clearError(this, document.getElementById('confirmPasswordError'));
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
    if (newPasswordEl) clearError(newPasswordEl, document.getElementById('newPasswordError'));
    if (confirmPasswordEl) clearError(confirmPasswordEl, document.getElementById('confirmPasswordError'));

    const email = emailEl ? emailEl.value.trim() : '';
    const newPassword = newPasswordEl ? newPasswordEl.value : '';
    const confirmPassword = confirmPasswordEl ? confirmPasswordEl.value : '';
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

    // Validate new password
    if (!newPassword) {
      showError(newPasswordEl, document.getElementById('newPasswordError'), 'Please enter a new password');
      hasErrors = true;
      errorMessages.push('New Password');
    } else {
      const passwordValidation = validatePassword(newPassword);
      if (passwordValidation !== true) {
        showError(newPasswordEl, document.getElementById('newPasswordError'), passwordValidation);
        hasErrors = true;
        errorMessages.push('New Password');
      }
    }

    // Validate confirm password
    if (!confirmPassword) {
      showError(confirmPasswordEl, document.getElementById('confirmPasswordError'), 'Please confirm your new password');
      hasErrors = true;
      errorMessages.push('Confirm Password');
    } else if (newPassword && confirmPassword && newPassword !== confirmPassword) {
      showError(confirmPasswordEl, document.getElementById('confirmPasswordError'), 'Passwords do not match');
      hasErrors = true;
      errorMessages.push('Confirm Password');
    }

    if (hasErrors) {
      e.preventDefault();
      
      // Show error modal
      const errorModal = document.getElementById('forgotPasswordErrorModal');
      const errorMessageEl = document.getElementById('forgotPasswordErrorModalMessage');
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
      } else if (newPasswordEl && newPasswordEl.classList.contains('is-invalid')) {
        newPasswordEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        newPasswordEl.focus();
      } else if (confirmPasswordEl && confirmPasswordEl.classList.contains('is-invalid')) {
        confirmPasswordEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        confirmPasswordEl.focus();
      }
      
      return false;
    }
  });
});

