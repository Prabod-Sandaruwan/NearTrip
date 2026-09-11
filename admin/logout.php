<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Logout Endpoint (admin/logout.php)
 * 
 * Requirement Coverage:
 * - SE-07, SE-08: Secure session destruction & logout redirect
 * ===================================================================
 */

// Initialize or resume existing session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// If session cookie exists, clear it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session on server
session_destroy();

// Redirect to login page with success notice
header("Location: login.php?logout=success");
exit();
?>
