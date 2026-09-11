<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Delete Attraction Confirmation & Execution Page (admin/delete_attraction.php)
 * 
 * Requirement Coverage:
 * - FR-ADM-04: Delete attraction record with confirmation step
 * - SE-05: Prepared statement DELETE query
 * - SE-11: Admin role session security guard
 * ===================================================================
 */

// Step 1: Include Database Config & Auth Helper
require_once __DIR__ . '/../includes/config.php';

// Step 2: Enforce Admin Security Guard (SE-11)
requireAdmin();

// Capture Attraction ID from GET or POST
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    $_SESSION['message'] = "Invalid Attraction ID specified for deletion.";
    $_SESSION['message_type'] = "error";
    header("Location: attractions.php");
    exit();
}

// -------------------------------------------------------------------
// Step 3: FETCH ATTRACTION DETAILS TO VERIFY RECORD EXISTENCE
// -------------------------------------------------------------------
$fetchSql = "SELECT * FROM `attraction` WHERE `attraction_id` = ? LIMIT 1";
$stmt = $conn->prepare($fetchSql);
$attraction = null;

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $attraction = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$attraction) {
    $_SESSION['message'] = "Attraction record not found or already deleted.";
    $_SESSION['message_type'] = "error";
    header("Location: attractions.php");
    exit();
}

// -------------------------------------------------------------------
// Step 4: METHOD 2 - PROCESS CONFIRMED DELETION (POST)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === '1') {
    
    $deleteSql = "DELETE FROM `attraction` WHERE `attraction_id` = ?";
    $delStmt = $conn->prepare($deleteSql);
    
    if ($delStmt) {
        $delStmt->bind_param("i", $id);
        if ($delStmt->execute()) {
            $delStmt->close();
            // Set success flash message and redirect to attractions list
            $_SESSION['message'] = "Attraction '" . $attraction['name'] . "' deleted permanently!";
            $_SESSION['message_type'] = "success";
            header("Location: attractions.php");
            exit();
        } else {
            $error = "Error deleting attraction record: " . $delStmt->error;
            $delStmt->close();
        }
    } else {
        $error = "Database Query Preparation Failed: " . $conn->error;
    }
}

// Step 5: Define Page Title & Include Header Component
$pageTitle = "Confirm Delete - " . htmlspecialchars($attraction['name']);
include_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================================================================
     METHOD 1: CONFIRMATION DIALOG INTERFACE
     =================================================================== -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 600px; margin: 0 auto; text-align: center;">
        
        <!-- Confirmation Card Container -->
        <div class="admin-panel-card" style="padding: 48px 32px; border-top: 5px solid #EF4444;">
            
            <!-- Warning Icon -->
            <div style="width: 70px; height: 70px; border-radius: 50%; background: #FEE2E2; color: #EF4444; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 20px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

            <h2 style="font-size: 1.6rem; color: var(--text-dark); margin-bottom: 10px;">Confirm Permanent Deletion</h2>
            <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 24px;">
                Are you sure you want to permanently delete <strong>"<?php echo htmlspecialchars($attraction['name']); ?>"</strong> (ID: #<?php echo $id; ?>)?
            </p>

            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 12px; padding: 14px; margin-bottom: 28px; color: #991B1B; font-size: 0.9rem; font-weight: 500;">
                <i class="fas fa-circle-exclamation"></i> Warning: This action cannot be undone! The destination will be removed from all maps and day planners.
            </div>

            <!-- Confirmation Action Form -->
            <form action="delete_attraction.php?id=<?php echo $id; ?>" method="POST" style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="confirm_delete" value="1">
                
                <!-- Danger Confirm Button -->
                <button type="submit" class="btn-action-danger" style="padding: 12px 28px; font-size: 0.95rem;">
                    <i class="fas fa-trash-can"></i> Yes, Delete Permanently
                </button>

                <!-- Cancel Button -->
                <a href="attractions.php" class="quick-action-btn outline" style="padding: 12px 24px;">
                    Cancel
                </a>
            </form>

        </div>

    </div>
</main>

<?php
// Include Footer Component
include_once __DIR__ . '/../includes/footer.php';
?>
