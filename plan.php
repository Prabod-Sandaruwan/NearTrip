<?php
/**
 * One-day itinerary planner.
 * Allows users to add, reorder, and remove attractions for their day trip,
 * calculate route distances, and preview the tour route on a map.
 */

// Load database configuration and helpers
require_once __DIR__ . '/includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize empty itinerary session list if not set
if (!isset($_SESSION['itinerary']) || !is_array($_SESSION['itinerary'])) {
    $_SESSION['itinerary'] = array();
}

// Handle itinerary actions: add, remove, reorder, clear, save, and load

// Add attraction to itinerary
if (isset($_GET['add'])) {
    $attractionId = intval($_GET['add']);
    
    if ($attractionId > 0) {
        // Check if attraction exists in database
        $checkStmt = $conn->prepare("SELECT attraction_id, name FROM attraction WHERE attraction_id = ?");
        $checkStmt->bind_param("i", $attractionId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $attRow = $checkResult->fetch_assoc();
            
            // Avoid adding the same attraction twice
            if (!in_array($attractionId, $_SESSION['itinerary'])) {
                $_SESSION['itinerary'][] = $attractionId;
                $_SESSION['itinerary_msg'] = [
                    'type' => 'success',
                    'text' => '"' . htmlspecialchars($attRow['name']) . '" has been added to your itinerary!'
                ];
            } else {
                $_SESSION['itinerary_msg'] = [
                    'type' => 'warning',
                    'text' => 'This attraction is already in your itinerary!'
                ];
            }
        } else {
            $_SESSION['itinerary_msg'] = [
                'type' => 'danger',
                'text' => 'Selected attraction does not exist.'
            ];
        }
        $checkStmt->close();
    }
    
    // Refresh page after adding
    header("Location: plan.php");
    exit();
}

// Remove attraction from itinerary
if (isset($_GET['remove'])) {
    $attractionId = intval($_GET['remove']);
    
    if (in_array($attractionId, $_SESSION['itinerary'])) {
        // Remove item and re-index array
        $_SESSION['itinerary'] = array_values(array_diff($_SESSION['itinerary'], array($attractionId)));
        $_SESSION['itinerary_msg'] = [
            'type' => 'info',
            'text' => 'Attraction removed from your itinerary.'
        ];
    }
    
    header("Location: plan.php");
    exit();
}

// Move attraction up in the visit sequence
if (isset($_GET['up'])) {
    $index = intval($_GET['up']);
    
    // Check if valid index and not already at top (index > 0)
    if ($index > 0 && $index < count($_SESSION['itinerary'])) {
        $temp = $_SESSION['itinerary'][$index];
        $_SESSION['itinerary'][$index] = $_SESSION['itinerary'][$index - 1];
        $_SESSION['itinerary'][$index - 1] = $temp;
        
        $_SESSION['itinerary_msg'] = [
            'type' => 'success',
            'text' => 'Itinerary order updated.'
        ];
    }
    
    header("Location: plan.php");
    exit();
}

// Move attraction down in the visit sequence
if (isset($_GET['down'])) {
    $index = intval($_GET['down']);
    
    // Check if valid index and not already at bottom
    if ($index >= 0 && $index < count($_SESSION['itinerary']) - 1) {
        $temp = $_SESSION['itinerary'][$index];
        $_SESSION['itinerary'][$index] = $_SESSION['itinerary'][$index + 1];
        $_SESSION['itinerary'][$index + 1] = $temp;
        
        $_SESSION['itinerary_msg'] = [
            'type' => 'success',
            'text' => 'Itinerary order updated.'
        ];
    }
    
    header("Location: plan.php");
    exit();
}

// Clear all attractions from the itinerary
if (isset($_GET['clear'])) {
    $_SESSION['itinerary'] = array();
    $_SESSION['itinerary_msg'] = [
        'type' => 'info',
        'text' => 'Your itinerary has been cleared.'
    ];
    
    header("Location: plan.php");
    exit();
}

// Save itinerary to logged-in user account
if (isset($_POST['save_itinerary']) || isset($_GET['save_tour'])) {
    if (!isLoggedIn()) {
        $_SESSION['itinerary_msg'] = [
            'type' => 'warning',
            'text' => 'Please log in to your account to save your itinerary.'
        ];
        header("Location: admin/login.php");
        exit();
    }
    
    if (empty($_SESSION['itinerary'])) {
        $_SESSION['itinerary_msg'] = [
            'type' => 'warning',
            'text' => 'Cannot save an empty itinerary. Please add attractions first.'
        ];
        header("Location: plan.php");
        exit();
    }
    
    $userId = (int)$_SESSION['admin_id'];
    $customName = isset($_POST['itinerary_name']) ? trim($_POST['itinerary_name']) : '';
    if (empty($customName)) {
        $customName = "One-Day Trip (" . count($_SESSION['itinerary']) . " Places)";
    }
    $description = "Saved on " . date('M d, Y - h:i A');
    
    // Insert into `itinerary` table
    $stmt = $conn->prepare("INSERT INTO `itinerary` (`admin_id`, `name`, `description`) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $userId, $customName, $description);
        if ($stmt->execute()) {
            $newItineraryId = $stmt->insert_id;
            $stmt->close();
            
            // Insert sequence into `itinerary_attraction`
            $stmtItem = $conn->prepare("INSERT INTO `itinerary_attraction` (`itinerary_id`, `attraction_id`, `day_number`) VALUES (?, ?, ?)");
            if ($stmtItem) {
                $seq = 1;
                foreach ($_SESSION['itinerary'] as $attId) {
                    $attIdInt = (int)$attId;
                    $stmtItem->bind_param("iii", $newItineraryId, $attIdInt, $seq);
                    $stmtItem->execute();
                    $seq++;
                }
                $stmtItem->close();
            }
            
            $_SESSION['itinerary_msg'] = [
                'type' => 'success',
                'text' => 'Your tour "' . htmlspecialchars($customName) . '" has been saved to your account!'
            ];
        } else {
            $_SESSION['itinerary_msg'] = [
                'type' => 'danger',
                'text' => 'Error saving tour to database.'
            ];
        }
    }
    
    header("Location: plan.php");
    exit();
}

// Load saved itinerary from database into session
if (isset($_GET['load_saved'])) {
    $savedId = intval($_GET['load_saved']);
    if ($savedId > 0 && isLoggedIn()) {
        $userId = (int)$_SESSION['admin_id'];
        
        // Ensure this itinerary belongs to the logged-in user or admin
        $checkSql = isAdmin() 
            ? "SELECT * FROM `itinerary` WHERE `itinerary_id` = ?"
            : "SELECT * FROM `itinerary` WHERE `itinerary_id` = ? AND `admin_id` = ?";
            
        $checkStmt = $conn->prepare($checkSql);
        if (isAdmin()) {
            $checkStmt->bind_param("i", $savedId);
        } else {
            $checkStmt->bind_param("ii", $savedId, $userId);
        }
        $checkStmt->execute();
        $resCheck = $checkStmt->get_result();
        
        if ($resCheck && $resCheck->num_rows > 0) {
            $savedTour = $resCheck->fetch_assoc();
            
            // Fetch items in order
            $itemStmt = $conn->prepare("SELECT `attraction_id` FROM `itinerary_attraction` WHERE `itinerary_id` = ? ORDER BY `day_number` ASC");
            $itemStmt->bind_param("i", $savedId);
            $itemStmt->execute();
            $itemRes = $itemStmt->get_result();
            
            $newSessionItinerary = array();
            while ($iRow = $itemRes->fetch_assoc()) {
                $newSessionItinerary[] = (int)$iRow['attraction_id'];
            }
            $itemStmt->close();
            
            $_SESSION['itinerary'] = $newSessionItinerary;
            $_SESSION['itinerary_msg'] = [
                'type' => 'success',
                'text' => 'Saved tour "' . htmlspecialchars($savedTour['name']) . '" loaded into your itinerary!'
            ];
        }
        $checkStmt->close();
    }
    header("Location: plan.php");
    exit();
}

// Delete saved itinerary from database
if (isset($_GET['delete_saved'])) {
    $savedId = intval($_GET['delete_saved']);
    if ($savedId > 0 && isLoggedIn()) {
        $userId = (int)$_SESSION['admin_id'];
        
        // Verify ownership
        $checkSql = isAdmin() 
            ? "SELECT `itinerary_id` FROM `itinerary` WHERE `itinerary_id` = ?"
            : "SELECT `itinerary_id` FROM `itinerary` WHERE `itinerary_id` = ? AND `admin_id` = ?";
            
        $checkStmt = $conn->prepare($checkSql);
        if (isAdmin()) {
            $checkStmt->bind_param("i", $savedId);
        } else {
            $checkStmt->bind_param("ii", $savedId, $userId);
        }
        $checkStmt->execute();
        $resCheck = $checkStmt->get_result();
        
        if ($resCheck && $resCheck->num_rows > 0) {
            // Delete child rows first
            $del1 = $conn->prepare("DELETE FROM `itinerary_attraction` WHERE `itinerary_id` = ?");
            $del1->bind_param("i", $savedId);
            $del1->execute();
            $del1->close();
            
            // Delete parent row
            $del2 = $conn->prepare("DELETE FROM `itinerary` WHERE `itinerary_id` = ?");
            $del2->bind_param("i", $savedId);
            $del2->execute();
            $del2->close();
            
            $_SESSION['itinerary_msg'] = [
                'type' => 'info',
                'text' => 'Saved itinerary deleted.'
            ];
        }
        $checkStmt->close();
    }
    
    header("Location: plan.php");
    exit();
}

// Fetch attractions in the exact order stored in the session
$itineraryAttractions = array();

if (!empty($_SESSION['itinerary'])) {
    // Sanitize integer IDs for SQL query
    $cleanIds = array_map('intval', $_SESSION['itinerary']);
    $implodedIds = implode(',', $cleanIds);
    
    // Query with ORDER BY FIELD to maintain precise session array order
    $sql = "SELECT a.*, c.category_name 
            FROM `attraction` a 
            LEFT JOIN `category` c ON a.category_id = c.category_id 
            WHERE a.`attraction_id` IN ($implodedIds) 
            ORDER BY FIELD(a.`attraction_id`, $implodedIds)";
            
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $fetchedItems = array();
        while ($row = $result->fetch_assoc()) {
            $fetchedItems[$row['attraction_id']] = $row;
        }
        
        // Build list matching session order
        foreach ($_SESSION['itinerary'] as $sId) {
            if (isset($fetchedItems[$sId])) {
                $itineraryAttractions[] = $fetchedItems[$sId];
            }
        }
    }
}

// Prepare tour stops and calculate distances and drive times
$tourMapStops = array();
$totalStraightDistance = 0.0;
$prevLat = null;
$prevLng = null;
$gmapsWaypoints = array();

if (!empty($itineraryAttractions)) {
    foreach ($itineraryAttractions as $idx => $item) {
        $lat = !empty($item['latitude']) ? (float)$item['latitude'] : 0.0;
        $lng = !empty($item['longitude']) ? (float)$item['longitude'] : 0.0;
        
        if ($lat != 0.0 && $lng != 0.0) {
            $rawImg = !empty($item['image_url']) ? $item['image_url'] : 'assets/hero.webp';
            $cleanImg = ltrim($rawImg, '/');
            $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
            
            $stepDistFromPrev = 0.0;
            if ($prevLat !== null && $prevLng !== null) {
                // Calculate distance between consecutive stops using Haversine formula
                $earthRadius = 6371; // km
                $dLat = deg2rad($lat - $prevLat);
                $dLon = deg2rad($lng - $prevLng);
                $a = sin($dLat / 2) * sin($dLat / 2) +
                     cos(deg2rad($prevLat)) * cos(deg2rad($lat)) *
                     sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                // Add winding factor of ~1.25 for local road conditions
                $stepDistFromPrev = round($earthRadius * $c * 1.25, 1);
                $totalStraightDistance += $stepDistFromPrev;
            }
            $prevLat = $lat;
            $prevLng = $lng;

            $tourMapStops[] = [
                'id' => (int)$item['attraction_id'],
                'step' => $idx + 1,
                'name' => $item['name'],
                'lat' => $lat,
                'lng' => $lng,
                'location' => !empty($item['location']) ? $item['location'] : 'Warakapola Zone',
                'category' => !empty($item['category_name']) ? $item['category_name'] : 'Attraction',
                'hours' => !empty($item['opening_hours']) ? $item['opening_hours'] : 'Regular Hours',
                'fee' => !empty($item['entrance_fee']) ? $item['entrance_fee'] : 'Free / Standard',
                'image' => $imagePath,
                'dist_from_prev' => $stepDistFromPrev
            ];
            $gmapsWaypoints[] = $lat . ',' . $lng;
        }
    }
}

$totalRouteDistanceKm = round($totalStraightDistance, 1);
$totalTravelMinutes = max(15, round(($totalRouteDistanceKm / 28) * 60));
if ($totalTravelMinutes >= 60) {
    $hoursPart = floor($totalTravelMinutes / 60);
    $minsPart = $totalTravelMinutes % 60;
    $totalRouteTimeString = $hoursPart . " hr " . ($minsPart > 0 ? $minsPart . " mins" : "");
} else {
    $totalRouteTimeString = $totalTravelMinutes . " mins";
}

// Build Google Maps navigation link
$googleMapsUrl = "";
if (count($gmapsWaypoints) >= 2) {
    $origin = $gmapsWaypoints[0];
    $dest = $gmapsWaypoints[count($gmapsWaypoints) - 1];
    $midPoints = array_slice($gmapsWaypoints, 1, count($gmapsWaypoints) - 2);
    $googleMapsUrl = "https://www.google.com/maps/dir/?api=1&origin=" . $origin . "&destination=" . $dest;
    if (!empty($midPoints)) {
        $googleMapsUrl .= "&waypoints=" . implode('|', $midPoints);
    }
} elseif (count($gmapsWaypoints) === 1) {
    $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query=" . $gmapsWaypoints[0];
}

// Load Leaflet map stylesheet
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />';

// Page title
$pageTitle = "One-Day Itinerary Planner - NearTrip Warakapola";

// Navigation header
include_once __DIR__ . '/includes/header.php';
?>

<!-- Hero banner -->
<section class="page-hero">
    <div>
        <h1 class="page-hero-title"><i class="fas fa-route"></i> One-Day Itinerary Planner</h1>
        <p class="page-hero-subtext">Organize and customize your perfect day trip around Dorawaka & Warakapola step-by-step.</p>
    </div>
</section>

<!-- Main itinerary section -->
<main class="why-choose-us" style="padding: 60px 20px;">
    <div class="section-container" style="max-width: 1200px;">

        <!-- Status message -->
        <?php if (isset($_SESSION['itinerary_msg'])): ?>
            <div class="itinerary-alert itinerary-alert-<?php echo htmlspecialchars($_SESSION['itinerary_msg']['type']); ?>">
                <i class="fas <?php 
                    echo ($_SESSION['itinerary_msg']['type'] === 'success') ? 'fa-circle-check' : 
                        (($_SESSION['itinerary_msg']['type'] === 'warning') ? 'fa-triangle-exclamation' : 'fa-circle-info'); 
                ?>"></i>
                <span><?php echo htmlspecialchars($_SESSION['itinerary_msg']['text']); ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
            <?php unset($_SESSION['itinerary_msg']); ?>
        <?php endif; ?>

        <?php if (empty($itineraryAttractions)): ?>
            <!-- Shown when itinerary is empty -->
            <div class="itinerary-empty-card">
                <div class="empty-icon-wrapper">
                    <i class="fas fa-map-location-dot"></i>
                </div>
                <h2>No attractions selected yet</h2>
                <p>Browse beautiful places around Dorawaka & Warakapola and add them to your custom day trip itinerary!</p>
                <div class="empty-actions">
                    <a href="places.php" class="btn-primary-lg">
                        <i class="fas fa-compass"></i> Browse Destinations
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Selected attractions list and controls -->
            
            <div class="itinerary-header-actions">
                <div class="itinerary-count-badge">
                    <i class="fas fa-list-check"></i>
                    <span><strong><?php echo count($itineraryAttractions); ?></strong> Attraction<?php echo (count($itineraryAttractions) > 1) ? 's' : ''; ?> Selected</span>
                </div>
                <div class="itinerary-top-btns">
                    <?php if (!empty($tourMapStops)): ?>
                        <a href="#plannerTourMapSection" class="btn-action-map-jump" onclick="scrollToTourMap(); return false;" title="Quick scroll down to the tour route map">
                            <i class="fas fa-map-location-dot"></i> View on Map <i class="fas fa-arrow-down"></i>
                        </a>
                    <?php endif; ?>
                    <a href="places.php" class="btn-action-outline">
                        <i class="fas fa-plus"></i> Add More Places
                    </a>
                    <a href="plan.php?clear=1" class="btn-action-danger" onclick="return confirm('Are you sure you want to clear all attractions from your itinerary?');">
                        <i class="fas fa-trash-can"></i> Clear All
                    </a>
                </div>
            </div>

            <div class="itinerary-grid-wrapper">
                
                <!-- List of itinerary stops -->
                <div class="itinerary-cards-list">
                    <h3 class="itinerary-section-title">Selected Stop Sequence</h3>
                    
                    <?php 
                    $totalCount = count($itineraryAttractions);
                    foreach ($itineraryAttractions as $index => $item): 
                        // Resolve Image
                        $rawImg = !empty($item['image_url']) ? $item['image_url'] : 'assets/hero.webp';
                        $cleanImg = ltrim($rawImg, '/');
                        $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
                    ?>
                        <div class="itinerary-item-card">
                            
                            <!-- Step Number Circle -->
                            <div class="step-number-circle">
                                <?php echo ($index + 1); ?>
                            </div>

                            <!-- Card Image -->
                            <div class="itinerary-card-img-wrapper">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>

                            <!-- Card Info Details -->
                            <div class="itinerary-card-content">
                                <div class="itinerary-card-header">
                                    <h4 class="itinerary-title">
                                        <a href="details.php?id=<?php echo $item['attraction_id']; ?>">
                                             <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </h4>
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="itinerary-cat-badge">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['location'])): ?>
                                    <div class="itinerary-detail-row">
                                        <i class="fas fa-location-dot"></i>
                                        <span><?php echo htmlspecialchars($item['location']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="itinerary-meta-grid">
                                    <?php if (!empty($item['opening_hours'])): ?>
                                        <span class="itinerary-meta-item">
                                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($item['opening_hours']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($item['entrance_fee'])): ?>
                                        <span class="itinerary-meta-item">
                                            <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($item['entrance_fee']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stop actions: locate on map, reorder up or down, remove -->
                            <div class="itinerary-card-controls">
                                <!-- Locate on Map Button -->
                                <button type="button" class="btn-locate-card" onclick="focusPlannerStop(<?php echo $index; ?>);" title="Locate stop #<?php echo ($index + 1); ?> on the map above">
                                    <i class="fas fa-location-crosshairs"></i> View on Map
                                </button>

                                <!-- Move up -->
                                <?php if ($index > 0): ?>
                                    <a href="plan.php?up=<?php echo $index; ?>" class="btn-reorder btn-up" title="Move Up in sequence">
                                        <i class="fas fa-arrow-up"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-reorder btn-disabled" title="First item (Cannot move up)">
                                        <i class="fas fa-arrow-up"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Move down -->
                                <?php if ($index < $totalCount - 1): ?>
                                    <a href="plan.php?down=<?php echo $index; ?>" class="btn-reorder btn-down" title="Move Down in sequence">
                                        <i class="fas fa-arrow-down"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-reorder btn-disabled" title="Last item (Cannot move down)">
                                        <i class="fas fa-arrow-down"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Remove stop -->
                                <a href="plan.php?remove=<?php echo $item['attraction_id']; ?>" class="btn-remove-item" title="Remove from itinerary" onclick="return confirm('Remove this attraction from your itinerary?');">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- Sidebar summary of the visit plan -->
                <div class="itinerary-summary-sidebar">
                    
                    <div class="visit-plan-card">
                        <div class="visit-plan-header">
                            <h3><i class="fas fa-clipboard-list"></i> Your One-Day Visit Plan</h3>
                            <span class="plan-subtitle">Recommended Visit Overview</span>
                        </div>
                        
                        <div class="visit-plan-body">
                            <ul class="visit-plan-list">
                                <?php foreach ($itineraryAttractions as $idx => $att): ?>
                                    <li class="visit-plan-item">
                                        <div class="plan-step-num"><?php echo ($idx + 1); ?></div>
                                        <div class="plan-step-details">
                                            <strong><?php echo htmlspecialchars($att['name']); ?></strong>
                                            
                                             <?php if (!empty($att['opening_hours'])): ?>
                                                <div class="plan-sub-detail">
                                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($att['opening_hours']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($att['location'])): ?>
                                                <div class="plan-sub-detail">
                                                    <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($att['location']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="visit-plan-summary">
                                <div class="summary-line">
                                    <span>Total Attractions:</span>
                                    <strong><?php echo count($itineraryAttractions); ?> Places</strong>
                                </div>
                                <div class="summary-line">
                                    <span>Estimated Route Distance:</span>
                                    <strong>~<?php echo $totalRouteDistanceKm; ?> km</strong>
                                </div>
                                <div class="summary-line">
                                    <span>Estimated Drive Time:</span>
                                    <strong>~<?php echo $totalRouteTimeString; ?></strong>
                                </div>
                            </div>

                            <div class="visit-plan-actions">
                                <?php if (isLoggedIn()): ?>
                                    <form action="plan.php" method="POST" class="save-itinerary-form">
                                        <input type="hidden" name="save_itinerary" value="1">
                                        <div class="save-input-group">
                                            <input type="text" name="itinerary_name" placeholder="Tour Name (e.g. My Warakapola Trip)..." class="save-title-input">
                                            <button type="submit" class="btn-save-account">
                                                <i class="fas fa-bookmark"></i> Save Tour to Account
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <a href="admin/login.php" class="btn-login-to-save" title="Login to save tour to your account">
                                        <i class="fas fa-right-to-bracket"></i> Login to Save Tour
                                    </a>
                                <?php endif; ?>

                                <a href="places.php" class="btn-add-more">
                                    <i class="fas fa-map-location-dot"></i> Add More Places
                                </a>
                                <a href="plan.php?clear=1" class="btn-clear-link" onclick="return confirm('Are you sure you want to clear your itinerary?');">
                                    <i class="fas fa-trash"></i> Clear All
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Interactive tour route map -->
            <?php if (!empty($tourMapStops)): ?>
                <div id="plannerTourMapSection" class="planner-map-card" style="margin-top: 36px; scroll-margin-top: 90px;">
                    <div class="planner-map-header">
                        <div class="planner-map-title-group">
                            <h3><i class="fas fa-map-location-dot"></i> Interactive Tour Route & Stop Preview</h3>
                            <p class="planner-map-subtitle">Visual itinerary map connecting your selected stops in sequential order (1 &rarr; <?php echo count($tourMapStops); ?>).</p>
                        </div>
                        
                        <div class="route-action-buttons">
                            <button type="button" id="btnFitPlannerMap" class="btn-route-action" title="Fit map view to all tour stops">
                                <i class="fas fa-expand"></i> Fit Route
                            </button>
                            <a href="map.php?tour=active" class="btn-route-action btn-primary-action" title="Open in fullscreen interactive map mode">
                                <i class="fas fa-up-right-from-square"></i> Fullscreen Map Mode
                            </a>
                            <?php if (!empty($googleMapsUrl)): ?>
                                <a href="<?php echo htmlspecialchars($googleMapsUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-route-action btn-gmaps" title="Open multi-stop driving directions in Google Maps">
                                    <i class="fab fa-google"></i> Google Maps Navigation
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Route Statistics Pill Bar -->
                    <div class="route-stats-pill-bar">
                        <div class="route-stat-item">
                            <i class="fas fa-location-dot"></i>
                            <span>Stops: <strong><?php echo count($itineraryAttractions); ?> Places</strong></span>
                        </div>
                        <div class="route-stat-divider"></div>
                        <div class="route-stat-item">
                            <i class="fas fa-road"></i>
                            <span>Estimated Route Distance: <strong>~<?php echo $totalRouteDistanceKm; ?> km</strong></span>
                        </div>
                        <div class="route-stat-divider"></div>
                        <div class="route-stat-item">
                            <i class="fas fa-car-side"></i>
                            <span>Estimated Travel Time: <strong>~<?php echo $totalRouteTimeString; ?></strong></span>
                        </div>
                        <div class="route-stat-divider"></div>
                        <div class="route-stat-item" style="color: #059669;">
                            <i class="fas fa-circle-check"></i>
                            <span><strong>Sequential 1-Day Route Ready</strong></span>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div id="plannerTourMap" class="planner-tour-map-box"></div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php if (!empty($tourMapStops)): ?>
<!-- Tour route map initialization script -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var mapEl = document.getElementById('plannerTourMap');
    if (!mapEl) return;

    var stopsData = <?php echo json_encode($tourMapStops, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    if (!stopsData || stopsData.length === 0) return;

    // Initialize map centered on the first stop or Dorawaka
    var defaultLat = stopsData[0].lat || 7.1592;
    var defaultLng = stopsData[0].lng || 80.2020;
    var tourMap = L.map('plannerTourMap').setView([defaultLat, defaultLng], 12);

    // Add map tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(tourMap);

    var markers = [];
    var latlngs = [];

    stopsData.forEach(function(stop, idx) {
        var isStart = (idx === 0);
        var isFinish = (idx === stopsData.length - 1 && stopsData.length > 1);

        var pinClass = 'tour-pin-container';
        if (isStart) pinClass += ' tour-pin-start';
        else if (isFinish) pinClass += ' tour-pin-finish';

        var pulseHtml = isStart ? '<div class="tour-pin-pulse"></div>' : '';

        // Create numbered pin marker icon
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

        var marker = L.marker([stop.lat, stop.lng], { icon: customIcon }).addTo(tourMap);
        latlngs.push([stop.lat, stop.lng]);

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
        markers.push(marker);
    });

    // Draw route line connecting all stops
    if (latlngs.length >= 2) {
        // Glowing background glow line
        L.polyline(latlngs, {
            color: '#10B981',
            weight: 8,
            opacity: 0.35,
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(tourMap);

        // Crisp dashed route line
        L.polyline(latlngs, {
            color: '#047857',
            weight: 4,
            opacity: 0.9,
            dashArray: '8, 8',
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(tourMap);
    }

    // Adjust map view to fit all stops
    function fitAllStops() {
        if (latlngs.length > 1) {
            tourMap.fitBounds(L.latLngBounds(latlngs), { padding: [50, 50], maxZoom: 14 });
        } else if (latlngs.length === 1) {
            tourMap.setView(latlngs[0], 13);
        }
    }
    fitAllStops();

    // Button to reset view to fit all stops
    var btnFit = document.getElementById('btnFitPlannerMap');
    if (btnFit) {
        btnFit.addEventListener('click', function() {
            fitAllStops();
        });
    }

    // Scroll down to the tour route map
    window.scrollToTourMap = function() {
        var el = document.getElementById('plannerTourMapSection');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.style.transition = 'box-shadow 0.3s ease';
            el.style.boxShadow = '0 0 0 4px rgba(45, 125, 70, 0.4), 0 8px 24px rgba(0,0,0,0.12)';
            setTimeout(function() {
                el.style.boxShadow = '';
            }, 1200);
        }
    };

    // Pan and zoom map to a specific stop
    window.focusPlannerStop = function(stopIndex) {
        if (markers[stopIndex]) {
            var targetMarker = markers[stopIndex];
            var targetLatLng = targetMarker.getLatLng();
            
            // Scroll smoothly to map
            mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            setTimeout(function() {
                tourMap.flyTo(targetLatLng, 14, { animate: true, duration: 1 });
                setTimeout(function() {
                    targetMarker.openPopup();
                }, 800);
            }, 300);
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
<?php endif; ?>

<?php
// Page footer
include_once __DIR__ . '/includes/footer.php';
?>
