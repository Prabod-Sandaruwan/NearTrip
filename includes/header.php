<?php
/**
 * Navigation header component.
 * Displays the website header and navigation menu based on user role.
 */

require_once __DIR__ . '/config.php';

// Adjust path if page is inside a subfolder (admin/ or user/)
$inSubfolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false);
$prefix = $inSubfolder ? '../' : '';

// Set greeting message based on current time of day
date_default_timezone_set('Asia/Colombo');
$currentHour = (int) date('H');
if ($currentHour >= 5 && $currentHour < 12) {
    $greeting = "Good Morning!";
} elseif ($currentHour >= 12 && $currentHour < 17) {
    $greeting = "Good Afternoon!";
} elseif ($currentHour >= 17 && $currentHour < 21) {
    $greeting = "Good Evening!";
} else {
    $greeting = "Good Night!";
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'NearTrip - Explore Beautiful Places Near Dorawaka & Warakapola'; ?></title>
    <meta name="description" content="Help local tourists, family trip organizers, and history enthusiasts explore attractions within a 25km radius of Dorawaka, Warakapola.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo $prefix; ?>assets/css/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>

    <!-- Main Navigation Bar -->
    <header class="navbar">
        <div class="nav-container">
            
            <!-- Logo and Brand Name -->
            <a href="<?php echo $prefix; ?>index.php" class="navbar-brand">
                <span>NearTrip</span>
            </a>

            <!-- Mobile menu button -->
            <input type="checkbox" id="nav-toggle" class="nav-toggle">
            <label for="nav-toggle" class="nav-toggle-label" aria-label="Toggle navigation menu">
                <span></span>
            </label>

            <!-- Navigation Links -->
            <nav class="nav-menu">
                <ul>
                    <li><a href="<?php echo $prefix; ?>index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Home</a></li>
                    <li><a href="<?php echo $prefix; ?>places.php" class="nav-link <?= ($currentPage == 'places.php') ? 'active' : '' ?>">Destinations</a></li>
                    <li><a href="<?php echo $prefix; ?>map.php" class="nav-link <?= ($currentPage == 'map.php') ? 'active' : '' ?>">Map</a></li>
                    <li><a href="<?php echo $prefix; ?>safety.php" class="nav-link <?= ($currentPage == 'safety.php') ? 'active' : '' ?>">Safety Tips</a></li>
                    <?php 
                        $itineraryCount = (isset($_SESSION['itinerary']) && is_array($_SESSION['itinerary'])) ? count($_SESSION['itinerary']) : 0;
                    ?>
                    <li>
                        <a href="<?php echo $prefix; ?>plan.php" class="nav-link <?= ($currentPage == 'plan.php' || $currentPage == 'itinerary.php') ? 'active' : '' ?>">
                            <i class="fas fa-map-location-dot" style="margin-right: 4px;"></i> Itinerary<?php if ($itineraryCount > 0): ?> <span class="nav-itinerary-badge"><?php echo $itineraryCount; ?></span><?php endif; ?>
                        </a>
                    </li>

                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="<?php echo $prefix; ?>admin/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : '' ?>">Admin Panel</a></li>
                            <li><a href="<?php echo $prefix; ?>admin/users.php" class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>">Users & Roles</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $prefix; ?>user/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? 'active' : '' ?>">My Dashboard</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- User Account Actions -->
                <div class="nav-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo $prefix; ?>user/profile.php" class="user-greeting-badge" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; color: rgba(255, 255, 255, 0.95); background: rgba(255, 255, 255, 0.12); padding: 8px 16px; border-radius: 50px; font-size: 0.88rem; transition: all 0.3s ease; border: 1px solid rgba(255, 255, 255, 0.2);" title="Manage Account Profile & Password">
                            <i class="fas fa-user-circle" style="color: #60A5FA;"></i>
                            <span>Welcome, <strong><?php echo htmlspecialchars(getCurrentUsername()); ?></strong>!</span>
                        </a>

                        <a href="<?php echo $prefix; ?>admin/logout.php" class="btn-outline-logout" title="Logout">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $prefix; ?>admin/login.php" class="btn-login">
                            <i class="fas fa-right-to-bracket"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </nav>

        </div>
    </header>
