<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Administrative Control Dashboard (admin/dashboard.php)
 * 
 * Database: neartrip
 * Tables: attraction, category, administrator
 * 
 * Requirement Coverage & Viva Defense Points:
 * - SE-11: Procedural Session Guard & Role Access Control (Admin Role Required)
 * - Dynamic SQL Aggregation Queries (COUNT total attractions, categories, active spots)
 * - 3-Column Responsive Grid Summary Cards (Total Attractions, Total Categories, Active Spots)
 * - Quick Action Controller Buttons (Add New Attraction, Manage Categories, Manage Destinations)
 * - Recently Added Attractions Data Table with Edit/Delete Actions
 * ===================================================================
 */

// Step 1: Include Database Configuration & Auth Guard
require_once __DIR__ . '/../includes/config.php';

// Step 2: SE-11 Security Check - Require Admin Role
// If session is unauthenticated or role is not 'admin', redirect to login.php
requireAdmin();

// -------------------------------------------------------------------
// Step 3: DATABASE STATISTICAL QUERIES FOR SUMMARY CARDS
// -------------------------------------------------------------------
$totalAttractions = 0;
$totalCategories  = 0;
$activeAttractions = 0;

// Query 1: Get Total Number of Attractions in Database
$sqlAttractions = "SELECT COUNT(*) AS total FROM `attraction`";
$resAttractions = $conn->query($sqlAttractions);
if ($resAttractions) {
    $row = $resAttractions->fetch_assoc();
    $totalAttractions = (int)$row['total'];
}

// Query 2: Get Total Number of Categories in Database
$sqlCategories = "SELECT COUNT(*) AS total FROM `category`";
$resCategories = $conn->query($sqlCategories);
if ($resCategories) {
    $row = $resCategories->fetch_assoc();
    $totalCategories = (int)$row['total'];
}

// Query 3: Get Active Attractions Count (with valid coordinates)
$sqlActive = "SELECT COUNT(*) AS total FROM `attraction` WHERE `latitude` IS NOT NULL AND `longitude` IS NOT NULL AND `latitude` != 0";
$resActive = $conn->query($sqlActive);
if ($resActive) {
    $row = $resActive->fetch_assoc();
    $activeAttractions = (int)$row['total'];
}
if ($activeAttractions == 0 && $totalAttractions > 0) {
    $activeAttractions = $totalAttractions; // Fallback count
}

// -------------------------------------------------------------------
// Step 4: FETCH RECENTLY ADDED ATTRACTIONS (LATEST 5 ROWS)
// -------------------------------------------------------------------
$recentAttractions = [];
$sqlRecent = "SELECT a.attraction_id, a.name, a.location, a.entrance_fee, c.category_name 
              FROM `attraction` a 
              LEFT JOIN `category` c ON a.category_id = c.category_id 
              ORDER BY a.attraction_id DESC 
              LIMIT 5";

$resRecent = $conn->query($sqlRecent);
if ($resRecent && $resRecent->num_rows > 0) {
    while ($row = $resRecent->fetch_assoc()) {
        $recentAttractions[] = $row;
    }
}

// Step 5: Define Page Title
$pageTitle = "Admin Dashboard - NearTrip Local Tourist Day-Visit Planner";

// Step 6: Include Modular Navigation Header Component
include_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================================================================
     ADMIN DASHBOARD SECTION
     =================================================================== -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Welcome Banner Header -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-shield-halved"></i> Administrative Control Panel
                </span>
                <h1 class="admin-welcome-title">Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</h1>
                <p class="admin-welcome-subtext">Manage Warakapola & Dorawaka destinations, categories, and system planner records.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="add.php" class="btn-primary-action">
                    <i class="fas fa-plus"></i> Add New Attraction
                </a>
            </div>
        </div>

        <!-- ===============================================================
             1. SUMMARY CARDS GRID (3 COLUMNS DESKTOP / 2 TABLET / 1 MOBILE)
             =============================================================== -->
        <div class="admin-stats-row">
            
            <!-- Card 1: Total Attractions (Green #2D7D46) -->
            <div class="admin-stat-card card-green">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper green">
                        <i class="fas fa-monument"></i>
                    </div>
                    <span class="stat-badge">Total</span>
                </div>
                <div class="stat-card-body">
                    <h2 class="stat-number"><?php echo $totalAttractions; ?></h2>
                    <p class="stat-title">Total Attractions</p>
                </div>
                <div class="stat-card-footer">
                    <a href="attractions.php" class="stat-link">View All →</a>
                </div>
            </div>

            <!-- Card 2: Total Categories (Blue #2980B9) -->
            <div class="admin-stat-card card-blue">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper blue">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <span class="stat-badge">Categories</span>
                </div>
                <div class="stat-card-body">
                    <h2 class="stat-number"><?php echo $totalCategories; ?></h2>
                    <p class="stat-title">Total Categories</p>
                </div>
                <div class="stat-card-footer">
                    <a href="categories.php" class="stat-link">View All →</a>
                </div>
            </div>

            <!-- Card 3: Active Attractions (Gold #F5A623) -->
            <div class="admin-stat-card card-gold">
                <div class="stat-card-header">
                    <div class="stat-icon-wrapper gold">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="stat-badge">Active</span>
                </div>
                <div class="stat-card-body">
                    <h2 class="stat-number"><?php echo $activeAttractions; ?></h2>
                    <p class="stat-title">Active Attractions</p>
                </div>
                <div class="stat-card-footer">
                    <a href="attractions.php" class="stat-link">View All →</a>
                </div>
            </div>

        </div>

        <!-- ===============================================================
             2. QUICK ADMINISTRATIVE ACTION BUTTONS BLOCK
             =============================================================== -->
        <div class="admin-panel-card" style="margin-top: 32px;">
            <h3 class="panel-card-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h3>
            
            <div class="quick-actions-flex">
                <a href="attractions.php" class="quick-action-btn secondary">
                    <i class="fas fa-list-check"></i>
                    <span>Manage Destinations</span>
                </a>
                
                <a href="categories.php" class="quick-action-btn info">
                    <i class="fas fa-folder-tree"></i>
                    <span>Manage Categories</span>
                </a>

                <a href="users.php" class="quick-action-btn primary" style="background: #10B981;">
                    <i class="fas fa-users-gear"></i>
                    <span>Manage Users & Roles</span>
                </a>
                
                <a href="../map.php" class="quick-action-btn outline">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>View Interactive Map</span>
                </a>
            </div>
        </div>

        <!-- ===============================================================
             3. RECENTLY ADDED ATTRACTIONS DATA TABLE
             =============================================================== -->
        <div class="admin-panel-card" style="margin-top: 32px;">
            <div class="panel-card-header flex-between">
                <h3 class="panel-card-title" style="margin: 0;">
                    <i class="fas fa-clock-rotate-left"></i> Recently Added Attractions
                </h3>
                <a href="attractions.php" style="font-size: 0.9rem; color: var(--primary); font-weight: 600;">View All →</a>
            </div>

            <div class="admin-table-responsive" style="margin-top: 20px;">
                <?php if (!empty($recentAttractions)): ?>
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Attraction Name</th>
                                <th>Category</th>
                                <th>Location Address</th>
                                <th>Entrance Fee</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttractions as $item): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$item['attraction_id']; ?></strong></td>
                                    <td>
                                        <strong style="color: var(--text-dark);"><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="table-badge">
                                            <?php echo !empty($item['category_name']) ? htmlspecialchars($item['category_name']) : 'General'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.88rem; color: var(--text-muted);">
                                            <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($item['location']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.88rem; font-weight: 600; color: #166534;">
                                            <?php echo !empty($item['entrance_fee']) ? htmlspecialchars($item['entrance_fee']) : 'Free'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="table-action-btns">
                                            <a href="../details.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn edit" title="Edit Attraction">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn delete" onclick="return confirm('Are you sure you want to delete this attraction?');" title="Delete Attraction">
                                                <i class="fas fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p>No attraction records currently exist in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php
// Include Modular Footer Component
include_once __DIR__ . '/../includes/footer.php';
?>
