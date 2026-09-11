<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Interactive Map Page (map.php)
 * 
 * Database: neartrip
 * Table: attraction
 * 
 * Requirement Coverage (FR-MAP-01 to FR-MAP-06):
 * - FR-MAP-01: Leaflet.js Interactive Map Integration centered on Dorawaka (7.1592° N, 80.2020° E)
 * - FR-MAP-02: Dynamic marker placement from MySQL database coordinates
 * - FR-MAP-03: Interactive Marker Popups with Name, Image, Description, and Details Link
 * - FR-MAP-06: Quick navigation to destination details page (details.php?id=X)
 * ===================================================================
 */

// Step 1: Set page title prior to including header component
$pageTitle = "Interactive Map - Discover Attractions Near Warakapola";

// Step 2: Include database connection & session authentication
require_once __DIR__ . '/includes/config.php';

// Step 3: Include modular navigation header
include_once __DIR__ . '/includes/header.php';

// -------------------------------------------------------------------
// Step 4: FETCH ATTRACTIONS WITH VALID LATITUDE & LONGITUDE FROM DB
// -------------------------------------------------------------------
$attractions = [];

// Query database for attractions with non-null coordinates
$sql = "SELECT attraction_id, name, description, location, latitude, longitude, category_id, image_url 
        FROM `attraction` 
        WHERE `latitude` IS NOT NULL 
          AND `longitude` IS NOT NULL 
          AND `latitude` != 0 
          AND `longitude` != 0 
        ORDER BY `attraction_id` DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Resolve image URL or assign fallback
        $rawImg = !empty($row['image_url']) ? $row['image_url'] : 'assets/hero.webp';
        $cleanImg = ltrim($rawImg, '/');
        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
        
        $row['image_url'] = $imagePath;
        
        // Truncate description for map popup (max 50 characters for clean UI)
        $descText = strip_tags($row['description']);
        if (mb_strlen($descText) > 50) {
            $shortDesc = mb_substr($descText, 0, 50) . '...';
        } else {
            $shortDesc = $descText;
        }
        $row['short_description'] = $shortDesc;
        
        // Convert coordinates to float numbers for JS consumption
        $row['latitude'] = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];

        $attractions[] = $row;
    }
}
?>

<!-- ===================================================================
     LEAFLET.JS CSS (FR-MAP-01 CDN)
     =================================================================== -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<!-- ===================================================================
     PAGE HERO BANNER
     =================================================================== -->
<section class="page-hero">
    <div>
        <h1 class="page-hero-title">Interactive Tourist Map</h1>
        <p class="page-hero-subtext">Discover hidden local attractions, heritage sites, and natural waterfalls within a 25 km radius of Dorawaka & Warakapola.</p>
    </div>
</section>

<!-- ===================================================================
     MAIN MAP SECTION
     =================================================================== -->
<main class="map-page-section" style="padding: 40px 20px 80px 20px;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Top Information & Statistics Bar (Simple Clean Design) -->
        <div class="map-info-bar">
            <div class="map-info-simple-text">
                <i class="fas fa-location-dot" style="color: #7C3AED; font-size: 1.1rem;"></i>
                <span><strong>25 km Exploration Zone</strong> &bull; Centered at Dorawaka (7.1592° N, 80.2020° E)</span>
            </div>
            
            <div class="map-info-simple-count">
                <i class="fas fa-map-pin" style="color: #7C3AED;"></i> 
                <span><strong><?php echo count($attractions); ?></strong> Attractions Found on Map</span>
            </div>
        </div>

        <?php if (empty($attractions)): ?>
            <!-- Error State: If no attractions have valid coordinates -->
            <div class="map-empty-box">
                <i class="fas fa-triangle-exclamation" style="font-size: 3rem; color: var(--secondary); margin-bottom: 16px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--text-dark); margin-bottom: 8px;">No Attractions Found on Map</h3>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px auto;">There are currently no attractions with valid latitude and longitude coordinates in the database.</p>
                <a href="places.php" class="btn-explore">View All Destinations</a>
            </div>
        <?php else: ?>
            <!-- Leaflet Interactive Map Container (FR-MAP-01) -->
            <div id="map" class="interactive-map-box" style="height: 70vh;"></div>
        <?php endif; ?>

    </div>
</main>

<!-- ===================================================================
     LEAFLET.JS SCRIPT (FR-MAP-01 CDN)
     =================================================================== -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Check if map container exists in DOM
    var mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // -----------------------------------------------------------------
    // 1. INITIALIZE MAP CENTERED ON DORAWAKA (7.1592, 80.2020) AT ZOOM 12
    // -----------------------------------------------------------------
    var dorawakaLat = 7.1592;
    var dorawakaLng = 80.2020;
    var map = L.map('map').setView([dorawakaLat, dorawakaLng], 12);

    // -----------------------------------------------------------------
    // 2. ADD OPENSTREETMAP TILE LAYER
    // -----------------------------------------------------------------
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // -----------------------------------------------------------------
    // 3. PASS PHP ATTRACTIONS DATA SAFELY TO JAVASCRIPT
    // -----------------------------------------------------------------
    var attractions = <?php echo json_encode($attractions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Custom Violet/Purple Pin Marker Icon (Replaced green icon)
    var markerIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // -----------------------------------------------------------------
    // 4. LOOP THROUGH ATTRACTIONS AND BIND POPUPS (FR-MAP-02 & FR-MAP-03)
    // -----------------------------------------------------------------
    attractions.forEach(function(attraction) {
        if (!attraction.latitude || !attraction.longitude) return;

        // Add Marker to Map
        var marker = L.marker([attraction.latitude, attraction.longitude], { icon: markerIcon }).addTo(map);

        // Escape HTML content for security
        var safeName = escapeHtml(attraction.name);
        var safeLocation = escapeHtml(attraction.location || 'Warakapola Zone');
        var safeDesc = escapeHtml(attraction.short_description);
        var safeImage = escapeHtml(attraction.image_url);
        var attractionId = parseInt(attraction.attraction_id);

        // Build Popup HTML Content
        var popupHtml = `
            <div class="map-popup-card">
                <div class="map-popup-img-wrapper">
                    <img src="${safeImage}" alt="${safeName}" class="map-popup-img">
                </div>
                <div class="map-popup-body">
                    <h4 class="map-popup-title">${safeName}</h4>
                    <p class="map-popup-location"><i class="fas fa-location-dot"></i> ${safeLocation}</p>
                    <p class="map-popup-desc">${safeDesc}</p>
                    <a href="details.php?id=${attractionId}" class="map-popup-btn">
                        View Details <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        `;

        // Bind popup to marker
        marker.bindPopup(popupHtml, { maxWidth: 280 });
    });

    // Helper function to escape HTML inside JS
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<?php
// Include Footer Component
include_once __DIR__ . '/includes/footer.php';
?>
