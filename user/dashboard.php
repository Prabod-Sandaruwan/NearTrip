<?php
/**
 * User dashboard page.
 * Displays saved itineraries and quick access links for registered users.
 */

// Load database configuration and helpers
require_once __DIR__ . '/../includes/config.php';

// Require user login to view this page
requireUser();

// Page title
$pageTitle = "My Explorer Dashboard - NearTrip";

// Fetch saved itineraries for current user
$userId = (int)$_SESSION['admin_id'];
$savedItineraries = [];
$savedSql = "SELECT i.*, COUNT(ia.attraction_id) AS total_places 
            FROM `itinerary` i 
            LEFT JOIN `itinerary_attraction` ia ON i.itinerary_id = ia.itinerary_id 
            WHERE i.`admin_id` = ? 
            GROUP BY i.`itinerary_id` 
            ORDER BY i.`itinerary_id` DESC";
$savedStmt = $conn->prepare($savedSql);
if ($savedStmt) {
    $savedStmt->bind_param("i", $userId);
    $savedStmt->execute();
    $savedRes = $savedStmt->get_result();
    if ($savedRes && $savedRes->num_rows > 0) {
        while ($sRow = $savedRes->fetch_assoc()) {
            $savedItineraries[] = $sRow;
        }
    }
    $savedStmt->close();
}

// Navigation header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Main dashboard content -->
<main class="dashboard-page-section">
    <div class="section-container">
        
        <!-- Welcome Hero Banner -->
        <div class="dashboard-hero-banner user-theme">
            <div class="dashboard-hero-content">
                <span class="role-badge user"><i class="fas fa-user"></i> Local Explorer Account</span>
                <h1 class="dashboard-hero-title">Welcome back, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</h1>
                <p class="dashboard-hero-subtext">Plan your day trip, discover hidden waterfalls, and explore attractions within 25km of Warakapola & Dorawaka.</p>
            </div>
        </div>

        <!-- Saved Tours Section -->
        <h3 class="dashboard-section-heading" style="margin-top: 30px;"><i class="fas fa-bookmark"></i> My Saved Tours & Day Plans</h3>

        <?php if (!empty($savedItineraries)): ?>
            <div class="saved-tours-grid">
                <?php foreach ($savedItineraries as $tour): ?>
                    <div class="dashboard-action-card" style="border-left: 4px solid #2D7D46;">
                        <div class="action-card-header" style="justify-content: space-between;">
                            <h4><i class="fas fa-route" style="color: #2D7D46;"></i> <?php echo htmlspecialchars($tour['name']); ?></h4>
                            <span style="background: rgba(45, 125, 70, 0.1); color: #2D7D46; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 50px;">
                                <?php echo (int)$tour['total_places']; ?> Places
                            </span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 8px 0 16px 0;">
                            <?php echo htmlspecialchars($tour['description']); ?>
                        </p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="../plan.php?load_saved=<?php echo $tour['itinerary_id']; ?>" class="btn-action-primary" style="flex: 1; font-size: 0.85rem; padding: 8px 10px; justify-content: center;">
                                <i class="fas fa-folder-open"></i> Load Plan
                            </a>
                            <a href="../map.php?itinerary_id=<?php echo $tour['itinerary_id']; ?>" class="btn-action-secondary" style="flex: 1; font-size: 0.85rem; background: #059669; color: #FFFFFF; padding: 8px 10px; justify-content: center; text-decoration: none;" title="View this tour route on the interactive map">
                                <i class="fas fa-map-location-dot"></i> View on Map
                            </a>
                            <a href="../plan.php?delete_saved=<?php echo $tour['itinerary_id']; ?>" class="btn-action-danger" style="font-size: 0.85rem; padding: 8px 12px;" onclick="return confirm('Delete this saved tour?');" title="Delete Saved Tour">
                                <i class="fas fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: #FFFFFF; border-radius: 12px; padding: 30px; text-align: center; border: 1px dashed #CBD5E1; margin-bottom: 40px;">
                <i class="fas fa-bookmark" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                <h4 style="color: var(--text-dark); margin-bottom: 6px;">No saved tours yet</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">Create a customized day trip in the itinerary planner and save it to your account!</p>
                <a href="../plan.php" class="btn-action-primary" style="display: inline-flex;">
                    <i class="fas fa-calendar-plus"></i> Open Planner & Create Tour
                </a>
            </div>
        <?php endif; ?>

        <!-- Quick User Trip Planning Actions Grid -->
        <h3 class="dashboard-section-heading"><i class="fas fa-route"></i> Day Trip Planning Options</h3>
        
        <div class="dashboard-actions-grid">
            
            <!-- Action Card 1: Explore Destinations -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-compass action-icon"></i>
                    <h4>Explore Destinations</h4>
                </div>
                <p>Browse Warakapola heritage sites, waterfalls, and eco-tourism spots with search & category filters.</p>
                <a href="../places.php" class="btn-action-primary">
                    <i class="fas fa-arrow-right"></i> Browse Places
                </a>
            </div>

            <!-- Action Card 2: Plan Day Visit Itinerary -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-calendar-plus action-icon"></i>
                    <h4>Plan Day Itinerary</h4>
                </div>
                <p>Add your favorite places to your day trip planner and compute optimal route schedules.</p>
                <a href="../itinerary.php" class="btn-action-primary">
                    <i class="fas fa-route"></i> Open Planner
                </a>
            </div>

            <!-- Action Card 3: Interactive Location Map -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-map-location-dot action-icon"></i>
                    <h4>Interactive Map</h4>
                </div>
                <p>View interactive GPS map of all attractions in the Warakapola & Dorawaka 25km radius zone.</p>
                <a href="../map.php" class="btn-action-secondary">
                    <i class="fas fa-map"></i> Open Map
                </a>
            </div>

            <!-- Action Card 4: Safety Guidelines -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-shield-halved action-icon"></i>
                    <h4>Safety & Travel Tips</h4>
                </div>
                <p>Check emergency contacts, weather advice, and travel tips before heading out on your day trip.</p>
                <a href="../safety.php" class="btn-action-secondary">
                    <i class="fas fa-circle-info"></i> View Safety Tips
                </a>
            </div>

            <!-- Action Card 5: Account & Security Settings -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-user-gear action-icon"></i>
                    <h4>Account Settings</h4>
                </div>
                <p>Update your username, email address, and change your account security password.</p>
                <a href="profile.php" class="btn-action-secondary">
                    <i class="fas fa-id-card"></i> Edit Profile
                </a>
            </div>

            <!-- Action Card 6: Logout -->
            <div class="dashboard-action-card">
                <div class="action-card-header">
                    <i class="fas fa-right-from-bracket action-icon text-danger"></i>
                    <h4>Logout Account</h4>
                </div>
                <p>Safely log out of your explorer account session.</p>
                <a href="../admin/logout.php" class="btn-action-danger">
                    <i class="fas fa-power-off"></i> Logout
                </a>
            </div>

        </div>

    </div>
</main>

<?php
// Page footer
include_once __DIR__ . '/../includes/footer.php';
?>
