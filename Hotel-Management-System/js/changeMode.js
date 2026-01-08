function changeMode() {
    const wasDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const newTheme = wasDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', newTheme);

    document.querySelectorAll('#mode i, #mode-lg i').forEach(function (icon) {
        icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    });

    const logoPath = newTheme === 'dark' 
        ? (window.IMAGES_URL || '') + '/logo/logoW.png'
        : (window.IMAGES_URL || '') + '/logo/logoB.png';
    
    document.querySelectorAll('#site-logo, #footer-logo').forEach(function (logo) {
        logo.src = logoPath;
    });

    document.querySelectorAll('.text-black, .text-white').forEach(function (el) {
        if (newTheme === 'dark') {
            el.classList.remove('text-black');
            el.classList.add('text-white');
        } else {
            el.classList.remove('text-white');
            el.classList.add('text-black');
        }
    });

    document.querySelectorAll('.btn-outline-dark, .btn-outline-light').forEach(function (el) {
        if (newTheme === 'dark') {
            el.classList.remove('btn-outline-dark');
            el.classList.add('btn-outline-light');
        } else {
            el.classList.remove('btn-outline-light');
            el.classList.add('btn-outline-dark');
        }
    });

    document.querySelectorAll('.badge').forEach(function (badge) {
        if (newTheme === 'light') {
            badge.classList.remove('bg-light', 'text-dark');
            badge.classList.add('bg-dark', 'text-white');
        } else {
            badge.classList.remove('bg-dark', 'text-white');
            badge.classList.add('bg-light', 'text-dark');
        }
    });

    const aboutSection = document.querySelector('#about-section');
    if (aboutSection) {
        const imageUrl = aboutSection.dataset.bgImage || '../images/loginRegisterImg/img.jpg';
        if (wasDark) {
            aboutSection.style.background = `linear-gradient(rgba(245, 240, 230, 0.85), rgba(245, 240, 230, 0.85)), url('${imageUrl}') center/cover no-repeat`;
        } else {
            aboutSection.style.background = `linear-gradient(rgba(30, 30, 30, 0.9), rgba(30, 30, 30, 0.9)), url('${imageUrl}') center/cover no-repeat`;
        }
    }
}

function applyLogo(theme) {
    const logoPath = theme === 'dark' 
        ? (window.IMAGES_URL || '') + '/logo/logoW.png'
        : (window.IMAGES_URL || '') + '/logo/logoB.png';
    document.querySelectorAll('#site-logo, #footer-logo').forEach(function (logo) {
        logo.src = logoPath;
    });
}

function updateStoredModeAndLogo() {
    setTimeout(function () {
        const now = document.documentElement.getAttribute('data-bs-theme') || 'light';
        localStorage.setItem('siteMode', now);
        applyLogo(now);
    }, 10);
}

document.addEventListener('DOMContentLoaded', function () {
    const stored = localStorage.getItem('siteMode');
    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
    
    if (stored && stored !== current) {
        if (typeof changeMode === 'function') {
            changeMode();
        } else {
            document.documentElement.setAttribute('data-bs-theme', stored);
            document.querySelectorAll('#mode i, #mode-lg i').forEach(function (icon) {
                icon.className = stored === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            });
            document.querySelectorAll('.text-black, .text-white').forEach(function (el) {
                el.classList.toggle('text-black');
                el.classList.toggle('text-white');
            });
            document.querySelectorAll('.btn-outline-dark, .btn-outline-light').forEach(function (el) {
                el.classList.toggle('btn-outline-dark');
                el.classList.toggle('btn-outline-light');
            });
            applyLogo(stored);
        }
    }
    
    document.querySelectorAll('#mode, #mode-lg').forEach(function (btn) {
        btn.addEventListener('click', updateStoredModeAndLogo);
    });
});
