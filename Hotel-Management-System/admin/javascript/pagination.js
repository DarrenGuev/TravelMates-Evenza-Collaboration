(function (window, document) {
    'use strict';

    const AdminPagination = {
        generatePaginationHTML(currentPage, totalPages, goToFnName = 'goToPage') {
            if (totalPages <= 1) return '';

            let html = '';
            // Previous
            html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="${goToFnName}(${currentPage - 1}); return false;" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>`;

            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html += `<li class="page-item">
                            <a class="page-link" href="#" onclick="${goToFnName}(1); return false;">1</a>
                        </li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="${goToFnName}(${i}); return false;">${i}</a>
                        </li>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item">
                            <a class="page-link" href="#" onclick="${goToFnName}(${totalPages}); return false;">${totalPages}</a>
                        </li>`;
            }

            // Next
            html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="${goToFnName}(${currentPage + 1}); return false;" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>`;

            return html;
        },

        generatePaginationControls(totalPages, currentPage, topControlsId, bottomControlsId, goToFnName = 'goToPage') {
            const html = this.generatePaginationHTML(currentPage, totalPages, goToFnName);
            const top = document.getElementById(topControlsId);
            const bottom = document.getElementById(bottomControlsId);
            if (top) top.innerHTML = html;
            if (bottom) bottom.innerHTML = html;
        },

        updatePaginationInfo(start, end, total, ids = {}) {
            const {
                topStartId = 'showingStart',
                topEndId = 'showingEnd',
                topTotalId = 'totalFeatures',
                bottomStartId = 'showingStartBottom',
                bottomEndId = 'showingEndBottom',
                bottomTotalId = 'totalFeaturesBottom'
            } = ids;

            function setText(id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            }

            setText(topStartId, total > 0 ? start : 0);
            setText(topEndId, end);
            setText(topTotalId, total);

            setText(bottomStartId, total > 0 ? start : 0);
            setText(bottomEndId, end);
            setText(bottomTotalId, total);
        }
    };

    window.AdminPagination = AdminPagination;
})(window, document);