<!-- Global Page Loader -->
<div id="page-loader" class="page-loader">
    <div class="loader-content">
        <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-white fw-semibold">Loading...</p>
    </div>
</div>

<style>
    /* Global Page Loader Styles */
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(5px);

    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
}

.page-loader.hidden {
    opacity: 0;
    visibility: hidden;
}

.loader-content {
    text-align: center;
}

.page-loader .spinner-border {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-content p {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Logo loader variant */
.page-loader.with-logo .loader-logo {
    width: 80px;
    height: auto;
    margin-bottom: 1rem;
    animation: bounce 1s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>

<script>
    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        const loader = document.getElementById('page-loader');
        if (loader) {
            setTimeout(function() {
                loader.classList.add('hidden');
                // Remove from DOM after transition
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 300);
            }, 300); // Small delay for smoother UX
        }
    });

    // Show loader on page navigation (optional - for form submissions and links)
    document.addEventListener('DOMContentLoaded', function() {
        // Show loader when navigating away
        window.addEventListener('beforeunload', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.display = 'flex';
                loader.classList.remove('hidden');
            }
        });
    });
</script>