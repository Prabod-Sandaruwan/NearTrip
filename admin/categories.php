<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Admin Categories Management Page (admin/categories.php)
 * 
 * Database: neartrip
 * Table: category, attraction
 * 
 * Requirement Coverage:
 * - Manage Categories CRUD (Create, Read, Update, Delete)
 * - SE-11: Admin Role Session Security Guard
 * - FR-ADM-07, FR-ADM-08: Flash notification messages
 * ===================================================================
 */

// Step 1: Include Config & Auth Guard
require_once __DIR__ . '/../includes/config.php';

// Step 2: Enforce Admin Security Guard (SE-11)
requireAdmin();

// Initialize variables
$error = '';
$success = '';
$editCategory = null;
$categoryNameInput = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$catId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// -------------------------------------------------------------------
// 1. PROCESS DELETE CATEGORY ACTION
// -------------------------------------------------------------------
if ($action === 'delete' && $catId > 0) {
    // Check if category exists
    $checkSql = "SELECT `category_name` FROM `category` WHERE `category_id` = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("i", $catId);
        $checkStmt->execute();
        $res = $checkStmt->get_result();
        if ($res && $res->num_rows === 1) {
            $catData = $res->fetch_assoc();
            
            // Step 1: Update linked attractions to NULL category_id to prevent foreign key errors
            $unlinkSql = "UPDATE `attraction` SET `category_id` = NULL WHERE `category_id` = ?";
            $unlinkStmt = $conn->prepare($unlinkSql);
            if ($unlinkStmt) {
                $unlinkStmt->bind_param("i", $catId);
                $unlinkStmt->execute();
                $unlinkStmt->close();
            }

            // Step 2: Delete Category Record
            $delSql = "DELETE FROM `category` WHERE `category_id` = ?";
            $delStmt = $conn->prepare($delSql);
            if ($delStmt) {
                $delStmt->bind_param("i", $catId);
                if ($delStmt->execute()) {
                    $delStmt->close();
                    $_SESSION['message'] = "Category '" . $catData['category_name'] . "' deleted successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: categories.php");
                    exit();
                }
                $delStmt->close();
            }
        }
        $checkStmt->close();
    }
}

// -------------------------------------------------------------------
// 2. PROCESS EDIT CATEGORY FETCH
// -------------------------------------------------------------------
if ($action === 'edit' && $catId > 0) {
    $editSql = "SELECT * FROM `category` WHERE `category_id` = ? LIMIT 1";
    $editStmt = $conn->prepare($editSql);
    if ($editStmt) {
        $editStmt->bind_param("i", $catId);
        $editStmt->execute();
        $res = $editStmt->get_result();
        if ($res && $res->num_rows === 1) {
            $editCategory = $res->fetch_assoc();
            $categoryNameInput = $editCategory['category_name'];
        }
        $editStmt->close();
    }
}

// -------------------------------------------------------------------
// 3. PROCESS FORM SUBMISSION (ADD OR UPDATE CATEGORY)
// -------------------------------------------------------------------
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryNameInput = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
    $formCatId        = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    if (empty($categoryNameInput)) {
        $error = "Category Name cannot be empty.";
    } elseif (mb_strlen($categoryNameInput) < 2) {
        $error = "Category Name must be at least 2 characters long.";
    } else {
        // CASE A: UPDATE EXISTING CATEGORY
        if ($formCatId > 0) {
            // Check for duplicate name
            $dupSql = "SELECT `category_id` FROM `category` WHERE `category_name` = ? AND `category_id` != ? LIMIT 1";
            $dupStmt = $conn->prepare($dupSql);
            $dupStmt->bind_param("si", $categoryNameInput, $formCatId);
            $dupStmt->execute();
            if ($dupStmt->get_result()->num_rows > 0) {
                $error = "A category with the name '" . htmlspecialchars($categoryNameInput) . "' already exists.";
                $dupStmt->close();
            } else {
                $dupStmt->close();
                $updSql = "UPDATE `category` SET `category_name` = ? WHERE `category_id` = ?";
                $updStmt = $conn->prepare($updSql);
                if ($updStmt) {
                    $updStmt->bind_param("si", $categoryNameInput, $formCatId);
                    if ($updStmt->execute()) {
                        $updStmt->close();
                        $_SESSION['message'] = "Category updated to '" . $categoryNameInput . "' successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: categories.php");
                        exit();
                    } else {
                        $error = "Update Error: " . $updStmt->error;
                    }
                }
            }
        } 
        // CASE B: ADD NEW CATEGORY
        else {
            // Check for duplicate name
            $dupSql = "SELECT `category_id` FROM `category` WHERE `category_name` = ? LIMIT 1";
            $dupStmt = $conn->prepare($dupSql);
            $dupStmt->bind_param("s", $categoryNameInput);
            $dupStmt->execute();
            if ($dupStmt->get_result()->num_rows > 0) {
                $error = "A category with the name '" . htmlspecialchars($categoryNameInput) . "' already exists.";
                $dupStmt->close();
            } else {
                $dupStmt->close();
                $insSql = "INSERT INTO `category` (`category_name`) VALUES (?)";
                $insStmt = $conn->prepare($insSql);
                if ($insStmt) {
                    $insStmt->bind_param("s", $categoryNameInput);
                    if ($insStmt->execute()) {
                        $insStmt->close();
                        $_SESSION['message'] = "New category '" . $categoryNameInput . "' added successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: categories.php");
                        exit();
                    } else {
                        $error = "Insert Error: " . $insStmt->error;
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------------
// 4. FETCH ALL CATEGORIES WITH LINKED ATTRACTIONS COUNT
// -------------------------------------------------------------------
$categories = [];
$listSql = "SELECT c.category_id, c.category_name, COUNT(a.attraction_id) AS total_attractions 
            FROM `category` c 
            LEFT JOIN `attraction` a ON c.category_id = a.category_id 
            GROUP BY c.category_id 
            ORDER BY c.category_name ASC";

$listRes = $conn->query($listSql);
if ($listRes && $listRes->num_rows > 0) {
    while ($row = $listRes->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Step 5: Define Page Title & Include Header Component
$pageTitle = "Manage Categories - NearTrip Admin";
include_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================================================================
     CATEGORIES MANAGEMENT SECTION
     =================================================================== -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Flash Notification Banner -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="admin-flash-banner alert-<?php echo isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo isset($_SESSION['message_type']) && $_SESSION['message_type'] === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
            </div>
            <?php 
                unset($_SESSION['message']); 
                unset($_SESSION['message_type']); 
            ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="admin-flash-banner alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header Banner -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-folder-tree"></i> Category Management
                </span>
                <h1 class="admin-welcome-title">Manage Categories</h1>
                <p class="admin-welcome-subtext">Add, update, or remove attraction categories for Warakapola & Dorawaka destinations.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="dashboard.php" class="quick-action-btn outline">
                    <i class="fas fa-gauge"></i> Dashboard
                </a>
                <a href="attractions.php" class="quick-action-btn secondary">
                    <i class="fas fa-list-check"></i> Manage Destinations
                </a>
            </div>
        </div>

        <!-- 2-COLUMN GRID: FORM CARD LEFT (~35%), TABLE CARD RIGHT (~65%) -->
        <div class="admin-categories-grid">
            
            <!-- LEFT COLUMN: ADD / EDIT CATEGORY FORM CARD -->
            <div class="admin-panel-card">
                <h3 class="panel-card-title" style="font-size: 1.15rem;">
                    <?php if ($editCategory): ?>
                        <i class="fas fa-pen-to-square"></i> Edit Category
                    <?php else: ?>
                        <i class="fas fa-folder-plus"></i> Add New Category
                    <?php endif; ?>
                </h3>

                <form action="categories.php<?php echo $editCategory ? '?action=edit&id=' . (int)$editCategory['category_id'] : ''; ?>" method="POST" autocomplete="off">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?php echo (int)$editCategory['category_id']; ?>">
                    <?php endif; ?>

                    <div style="margin-bottom: 18px;">
                        <label for="category_name" class="form-label" style="font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; display: block;">
                            Category Name <span class="required-star">*</span>
                        </label>
                        <input type="text" 
                               id="category_name" 
                               name="category_name" 
                               class="form-input" 
                               placeholder="e.g. Waterfalls, Heritage, Nature" 
                               value="<?php echo htmlspecialchars($categoryNameInput); ?>" 
                               required>
                    </div>

                    <div style="display: flex; gap: 10px; flex-direction: column;">
                        <button type="submit" class="btn-primary-action" style="width: 100%; justify-content: center;">
                            <?php if ($editCategory): ?>
                                <i class="fas fa-rotate"></i> Update Category
                            <?php else: ?>
                                <i class="fas fa-plus"></i> Save Category
                            <?php endif; ?>
                        </button>

                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="quick-action-btn outline" style="justify-content: center; width: 100%;">
                                Cancel Editing
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN: CATEGORIES LIST DATA TABLE -->
            <div class="admin-panel-card">
                <div class="panel-card-header flex-between" style="margin-bottom: 16px;">
                    <h3 class="panel-card-title" style="margin: 0; font-size: 1.15rem;">
                        <i class="fas fa-tags"></i> Category Directory (<?php echo count($categories); ?>)
                    </h3>
                </div>

                <div class="admin-table-responsive">
                    <?php if (!empty($categories)): ?>
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Linked Destinations</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr <?php echo ($editCategory && $editCategory['category_id'] == $cat['category_id']) ? 'style="background: #FEF3C7;"' : ''; ?>>
                                        <td><strong>#<?php echo (int)$cat['category_id']; ?></strong></td>
                                        <td>
                                            <strong style="color: var(--text-dark); font-size: 0.98rem;">
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="table-badge" style="background: #E0F2FE; color: #0369A1;">
                                                <i class="fas fa-location-dot"></i> <?php echo (int)$cat['total_attractions']; ?> Destinations
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="table-action-btns">
                                                <!-- Filter Places by Category -->
                                                <a href="../places.php?category=<?php echo urlencode($cat['category_name']); ?>" class="tbl-btn view" title="View Destinations in Category">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <!-- Edit Category -->
                                                <a href="categories.php?action=edit&id=<?php echo (int)$cat['category_id']; ?>" class="tbl-btn edit" title="Edit Category">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </a>
                                                <!-- Delete Category -->
                                                <a href="categories.php?action=delete&id=<?php echo (int)$cat['category_id']; ?>" class="tbl-btn delete" onclick="return confirm('Are you sure you want to delete category \'<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>\'? Linked attractions will revert to general category.');" title="Delete Category">
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
                            <i class="fas fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>No categories currently exist. Add your first category using the form on the left.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>
</main>

<?php
// Include Footer Component
include_once __DIR__ . '/../includes/footer.php';
?>
