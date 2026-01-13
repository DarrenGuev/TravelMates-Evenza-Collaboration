<?php
session_start();

require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TravelMates</title>
    <link rel="icon" type="image/png" href="<?php echo IMAGES_URL; ?>/logo/logoW.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
</head>

<body>
    <?php include CHATBOT_PATH . '/chatbotUI.php'; ?>
    <?php include INCLUDES_PATH . '/navbar.php'; ?>

    <div class="container pt-5" id="eventsContainer">
        <div class="row">
            <div class="col">
                <h2 class="mt-5 pt-5 mb-2 text-center fw-bold h-font">EVENTS</h2>
                <div class="mx-auto mt-3 mb-3" style="width: 80px; height: 4px; background-color: #FF9900;"></div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-4">
        <div class="row mb-3">
            <div class="col-12">
                <span class="text-muted">Showing <span id="showingStart">0</span>-<span id="showingEnd">0</span> of <span id="totalEvents">0</span> events</span>
            </div>
        </div>
        <div class="row g-4 pb-5" id="eventsCardContainer">
            <!--dito mag popopulate ang cards-->
        </div>
        <div class="row" id="eventsLoader">
            <div class="col text-center py-5">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading events...</p>
            </div>
        </div>
        <div class="row d-none" id="eventsError">
            <div class="col text-center py-5">
                <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                <p class="mt-2 text-muted">Failed to load events. Please try again later.</p>
            </div>
        </div>
        <!--no events message -->
        <div class="row d-none" id="noEvents">
            <div class="col text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                <p class="mt-2 text-muted">No events found.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <nav aria-label="Event pagination">
                    <ul class="pagination justify-content-center" id="paginationBottom">
                        <!--dito rin mag popopulate yung pagination-->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <?php include INCLUDES_PATH . '/footer.php'; ?>

    <script>
        window.IMAGES_URL = '<?php echo IMAGES_URL; ?>';
    </script>
    <script src="<?php echo JS_URL; ?>/changeMode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        const EVENTS_API_URL = 'http://localhost/TravelMates-Evenza-Collaboration/evenza/api/events.php';
        let allEvents = [];
        let currentPage = 1;
        const eventsPerPage = 8;

        async function fetchEvents() {
            const loader = document.getElementById('eventsLoader');
            const errorDiv = document.getElementById('eventsError');
            const container = document.getElementById('eventsCardContainer');

            try {
                loader.classList.remove('d-none');
                errorDiv.classList.add('d-none');
                container.innerHTML = '';

                const response = await fetch(EVENTS_API_URL);
                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();

                if (result.success && result.data && result.data.events) {
                    allEvents = result.data.events;
                    renderEvents();
                } else {
                    throw new Error('Invalid data format');
                }
            } catch (error) {
                console.error('Error fetching events:', error);
                errorDiv.classList.remove('d-none');
            } finally {
                loader.classList.add('d-none');
            }
        }

        function renderEvents() {
            const container = document.getElementById('eventsCardContainer');
            const noEventsDiv = document.getElementById('noEvents');

            container.innerHTML = '';

            if (allEvents.length === 0) {
                noEventsDiv.classList.remove('d-none');
                updatePaginationInfo(0, 0, 0);
                return;
            }

            noEventsDiv.classList.add('d-none');

            //this will calculate pagination
            const totalEvents = allEvents.length;
            const totalPages = Math.ceil(totalEvents / eventsPerPage);
            const startIndex = (currentPage - 1) * eventsPerPage;
            const endIndex = Math.min(startIndex + eventsPerPage, totalEvents);

            // Render events for current page
            const eventsToShow = allEvents.slice(startIndex, endIndex);
            eventsToShow.forEach(event => {
                const card = createEventCard(event);
                container.appendChild(card);
            });
            updatePaginationInfo(startIndex + 1, endIndex, totalEvents);
            renderPagination(totalPages);
        }

        function updatePaginationInfo(start, end, total) {
            document.getElementById('showingStart').textContent = start;
            document.getElementById('showingEnd').textContent = end;
            document.getElementById('totalEvents').textContent = total;
        }

        function renderPagination(totalPages) {
            const paginationBottom = document.getElementById('paginationBottom');

            const paginationHTML = generatePaginationHTML(totalPages);
            paginationBottom.innerHTML = paginationHTML;
        }

        function generatePaginationHTML(totalPages) {
            if (totalPages <= 1) return '';

            let html = '';

            // Previous button
            html += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link border-0 shadow-sm" href="#" onclick="goToPage(${currentPage - 1}); return false;" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            `;

            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            // First page
            if (startPage > 1) {
                html += `
                    <li class="page-item">
                        <a class="page-link border-0 shadow-sm" href="#" onclick="goToPage(1); return false;">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    html += '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === currentPage ? 'active bg-warning border-warning' : '';
                html += `
                    <li class="page-item ${activeClass}">
                        <a class="page-link border-0 shadow-sm ${activeClass}" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            // Last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
                }
                html += `
                    <li class="page-item">
                        <a class="page-link border-0 shadow-sm" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a>
                    </li>
                `;
            }

            // Next button
            html += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link border-0 shadow-sm" href="#" onclick="goToPage(${currentPage + 1}); return false;" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            `;

            return html;
        }

        //this will navigate to specific page
        function goToPage(page) {
            const totalPages = Math.ceil(allEvents.length / eventsPerPage);
            if (page < 1 || page > totalPages) return;

            currentPage = page;
            renderEvents();

            document.getElementById('eventsContainer').scrollIntoView({
                behavior: 'smooth'
            }); ///scroll to top of the events
        }

        //create a single event card
        function createEventCard(event) {
            const col = document.createElement('div');
            col.className = 'col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3';

            //truncate description for preview
            const shortDesc = event.description.length > 100 ?
                event.description.substring(0, 100) + '...' :
                event.description;

            col.innerHTML = `
                <div class="card h-100 bg-transparent shadow rounded-3">
                    <div class="ratio ratio-4x3 overflow-hidden rounded-top-3 position-relative gallery-item">
                        <img src="${event.image}" 
                             class="card-img-top img-fluid" 
                             alt="${escapeHtml(event.title)}"
                             onerror="this.src='${window.IMAGES_URL}/carousel/carousel1.jpg'">
                    </div>
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-1">
                            ${escapeHtml(event.title)}
                        </h5>
                        <p class="text-secondary fst-italic small mb-2">
                            ${escapeHtml(event.category)} Event
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-geo-alt-fill me-1"></i>${escapeHtml(event.venue)}
                        </p>
                        <div class="fst-italic">
                            <span class="text-muted small">${escapeHtml(shortDesc)}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 p-4 pt-0">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="${event.links.reserve}" class="btn btn-warning flex-grow-1">Reserve Now</a>
                            <a href="${event.links.view}" class="btn btn-outline-secondary flex-grow-1">
                                View Full Details
                            </a>
                        </div>
                    </div>
                </div>
            `;

            return col;
        }

        //escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', fetchEvents);
    </script>
</body>

</html>