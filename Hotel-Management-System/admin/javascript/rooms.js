// Room Type Edit Functions for Add Room Type Modal
function enableEditMode(typeId, currentName) {
    document.getElementById('displayMode' + typeId).classList.add('d-none');
    document.getElementById('editMode' + typeId).classList.remove('d-none');
    const input = document.getElementById('editInput' + typeId);
    input.value = currentName;
    input.focus();
    input.select();
}

function cancelEditMode(typeId) {
    document.getElementById('displayMode' + typeId).classList.remove('d-none');
    document.getElementById('editMode' + typeId).classList.add('d-none');
}

// Room Type Edit Functions for Delete/Edit Room Type Modal
function enableDeleteModalEditMode(typeId, currentName) {
    document.getElementById('deleteDisplayMode' + typeId).classList.add('d-none');
    document.getElementById('deleteEditMode' + typeId).classList.remove('d-none');
    const input = document.getElementById('deleteEditInput' + typeId);
    input.value = currentName;
    input.focus();
    input.select();
}

function cancelDeleteModalEditMode(typeId) {
    document.getElementById('deleteDisplayMode' + typeId).classList.remove('d-none');
    document.getElementById('deleteEditMode' + typeId).classList.add('d-none');
}

// Pagination variables
const roomsPerPage = 7;
let currentPage = 1;
let currentFilter = 'All';

function filterRooms(roomType) {
    currentFilter = roomType;
    currentPage = 1; // Reset to first page when filter changes

    // Update active tab
    document.querySelectorAll('#roomTabs .nav-link').forEach(tab => {
        tab.classList.remove('active');
    });
    const activeTab = document.querySelector(`#roomTabs [data-room-type="${roomType}"]`);
    if (activeTab) {
        activeTab.classList.add('active');
    }

    applyPagination();
}

function getFilteredRows() {
    const tableRows = document.querySelectorAll('#roomsTableBody tr');
    const filtered = [];
    tableRows.forEach(row => {
        if (currentFilter === 'All' || row.dataset.roomType === currentFilter) {
            filtered.push(row);
        }
    });
    return filtered;
}

function applyPagination() {
    const filteredRows = getFilteredRows();
    const totalRooms = filteredRows.length;
    const totalPages = Math.ceil(totalRooms / roomsPerPage);

    // Ensure current page is valid
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * roomsPerPage;
    const endIndex = Math.min(startIndex + roomsPerPage, totalRooms);

    // Hide all rows first
    document.querySelectorAll('#roomsTableBody tr').forEach(row => {
        row.style.display = 'none';
    });

    // Show only rows for current page
    filteredRows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = '';
        }
    });

    // Update pagination info (top and bottom)
    AdminPagination.updatePaginationInfo(startIndex + 1, endIndex, totalRooms, {
        topStartId: 'showingStart',
        topEndId: 'showingEnd',
        topTotalId: 'totalRooms',
        bottomStartId: 'showingStartBottom',
        bottomEndId: 'showingEndBottom',
        bottomTotalId: 'totalRoomsBottom'
    });

    // Generate pagination controls
    AdminPagination.generatePaginationControls(totalPages, currentPage, 'paginationControls', 'paginationControlsBottom', 'goToPage');
}

function goToPage(page) {
    const filteredRows = getFilteredRows();
    const totalPages = Math.ceil(filteredRows.length / roomsPerPage);

    if (page < 1 || page > totalPages) return;

    currentPage = page;
    applyPagination();
}

document.addEventListener('DOMContentLoaded', function () {
    filterRooms('All');
});

// Function to add custom feature via AJAX
function addCustomFeature(containerId, inputId, checkboxName, roomId = null, categorySelectId = null) {
    const input = document.getElementById(inputId);
    const featureName = input.value.trim();
    let category = 'General';
    if (categorySelectId) {
        const categorySelect = document.getElementById(categorySelectId);
        if (categorySelect) {
            category = categorySelect.value;
        }
    }

    if (!featureName) {
        showAlert('Please enter a feature name to continue.', 'warning', 'Feature Name Required');
        input.focus();
        return;
    }

    // Create FormData
    const formData = new FormData();
    formData.append('featureName', featureName);
    formData.append('category', category);

    // Send AJAX request
    fetch('php/add_feature.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addFeatureCheckboxToCategory(containerId, data.featureId, data.featureName, data.category, checkboxName, roomId, true);
                addFeatureToAllContainers(data.featureId, data.featureName, data.category, containerId);
                input.value = '';
                showToast('Feature "' + data.featureName + '" added to ' + data.category + ' successfully!', 'success');
            } else if (data.error === 'Feature already exists') {
                const existingCheckbox = document.querySelector('#' + containerId + ' input[value="' + data.featureId + '"]');
                if (existingCheckbox) {
                    existingCheckbox.checked = true;
                    showToast('Feature already exists. It has been selected.', 'info');
                } else {
                    addFeatureCheckboxToCategory(containerId, data.featureId, featureName, data.category || 'General', checkboxName, roomId, true);
                    showToast('Feature already exists. It has been added and selected.', 'info');
                }
                input.value = '';
            } else {
                showToast('Error: ' + data.error, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while adding the feature', 'danger');
        });
}

function addFeatureCheckboxToCategory(containerId, featureId, featureName, category, checkboxName, roomId = null, isChecked = false) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const existingCheckbox = container.querySelector('input[value="' + featureId + '"]');
    if (existingCheckbox) {
        if (isChecked) existingCheckbox.checked = true;
        return;
    }

    // Find existing category section by looking for h6 with matching text
    let categorySection = null;
    const allSections = container.querySelectorAll('.mb-3');
    allSections.forEach(section => {
        const h6 = section.querySelector('h6');
        if (h6 && h6.textContent.trim() === category) {
            categorySection = section;
        }
    });

    if (!categorySection) {
        // Create new category section only if it doesn't exist
        categorySection = document.createElement('div');
        categorySection.className = 'col-12 col-md-6 col-lg-4 mb-3';
        categorySection.innerHTML = `
                    <h6 class="text-muted border-bottom pb-1"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(category)}</h6>
                    <div class="row category-features"></div>
                `;

        // Insert before the "Add Custom Feature" section if it exists
        const customFeatureSection = container.querySelector('.border-top.pt-3')?.closest('.row.justify-content-center')
            || container.querySelector('.mt-3.border-top');
        if (customFeatureSection) {
            customFeatureSection.parentNode.insertBefore(categorySection, customFeatureSection);
        } else {
            // For add modal, insert into the row container
            const rowContainer = container.querySelector('.row.justify-content-center');
            if (rowContainer) {
                rowContainer.appendChild(categorySection);
            } else {
                container.appendChild(categorySection);
            }
        }
    }

    // Find the features row within the category section
    let featuresRow = categorySection.querySelector('.category-features') || categorySection.querySelector('.row');
    if (!featuresRow) {
        featuresRow = document.createElement('div');
        featuresRow.className = 'row category-features';
        categorySection.appendChild(featuresRow);
    }

    const checkboxId = roomId ? 'editFeature' + roomId + '_' + featureId : 'feature' + featureId;
    const colDiv = document.createElement('div');
    colDiv.className = 'col-6';
    colDiv.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="${checkboxName}" value="${featureId}" id="${checkboxId}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="${checkboxId}">
                        ${escapeHtml(featureName)}
                    </label>
                </div>
            `;

    featuresRow.appendChild(colDiv);
}

function addFeatureCheckbox(containerId, featureId, featureName, checkboxName, roomId = null, isChecked = false) {
    addFeatureCheckboxToCategory(containerId, featureId, featureName, 'General', checkboxName, roomId, isChecked);
}

function addFeatureToAllContainers(featureId, featureName, category, excludeContainerId) {
    if (excludeContainerId !== 'addRoomFeaturesContainer') {
        addFeatureCheckboxToCategory('addRoomFeaturesContainer', featureId, featureName, category, 'features[]', null, false);
    }

    document.querySelectorAll('[id^="editRoomFeaturesContainer"]').forEach(container => {
        if (container.id !== excludeContainerId) {
            const roomId = container.id.replace('editRoomFeaturesContainer', '');
            addFeatureCheckboxToCategory(container.id, featureId, featureName, category, 'editFeatures[]', roomId, false);
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.cssText = 'z-index: 99999; max-width: 600px; width: calc(100% - 2rem);';
    alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
    document.body.appendChild(alertDiv);
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}