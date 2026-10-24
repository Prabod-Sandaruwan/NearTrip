<?php
/**
 * User and role management page.
 * Allows administrators to view user accounts, change user roles, and delete accounts.
 */

// Include database configuration and authentication helper
require_once __DIR__ . '/../includes/config.php';

// Ensure only logged-in administrators can access this page
requireAdmin();

$currentAdminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle account role change
if ($action === 'toggle_role' && $userId > 0) {
    $newRole = isset($_GET['new_role']) ? trim($_GET['new_role']) : '';
    
    if (!in_array($newRole, ['admin', 'user'])) {
        $_SESSION['message'] = "Invalid role specified.";
        $_SESSION['message_type'] = "error";
    } elseif ($userId === $currentAdminId) {
        $_SESSION['message'] = "Security Warning: You cannot change the role of your own active logged-in account!";
        $_SESSION['message_type'] = "error";
    } else {
        // Update user role in database
        $updSql = "UPDATE `administrator` SET `role` = ? WHERE `admin_id` = ?";
        $updStmt = $conn->prepare($updSql);
        if ($updStmt) {
            $updStmt->bind_param("si", $newRole, $userId);
            if ($updStmt->execute()) {
                $updStmt->close();
                $_SESSION['message'] = "Account role updated to '" . strtoupper($newRole) . "' successfully!";
                $_SESSION['message_type'] = "success";
                header("Location: users.php");
                exit();
            } else {
                $_SESSION['message'] = "Error updating role: " . $updStmt->error;
                $_SESSION['message_type'] = "error";
                $updStmt->close();
            }
        }
    }
    header("Location: users.php");
    exit();
}

// Handle account deletion
if ($action === 'delete' && $userId > 0) {
    if ($userId === $currentAdminId) {
        $_SESSION['message'] = "Security Warning: You cannot delete your own active logged-in account!";
        $_SESSION['message_type'] = "error";
    } else {
        // Fetch username to display in notification
        $fetchSql = "SELECT `username` FROM `administrator` WHERE `admin_id` = ? LIMIT 1";
        $fetchStmt = $conn->prepare($fetchSql);
        $targetUser = null;
        if ($fetchStmt) {
            $fetchStmt->bind_param("i", $userId);
            $fetchStmt->execute();
            $res = $fetchStmt->get_result();
            if ($res && $res->num_rows === 1) {
                $targetUser = $res->fetch_assoc();
            }
            $fetchStmt->close();
        }

        if ($targetUser) {
            $delSql = "DELETE FROM `administrator` WHERE `admin_id` = ?";
            $delStmt = $conn->prepare($delSql);
            if ($delStmt) {
                $delStmt->bind_param("i", $userId);
                if ($delStmt->execute()) {
                    $delStmt->close();
                    $_SESSION['message'] = "User account '" . $targetUser['username'] . "' deleted successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: users.php");
                    exit();
                } else {
                    $_SESSION['message'] = "Error deleting account: " . $delStmt->error;
                    $_SESSION['message_type'] = "error";
                    $delStmt->close();
                }
            }
        } else {
            $_SESSION['message'] = "Account not found or already deleted.";
            $_SESSION['message_type'] = "error";
        }
    }
    header("Location: users.php");
    exit();
}

// Fetch all registered user accounts from database
$usersList = [];
$listSql = "SELECT `admin_id`, `username`, `email`, `role`, `created_at` 
            FROM `administrator` 
            ORDER BY `admin_id` DESC";

$listResult = $conn->query($listSql);
if ($listResult && $listResult->num_rows > 0) {
    while ($row = $listResult->fetch_assoc()) {
        $usersList[] = $row;
    }
}

// Page title and header
$pageTitle = "User & Role Management - NearTrip Admin";
include_once __DIR__ . '/../includes/header.php';
?>

<!-- User and role management section -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Flash message banner -->
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

        <!-- Page Header Banner -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-users-gear"></i> Account Access Control
                </span>
                <h1 class="admin-welcome-title">User & Role Management</h1>
                <p class="admin-welcome-subtext">Manage registered accounts, view emails, and grant or revoke Administrator privileges.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="dashboard.php" class="quick-action-btn outline">
                    <i class="fas fa-gauge"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Registered users table -->
        <div class="admin-panel-card" style="margin-top: 24px;">
            <div class="panel-card-header flex-between" style="margin-bottom: 20px;">
                <h3 class="panel-card-title" style="margin: 0;">
                    <i class="fas fa-users"></i> Registered Accounts (<?php echo count($usersList); ?>)
                </h3>
            </div>

            <div class="admin-table-responsive">
                <?php if (!empty($usersList)): ?>
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Name</th>
                                <th>Email Address</th>
                                <th>Account Role</th>
                                <th>Registered Date</th>
                                <th style="text-align: right;">Role & Account Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $user): ?>
                                <?php $isSelf = ($user['admin_id'] === $currentAdminId); ?>
                                <tr <?php echo $isSelf ? 'style="background: #F0FDF4;"' : ''; ?>>
                                    <td><strong>#<?php echo (int)$user['admin_id']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #E2E8F0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong style="color: var(--text-dark); font-size: 0.98rem; display: block;">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </strong>
                                                <?php if ($isSelf): ?>
                                                    <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">(You / Active Session)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.9rem; color: #334155;">
                                            <i class="fas fa-envelope" style="color: var(--text-muted); margin-right: 4px;"></i> 
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="role-badge admin" style="margin: 0;">
                                                <i class="fas fa-shield-halved"></i> Administrator
                                            </span>
                                        <?php else: ?>
                                            <span class="role-badge user" style="margin: 0;">
                                                <i class="fas fa-user"></i> Regular User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="table-action-btns" style="gap: 8px;">
                                            
                                            <!-- Change role -->
                                            <?php if ($isSelf): ?>
                                                <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic; padding: 6px 10px;">Protected</span>
                                            <?php else: ?>
                                                <?php if ($user['role'] === 'user'): ?>
                                                    <a href="users.php?action=toggle_role&id=<?php echo (int)$user['admin_id']; ?>&new_role=admin" 
                                                       class="quick-action-btn primary" 
                                                       style="padding: 6px 12px; font-size: 0.8rem;" 
                                                       onclick="return confirm('Promote user \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\' to Administrator?');" 
                                                       title="Grant Admin Privileges">
                                                        <i class="fas fa-user-shield"></i> Make Admin
                                                    </a>
                                                <?php else: ?>
                                                    <a href="users.php?action=toggle_role&id=<?php echo (int)$user['admin_id']; ?>&new_role=user" 
                                                       class="quick-action-btn outline" 
                                                       style="padding: 6px 12px; font-size: 0.8rem; background: #FEF3C7; color: #D97706; border-color: #FCD34D;" 
                                                       onclick="return confirm('Demote admin \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\' to Regular User?');" 
                                                       title="Change to Regular User">
                                                        <i class="fas fa-user-minus"></i> Make User
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Delete account -->
                                                <a href="users.php?action=delete&id=<?php echo (int)$user['admin_id']; ?>" 
                                                   class="tbl-btn delete" 
                                                   onclick="return confirm('Are you sure you want to delete account \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\'?');" 
                                                   title="Delete Account">
                                                    <i class="fas fa-trash-can"></i>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="fas fa-users-slash" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No user accounts found in database.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</main>

<?php
// Render footer
include_once __DIR__ . '/../includes/footer.php';
?>
