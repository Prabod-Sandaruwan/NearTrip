<?php
/**
 * Interactive map and tour route viewer.
 * Displays all attractions or a planned tour route on a Leaflet map.
 */

// Load database configuration and helpers
require_once __DIR__ . '/includes/config.php';

// Load Leaflet map stylesheet
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />';

// Fetch all attractions that have valid coordinates
$attractions = [];
$sql = "SELECT a.attraction_id, a.name, a.description, a.location, a.latitude, a.longitude, 
               a.category_id, a.image_url, a.opening_hours, a.entrance_fee, c.category_name 
        FROM `attraction` a 
        LEFT JOIN `category` c ON a.category_id = c.category_id 
        WHERE a.`latitude` IS NOT NULL 
          AND a.`longitude` IS NOT NULL 
          AND a.`latitude` != 0 
          AND a.`longitude` != 0 
        ORDER BY a.`attraction_id` DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rawImg = !empty($row['image_url']) ? $row['image_url'] : 'assets/hero.webp';
        $cleanImg = ltrim($rawImg, '/');
        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
        
        $row['image_url'] = $imagePath;
        
        $descText = strip_tags($row['description'] ?? '');
        $shortDesc = (mb_strlen($descText) > 60) ? mb_substr($descText, 0, 60) . '...' : $descText;
        $row['short_description'] = $shortDesc;
        
        $row['latitude'] = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];

        $attractions[] = $row;
    }
}

// Check if a saved tour or active session tour is requested
$tourStopsRaw = [];
$tourTitle = "";
$isTourMode = false;
$savedTourId = isset($_GET['itinerary_id']) ? (int)$_GET['itinerary_id'] : 0;

if ($savedTourId > 0) {
    // Load saved tour by ID
    $tStmt = $conn->prepare("SELECT `name`, `description` FROM `itinerary` WHERE `itinerary_id` = ?");
    if ($tStmt) {
        $tStmt->bind_param("i", $savedTourId);
        $tStmt->execute();
        $tRes = $tStmt->get_result();
        if ($tRes && $tRes->num_rows > 0) {
            $tRow = $tRes->fetch_assoc();
            $tourTitle = $tRow['name'];
            $isTourMode = true;
            
            // Fetch sequenced tour stops
            $iStmt = $conn->prepare("SELECT a.*, c.category_name, ia.day_number 
                                     FROM `itinerary_attraction` ia 
                                     JOIN `attraction` a ON ia.attraction_id = a.attraction_id 
                                     LEFT JOIN `category` c ON a.category_id = c.category_id 
                                     WHERE ia.itinerary_id = ? 
                                     ORDER BY ia.day_number ASC");
            if ($iStmt) {
                $iStmt->bind_param("i", $savedTourId);
                $iStmt->execute();
                $iRes = $iStmt->get_result();
                while ($iRow = $iRes->fetch_assoc()) {
                    $tourStopsRaw[] = $iRow;
                }
                $iStmt->close();
            }
        }
        $tStmt->close();
    }
} elseif (isset($_GET['tour']) || !empty($_SESSION['itinerary'])) {
    if (!empty($_SESSION['itinerary'])) {
        $tourTitle = "My Active Tour Plan";
        $isTourMode = (isset($_GET['tour']) && $_GET['tour'] === 'active') || !isset($_GET['view_all']);
        
        $cleanIds = array_map('intval', $_SESSION['itinerary']);
        $implodedIds = implode(',', $cleanIds);
        $sqlTour = "SELECT a.*, c.category_name 
                    FROM `attraction` a 
                    LEFT JOIN `category` c ON a.category_id = c.category_id 
                    WHERE a.`attraction_id` IN ($implodedIds) 
                    ORDER BY FIELD(a.`attraction_id`, $implodedIds)";
        $resTour = $conn->query($sqlTour);
        if ($resTour && $resTour->num_rows > 0) {
            $tFetched = [];
            while ($tRow = $resTour->fetch_assoc()) {
                $tFetched[$tRow['attraction_id']] = $tRow;
            }
            foreach ($_SESSION['itinerary'] as $sId) {
                if (isset($tFetched[$sId])) {
                    $tourStopsRaw[] = $tFetched[$sId];
                }
            }
        }
    }
}

// Calculate distances and estimated travel times between stops
$processedTourStops = [];
$totalTourKm = 0.0;
$prevLat = null;
$prevLng = null;
$gmapsWaypoints = [];

foreach ($tourStopsRaw as $sIdx => $stop) {
    $lat = !empty($stop['latitude']) ? (float)$stop['latitude'] : 0.0;
    $lng = !empty($stop['longitude']) ? (float)$stop['longitude'] : 0.0;
    
    if ($lat != 0.0 && $lng != 0.0) {
        $rawImg = !empty($stop['image_url']) ? $stop['image_url'] : 'assets/hero.webp';
        $cleanImg = ltrim($rawImg, '/');
        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
        
        $distFromPrev = 0.0;
        if ($prevLat !== null && $prevLng !== null) {
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat - $prevLat);
            $dLon = deg2rad($lng - $prevLng);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($prevLat)) * cos(deg2rad($lat)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distFromPrev = round($earthRadius * $c * 1.25, 1);
            $totalTourKm += $distFromPrev;
        }
        $prevLat = $lat;
        $prevLng = $lng;

        $descText = strip_tags($stop['description'] ?? '');
        $shortDesc = mb_strlen($descText) > 60 ? mb_substr($descText, 0, 60) . '...' : $descText;

        $processedTourStops[] = [
            'id' => (int)$stop['attraction_id'],
            'step' => $sIdx + 1,
            'name' => $stop['name'],
            'lat' => $lat,
            'lng' => $lng,
            'location' => !empty($stop['location']) ? $stop['location'] : 'Warakapola Zone',
            'category' => !empty($stop['category_name']) ? $stop['category_name'] : 'Attraction',
            'hours' => !empty($stop['opening_hours']) ? $stop['opening_hours'] : 'Regular Hours',
            'fee' => !empty($stop['entrance_fee']) ? $stop['entrance_fee'] : 'Free / Standard',
            'image' => $imagePath,
            'description' => $shortDesc,
            'dist_from_prev' => $distFromPrev
        ];
        $gmapsWaypoints[] = $lat . ',' . $lng;
    }
}

$totalTourKm = round($totalTourKm, 1);
$tourMins = max(15, round(($totalTourKm / 28) * 60));
if ($tourMins >= 60) {
    $h = floor($tourMins / 60);
    $m = $tourMins % 60;
    $tourTimeText = $h . " hr " . ($m > 0 ? $m . " mins" : "");
} else {
    $tourTimeText = $tourMins . " mins";
}

// Build Google Maps directions link
$googleMapsNavUrl = "";
if (count($gmapsWaypoints) >= 2) {
    $origin = $gmapsWaypoints[0];
    $dest = $gmapsWaypoints[count($gmapsWaypoints) - 1];
    $mids = array_slice($gmapsWaypoints, 1, count($gmapsWaypoints) - 2);
    $googleMapsNavUrl = "https://www.google.com/maps/dir/?api=1&origin=" . $origin . "&destination=" . $dest;
    if (!empty($mids)) {
        $googleMapsNavUrl .= "&waypoints=" . implode('|', $mids);
    }
} elseif (count($gmapsWaypoints) === 1) {
    $googleMapsNavUrl = "https://www.google.com/maps/search/?api=1&query=" . $gmapsWaypoints[0];
}

$pageTitle = !empty($tourTitle) 
    ? htmlspecialchars($tourTitle) . " - Route Map - NearTrip Warakapola" 
    : "Interactive Tourist Map - Discover Attractions Near Warakapola";

// Page navigation header
include_once __DIR__ . '/includes/header.php';
?>

<!-- Hero banner -->
<section class="page-hero">
    <div>
        <h1 class="page-hero-title">
            <?php if (!empty($processedTourStops) && $isTourMode): ?>
                <i class="fas fa-route" style="color: #F5A623; margin-right: 8px;"></i> <?php echo htmlspecialchars($tourTitle); ?>
            <?php else: ?>
                <i class="fas fa-map-location-dot" style="margin-right: 8px;"></i> Interactive Tourist Map
            <?php endif; ?>
        </h1>
        <p class="page-hero-subtext">
            <?php if (!empty($processedTourStops) && $isTourMode): ?>
                Explore your customized day visit sequence, calculate route travel distances, and navigate between stops.
            <?php else: ?>
                Discover hidden local attractions, heritage sites, and natural waterfalls within a 25 km radius of Dorawaka & Warakapola.
            <?php endif; ?>
        </p>
    </div>
</section>

<!-- Main map section -->
<main class="map-page-section" style="padding: 30px 20px 80px 20px;">
    <div class="section-container" style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Mode Switcher Tabs (If Tour Stops Exist) -->
        <?php if (!empty($processedTourStops)): ?>
            <div class="map-mode-toggle-bar">
                <button type="button" id="btnModeTour" class="map-mode-btn <?php echo $isTourMode ? 'active' : ''; ?>">
                    <i class="fas fa-route"></i>
                    <span>Planned Tour Route (<?php echo count($processedTourStops); ?> Stops)</span>
                    <span class="map-mode-badge"><?php echo $totalTourKm; ?> km</span>
                </button>
                <button type="button" id="btnModeAll" class="map-mode-btn <?php echo !$isTourMode ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i>
                    <span>All Warakapola Attractions</span>
                    <span class="map-mode-badge"><?php echo count($attractions); ?></span>
                </button>

                <div class="route-action-buttons" style="margin-left: auto;">
                    <button type="button" id="btnFitMapBounds" class="btn-route-action" title="Fit map to view all points">
                        <i class="fas fa-expand"></i> Fit View
                    </button>
                    <a href="plan.php" class="btn-route-action btn-primary-action" title="Edit itinerary in planner">
                        <i class="fas fa-pen-to-square"></i> Planner
                    </a>
                    <?php if (!empty($googleMapsNavUrl)): ?>
                        <a href="<?php echo htmlspecialchars($googleMapsNavUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-route-action btn-gmaps" title="Open multi-stop navigation in Google Maps">
                            <i class="fab fa-google"></i> Google Maps Nav
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tour route statistics -->
            <div id="tourStatsBar" class="route-stats-pill-bar" style="<?php echo $isTourMode ? '' : 'display:none;'; ?>">
                <div class="route-stat-item">
                    <i class="fas fa-flag-checkered"></i>
                    <span>Stops: <strong><?php echo count($processedTourStops); ?> Planned Places</strong></span>
                </div>
                <div class="route-stat-divider"></div>
                <div class="route-stat-item">
                    <i class="fas fa-road"></i>
                    <span>Driving Route Distance: <strong>~<?php echo $totalTourKm; ?> km</strong></span>
                </div>
                <div class="route-stat-divider"></div>
                <div class="route-stat-item">
                    <i class="fas fa-car"></i>
                    <span>Estimated Transit Time: <strong>~<?php echo $tourTimeText; ?></strong></span>
                </div>
                <div class="route-stat-divider"></div>
                <div class="route-stat-item" style="color: #059669;">
                    <i class="fas fa-circle-check"></i>
                    <span>Order: <strong>Stop 1 &rarr; Stop <?php echo count($processedTourStops); ?></strong></span>
                </div>
            </div>
        <?php else: ?>
            <!-- Exploration zone info bar -->
            <div class="map-info-bar">
                <div class="map-info-simple-text">
                    <i class="fas fa-location-dot" style="color: var(--primary); font-size: 1.1rem;"></i>
                    <span><strong>25 km Exploration Zone</strong> &bull; Centered at Dorawaka (7.1592&deg; N, 80.2020&deg; E)</span>
                </div>
                
                <div class="map-info-simple-count">
                    <i class="fas fa-map-pin" style="color: var(--primary);"></i> 
                    <span><strong><?php echo count($attractions); ?></strong> Attractions Found on Map</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($attractions)): ?>
            <!-- Shown when no attractions have coordinates -->
            <div class="map-empty-box">
                <i class="fas fa-triangle-exclamation" style="font-size: 3rem; color: var(--secondary); margin-bottom: 16px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--text-dark); margin-bottom: 8px;">No Attractions Found on Map</h3>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px auto;">There are currently no attractions with valid latitude and longitude coordinates in the database.</p>
                <a href="places.php" class="btn-explore">View All Destinations</a>
            </div>
        <?php else: ?>
            <!-- Leaflet map container -->
            <div id="map" class="interactive-map-box" style="height: 65vh; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: var(--shadow-md);"></div>

            <!-- Timeline list of tour stops -->
            <?php if (!empty($processedTourStops)): ?>
                <div id="tourTimelineDrawer" class="tour-timeline-drawer" style="<?php echo $isTourMode ? '' : 'display:none;'; ?>">
                    <div class="tour-timeline-header">
                        <div class="tour-timeline-title">
                            <i class="fas fa-timeline" style="color: var(--primary);"></i>
                            <span>Stop-by-Stop Tour Itinerary (Click to Jump on Map)</span>
                        </div>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                            Sequential Day Visit Schedule
                        </span>
                    </div>

                    <div class="tour-timeline-strip">
                        <?php foreach ($processedTourStops as $tIdx => $tStop): 
                            $isStart = ($tIdx === 0);
                            $isEnd = ($tIdx === count($processedTourStops) - 1 && count($processedTourStops) > 1);
                        ?>
                            <div class="tour-timeline-step-card" onclick="focusTourStop(<?php echo $tIdx; ?>);" id="timeline-card-<?php echo $tIdx; ?>">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span class="timeline-step-badge <?php echo $isStart ? 'start' : ($isEnd ? 'finish' : ''); ?>">
                                        <?php echo $tStop['step']; ?>
                                    </span>
                                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748B; text-transform: uppercase;">
                                        <?php echo $isStart ? '🚩 Starting Spot' : ($isEnd ? '🏁 Final Stop' : 'Stop ' . $tStop['step']); ?>
                                    </span>
                                </div>
                                <h4 class="timeline-step-name" title="<?php echo htmlspecialchars($tStop['name']); ?>">
                                    <?php echo htmlspecialchars($tStop['name']); ?>
                                </h4>
                                <div class="timeline-step-loc">
                                    <i class="fas fa-location-dot" style="color: var(--primary); font-size: 0.75rem;"></i>
                                    <span><?php echo htmlspecialchars($tStop['location']); ?></span>
                                </div>
                                <?php if ($tStop['dist_from_prev'] > 0): ?>
                                    <span class="timeline-step-dist">
                                        <i class="fas fa-arrow-right"></i> +<?php echo $tStop['dist_from_prev']; ?> km from Stop <?php echo ($tStop['step'] - 1); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="timeline-step-dist" style="background: #ECFDF5; color: #059669;">
                                        <i class="fas fa-play"></i> Tour Origin
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<!-- Leaflet map script -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Attraction and tour route data
    var allAttractions = <?php echo json_encode($attractions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var tourStops = <?php echo json_encode($processedTourStops, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var initialMode = "<?php echo (!empty($processedTourStops) && $isTourMode) ? 'tour' : 'all'; ?>";

    // Initialize map centered on Dorawaka
    var dorawakaLat = 7.1592;
    var dorawakaLng = 80.2020;
    var map = L.map('map').setView([dorawakaLat, dorawakaLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Setup marker and route layers
    var allMarkersLayer = L.layerGroup().addTo(map);
    var tourRouteLayer = L.layerGroup();
    var tourMarkers = [];
    var tourLatLngs = [];

    // General Marker Pin
    var generalMarkerIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // Populate "All Attractions" Markers
    allAttractions.forEach(function(attraction) {
        if (!attraction.latitude || !attraction.longitude) return;

        var marker = L.marker([attraction.latitude, attraction.longitude], { icon: generalMarkerIcon });

        var safeName = escapeHtml(attraction.name);
        var safeLocation = escapeHtml(attraction.location || 'Warakapola Zone');
        var safeDesc = escapeHtml(attraction.short_description || '');
        var safeImage = escapeHtml(attraction.image_url);
        var attractionId = parseInt(attraction.attraction_id);

        var popupHtml = `
            <div class="map-popup-card">
                <div class="map-popup-img-wrapper">
                    <img src="${safeImage}" alt="${safeName}" class="map-popup-img">
                </div>
                <div class="map-popup-body">
                    <h4 class="map-popup-title">${safeName}</h4>
                    <p class="map-popup-location"><i class="fas fa-location-dot"></i> ${safeLocation}</p>
                    <p class="map-popup-desc">${safeDesc}</p>
                    <div style="display:flex; gap:6px;">
                        <a href="details.php?id=${attractionId}" class="map-popup-btn" style="flex:1; justify-content:center;">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="plan.php?add=${attractionId}" class="map-popup-btn" style="background:#F5A623; padding:7px 10px;" title="Add to Itinerary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
        marker.bindPopup(popupHtml, { maxWidth: 280 });
        allMarkersLayer.addLayer(marker);
    });

    // Populate "Tour Route" Markers & Polyline
    if (tourStops && tourStops.length > 0) {
        tourStops.forEach(function(stop, idx) {
            var isStart = (idx === 0);
            var isFinish = (idx === tourStops.length - 1 && tourStops.length > 1);

            var pinClass = 'tour-pin-container';
            if (isStart) pinClass += ' tour-pin-start';
            else if (isFinish) pinClass += ' tour-pin-finish';

            var pulseHtml = isStart ? '<div class="tour-pin-pulse"></div>' : '';

            var customIcon = L.divIcon({
                className: 'custom-tour-pin',
                html: `
                    <div class="${pinClass}">
                        ${pulseHtml}
                        <div class="tour-pin-body">
                            <span class="tour-pin-number">${stop.step}</span>
                        </div>
                    </div>
                `,
                iconSize: [38, 38],
                iconAnchor: [19, 36],
                popupAnchor: [0, -34]
            });

            var marker = L.marker([stop.lat, stop.lng], { icon: customIcon });
            tourLatLngs.push([stop.lat, stop.lng]);

            var stopBadgeText = isStart 
                ? 'Stop #' + stop.step + ' (Start)' 
                : (isFinish ? 'Stop #' + stop.step + ' (Final Stop)' : 'Stop #' + stop.step);
            var badgeColor = isStart ? '#059669' : (isFinish ? '#DC2626' : '#2D7D46');

            var popupHtml = `
                <div class="map-popup-card">
                    <div class="map-popup-img-wrapper">
                        <img src="${escapeHtml(stop.image)}" alt="${escapeHtml(stop.name)}" class="map-popup-img">
                    </div>
                    <div class="map-popup-body">
                        <span style="background:${badgeColor}; color:#fff; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:50px; display:inline-block; margin-bottom:6px;">
                            ${stopBadgeText}
                        </span>
                        <h4 class="map-popup-title">${escapeHtml(stop.name)}</h4>
                        <p class="map-popup-location"><i class="fas fa-location-dot"></i> ${escapeHtml(stop.location)}</p>
                        <div style="font-size:0.8rem; color:#475569; margin-bottom:10px;">
                            <div><i class="fas fa-clock" style="color:#64748B;"></i> ${escapeHtml(stop.hours)}</div>
                            <div><i class="fas fa-ticket" style="color:#64748B;"></i> ${escapeHtml(stop.fee)}</div>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <a href="details.php?id=${stop.id}" class="map-popup-btn" style="flex:1; justify-content:center;">
                                Details <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${stop.lat},${stop.lng}" target="_blank" rel="noopener" class="map-popup-btn" style="background:#EA4335; padding:7px 10px;" title="Navigate with Google Maps">
                                <i class="fab fa-google"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
            marker.bindPopup(popupHtml, { maxWidth: 280 });
            tourRouteLayer.addLayer(marker);
            tourMarkers.push(marker);
        });

        // Add connecting polyline if 2+ stops
        if (tourLatLngs.length >= 2) {
            // Glow layer
            L.polyline(tourLatLngs, {
                color: '#10B981',
                weight: 9,
                opacity: 0.35,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(tourRouteLayer);

            // Crisp dashed line
            L.polyline(tourLatLngs, {
                color: '#047857',
                weight: 4,
                opacity: 0.95,
                dashArray: '8, 8',
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(tourRouteLayer);
        }
    }

    // Switch between all attractions view and tour route view
    var btnModeTour = document.getElementById('btnModeTour');
    var btnModeAll = document.getElementById('btnModeAll');
    var tourStatsBar = document.getElementById('tourStatsBar');
    var tourTimelineDrawer = document.getElementById('tourTimelineDrawer');
    var currentMode = 'all';

    function setMode(mode) {
        currentMode = mode;
        if (mode === 'tour' && tourStops.length > 0) {
            if (map.hasLayer(allMarkersLayer)) map.removeLayer(allMarkersLayer);
            if (!map.hasLayer(tourRouteLayer)) map.addLayer(tourRouteLayer);

            if (btnModeTour) btnModeTour.classList.add('active');
            if (btnModeAll) btnModeAll.classList.remove('active');
            if (tourStatsBar) tourStatsBar.style.display = 'flex';
            if (tourTimelineDrawer) tourTimelineDrawer.style.display = 'block';

            fitTourRoute();
        } else {
            if (map.hasLayer(tourRouteLayer)) map.removeLayer(tourRouteLayer);
            if (!map.hasLayer(allMarkersLayer)) map.addLayer(allMarkersLayer);

            if (btnModeTour) btnModeTour.classList.remove('active');
            if (btnModeAll) btnModeAll.classList.add('active');
            if (tourStatsBar) tourStatsBar.style.display = 'none';
            if (tourTimelineDrawer) tourTimelineDrawer.style.display = 'none';

            map.setView([dorawakaLat, dorawakaLng], 12);
        }
    }

    function fitTourRoute() {
        if (tourLatLngs.length > 1) {
            map.fitBounds(L.latLngBounds(tourLatLngs), { padding: [50, 50], maxZoom: 14 });
        } else if (tourLatLngs.length === 1) {
            map.setView(tourLatLngs[0], 14);
        }
    }

    if (btnModeTour) {
        btnModeTour.addEventListener('click', function() { setMode('tour'); });
    }
    if (btnModeAll) {
        btnModeAll.addEventListener('click', function() { setMode('all'); });
    }

    var btnFit = document.getElementById('btnFitMapBounds');
    if (btnFit) {
        btnFit.addEventListener('click', function() {
            if (currentMode === 'tour') {
                fitTourRoute();
            } else {
                map.setView([dorawakaLat, dorawakaLng], 12);
            }
        });
    }

    // Set initial mode
    if (initialMode === 'tour' && tourStops.length > 0) {
        setMode('tour');
    }

    // Pan and zoom map to a specific stop when timeline card is clicked
    window.focusTourStop = function(stopIndex) {
        if (tourMarkers[stopIndex]) {
            var marker = tourMarkers[stopIndex];
            var latLng = marker.getLatLng();

            // Highlight timeline card
            document.querySelectorAll('.tour-timeline-step-card').forEach(function(c) {
                c.classList.remove('active');
            });
            var activeCard = document.getElementById('timeline-card-' + stopIndex);
            if (activeCard) {
                activeCard.classList.add('active');
                activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }

            // Fly camera smoothly to stop
            map.flyTo(latLng, 15, { animate: true, duration: 1 });
            setTimeout(function() {
                marker.openPopup();
            }, 800);
        }
    };

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
// Page footer
include_once __DIR__ . '/includes/footer.php';
?>
