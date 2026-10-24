<?php
/**
 * User profile and password settings page.
 * Allows users to update their username, email, and password.
 */

require_once __DIR__ . '/../includes/config.php';

// Require user login
requireLogin();

$userId = (int)$_SESSION['admin_id'];
$errorMessage = "";
$successMessage = "";

// Fetch current user data from database
$fetchSql = "SELECT `admin_id`, `username`, `email`, `password_hash`, `role` FROM `administrator` WHERE `admin_id` = ? LIMIT 1";
$stmt = $conn->prepare($fetchSql);
$currentUser = null;

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $currentUser = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$currentUser) {
    die("User record not found.");
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    // Update profile details (username and email)
    if ($formAction === 'update_profile') {
        $newUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
        $newEmail    = isset($_POST['email']) ? trim($_POST['email']) : '';

        if (empty($newUsername) || empty($newEmail)) {
            $errorMessage = "Username and Email fields cannot be empty.";
        } elseif (strlen($newUsername) < 3) {
            $errorMessage = "Username must be at least 3 characters long.";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } else {
            // Check if username or email is taken by another account
            $dupSql = "SELECT `admin_id` FROM `administrator` WHERE (`username` = ? OR `email` = ?) AND `admin_id` != ? LIMIT 1";
            $dupStmt = $conn->prepare($dupSql);
            if ($dupStmt) {
                $dupStmt->bind_param("ssi", $newUsername, $newEmail, $userId);
                $dupStmt->execute();
                $dupRes = $dupStmt->get_result();

                if ($dupRes && $dupRes->num_rows > 0) {
                    $errorMessage = "Username or Email address is already used by another account.";
                } else {
                    // Update database
                    $updSql = "UPDATE `administrator` SET `username` = ?, `email` = ? WHERE `admin_id` = ?";
                    $updStmt = $conn->prepare($updSql);
                    if ($updStmt) {
                        $updStmt->bind_param("ssi", $newUsername, $newEmail, $userId);
                        if ($updStmt->execute()) {
                            // Refresh session state
                            $_SESSION['username'] = $newUsername;
                            $_SESSION['email']    = $newEmail;
                            
                            $currentUser['username'] = $newUsername;
                            $currentUser['email']    = $newEmail;

                            $successMessage = "Profile information updated successfully!";
                        } else {
                            $errorMessage = "Failed to update profile: " . $updStmt->error;
                        }
                        $updStmt->close();
                    }
                }
                $dupStmt->close();
            }
        }
    }

    // Change account password
    elseif ($formAction === 'change_password') {
        $currentPassword = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
        $newPassword     = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "All password fields are required.";
        } elseif (!password_verify($currentPassword, $currentUser['password_hash'])) {
            $errorMessage = "Incorrect current password. Please try again.";
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = "New password must be at least 6 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "New password and confirmation do not match.";
        } else {
            // Hash new password and update database
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pwSql = "UPDATE `administrator` SET `password_hash` = ? WHERE `admin_id` = ?";
            $pwStmt = $conn->prepare($pwSql);
            if ($pwStmt) {
                $pwStmt->bind_param("si", $newPasswordHash, $userId);
                if ($pwStmt->execute()) {
                    $currentUser['password_hash'] = $newPasswordHash;
                    $successMessage = "Your password has been changed successfully!";
                } else {
                    $errorMessage = "Failed to update password: " . $pwStmt->error;
                }
                $pwStmt->close();
            }
        }
    }
}

// Page title
$pageTitle = "Account Settings & Profile - NearTrip";

// Navigation header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Main profile section -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; min-height: 85vh;">
    <div class="section-container" style="max-width: 900px; margin: 0 auto;">

        <!-- Back Button & Page Header Banner -->
        <div style="margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 18px;">
            <div>
                <span class="role-badge <?php echo isAdmin() ? 'admin' : 'user'; ?>" style="margin-bottom: 8px;">
                    <i class="fas fa-id-card"></i> Account Management
                </span>
                <h1 style="font-size: 1.85rem; font-weight: 700; color: #FFFFFF; margin: 6px 0 4px 0;">Account Settings</h1>
                <p style="color: #FFFFFF; font-size: 0.95rem; opacity: 0.9; margin: 0;">Update your account username, email address, and security password.</p>
            </div>
            
            <div>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php" class="quick-action-btn outline">
                        <i class="fas fa-arrow-left"></i> Back to Admin Panel
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="quick-action-btn outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Banners -->
        <?php if (!empty($errorMessage)): ?>
            <div class="admin-flash-banner alert-danger" style="margin-bottom: 20px;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="admin-flash-banner alert-success" style="margin-bottom: 20px;">
                <i class="fas fa-circle-check"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="user-profile-grid">
            
            <!-- Card 1: Profile Information -->
            <div class="admin-panel-card" style="background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #F1F5F9;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #1E293B;">Profile Details</h3>
                        <span style="font-size: 0.82rem; color: #64748B;">Change your display name and email</span>
                    </div>
                </div>

                <form action="profile.php" method="POST" autocomplete="off">
                    <input type="hidden" name="form_action" value="update_profile">
                    
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="prof_username" class="form-label" style="font-weight: 500; font-size: 0.9rem; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-user" style="color: var(--primary);"></i> Username
                        </label>
                        <input type="text" 
                               id="prof_username" 
                               name="username" 
                               class="form-input" 
                               style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;" 
                               value="<?php echo htmlspecialchars($currentUser['username']); ?>" 
                               required>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="prof_email" class="form-label" style="font-weight: 500; font-size: 0.9rem; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-envelope" style="color: var(--primary);"></i> Email Address
                        </label>
                        <input type="email" 
                               id="prof_email" 
                               name="email" 
                               class="form-input" 
                               style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;" 
                               value="<?php echo htmlspecialchars($currentUser['email']); ?>" 
                               required>
                    </div>



                    <button type="submit" class="btn-profile-save">
                        <i class="fas fa-circle-check"></i> Save Profile Details
                    </button>
                </form>
            </div>

            <!-- Card 2: Password Change -->
            <div class="admin-panel-card" style="background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #F1F5F9;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #1E293B;">Security Password</h3>
                        <span style="font-size: 0.82rem; color: #64748B;">Update your account password</span>
                    </div>
                </div>

                <form action="profile.php" method="POST" autocomplete="off">
                    <input type="hidden" name="form_action" value="change_password">
                    
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="current_password" class="form-label" style="font-weight: 500; font-size: 0.9rem; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-key" style="color: #D97706;"></i> Current Password
                        </label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="form-input" 
                               style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;" 
                               placeholder="Enter current password" 
                               required>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="new_password" class="form-label" style="font-weight: 500; font-size: 0.9rem; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-lock" style="color: #D97706;"></i> New Password (Min 6 chars)
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-input" 
                               style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;" 
                               placeholder="Enter new password" 
                               required>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="confirm_password" class="form-label" style="font-weight: 500; font-size: 0.9rem; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-shield-check" style="color: #D97706;"></i> Confirm New Password
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-input" 
                               style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;" 
                               placeholder="Re-enter new password" 
                               required>
                    </div>

                    <button type="submit" class="btn-password-save">
                        <i class="fas fa-shield-halved"></i> Update Security Password
                    </button>
                </form>
            </div>

        </div>

    </div>
</main>

<?php
// Page footer
include_once __DIR__ . '/../includes/footer.php';
?>
