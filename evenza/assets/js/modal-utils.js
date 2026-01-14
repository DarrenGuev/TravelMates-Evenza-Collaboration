/**
 * Modal Utility Functions
 * Reusable functions for showing success/error modals across the application
 */

/**
 * Show a custom modal with success or error styling
 * @param {string} message - The message to display
 * @param {string} type - 'success' or 'error'
 * @param {string} title - Optional custom title (defaults to "Action Successful" or "Error")
 */
function showCustomModal(message, type = 'success', title = null) {
    // Ensure modals exist in the DOM
    let successModal = document.getElementById('customSuccessModal');
    let errorModal = document.getElementById('customErrorModal');
    
    // Create modals if they don't exist
    if (!successModal) {
        createModalElements();
        successModal = document.getElementById('customSuccessModal');
        errorModal = document.getElementById('customErrorModal');
    }
    
    if (type === 'success') {
        const modalMessage = document.getElementById('customSuccessModalMessage');
        const modalTitle = document.getElementById('customSuccessModalTitle');
        
        if (modalMessage) modalMessage.textContent = message;
        if (modalTitle) modalTitle.textContent = title || 'Action Successful';
        
        const bsModal = new bootstrap.Modal(successModal, {
            backdrop: true,
            keyboard: true
        });
        bsModal.show();
    } else {
        const modalMessage = document.getElementById('customErrorModalMessage');
        const modalTitle = document.getElementById('customErrorModalTitle');
        
        if (modalMessage) modalMessage.textContent = message;
        if (modalTitle) modalTitle.textContent = title || 'Error';
        
        const bsModal = new bootstrap.Modal(errorModal, {
            backdrop: true,
            keyboard: true
        });
        bsModal.show();
    }
}

/**
 * Create modal elements if they don't exist
 */
function createModalElements() {
    const modalHTML = `
    <!-- Success Modal -->
    <div class="modal fade" id="customSuccessModal" tabindex="-1" aria-labelledby="customSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-check-circle custom-alert-icon success-icon"></i>
                    </div>
                    <h5 class="custom-alert-title" id="customSuccessModalTitle">Action Successful</h5>
                    <p class="custom-alert-message" id="customSuccessModalMessage"></p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn btn-primary-luxury px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="customErrorModal" tabindex="-1" aria-labelledby="customErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-alert-modal">
                <div class="modal-body custom-alert-body text-center">
                    <div class="custom-alert-icon-wrapper mb-4">
                        <i class="fas fa-times-circle custom-alert-icon error-icon"></i>
                    </div>
                    <h5 class="custom-alert-title" id="customErrorModalTitle">Error</h5>
                    <p class="custom-alert-message" id="customErrorModalMessage"></p>
                </div>
                <div class="modal-footer custom-alert-footer justify-content-center">
                    <button type="button" class="btn btn-primary-luxury px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    `;
    
    // Append to body if not already present
    if (!document.getElementById('customSuccessModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

/**
 * Initialize modals on page load (for PHP session-based messages)
 */
function initModalsFromSession() {
    // Check for success message in URL or data attribute
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success_msg');
    const errorMsg = urlParams.get('error_msg');
    
    if (successMsg) {
        showCustomModal(decodeURIComponent(successMsg), 'success');
        // Clean URL
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]success_msg=[^&]*/, '').replace(/[?&]error_msg=[^&]*/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
    
    if (errorMsg) {
        showCustomModal(decodeURIComponent(errorMsg), 'error');
        // Clean URL
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]success_msg=[^&]*/, '').replace(/[?&]error_msg=[^&]*/, '');
        window.history.replaceState({}, document.title, newUrl);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        createModalElements();
        initModalsFromSession();
    });
} else {
    createModalElements();
    initModalsFromSession();
}

