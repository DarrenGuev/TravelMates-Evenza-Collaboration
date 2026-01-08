// Confirm delete functions
function confirmDeleteFeature(featureId) {
    if (confirm('Are you sure you want to delete this feature?')) {
        document.getElementById('deleteFeatureForm' + featureId).submit();
    }
}

function confirmDeleteCategory(catId, catName) {
    if (confirm('Are you sure you want to delete the category: ' + catName + '?')) {
        document.getElementById('deleteCategoryForm' + catId).submit();
    }
}

// Pagination and filter variables
const featuresPerPage = 7;
let currentPage = 1;
let currentFilter = 'All';

// Category edit mode functions
function enableCategoryEditMode(catId, currentName) {
    document.getElementById('displayMode' + catId).classList.add('d-none');
    document.getElementById('editMode' + catId).classList.remove('d-none');
    const input = document.getElementById('editInput' + catId);
    input.value = currentName;
    input.focus();
    input.select();
}

function cancelCategoryEditMode(catId) {
    document.getElementById('displayMode' + catId).classList.remove('d-none');
    document.getElementById('editMode' + catId).classList.add('d-none');
}

function filterFeatures(category) {
    currentFilter = category;
    currentPage = 1; // Reset to first page when filter changes

    // Update active tab
    document.querySelectorAll('#categoryTabs .nav-link').forEach(tab => {
        if (tab.getAttribute('data-category') === category) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    applyPagination();
}

function getFilteredRows() {
    const tableRows = document.querySelectorAll('#featuresTableBody tr:not(#noFeaturesRow)');
    const filtered = [];
    tableRows.forEach(row => {
        if (currentFilter === 'All' || row.dataset.category === currentFilter) {
            filtered.push(row);
        }
    });
    return filtered;
}

function applyPagination() {
    const filteredRows = getFilteredRows();
    const totalFeatures = filteredRows.length;
    const totalPages = Math.ceil(totalFeatures / featuresPerPage);

    // Ensure current page is valid
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * featuresPerPage;
    const endIndex = Math.min(startIndex + featuresPerPage, totalFeatures);

    // Hide all rows first
    document.querySelectorAll('#featuresTableBody tr').forEach(row => {
        row.style.display = 'none';
    });

    // Show only rows for current page
    filteredRows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = '';
        }
    });

    // Show/hide empty state row
    const noFeaturesRow = document.getElementById('noFeaturesRow');
    const noFeaturesMessage = document.getElementById('noFeaturesMessage');
    if (totalFeatures === 0) {
        noFeaturesRow.style.display = '';
        if (currentFilter === 'All') {
            noFeaturesMessage.textContent = 'There are no features available. Add a new feature to get started.';
        } else {
            noFeaturesMessage.textContent = `There are no features in the "${currentFilter}" category yet.`;
        }
    } else {
        noFeaturesRow.style.display = 'none';
    }

    // Update pagination info (top and bottom)
    updatePaginationInfo(totalFeatures > 0 ? startIndex + 1 : 0, endIndex, totalFeatures);

    // Generate pagination controls
    generatePaginationControls(totalPages);
}

function updatePaginationInfo(start, end, total) {
    // Top pagination info
    document.getElementById('showingStart').textContent = start;
    document.getElementById('showingEnd').textContent = end;
    document.getElementById('totalFeatures').textContent = total;

    // Bottom pagination info
    document.getElementById('showingStartBottom').textContent = start;
    document.getElementById('showingEndBottom').textContent = end;
    document.getElementById('totalFeaturesBottom').textContent = total;
}

function generatePaginationControls(totalPages) {
    const paginationHTML = generatePaginationHTML(totalPages);
    document.getElementById('paginationControls').innerHTML = paginationHTML;
    document.getElementById('paginationControlsBottom').innerHTML = paginationHTML;
}

function generatePaginationHTML(totalPages) {
    if (totalPages <= 1) {
        return '';
    }

    let html = '';

    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;" aria-label="Previous">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>`;

    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page and ellipsis
    if (startPage > 1) {
        html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="goToPage(1); return false;">1</a>
                </li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                </li>`;
    }

    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a>
                </li>`;
    }

    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>`;

    return html;
}

function goToPage(page) {
    const filteredRows = getFilteredRows();
    const totalPages = Math.ceil(filteredRows.length / featuresPerPage);

    if (page < 1 || page > totalPages) return;

    currentPage = page;
    applyPagination();
}

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function () {
    filterFeatures('All');
});