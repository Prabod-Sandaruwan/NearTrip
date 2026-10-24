<?php
/**
 * Login and registration page.
 * Handles user sign in, account registration, and session initialization.
 */

// Load database configuration and helpers
require_once __DIR__ . '/../includes/config.php';

// If user or admin is already logged in, redirect to their respective dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: ../index.php");
        exit();
    }
}

// Determine active authentication tab (login vs register)
$activeTab = (isset($_GET['action']) && $_GET['action'] === 'register') || (isset($_POST['action']) && $_POST['action'] === 'register') ? 'register' : 'login';

// Initialize error & notification message variables
$errorMessage = "";
$successMessage = "";

// Check for logout success query parameter
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $successMessage = "You have been logged out successfully.";
}

// Check for timeout notification message
if (isset($_SESSION['timeout_message'])) {
    $errorMessage = $_SESSION['timeout_message'];
    unset($_SESSION['timeout_message']);
}

// Handle login and registration form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = isset($_POST['action']) ? $_POST['action'] : 'login';

    // Handle user registration
    if ($action === 'register') {
        $username        = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email           = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password        = isset($_POST['password']) ? trim($_POST['password']) : '';
        $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

        // Validation Rules
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            $errorMessage = "Please fill in all required registration fields.";
            $activeTab = 'register';
        } elseif (strlen($username) < 3) {
            $errorMessage = "Username must be at least 3 characters long.";
            $activeTab = 'register';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
            $activeTab = 'register';
        } elseif (strlen($password) < 6) {
            $errorMessage = "Password must be at least 6 characters long.";
            $activeTab = 'register';
        } elseif ($password !== $confirmPassword) {
            $errorMessage = "Passwords do not match. Please re-enter.";
            $activeTab = 'register';
        } else {
            // Check if email or username already exists in database
            $checkSql = "SELECT `admin_id` FROM `administrator` WHERE `email` = ? OR `username` = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param("ss", $email, $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult && $checkResult->num_rows > 0) {
                    $errorMessage = "An account with that email or username already exists.";
                    $activeTab = 'register';
                } else {
                    // Hash password using BCRYPT / DEFAULT
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $defaultRole = 'user'; // Public signups get 'user' role

                    // Insert new account record
                    $insertSql = "INSERT INTO `administrator` (`username`, `password_hash`, `email`, `role`) VALUES (?, ?, ?, ?)";
                    $insertStmt = $conn->prepare($insertSql);
                    if ($insertStmt) {
                        $insertStmt->bind_param("ssss", $username, $passwordHash, $email, $defaultRole);
                        if ($insertStmt->execute()) {
                            $newUserId = $insertStmt->insert_id;

                            // Prevent session fixation attack
                            session_regenerate_id(true);

                            // Auto login the new user
                            $_SESSION['admin_id']      = $newUserId;
                            $_SESSION['username']      = $username;
                            $_SESSION['email']         = $email;
                            $_SESSION['role']          = $defaultRole;
                            $_SESSION['last_activity'] = time();

                            $insertStmt->close();
                            $checkStmt->close();

                            // Redirect to home page
                            header("Location: ../index.php");
                            exit();
                        } else {
                            $errorMessage = "Registration failed. Please try again.";
                            $activeTab = 'register';
                        }
                        $insertStmt->close();
                    }
                }
                $checkStmt->close();
            }
        }
    } 
    
    // Handle user and admin login
    else {
        $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($email) || empty($password)) {
            $errorMessage = "Please fill in all required fields.";
            $activeTab = 'login';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
            $activeTab = 'login';
        } else {
            $sql = "SELECT `admin_id`, `username`, `password_hash`, `email`, `role` 
                    FROM `administrator` 
                    WHERE `email` = ? 
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $userRow = $result->fetch_assoc();

                    if (password_verify($password, $userRow['password_hash'])) {
                        
                        // Prevent session fixation attack
                        session_regenerate_id(true);

                        // Set Session Variables
                        $_SESSION['admin_id']      = $userRow['admin_id'];
                        $_SESSION['username']      = $userRow['username'];
                        $_SESSION['email']         = $userRow['email'];
                        $_SESSION['role']          = $userRow['role'];
                        $_SESSION['last_activity'] = time();

                        $stmt->close();

                        // Redirect based on role
                        if ($userRow['role'] === 'admin') {
                            header("Location: dashboard.php");
                        } else {
                            header("Location: ../index.php");
                        }
                        exit();
                    } else {
                        $errorMessage = "Invalid email or password.";
                        $activeTab = 'login';
                    }
                } else {
                    $errorMessage = "Invalid email or password.";
                    $activeTab = 'login';
                }
                $stmt->close();
            } else {
                $errorMessage = "Database query error. Please try again later.";
                $activeTab = 'login';
            }
        }
    }
}

// Page title
$pageTitle = ($activeTab === 'register') ? "Sign Up - NearTrip" : "Login - NearTrip";
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Authentication card with login and register tabs -->
<main class="auth-page-section">
    <div class="auth-card-container">
        
        <!-- Authentication Card Brand Header -->
        <div class="auth-card-header">
            <div class="auth-brand-logo">
                <i class="fas fa-compass"></i>
            </div>
            <h2 class="auth-title">NearTrip Day-Visit Planner</h2>
            <p class="auth-subtitle">Explore Warakapola & Dorawaka attractions within a 25km radius</p>
        </div>

        <!-- Tab Controls (Login vs Sign Up) -->
        <div class="auth-tabs">
            <a href="login.php?action=login" class="auth-tab-btn <?php echo ($activeTab === 'login') ? 'active' : ''; ?>">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </a>
            <a href="login.php?action=register" class="auth-tab-btn <?php echo ($activeTab === 'register') ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
        </div>

        <!-- Notification Banners -->
        <?php if (!empty($errorMessage)): ?>
            <div class="auth-alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="auth-alert alert-success">
                <i class="fas fa-circle-check"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login form -->
        <?php if ($activeTab === 'login'): ?>
            <form action="login.php" method="POST" class="auth-form" autocomplete="off">
                <input type="hidden" name="action" value="login">
                
                <!-- Email field -->
                <div class="form-group">
                    <label for="login_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" 
                           id="login_email" 
                           name="email" 
                           class="form-input" 
                           placeholder="e.g. user@neartrip.com" 
                           value="<?php echo isset($_POST['email']) && $activeTab === 'login' ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>

                <!-- Password field -->
                <div class="form-group">
                    <label for="login_password" class="form-label">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <input type="password" 
                           id="login_password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Enter your account password" 
                           required>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-right-to-bracket"></i> Login
                </button>
            </form>

        <!-- Registration form -->
        <?php else: ?>
            <form action="login.php" method="POST" class="auth-form" autocomplete="off">
                <input type="hidden" name="action" value="register">

                <!-- Username field -->
                <div class="form-group">
                    <label for="reg_username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           id="reg_username" 
                           name="username" 
                           class="form-input" 
                           placeholder="Choose a username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                </div>
                
                <!-- Email address field -->
                <div class="form-group">
                    <label for="reg_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" 
                           id="reg_email" 
                           name="email" 
                           class="form-input" 
                           placeholder="e.g. traveler@example.com" 
                           value="<?php echo isset($_POST['email']) && $activeTab === 'register' ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>

                <!-- Password field -->
                <div class="form-group">
                    <label for="reg_password" class="form-label">
                        <i class="fas fa-lock"></i> Password (Min 6 chars)
                    </label>
                    <input type="password" 
                           id="reg_password" 
                           name="password" 
                           class="form-input" 
                           placeholder="Create password" 
                           required>
                </div>

                <!-- Confirm password field -->
                <div class="form-group">
                    <label for="reg_confirm_password" class="form-label">
                        <i class="fas fa-shield-halved"></i> Confirm Password
                    </label>
                    <input type="password" 
                           id="reg_confirm_password" 
                           name="confirm_password" 
                           class="form-input" 
                           placeholder="Re-enter password" 
                           required>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
        <?php endif; ?>

    </div>
</main>

<?php
// Page footer
include_once __DIR__ . '/../includes/footer.php';
?>
