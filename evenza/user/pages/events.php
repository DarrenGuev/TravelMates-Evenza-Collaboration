<?php 
session_start();
require_once '../../core/connect.php';

function getEventImagePath($imagePath) {
    $imageDir = '../../assets/images/event_images/';
    $placeholder = $imageDir . 'placeholder.jpg';
    
    if (empty($imagePath)) {
        return $placeholder;
    }
    
    $imagePath = ltrim($imagePath, '/\\');
    
    // Remove '../../assets/' if already present
    if (strpos($imagePath, '../../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../../assets/images/event_images/'));
    }
    // Remove '../assets/' if already present
    if (strpos($imagePath, '../assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('../assets/images/event_images/'));
    }
    // Remove 'assets/' if present
    if (strpos($imagePath, 'assets/images/event_images/') === 0) {
        $imagePath = substr($imagePath, strlen('assets/images/event_images/'));
    }
    
    $filename = basename($imagePath);
    $filename = str_replace(['/', '\\'], '', $filename);
    $imagePath = $imageDir . $filename;
    
    // Check if file exists (use __DIR__ to get script directory and normalize path)
    $fullPath = realpath(__DIR__ . '/' . $imagePath);
    if ($fullPath && file_exists($fullPath)) {
        return $imagePath;
    }
    
    return $placeholder;
}

$events = [];
$errorMessage = '';

$query = "SELECT eventId, title, venue, category, imagePath, description FROM events ORDER BY eventId DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $eventId = isset($row['eventId']) ? $row['eventId'] : 0;
        
        $event = [
            'eventId' => $eventId,
            'id' => $eventId,
            'title' => isset($row['title']) ? $row['title'] : '',
            'venue' => isset($row['venue']) ? $row['venue'] : '',
            'category' => isset($row['category']) ? $row['category'] : '',
            'imagePath' => isset($row['imagePath']) ? $row['imagePath'] : '',
            'status' => 'Active',
            'description' => isset($row['description']) ? $row['description'] : ''
        ];
        
        $originalImagePath = $event['imagePath'];
        $event['imagePath'] = getEventImagePath($event['imagePath']);
        
        if (stripos($event['title'], 'wine') !== false && !file_exists($event['imagePath']) && $event['imagePath'] === '../../assets/images/event_images/placeholder.jpg') {
            if (file_exists(__DIR__ . '/../../assets/images/event_images/wineTasting.jpg')) {
                $event['imagePath'] = '../../assets/images/event_images/wineTasting.jpg';
            }
        }
        
        $events[] = $event;
    }
    mysqli_free_result($result);
} else {
    $events = [];
    $errorMessage = mysqli_error($conn);
}

function getCategoryFilter($category) {
    $category = trim($category);
    $categoryLower = strtolower($category);
    
    $categoryMap = [
        'premium' => 'premium',
        'conference' => 'business',
        'business' => 'business',
        'wedding' => 'weddings',
        'weddings' => 'weddings',
        'seminar' => 'workshops',
        'workshop' => 'workshops',
        'workshops' => 'workshops',
        'social' => 'socials',
        'socials' => 'socials',
        'hotel-hosted events' => 'socials',
        'hotel-hosted' => 'socials'
    ];
    
    if (isset($categoryMap[$categoryLower])) {
        return $categoryMap[$categoryLower];
    }
    
    if (stripos($category, 'wedding') !== false) {
        return 'weddings';
    }
    if (stripos($category, 'workshop') !== false || stripos($category, 'seminar') !== false || 
        stripos($category, 'training') !== false || stripos($category, 'masterclass') !== false) {
        return 'workshops';
    }
    if (stripos($category, 'social') !== false || stripos($category, 'gala') !== false) {
        return 'socials';
    }
    if (stripos($category, 'premium') !== false || stripos($category, 'exhibition') !== false || 
        stripos($category, 'tasting') !== false) {
        return 'premium';
    }
    if (stripos($category, 'business') !== false || stripos($category, 'conference') !== false) {
        return 'business';
    }
    
    return 'all';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Events - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php $activePage = 'events'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="page-header py-5 mt-5">
        <div class="container" style="padding-top: 40px;">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title mb-4">Available Events</h1>

                    <div class="search-filter-section">
                        <div class="row g-3 align-items-end search-filter-row">
                            <div class="col-md-8 search-input-col">
                                <div class="search-box">
                                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>
                                    <input type="text" class="form-control luxury-input" id="searchInput" placeholder="Search by event name or venue...">
                                </div>
                            </div>
                            <div class="col-md-4 category-select-col">
                                <div class="custom-dropdown-wrapper">
                                    <select class="form-select luxury-input" id="categoryFilter" style="display: none;">
                                        <option value="all">All Categories</option>
                                        <option value="business">Business</option>
                                        <option value="weddings">Weddings</option>
                                        <option value="socials">Socials</option>
                                        <option value="workshops">Workshops</option>
                                    </select>
                                    <div class="custom-dropdown" id="customCategoryFilter">
                                        <div class="custom-dropdown-selected">
                                            <span>All Categories</span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="custom-dropdown-options">
                                            <div class="custom-dropdown-option" data-value="all">All Categories</div>
                                            <div class="custom-dropdown-option" data-value="business">Business</div>
                                            <div class="custom-dropdown-option" data-value="weddings">Weddings</div>
                                            <div class="custom-dropdown-option" data-value="socials">Socials</div>
                                            <div class="custom-dropdown-option" data-value="workshops">Workshops</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="events-grid-section" style="padding-top: 5px; padding-bottom: 3rem;">
        <div class="container">
            <div id="noResultsMessage" class="no-results-container" style="display: none;">
                <div class="no-results-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                        <line x1="9" y1="9" x2="13" y2="13"></line>
                        <line x1="13" y1="9" x2="9" y2="13"></line>
                    </svg>
                </div>
                <h2 class="no-results-title">No Events Found</h2>
                <p class="no-results-subtitle" id="noResultsSubtitle">We couldn't find any events matching your search. Please try a different keyword or browse all categories.</p>
                <button class="btn btn-clear-filters" id="clearFiltersBtn">Clear All Filters</button>
            </div>
            
            <div class="row g-4" id="eventsGrid">
                <?php if (!empty($errorMessage)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <p><strong>Database Error:</strong> <?php echo htmlspecialchars($errorMessage); ?></p>
                            <p class="small">Please check your database connection and ensure the events table exists.</p>
                        </div>
                    </div>
                <?php elseif (empty($events)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <p>No events available at this time.</p>
                            <p class="small">Please add events through the admin dashboard.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    
                    echo "<!-- DEBUG: Total events fetched: " . count($events) . " -->\n";
                    foreach ($events as $debugEvent) {
                        echo "<!-- Event: " . htmlspecialchars($debugEvent['title']) . " | Category: " . htmlspecialchars($debugEvent['category'] ?? 'N/A') . " | Status: " . (isset($debugEvent['status']) ? htmlspecialchars($debugEvent['status']) : 'N/A') . " | Filter Category: " . getCategoryFilter($debugEvent['category'] ?? '') . " -->\n";
                    }
                    ?>
                    <?php foreach ($events as $event): 
                        $categoryFilter = getCategoryFilter($event['category']);
                    ?>
                        <div class="col-lg-4 col-md-6 mb-4 event-card-wrapper" 
                             data-category="<?php echo htmlspecialchars($categoryFilter); ?>" 
                             data-name="<?php echo htmlspecialchars($event['title']); ?>">
                            <div class="card event-card h-100">
                                <div class="event-card-image">
                                    <img src="<?php echo htmlspecialchars($event['imagePath']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($event['title']); ?>"
                                         onerror="this.src='../../assets/images/event_images/placeholder.jpg'">
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p class="card-text event-venue-text"><?php echo htmlspecialchars($event['venue']); ?></p>
                                    <a href="eventDetails.php?id=<?php echo $event['id'] ?? $event['eventId']; ?>" class="btn btn-event-view w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/events.js"></script>
    <style>
        .custom-dropdown-wrapper {
            position: relative;
        }
        
        .custom-dropdown {
            position: relative;
            width: 100%;
        }
        
        .custom-dropdown-selected {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border: 2px solid #E8E4DC;
            border-radius: 8px;
            background-color: #FDFCF9;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 48px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark-gray);
        }
        
        .custom-dropdown-selected:hover {
            border-color: var(--accent-olive);
        }
        
        .custom-dropdown-selected i {
            transition: transform 0.3s ease;
            color: #6B7F5A;
        }
        
        .custom-dropdown.open .custom-dropdown-selected i {
            transform: rotate(180deg);
        }
        
        .custom-dropdown.open .custom-dropdown-selected {
            border-color: var(--accent-olive);
            box-shadow: 0 0 0 3px rgba(107, 127, 90, 0.1);
        }
        
        .custom-dropdown-options {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background-color: #FFFFFF;
            border: 2px solid #E8E4DC;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            display: none;
        }
        
        .custom-dropdown.open .custom-dropdown-options {
            display: block;
        }
        
        .custom-dropdown-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 0.95rem;
            color: #1A1A1A;
            background-color: #FFFFFF;
        }
        
        .custom-dropdown-option:first-child {
            border-radius: 6px 6px 0 0;
        }
        
        .custom-dropdown-option:last-child {
            border-radius: 0 0 6px 6px;
        }
        
        .custom-dropdown-option:hover {
            background-color: #6B7F5A !important;
            color: #FFFFFF !important;
        }
        
        .custom-dropdown-option.selected {
            background-color: #6B7F5A !important;
            color: #FFFFFF !important;
            font-weight: 600;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customDropdown = document.getElementById('customCategoryFilter');
            const nativeSelect = document.getElementById('categoryFilter');
            const selectedText = customDropdown ? customDropdown.querySelector('.custom-dropdown-selected span') : null;
            const options = customDropdown ? customDropdown.querySelectorAll('.custom-dropdown-option') : [];
            
            if (customDropdown && selectedText) {
                const initialValue = nativeSelect ? nativeSelect.value : 'all';
                options.forEach(opt => {
                    if (opt.getAttribute('data-value') === initialValue) {
                        opt.classList.add('selected');
                        selectedText.textContent = opt.textContent;
                    }
                });
                
                customDropdown.querySelector('.custom-dropdown-selected').addEventListener('click', function(e) {
                    e.stopPropagation();
                    customDropdown.classList.toggle('open');
                });
                
                document.addEventListener('click', function(e) {
                    if (!customDropdown.contains(e.target)) {
                        customDropdown.classList.remove('open');
                    }
                });
                
                options.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        
                        if (nativeSelect) {
                            nativeSelect.value = value;
                        }
                        
                        options.forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedText.textContent = text;
                        
                        customDropdown.classList.remove('open');
                        
                        if (nativeSelect) {
                            const changeEvent = new Event('change', { bubbles: true });
                            nativeSelect.dispatchEvent(changeEvent);
                        }
                    });
                });
            }
            
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = nativeSelect;
            const eventCards = document.querySelectorAll('.event-card-wrapper');

            function filterEvents() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const selectedCategory = categoryFilter ? categoryFilter.value : 'all';
                const noResultsMessage = document.getElementById('noResultsMessage');
                const eventsGrid = document.getElementById('eventsGrid');
                const noResultsSubtitle = document.getElementById('noResultsSubtitle');
                let visibleCount = 0;

                eventCards.forEach(card => {
                    const cardCategory = card.getAttribute('data-category');
                    const cardName = card.getAttribute('data-name') ? card.getAttribute('data-name').toLowerCase() : '';

                    const matchesSearch = !searchTerm || cardName.includes(searchTerm);
                    const matchesCategory = selectedCategory === 'all' || cardCategory === selectedCategory;

                    if (matchesSearch && matchesCategory) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (visibleCount === 0 && eventCards.length > 0) {
                    let subtitleHTML = "We couldn't find any events. Try adjusting your search or explore our ";
                    subtitleHTML += '<span class="tier-bronze">Bronze</span>, ';
                    subtitleHTML += '<span class="tier-silver">Silver</span>, and ';
                    subtitleHTML += '<span class="tier-gold">Gold</span> curated tiers.';
                    
                    noResultsSubtitle.innerHTML = subtitleHTML;
                    
                    eventsGrid.style.display = 'none';
                    noResultsMessage.style.display = 'flex';
                    setTimeout(() => {
                        noResultsMessage.style.opacity = '1';
                    }, 10);
                } else {
                    eventsGrid.style.display = '';
                    noResultsMessage.style.display = 'none';
                    noResultsMessage.style.opacity = '0';
                }
            }
            
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function() {
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    if (categoryFilter) {
                        categoryFilter.value = 'all';
                    }
                    filterEvents();
                });
            }

            if (eventCards.length > 0) {
                eventCards.forEach(card => {
                    card.style.display = '';
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterEvents);
            }
            if (categoryFilter) {
                categoryFilter.addEventListener('change', filterEvents);
            }
        });
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

