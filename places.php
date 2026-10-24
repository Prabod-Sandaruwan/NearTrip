<?php
/**
 * Destinations page.
 * Displays all tourist attractions with category filters and search.
 */

// Set page title
$pageTitle = "Destinations - NearTrip Local Tourist Day-Visit Planner";

// Connect to database
require_once __DIR__ . '/includes/config.php';

// Initialize itinerary list in session if empty
if (!isset($_SESSION['itinerary']) || !is_array($_SESSION['itinerary'])) {
    $_SESSION['itinerary'] = array();
}

// Include navigation header
include_once __DIR__ . '/includes/header.php';

// Get all categories for the dropdown filter
$categoryList = [];
$catSql = "SELECT * FROM `category` ORDER BY `category_name` ASC";
$catResult = $conn->query($catSql);
if ($catResult && $catResult->num_rows > 0) {
    while ($catRow = $catResult->fetch_assoc()) {
        $categoryList[] = $catRow;
    }
}

// Get filter and search parameters
$selectedCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle category filter by name (e.g. ?category=waterfalls)
if ($selectedCategoryId === 0 && isset($_GET['category']) && !empty($_GET['category'])) {
    $catSlug = trim($_GET['category']);
    foreach ($categoryList as $cat) {
        if (strcasecmp($cat['category_name'], $catSlug) === 0 || stripos($cat['category_name'], $catSlug) !== false) {
            $selectedCategoryId = (int)$cat['category_id'];
            break;
        }
    }
}

// Get the name of the selected category
$selectedCategoryName = '';
if ($selectedCategoryId > 0) {
    foreach ($categoryList as $cat) {
        if ((int)$cat['category_id'] === $selectedCategoryId) {
            $selectedCategoryName = $cat['category_name'];
            break;
        }
    }
}

// Query attractions based on category and search keyword
if ($selectedCategoryId > 0 && !empty($searchQuery)) {
    $searchParam = "%" . $searchQuery . "%";
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            WHERE a.`category_id` = ? AND (a.`name` LIKE ? OR a.`description` LIKE ? OR a.`location` LIKE ?) 
            ORDER BY a.`attraction_id` DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $selectedCategoryId, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($selectedCategoryId > 0) {
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            WHERE a.`category_id` = ? 
            ORDER BY a.`attraction_id` DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selectedCategoryId);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif (!empty($searchQuery)) {
    $searchParam = "%" . $searchQuery . "%";
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            WHERE (a.`name` LIKE ? OR a.`description` LIKE ? OR a.`location` LIKE ?) 
            ORDER BY a.`attraction_id` DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            ORDER BY a.`attraction_id` DESC";
    $result = $conn->query($sql);
}
?>

    <!-- Page banner -->
    <section class="page-hero">
        <div>
            <h1 class="page-hero-title">Explore Destinations</h1>
            <p class="page-hero-subtext">Discover breathtaking attractions, heritage sites, and hidden local gems within a 25 km radius of Warakapola & Dorawaka.</p>
        </div>
    </section>

    <!-- Destinations listing and filter section -->
    <main class="why-choose-us" style="padding: 70px 20px;">
        <div class="section-container">
            
            <div class="section-header">
                <h2 class="section-title">WARAKAPOLA & DORAWAKA ATTRACTIONS</h2>
                <p style="color: var(--text-muted); margin-top: 10px;">Filter attractions by category or search by keywords to plan your visit.</p>
                
                <!-- Search and category filter form -->
                <form action="places.php" method="GET" class="filter-search-bar">
                    
                    <!-- Search input -->
                    <div class="filter-input-wrapper search-box">
                        <i class="fas fa-magnifying-glass search-icon"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search destinations or keywords..." class="filter-control">
                    </div>

                    <!-- Category dropdown -->
                    <div class="filter-input-wrapper select-box">
                        <i class="fas fa-tags select-icon"></i>
                        <select name="category_id" class="filter-control select-control" onchange="this.form.submit();">
                            <option value="0">All Categories (Show All)</option>
                            <?php foreach ($categoryList as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($selectedCategoryId == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter action buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-filter"></i> Filter
                        </button>

                        <?php if ($selectedCategoryId > 0 || !empty($searchQuery)): ?>
                            <!-- Reset filters button -->
                            <a href="places.php" class="btn-filter-reset" title="Show All / Reset Filters">
                                <i class="fas fa-rotate-left"></i> Show All
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>

            <!-- Destinations grid -->
            <div class="destinations-grid">
                
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            // Set image path or use default
                            $rawImg = !empty($row['image_url']) ? $row['image_url'] : 'assets/hero.webp';
                            $cleanImg = ltrim($rawImg, '/');
                            $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
                        ?>
                        <!-- Destination card -->
                        <div class="place-card">
                            
                            <!-- Card image and category badge -->
                            <div class="place-card-img-wrapper">
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="place-card-img">
                                <?php if (!empty($row['category_name'])): ?>
                                    <span class="place-card-category-badge">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Card details -->
                            <div class="place-card-body">
                                <h3 class="place-card-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                                
                                <?php if (!empty($row['location'])): ?>
                                    <div class="place-card-location">
                                        <i class="fas fa-location-dot"></i>
                                        <span><?php echo htmlspecialchars($row['location']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <p class="place-card-desc">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>

                                <div class="place-card-actions">
                                    <a href="details.php?id=<?php echo $row['attraction_id']; ?>" class="btn-card-action btn-explore">
                                        <i class="fas fa-compass"></i> Explore Details
                                    </a>
                                    <?php if (in_array($row['attraction_id'], $_SESSION['itinerary'])): ?>
                                        <a href="plan.php?remove=<?php echo $row['attraction_id']; ?>" class="btn-card-action btn-itinerary added" title="Click to remove from itinerary">
                                            <i class="fas fa-check-circle"></i> In Itinerary
                                        </a>
                                    <?php else: ?>
                                        <a href="plan.php?add=<?php echo $row['attraction_id']; ?>" class="btn-card-action btn-itinerary">
                                            <i class="fas fa-plus"></i> Add to Itinerary
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Empty state when no destinations match search -->
                    <div style="grid-column: 1 / -1;">
                        <?php
                            if ($selectedCategoryId > 0 && !empty($searchQuery)) {
                                render_no_results([
                                    'title'          => 'No Matching Attractions Found',
                                    'message'        => 'No places found under "' . (!empty($selectedCategoryName) ? $selectedCategoryName : 'Category') . '" matching "' . $searchQuery . '".',
                                    'icon'           => 'fas fa-filter-circle-xmark',
                                    'action_url'     => 'places.php',
                                    'action_text'    => 'Show All Destinations',
                                    'action_icon'    => 'fas fa-rotate-left'
                                ]);
                            } elseif ($selectedCategoryId > 0) {
                                render_no_results([
                                    'title'          => 'No Destinations in Selected Category',
                                    'message'        => 'There are currently no attractions added under category "' . (!empty($selectedCategoryName) ? $selectedCategoryName : 'Category') . '".',
                                    'icon'           => 'fas fa-tags',
                                    'action_url'     => 'places.php',
                                    'action_text'    => 'Show All Categories',
                                    'action_icon'    => 'fas fa-rotate-left'
                                ]);
                            } elseif (!empty($searchQuery)) {
                                render_no_results([
                                    'title'          => 'No Destinations Found',
                                    'message'        => 'We couldn\'t find any destinations matching "' . $searchQuery . '". Try different keywords.',
                                    'icon'           => 'fas fa-magnifying-glass-location',
                                    'action_url'     => 'places.php',
                                    'action_text'    => 'Clear Search',
                                    'action_icon'    => 'fas fa-xmark'
                                ]);
                            } else {
                                render_no_results([
                                    'title'          => 'No Destinations Found',
                                    'message'        => 'Attraction records added in the database will automatically display here.',
                                    'icon'           => 'fas fa-map-location-dot',
                                    'action_url'     => 'index.php',
                                    'action_text'    => 'Back to Home',
                                    'action_icon'    => 'fas fa-house'
                                ]);
                            }
                        ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </main>

<?php
// Include footer component
include_once __DIR__ . '/includes/footer.php';
?>
