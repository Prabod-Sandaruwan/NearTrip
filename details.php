<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Destination Details Page (details.php)
 * 
 * Requirement Coverage:
 * - Dynamic fetch by attraction_id (FR-DET-01–12)
 * - Prepared SQL queries with Category JOIN
 * - Display all 12 fields gracefully (name, description, category,
 *   location, latitude, longitude, opening_hours, entrance_fee,
 *   facilities, travel_tips, safety_guidelines, image_url)
 * - Graceful fallback handling for optional/missing fields
 * - FR-PL-04 No Results Component Integration
 * ===================================================================
 */

require_once __DIR__ . '/includes/config.php';

// -------------------------------------------------------------------
// 1. DYNAMIC ATTRACTION FETCH (FR-DET-01)
// -------------------------------------------------------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$attraction = null;

if ($id > 0) {
    // Prepared SQL statement joining category table
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            WHERE a.`attraction_id` = ?";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $attraction = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Dynamic Page Title
$pageTitle = ($attraction && !empty($attraction['name'])) 
    ? htmlspecialchars($attraction['name']) . " - NearTrip Warakapola" 
    : "Destination Not Found - NearTrip";

// Include Navigation Header
include_once __DIR__ . '/includes/header.php';
?>

<?php if (!$attraction): ?>
    <!-- ===================================================================
         NOT FOUND STATE (FR-PL-04 Integration)
         =================================================================== -->
    <main class="details-not-found-section" style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 60px 0;">
        <div class="section-container" style="width: 100%;">
            <?php
                render_no_results([
                    'title'          => 'Destination Not Found',
                    'message'        => 'The attraction you are looking for does not exist, has an invalid ID, or has been removed from our database.',
                    'icon'           => 'fas fa-compass-slash',
                    'action_url'     => 'places.php',
                    'action_text'    => 'Back to Destinations',
                    'action_icon'    => 'fas fa-arrow-left',
                    'secondary_url'  => 'index.php',
                    'secondary_text' => 'Go to Home',
                    'secondary_icon' => 'fas fa-house'
                ]);
            ?>
        </div>
    </main>
<?php else: ?>
    <?php
        // Resolve image URL or graceful fallback image (FR-DET-12)
        $rawImg = !empty($attraction['image_url']) ? $attraction['image_url'] : 'assets/hero.webp';
        $cleanImg = ltrim($rawImg, '/');
        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
        
        // Resolve GPS Coordinates with default Warakapola fallbacks (FR-DET-05, FR-DET-06)
        $rawLat = !empty($attraction['latitude']) ? floatval($attraction['latitude']) : 0;
        $rawLng = !empty($attraction['longitude']) ? floatval($attraction['longitude']) : 0;
        
        $hasCoordinates = ($rawLat != 0 && $rawLng != 0);
        $lat = $hasCoordinates ? $rawLat : 7.2245;
        $lng = $hasCoordinates ? $rawLng : 80.1983;

        // String field helpers
        $attName          = !empty($attraction['name']) ? $attraction['name'] : 'Unknown Attraction';
        $categoryName     = !empty($attraction['category_name']) ? $attraction['category_name'] : '';
        $locationText     = !empty($attraction['location']) ? $attraction['location'] : '';
        $descriptionText  = !empty($attraction['description']) ? $attraction['description'] : '';
        $openingHoursText = !empty($attraction['opening_hours']) ? $attraction['opening_hours'] : '';
        $entranceFeeText  = !empty($attraction['entrance_fee']) ? $attraction['entrance_fee'] : '';
        $facilitiesRaw    = !empty($attraction['facilities']) ? $attraction['facilities'] : '';
        $travelTipsText   = !empty($attraction['travel_tips']) ? $attraction['travel_tips'] : '';
        $safetyText       = !empty($attraction['safety_guidelines']) ? $attraction['safety_guidelines'] : '';
    ?>

    <!-- Leaflet CSS for Interactive Location Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    <!-- ===================================================================
         DESTINATION HERO BANNER
         =================================================================== -->
    <section class="details-hero" style="background-image: linear-gradient(180deg, rgba(15, 23, 42, 0.4) 0%, rgba(15, 23, 42, 0.85) 100%), url('<?php echo htmlspecialchars($imagePath); ?>');">
        <div class="details-hero-content">
            
            <div class="details-hero-badges">
                <?php if (!empty($categoryName)): ?>
                    <span class="details-hero-badge category">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($categoryName); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($entranceFeeText)): ?>
                    <span class="details-hero-badge fee">
                        <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($entranceFeeText); ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="details-hero-title"><?php echo htmlspecialchars($attName); ?></h1>
            
            <?php if (!empty($locationText)): ?>
                <div class="details-hero-location">
                    <i class="fas fa-location-dot"></i>
                    <span><?php echo htmlspecialchars($locationText); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================================================================
         BREADCRUMB NAVIGATION BAR
         =================================================================== -->
    <div class="details-breadcrumb-bar">
        <div class="details-container">
            <ul class="details-breadcrumb">
                <li><a href="index.php"><i class="fas fa-house"></i> Home</a></li>
                <li><i class="fas fa-chevron-right separator"></i></li>
                <li><a href="places.php">Destinations</a></li>
                <li><i class="fas fa-chevron-right separator"></i></li>
                <li class="active"><?php echo htmlspecialchars($attName); ?></li>
            </ul>
        </div>
    </div>

    <!-- ===================================================================
         MAIN ATTRACTION DETAILS GRID (FR-DET-01–12)
         =================================================================== -->
    <main class="details-section">
        <div class="details-container details-grid">
            
            <!-- LEFT COLUMN: MAIN CONTENT -->
            <div class="details-main-content">
                
                <!-- Overview & Description (FR-DET-04) -->
                <?php if (!empty($descriptionText)): ?>
                    <div class="details-glass-card">
                        <h2 class="details-card-title">
                            <i class="fas fa-circle-info"></i> About Destination
                        </h2>
                        <div class="details-card-body">
                            <p class="details-description">
                                <?php echo nl2br(htmlspecialchars($descriptionText)); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Facilities & Amenities (FR-DET-09) -->
                <?php 
                    $facilitiesList = !empty($facilitiesRaw) ? array_filter(array_map('trim', explode(',', $facilitiesRaw))) : [];
                    if (!empty($facilitiesList)): 
                ?>
                    <div class="details-card">
                        <h2 class="details-card-title">
                            <i class="fas fa-concierge-bell"></i> Available Facilities & Amenities
                        </h2>
                        <div class="details-card-body">
                            <div class="facilities-grid">
                                <?php foreach ($facilitiesList as $facility): ?>
                                    <div class="facility-badge">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?php echo htmlspecialchars($facility); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Travel Tips & Best Time to Visit (FR-DET-10) -->
                <?php if (!empty($travelTipsText)): ?>
                    <div class="details-card tip-card">
                        <h2 class="details-card-title tip-title">
                            <i class="fas fa-lightbulb"></i> Travel Tips & Best Time to Visit
                        </h2>
                        <div class="details-card-body">
                            <p class="tip-text">
                                <?php echo nl2br(htmlspecialchars($travelTipsText)); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Essential Safety Guidelines (FR-DET-11) -->
                <?php if (!empty($safetyText)): ?>
                    <div class="details-card safety-card">
                        <h2 class="details-card-title safety-title">
                            <i class="fas fa-shield-halved"></i> Essential Safety Guidelines
                        </h2>
                        <div class="details-card-body">
                            <p class="safety-text">
                                <?php echo nl2br(htmlspecialchars($safetyText)); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Interactive Location Mini-Map (FR-MAP-06) -->
                <div class="details-card">
                    <h2 class="details-card-title">
                        <i class="fas fa-map-location-dot"></i> Interactive Location Map
                    </h2>
                    <div class="details-card-body">
                        <?php if ($hasCoordinates): ?>
                            <p style="color: var(--text-muted); margin-bottom: 14px; font-size: 0.92rem;">
                                GPS Coordinates: <strong><?php echo number_format($lat, 6); ?>, <?php echo number_format($lng, 6); ?></strong>
                            </p>
                            <!-- Mini-Map Container (FR-MAP-06 Requirement) -->
                            <div id="mini-map" style="height: 300px; border-radius: 12px; border: 1px solid #CBD5E1; z-index: 1;"></div>
                        <?php else: ?>
                            <!-- Fallback when coordinates are missing or null -->
                            <div style="padding: 30px 20px; background: #F8FAFC; border-radius: 12px; text-align: center; border: 1px dashed #CBD5E1;">
                                <i class="fas fa-location-slash" style="font-size: 2.2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-weight: 500;">Location not available on map</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: STICKY SIDEBAR -->
            <div class="details-sidebar">
                
                <!-- Quick Information Widget -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Quick Information</h3>
                    
                    <ul class="widget-info-list">
                        <?php if (!empty($categoryName)): ?>
                            <li>
                                <div class="info-icon"><i class="fas fa-tag"></i></div>
                                <div>
                                    <span class="info-label">Category</span>
                                    <strong class="info-value"><?php echo htmlspecialchars($categoryName); ?></strong>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($openingHoursText)): ?>
                            <li>
                                <div class="info-icon"><i class="fas fa-clock"></i></div>
                                <div>
                                    <span class="info-label">Opening Hours</span>
                                    <strong class="info-value"><?php echo htmlspecialchars($openingHoursText); ?></strong>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($entranceFeeText)): ?>
                            <li>
                                <div class="info-icon"><i class="fas fa-ticket-alt"></i></div>
                                <div>
                                    <span class="info-label">Entrance Fee</span>
                                    <strong class="info-value"><?php echo htmlspecialchars($entranceFeeText); ?></strong>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($locationText)): ?>
                            <li>
                                <div class="info-icon"><i class="fas fa-location-dot"></i></div>
                                <div>
                                    <span class="info-label">Location Address</span>
                                    <strong class="info-value"><?php echo htmlspecialchars($locationText); ?></strong>
                                </div>
                            </li>
                        <?php endif; ?>

                        <li>
                            <div class="info-icon"><i class="fas fa-crosshairs"></i></div>
                            <div>
                                <span class="info-label">Coordinates</span>
                                <strong class="info-value"><?php echo number_format($lat, 4); ?>, <?php echo number_format($lng, 4); ?></strong>
                            </div>
                        </li>
                    </ul>

                    <!-- Action Buttons -->
                    <div class="widget-actions">
                        <!-- Add/Remove from Day Trip Itinerary -->
                        <?php if (isset($_SESSION['itinerary']) && is_array($_SESSION['itinerary']) && in_array((int)$attraction['attraction_id'], $_SESSION['itinerary'])): ?>
                            <a href="plan.php?remove=<?php echo (int)$attraction['attraction_id']; ?>" class="btn-sidebar-primary added" style="background-color: #27ae60;" title="Click to remove from itinerary">
                                <i class="fas fa-circle-check"></i> In Your Itinerary
                            </a>
                        <?php else: ?>
                            <a href="plan.php?add=<?php echo (int)$attraction['attraction_id']; ?>" class="btn-sidebar-primary">
                                <i class="fas fa-calendar-plus"></i> Add to Itinerary
                            </a>
                        <?php endif; ?>

                        <!-- Google Maps Navigation Link -->
                        <a href="https://www.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank" class="btn-sidebar-secondary">
                            <i class="fas fa-diamond-turn-right"></i> Get Directions
                        </a>

                        <!-- Back to Destinations Button -->
                        <a href="places.php" class="btn-sidebar-outline">
                            <i class="fas fa-arrow-left"></i> Back to Destinations
                        </a>
                    </div>
                </div>

                <!-- Support & Safety Widget -->
                <div class="sidebar-widget support-widget">
                    <h4><i class="fas fa-headset"></i> Day Trip Assistance</h4>
                    <p>Visiting Warakapola & Dorawaka? Check local safety guidelines before traveling.</p>
                    <a href="safety.php" style="color: var(--primary); font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-top: 10px;">
                        View Safety Tips <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

            </div>

        </div>
    </main>

    <!-- Leaflet JS Mini-Map Script (FR-MAP-06) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var miniMapElem = document.getElementById('mini-map');
            if (!miniMapElem) return;

            var lat = <?php echo (float)$lat; ?>;
            var lng = <?php echo (float)$lng; ?>;
            var name = <?php echo json_encode($attName); ?>;

            // Step 1: Initialize mini-map centered at attraction coordinates with Zoom Level 15
            var miniMap = L.map('mini-map').setView([lat, lng], 15);

            // Step 2: Add OpenStreetMap Tile Layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(miniMap);

            // Step 3: Add Leaflet Marker with Popup displaying attraction name
            var marker = L.marker([lat, lng]).addTo(miniMap);
            marker.bindPopup("<strong style='font-family: Poppins, sans-serif; font-size: 0.95rem; color: #1E293B;'>" + escapeHtml(name) + "</strong><br><span style='font-size: 0.8rem; color: #64748B;'>Location Marker</span>").openPopup();

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
<?php endif; ?>

<?php
// Include Footer Component
include_once __DIR__ . '/includes/footer.php';
?>
