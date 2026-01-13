(function() {
    'use strict';

    window.filterEvents = function() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const eventCards = document.querySelectorAll('.event-card-grid');

        eventCards.forEach(card => {
            const eventNameEl = card.querySelector('.event-name');
            const eventDateEl = card.querySelector('.event-date-time');
            const eventName = eventNameEl ? eventNameEl.textContent.toLowerCase() : '';
            const eventDate = eventDateEl ? eventDateEl.textContent.toLowerCase() : '';

            const matches = !searchTerm || eventName.includes(searchTerm) || eventDate.includes(searchTerm);

            card.style.display = matches ? '' : 'none';
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounce);
                debounce = setTimeout(filterEvents, 250);
            });
        }

        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 80);
                }
            });
        }, observerOptions);

        const eventCards = document.querySelectorAll('.event-card-grid');
        eventCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    });

})();

