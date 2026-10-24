<?php
/**
 * User and admin logout endpoint.
 * Clears session data, removes session cookie, and redirects to login page.
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
