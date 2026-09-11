<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Global Authentication & Role Helper Functions (includes/auth.php)
 * 
 * Requirement Coverage:
 * - Session Security & Timeout (SE-07, SE-08)
 * - Role Management & Route Guards (SE-11)
 * - Helper functions: isLoggedIn(), isAdmin(), isUser()
 * ===================================================================
 */

// -------------------------------------------------------------------
// 1. SESSION INITIALIZATION & INACTIVITY TIMEOUT CHECK
// -------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inactivity timeout limit: 30 minutes (1800 seconds)
define('SESSION_TIMEOUT_DURATION', 1800);

if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_DURATION) {
        // Session timed out - clear and destroy
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_message'] = "Your session has expired due to inactivity. Please log in again.";
    }
}
// Update last activity timestamp for active sessions
if (isset($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
}

// -------------------------------------------------------------------
// 2. HELPER FUNCTIONS FOR AUTHENTICATION & ROLE DETECTION
// -------------------------------------------------------------------

if (!function_exists('isLoggedIn')) {
    /**
     * Checks if a user or admin is currently logged in.
     * @return bool
     */
    function isLoggedIn() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Checks if the currently logged in account has the 'admin' role.
     * @return bool
     */
    function isAdmin() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('isUser')) {
    /**
     * Checks if the currently logged in account has the 'user' role.
     * @return bool
     */
    function isUser() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
    }
}

if (!function_exists('getCurrentUsername')) {
    /**
     * Returns the username of the logged-in account, or 'Guest'.
     * @return string
     */
    function getCurrentUsername() {
        return isLoggedIn() && isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    }
}

if (!function_exists('getCurrentUserRole')) {
    /**
     * Returns the role of the logged-in account, or 'guest'.
     * @return string
     */
    function getCurrentUserRole() {
        return isLoggedIn() && isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
    }
}

// -------------------------------------------------------------------
// 3. ROUTE GUARDS / ACCESS CONTROLLERS (SE-11)
// -------------------------------------------------------------------

if (!function_exists('requireLogin')) {
    /**
     * Guard: Requires any logged-in user or admin.
     * Redirects to login page if unauthenticated.
     */
    function requireLogin($loginPageUrl = null) {
        if (!isLoggedIn()) {
            if ($loginPageUrl === null) {
                $loginPageUrl = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false)
                    ? '../admin/login.php' 
                    : 'admin/login.php';
            }
            header("Location: " . $loginPageUrl);
            exit();
        }
    }
}

if (!function_exists('requireAdmin')) {
    /**
     * Guard: Requires logged-in account with 'admin' role.
     * Redirects unauthenticated users to login, and non-admins to user dashboard or home.
     */
    function requireAdmin() {
        if (!isLoggedIn()) {
            $url = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'login.php' : 'admin/login.php';
            header("Location: " . $url);
            exit();
        }
        if (!isAdmin()) {
            // User is logged in as regular 'user' role, redirect to homepage
            $url = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../index.php' : 'index.php';
            header("Location: " . $url);
            exit();
        }
    }
}

if (!function_exists('requireUser')) {
    /**
     * Guard: Requires logged-in account with 'user' role.
     * Redirects unauthenticated users to login, and admins to admin dashboard.
     */
    function requireUser() {
        if (!isLoggedIn()) {
            $url = (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../admin/login.php' : 'admin/login.php';
            header("Location: " . $url);
            exit();
        }
        if (isAdmin()) {
            // Logged in as admin, redirect to admin dashboard
            $url = (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../admin/dashboard.php' : 'admin/dashboard.php';
            header("Location: " . $url);
            exit();
        }
    }
}
