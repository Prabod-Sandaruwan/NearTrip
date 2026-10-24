<?php
/**
 * Attractions management page.
 * Displays all attractions with options to add, view, edit, or delete them.
 */

// Load database configuration and helpers
require_once __DIR__ . '/../includes/config.php';

// Require admin privileges
requireAdmin();

// Page title
$pageTitle = "Manage Attractions - NearTrip Admin";

// Navigation header
include_once __DIR__ . '/../includes/header.php';

// Fetch all attractions with category names from database
$attractions = [];
$sql = "SELECT a.*, c.category_name 
        FROM `attraction` a 
        LEFT JOIN `category` c ON a.category_id = c.category_id 
        ORDER BY a.attraction_id DESC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attractions[] = $row;
    }
}
?>

<!-- Main attractions table section -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Status message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="admin-flash-banner alert-<?php echo isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
            </div>
            <?php 
                // Clear flash message after displaying
                unset($_SESSION['message']); 
                unset($_SESSION['message_type']); 
            ?>
        <?php endif; ?>

        <!-- Page Header Bar -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-monument"></i> Attractions Management
                </span>
                <h1 class="admin-welcome-title">Manage Destinations</h1>
                <p class="admin-welcome-subtext">View, add, edit, or delete tourist spots in Warakapola & Dorawaka.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="add_attraction.php" class="btn-primary-action">
                    <i class="fas fa-square-plus"></i> Add New Attraction
                </a>
            </div>
        </div>

        <!-- Attractions Data Table Card -->
        <div class="admin-panel-card" style="margin-top: 24px;">
            <div class="panel-card-header flex-between" style="flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                <h3 class="panel-card-title" style="margin: 0;">
                    <i class="fas fa-list-check"></i> All Registered Attractions (<?php echo count($attractions); ?>)
                </h3>
                
                <div style="display: flex; gap: 10px;">
                    <a href="dashboard.php" class="quick-action-btn outline" style="padding: 8px 16px; font-size: 0.85rem;">
                        <i class="fas fa-gauge"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Table Responsive Wrapper -->
            <div class="admin-table-responsive">
                <?php if (!empty($attractions)): ?>
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Thumbnail</th>
                                <th>Attraction Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Entrance Fee</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attractions as $item): ?>
                                <?php
                                    // Resolve thumbnail image path
                                    $rawImg = !empty($item['image_url']) ? $item['image_url'] : 'assets/hero.webp';
                                    $cleanImg = ltrim($rawImg, '/');
                                    $imgSrc = file_exists(__DIR__ . '/../' . $cleanImg) ? '../' . $cleanImg : '../assets/hero.webp';
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$item['attraction_id']; ?></strong></td>
                                    <td>
                                        <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; border: 1px solid #E2E8F0;">
                                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-dark); font-size: 0.98rem;"><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="table-badge">
                                            <?php echo !empty($item['category_name']) ? htmlspecialchars($item['category_name']) : 'General'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.88rem; color: var(--text-muted);">
                                            <i class="fas fa-location-dot" style="color: var(--primary);"></i> <?php echo htmlspecialchars($item['location']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.88rem; font-weight: 600; color: #166534;">
                                            <?php echo !empty($item['entrance_fee']) ? htmlspecialchars($item['entrance_fee']) : 'Free'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="table-action-btns">
                                            <!-- View Details -->
                                            <a href="../details.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn view" title="View Public Page">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- Edit Attraction -->
                                            <a href="edit_attraction.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn edit" title="Edit Attraction">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                            <!-- Delete Attraction -->
                                            <a href="delete_attraction.php?id=<?php echo (int)$item['attraction_id']; ?>" class="tbl-btn delete" title="Delete Attraction">
                                                <i class="fas fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 12px; opacity: 0.5;"></i>
                        <h4 style="color: var(--text-dark); margin-bottom: 6px;">No Attractions Found</h4>
                        <p style="margin-bottom: 20px;">Click the button below to add your first destination.</p>
                        <a href="add_attraction.php" class="btn-primary-action">
                            <i class="fas fa-plus"></i> Add New Attraction
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php
// Page footer
include_once __DIR__ . '/../includes/footer.php';
?>
