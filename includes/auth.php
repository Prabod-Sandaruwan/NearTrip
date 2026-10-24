<?php
/**
 * Authentication and user role helper functions.
 * Handles user login status, role checks, session timeouts, and page access guards.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout to 30 minutes (1800 seconds)
define('SESSION_TIMEOUT_DURATION', 1800);

// Log out user if inactive for more than 30 minutes
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_DURATION) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_message'] = "Your session has expired due to inactivity. Please log in again.";
    }
}
// Update last activity time for logged-in users
if (isset($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
}

// Check if a user or admin is currently logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}

// Check if the logged-in user is an admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// Check if the logged-in user is a regular user
if (!function_exists('isUser')) {
    function isUser() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
    }
}

// Get the current user's username or return 'Guest'
if (!function_exists('getCurrentUsername')) {
    function getCurrentUsername() {
        return isLoggedIn() && isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    }
}

// Get the current user's role or return 'guest'
if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        return isLoggedIn() && isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
    }
}

// Require login to access a page. Redirects to login if not signed in.
if (!function_exists('requireLogin')) {
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

// Protect admin pages. Only admins can access; others are redirected.
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isLoggedIn()) {
            $url = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'login.php' : 'admin/login.php';
            header("Location: " . $url);
            exit();
        }
        if (!isAdmin()) {
            // Regular users are sent to the home page
            $url = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../index.php' : 'index.php';
            header("Location: " . $url);
            exit();
        }
    }
}

// Protect regular user pages. Only users can access; admins are sent to admin dashboard.
if (!function_exists('requireUser')) {
    function requireUser() {
        if (!isLoggedIn()) {
            $url = (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../admin/login.php' : 'admin/login.php';
            header("Location: " . $url);
            exit();
        }
        if (isAdmin()) {
            // Admins are sent to the admin dashboard
            $url = (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../admin/dashboard.php' : 'admin/dashboard.php';
            header("Location: " . $url);
            exit();
        }
    }
}
