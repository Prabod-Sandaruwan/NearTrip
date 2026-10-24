<?php
/**
 * Database connection setup.
 * Connects to the NearTrip MySQL database.
 */

define('DB_SERVER', '127.0.0.1');
define('DB_PORT', 3307);
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'neartrip');

// Attempt connection on port 3307 first with empty password
$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

// Fallback tries: port 3307 with 'root' password, then default port 3306
if ($conn->connect_error) {
    $conn = @new mysqli(DB_SERVER, 'root', 'root', DB_NAME, 3307);
    if ($conn->connect_error) {
        $conn = @new mysqli(DB_SERVER, 'root', '', DB_NAME, 3306);
        if ($conn->connect_error) {
            $conn = new mysqli(DB_SERVER, 'root', 'root', DB_NAME, 3306);
            if ($conn->connect_error) {
                die("Database Connection Error: " . $conn->connect_error);
            }
        }
    }
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Set global default timezone to Sri Lanka (Asia/Colombo)
date_default_timezone_set('Asia/Colombo');

// Require global authentication & helper functions
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>
