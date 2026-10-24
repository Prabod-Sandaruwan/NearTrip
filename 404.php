<?php
/**
 * 404 Page Not Found error page.
 */

// Set HTTP response code to 404 Not Found
http_response_code(404);

// Define Page Title
$pageTitle = "404 Page Not Found - NearTrip Local Tourist Planner";

// Include Navigation Header
include_once __DIR__ . '/includes/header.php';
?>

<main class="page-not-found-section" style="min-height: 75vh; display: flex; align-items: center; justify-content: center; padding: 40px 0;">
    <div class="section-container" style="width: 100%;">
        <?php
            render_no_results([
                'title'          => "404 - Page Not Found",
                'message'        => "Oops! The page or resource you are looking for doesn't exist, has been removed, or is temporarily unavailable.",
                'icon'           => "fas fa-compass-slash",
                'action_url'     => "index.php",
                'action_text'    => "Back to Home",
                'action_icon'    => "fas fa-house",
                'secondary_url'  => "places.php",
                'secondary_text' => "Explore Destinations",
                'secondary_icon' => "fas fa-map-location-dot"
            ]);
        ?>
    </div>
</main>

<?php
// Include Footer
include_once __DIR__ . '/includes/footer.php';
?>
