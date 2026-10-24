<?php
/**
 * Destination details page.
 * Loads and displays complete details for a selected attraction.
 */

require_once __DIR__ . '/includes/config.php';

// Get attraction ID from query string and load details from database
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
    <!-- Shown when attraction is not found -->
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
        // Prepare image path or use default fallback image
        $rawImg = !empty($attraction['image_url']) ? $attraction['image_url'] : 'assets/hero.webp';
        $cleanImg = ltrim($rawImg, '/');
        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
        
        // Prepare coordinates with default Warakapola location as fallback
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

    <!-- Leaflet map stylesheet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    <!-- Hero banner with title and main details -->
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

    <!-- Breadcrumb links -->
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

    <!-- Main details section -->
    <main class="details-section">
        <div class="details-container details-grid">
            
            <!-- Left column: main details -->
            <div class="details-main-content">
                
                <!-- About destination description -->
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

                <!-- Available facilities list -->
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

                <!-- Travel tips -->
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

                <!-- Safety guidelines -->
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

                <!-- Map location -->
                <div class="details-card">
                    <h2 class="details-card-title">
                        <i class="fas fa-map-location-dot"></i> Interactive Location Map
                    </h2>
                    <div class="details-card-body">
                        <?php if ($hasCoordinates): ?>
                            <p style="color: var(--text-muted); margin-bottom: 14px; font-size: 0.92rem;">
                                GPS Coordinates: <strong><?php echo number_format($lat, 6); ?>, <?php echo number_format($lng, 6); ?></strong>
                            </p>
                            <!-- Map container -->
                            <div id="mini-map" style="height: 300px; border-radius: 12px; border: 1px solid #CBD5E1; z-index: 1;"></div>
                        <?php else: ?>
                            <!-- Fallback message when coordinates are not set -->
                            <div style="padding: 30px 20px; background: #F8FAFC; border-radius: 12px; text-align: center; border: 1px dashed #CBD5E1;">
                                <i class="fas fa-location-slash" style="font-size: 2.2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-weight: 500;">Location not available on map</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right column: sidebar with key info and actions -->
            <div class="details-sidebar">
                
                <!-- Quick information card -->
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

                    <!-- Action buttons -->
                    <div class="widget-actions">
                        <!-- Add or remove from itinerary -->
                        <?php if (isset($_SESSION['itinerary']) && is_array($_SESSION['itinerary']) && in_array((int)$attraction['attraction_id'], $_SESSION['itinerary'])): ?>
                            <a href="plan.php?remove=<?php echo (int)$attraction['attraction_id']; ?>" class="btn-sidebar-primary added" style="background-color: #27ae60;" title="Click to remove from itinerary">
                                <i class="fas fa-circle-check"></i> In Your Itinerary
                            </a>
                        <?php else: ?>
                            <a href="plan.php?add=<?php echo (int)$attraction['attraction_id']; ?>" class="btn-sidebar-primary">
                                <i class="fas fa-calendar-plus"></i> Add to Itinerary
                            </a>
                        <?php endif; ?>

                        <!-- Google Maps directions link -->
                        <a href="https://www.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank" class="btn-sidebar-secondary">
                            <i class="fas fa-diamond-turn-right"></i> Get Directions
                        </a>

                        <!-- Return to destinations list -->
                        <a href="places.php" class="btn-sidebar-outline">
                            <i class="fas fa-arrow-left"></i> Back to Destinations
                        </a>
                    </div>
                </div>

                <!-- Safety tips callout -->
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

    <!-- Mini map setup script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var miniMapElem = document.getElementById('mini-map');
            if (!miniMapElem) return;

            var lat = <?php echo (float)$lat; ?>;
            var lng = <?php echo (float)$lng; ?>;
            var name = <?php echo json_encode($attName); ?>;

            // Initialize map with attraction coordinates
            var miniMap = L.map('mini-map').setView([lat, lng], 15);

            // Add map tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(miniMap);

            // Add marker for this attraction
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
// Page footer
include_once __DIR__ . '/includes/footer.php';
?>
