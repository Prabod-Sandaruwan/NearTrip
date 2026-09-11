<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Destinations Page (places.php)
 * 
 * Requirement Coverage:
 * - Category Filtering (FR-CAT-01–04)
 * - Prepared SQL Statements (WHERE category_id = ?)
 * - Search & Filter Reset ("Show All")
 * - User-Friendly "No Results" Integration (FR-PL-04)
 * ===================================================================
 */

// Define page title prior to including header
$pageTitle = "Destinations - NearTrip Local Tourist Day-Visit Planner";

// Include database configuration & connection to DB 'neartrip'
require_once __DIR__ . '/includes/config.php';

// Initialize itinerary session array if not exists
if (!isset($_SESSION['itinerary']) || !is_array($_SESSION['itinerary'])) {
    $_SESSION['itinerary'] = array();
}

// Include navigation header component
include_once __DIR__ . '/includes/header.php';

// Fetch all categories for dropdown filter (FR-CAT-01)
$categoryList = [];
$catSql = "SELECT * FROM `category` ORDER BY `category_name` ASC";
$catResult = $conn->query($catSql);
if ($catResult && $catResult->num_rows > 0) {
    while ($catRow = $catResult->fetch_assoc()) {
        $categoryList[] = $catRow;
    }
}

// Fetch GET filter parameters
$selectedCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle category slug/name filter (e.g., places.php?category=waterfalls)
if ($selectedCategoryId === 0 && isset($_GET['category']) && !empty($_GET['category'])) {
    $catSlug = trim($_GET['category']);
    foreach ($categoryList as $cat) {
        if (strcasecmp($cat['category_name'], $catSlug) === 0 || stripos($cat['category_name'], $catSlug) !== false) {
            $selectedCategoryId = (int)$cat['category_id'];
            break;
        }
    }
}

// Resolve selected category name
$selectedCategoryName = '';
if ($selectedCategoryId > 0) {
    foreach ($categoryList as $cat) {
        if ((int)$cat['category_id'] === $selectedCategoryId) {
            $selectedCategoryName = $cat['category_name'];
            break;
        }
    }
}

// Dynamic SQL Query with Prepared Statements (FR-CAT-02, FR-CAT-03)
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

    <!-- ===================================================================
         PAGE HERO BANNER
         =================================================================== -->
    <section class="page-hero">
        <div>
            <h1 class="page-hero-title">Explore Destinations</h1>
            <p class="page-hero-subtext">Discover breathtaking attractions, heritage sites, and hidden local gems within a 25 km radius of Warakapola & Dorawaka.</p>
        </div>
    </section>

    <!-- ===================================================================
         DESTINATIONS LISTING & CATEGORY FILTERING SECTION
         =================================================================== -->
    <main class="why-choose-us" style="padding: 70px 20px;">
        <div class="section-container">
            
            <div class="section-header">
                <h2 class="section-title">WARAKAPOLA & DORAWAKA ATTRACTIONS</h2>
                <p style="color: var(--text-muted); margin-top: 10px;">Filter attractions by category or search by keywords to plan your visit.</p>
                
                <!-- Category Filter & Search Bar Form (FR-CAT-01, FR-CAT-04) -->
                <form action="places.php" method="GET" class="filter-search-bar">
                    
                    <!-- Search Input -->
                    <div class="filter-input-wrapper search-box">
                        <i class="fas fa-magnifying-glass search-icon"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search destinations or keywords..." class="filter-control">
                    </div>

                    <!-- Category Dropdown Select (FR-CAT-01) -->
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

                    <!-- Action Buttons -->
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-filter"></i> Filter
                        </button>

                        <?php if ($selectedCategoryId > 0 || !empty($searchQuery)): ?>
                            <!-- "Show All" Resets Filter (FR-CAT-04) -->
                            <a href="places.php" class="btn-filter-reset" title="Show All / Reset Filters">
                                <i class="fas fa-rotate-left"></i> Show All
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>

            <!-- Dynamic Destinations Grid from DB 'neartrip' Table 'attraction' -->
            <div class="destinations-grid">
                
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            // Determine attraction image URL or fallback image
                            $rawImg = !empty($row['image_url']) ? $row['image_url'] : 'assets/hero.webp';
                            $cleanImg = ltrim($rawImg, '/');
                            $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
                        ?>
                        <!-- Destination Card -->
                        <div class="place-card">
                            
                            <!-- Top Image Container with Optional Category Badge -->
                            <div class="place-card-img-wrapper">
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="place-card-img">
                                <?php if (!empty($row['category_name'])): ?>
                                    <span class="place-card-category-badge">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
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
                    <!-- Empty State Fallback (FR-PL-04 Integration) -->
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
