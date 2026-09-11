<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * One-Day Itinerary Planner (plan.php)
 * 
 * Requirement Coverage:
 * - FR-IT-01: Allow users to select attractions for an itinerary
 * - FR-IT-02: Allow multiple attractions to be added to an itinerary
 * - FR-IT-03: Display selected attractions in the itinerary
 * - FR-IT-04: Allow users to remove attractions from the itinerary
 * - FR-IT-05: Allow users to arrange selected attractions (reorder up/down)
 * - FR-IT-06: Provide a simple one-day visit plan
 * - FR-IT-07: Prevent duplicate attraction entries
 * - FR-IT-08: Notify user when no attraction has been selected
 * - BSR-12: Same attraction shall not be added more than once
 * 
 * Security & Best Practices:
 * - Input validation & intval() sanitization
 * - Database attraction existence verification
 * - Prepared SQL queries
 * - Output escaping with htmlspecialchars()
 * ===================================================================
 */

// 1. PAGE SETUP & DATABASE CONNECTION
require_once __DIR__ . '/includes/config.php';

// Ensure Session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize itinerary session array if not exists (FR-IT-01, FR-IT-02)
if (!isset($_SESSION['itinerary']) || !is_array($_SESSION['itinerary'])) {
    $_SESSION['itinerary'] = array();
}

// -------------------------------------------------------------------
// 2. ITINERARY ACTIONS HANDLER (Add, Remove, Move Up, Move Down, Clear)
// -------------------------------------------------------------------

// ACTION A: Add Attraction to Itinerary (FR-IT-01, FR-IT-02, FR-IT-07, BSR-12)
if (isset($_GET['add'])) {
    $attractionId = intval($_GET['add']);
    
    if ($attractionId > 0) {
        // Validate if attraction actually exists in database (Security Requirement 2)
        $checkStmt = $conn->prepare("SELECT attraction_id, name FROM attraction WHERE attraction_id = ?");
        $checkStmt->bind_param("i", $attractionId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $attRow = $checkResult->fetch_assoc();
            
            // Check for duplicate entry (FR-IT-07, BSR-12)
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
    
    // Redirect to prevent form resubmission / duplicate GET actions
    header("Location: plan.php");
    exit();
}

// ACTION B: Remove Attraction from Itinerary (FR-IT-04)
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

// ACTION C: Move Up in Itinerary (FR-IT-05 - Reorder Logic)
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

// ACTION D: Move Down in Itinerary (FR-IT-05 - Reorder Logic)
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

// ACTION E: Clear All Attractions
if (isset($_GET['clear'])) {
    $_SESSION['itinerary'] = array();
    $_SESSION['itinerary_msg'] = [
        'type' => 'info',
        'text' => 'Your itinerary has been cleared.'
    ];
    
    header("Location: plan.php");
    exit();
}

// ACTION F: Save Itinerary to Logged-In User Account
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

// ACTION G: Load Saved Itinerary from Database into Session
if (isset($_GET['load_saved'])) {
    $savedId = intval($_GET['load_saved']);
    if ($savedId > 0 && isLoggedIn()) {
        $userId = (int)$_SESSION['admin_id'];
        
        // Security check: ensure this itinerary belongs to logged in user (or admin)
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

// ACTION H: Delete Saved Itinerary
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

// -------------------------------------------------------------------
// 3. FETCH ATTRACTIONS IN SESSION ORDER (FR-IT-03)
// -------------------------------------------------------------------
$itineraryAttractions = array();

if (!empty($_SESSION['itinerary'])) {
    // Sanitize integer IDs for SQL IN clause
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
        
        // Build final list matching exact session order, filtering out any missing items
        foreach ($_SESSION['itinerary'] as $sId) {
            if (isset($fetchedItems[$sId])) {
                $itineraryAttractions[] = $fetchedItems[$sId];
            }
        }
    }
}

// Define Page Title
$pageTitle = "One-Day Itinerary Planner - NearTrip Warakapola";

// Include Navigation Header
include_once __DIR__ . '/includes/header.php';
?>

<!-- ===================================================================
     PAGE HERO BANNER
     =================================================================== -->
<section class="page-hero">
    <div>
        <h1 class="page-hero-title"><i class="fas fa-route"></i> One-Day Itinerary Planner</h1>
        <p class="page-hero-subtext">Organize and customize your perfect day trip around Dorawaka & Warakapola step-by-step.</p>
    </div>
</section>

<!-- ===================================================================
     MAIN ITINERARY SECTION
     =================================================================== -->
<main class="why-choose-us" style="padding: 60px 20px;">
    <div class="section-container" style="max-width: 1200px;">

        <!-- Session Flash Message Notification -->
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
            <!-- ===============================================================
                 EMPTY ITINERARY STATE (FR-IT-08)
                 =============================================================== -->
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
            <!-- ===============================================================
                 ACTIVE ITINERARY LIST & CONTROLS (FR-IT-03, FR-IT-04, FR-IT-05)
                 =============================================================== -->
            
            <div class="itinerary-header-actions">
                <div class="itinerary-count-badge">
                    <i class="fas fa-list-check"></i>
                    <span><strong><?php echo count($itineraryAttractions); ?></strong> Attraction<?php echo (count($itineraryAttractions) > 1) ? 's' : ''; ?> Selected</span>
                </div>
                <div class="itinerary-top-btns">
                    <a href="places.php" class="btn-action-outline">
                        <i class="fas fa-plus"></i> Add More Places
                    </a>
                    <a href="plan.php?clear=1" class="btn-action-danger" onclick="return confirm('Are you sure you want to clear all attractions from your itinerary?');">
                        <i class="fas fa-trash-can"></i> Clear All
                    </a>
                </div>
            </div>

            <div class="itinerary-grid-wrapper">
                
                <!-- LEFT/MAIN COLUMN: REORDERABLE CARDS -->
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

                            <!-- Action Controls (Move Up, Move Down, Remove) -->
                            <div class="itinerary-card-controls">
                                <!-- Move Up Button (FR-IT-05) -->
                                <?php if ($index > 0): ?>
                                    <a href="plan.php?up=<?php echo $index; ?>" class="btn-reorder btn-up" title="Move Up in sequence">
                                        <i class="fas fa-arrow-up"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-reorder btn-disabled" title="First item (Cannot move up)">
                                        <i class="fas fa-arrow-up"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Move Down Button (FR-IT-05) -->
                                <?php if ($index < $totalCount - 1): ?>
                                    <a href="plan.php?down=<?php echo $index; ?>" class="btn-reorder btn-down" title="Move Down in sequence">
                                        <i class="fas fa-arrow-down"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-reorder btn-disabled" title="Last item (Cannot move down)">
                                        <i class="fas fa-arrow-down"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Remove Button (FR-IT-04) -->
                                <a href="plan.php?remove=<?php echo $item['attraction_id']; ?>" class="btn-remove-item" title="Remove from itinerary" onclick="return confirm('Remove this attraction from your itinerary?');">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- RIGHT COLUMN: STRUCTURED ONE-DAY VISIT PLAN DISPLAY (FR-IT-06) -->
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
                                    <span>Estimated Duration:</span>
                                    <strong>Full Day Visit</strong>
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

        <?php endif; ?>

    </div>
</main>

<?php
// Include Footer Component
include_once __DIR__ . '/includes/footer.php';
?>
