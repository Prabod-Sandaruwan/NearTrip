<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Edit Attraction Page (admin/edit_attraction.php)
 * 
 * Requirement Coverage:
 * - FR-ADM-03, FR-MGT-02, FR-MGT-03, FR-MGT-04: Edit & update attraction record
 * - SE-05: Server-side validation & prepared statement UPDATE
 * - SE-11: Admin role session security guard
 * ===================================================================
 */

// Step 1: Include Database Config & Auth Helper
require_once __DIR__ . '/../includes/config.php';

// Step 2: Enforce Admin Security Guard (SE-11)
requireAdmin();

// Capture Attraction ID from GET or POST
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    $_SESSION['message'] = "Invalid Attraction ID specified.";
    $_SESSION['message_type'] = "error";
    header("Location: attractions.php");
    exit();
}

// -------------------------------------------------------------------
// Step 3: FETCH EXISTING ATTRACTION DETAILS FROM DATABASE
// -------------------------------------------------------------------
$fetchSql = "SELECT * FROM `attraction` WHERE `attraction_id` = ? LIMIT 1";
$fetchStmt = $conn->prepare($fetchSql);
$attraction = null;

if ($fetchStmt) {
    $fetchStmt->bind_param("i", $id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    if ($result && $result->num_rows === 1) {
        $attraction = $result->fetch_assoc();
    }
    $fetchStmt->close();
}

if (!$attraction) {
    $_SESSION['message'] = "Attraction record not found.";
    $_SESSION['message_type'] = "error";
    header("Location: attractions.php");
    exit();
}

// Initialize form values with existing database record
$errors = [];
$name             = $attraction['name'];
$categoryId       = $attraction['category_id'];
$description      = $attraction['description'];
$location         = $attraction['location'];
$latitude         = $attraction['latitude'];
$longitude        = $attraction['longitude'];
$openingHours     = $attraction['opening_hours'];
$entranceFee      = $attraction['entrance_fee'];
$facilities       = $attraction['facilities'];
$travelTips       = $attraction['travel_tips'];
$safetyGuidelines = $attraction['safety_guidelines'];
$imageUrl         = $attraction['image_url'];

// Step 4: Fetch Available Categories for Dropdown Selector
$categories = [];
$catSql = "SELECT * FROM `category` ORDER BY `category_name` ASC";
$catResult = $conn->query($catSql);
if ($catResult && $catResult->num_rows > 0) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// -------------------------------------------------------------------
// Step 5: PROCESS UPDATE FORM SUBMISSION (POST)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize input values
    $name             = isset($_POST['name']) ? trim($_POST['name']) : '';
    $categoryId       = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $description      = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location         = isset($_POST['location']) ? trim($_POST['location']) : '';
    $latitude         = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? trim($_POST['latitude']) : null;
    $longitude        = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? trim($_POST['longitude']) : null;
    $openingHours     = isset($_POST['opening_hours']) ? trim($_POST['opening_hours']) : '';
    $entranceFee      = isset($_POST['entrance_fee']) ? trim($_POST['entrance_fee']) : '';
    $facilities       = isset($_POST['facilities']) ? trim($_POST['facilities']) : '';
    $travelTips       = isset($_POST['travel_tips']) ? trim($_POST['travel_tips']) : '';
    $safetyGuidelines = isset($_POST['safety_guidelines']) ? trim($_POST['safety_guidelines']) : '';
    $imageUrl         = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';

    // Validation Rules
    if (empty($name)) {
        $errors['name'] = "Attraction Name is required.";
    } elseif (mb_strlen($name) < 3) {
        $errors['name'] = "Attraction Name must be at least 3 characters long.";
    }

    if ($categoryId <= 0) {
        $errors['category_id'] = "Please select a valid Category.";
    }

    if (empty($description)) {
        $errors['description'] = "Description is required.";
    } elseif (mb_strlen($description) < 10) {
        $errors['description'] = "Description must be at least 10 characters long.";
    }

    if (empty($location)) {
        $errors['location'] = "Location address is required.";
    } elseif (mb_strlen($location) < 3) {
        $errors['location'] = "Location must be at least 3 characters long.";
    }

    if ($latitude !== null && !is_numeric($latitude)) {
        $errors['latitude'] = "Latitude must be a valid decimal number.";
    }

    if ($longitude !== null && !is_numeric($longitude)) {
        $errors['longitude'] = "Longitude must be a valid decimal number.";
    }

    // ---------------------------------------------------------------
    // Step 6: UPDATE DATABASE RECORD IF VALID
    // ---------------------------------------------------------------
    if (empty($errors)) {
        $latVal = ($latitude !== null) ? (float)$latitude : null;
        $lngVal = ($longitude !== null) ? (float)$longitude : null;

        $updateSql = "UPDATE `attraction` SET 
                        `name` = ?, 
                        `category_id` = ?, 
                        `description` = ?, 
                        `location` = ?, 
                        `latitude` = ?, 
                        `longitude` = ?, 
                        `opening_hours` = ?, 
                        `entrance_fee` = ?, 
                        `facilities` = ?, 
                        `travel_tips` = ?, 
                        `safety_guidelines` = ?, 
                        `image_url` = ? 
                      WHERE `attraction_id` = ?";
                      
        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            $stmt->bind_param(
                "sissddssssssi", 
                $name, 
                $categoryId, 
                $description, 
                $location, 
                $latVal, 
                $lngVal, 
                $openingHours, 
                $entranceFee, 
                $facilities, 
                $travelTips, 
                $safetyGuidelines, 
                $imageUrl,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                // Set success flash message and redirect to attractions list
                $_SESSION['message'] = "Attraction '" . $name . "' updated successfully!";
                $_SESSION['message_type'] = "success";
                header("Location: attractions.php");
                exit();
            } else {
                $errors['general'] = "Database Update Error: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $errors['general'] = "Database Query Preparation Failed: " . $conn->error;
        }
    }
}

// Step 7: Define Page Title & Include Header Component
$pageTitle = "Edit Attraction - " . htmlspecialchars($attraction['name']);
include_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================================================================
     EDIT ATTRACTION FORM SECTION
     =================================================================== -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 900px; margin: 0 auto;">
        
        <!-- Header Banner -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-pen-to-square"></i> Modify Destination Record
                </span>
                <h1 class="admin-welcome-title">Edit Attraction</h1>
                <p class="admin-welcome-subtext">Update information for #<?php echo $id; ?> - <?php echo htmlspecialchars($attraction['name']); ?>.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="attractions.php" class="quick-action-btn outline">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- General Error Alert -->
        <?php if (!empty($errors['general'])): ?>
            <div class="admin-flash-banner alert-danger" style="margin-top: 20px;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errors['general']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Card Container -->
        <div class="admin-panel-card" style="margin-top: 24px;">
            <form action="edit_attraction.php?id=<?php echo $id; ?>" method="POST" autocomplete="off" class="admin-form-grid">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <!-- 1. Attraction Name (Required) -->
                <div class="form-group-full">
                    <label for="name" class="form-label">
                        Attraction Name <span class="required-star">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-input <?php echo isset($errors['name']) ? 'has-error' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 2. Category Selector (Required) -->
                <div class="form-group-half">
                    <label for="category_id" class="form-label">
                        Category <span class="required-star">*</span>
                    </label>
                    <select id="category_id" name="category_id" class="form-input <?php echo isset($errors['category_id']) ? 'has-error' : ''; ?>" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo ($categoryId == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['category_id']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 3. Location Address (Required) -->
                <div class="form-group-half">
                    <label for="location" class="form-label">
                        Location Address <span class="required-star">*</span>
                    </label>
                    <input type="text" id="location" name="location" class="form-input <?php echo isset($errors['location']) ? 'has-error' : ''; ?>" value="<?php echo htmlspecialchars($location); ?>" required>
                    <?php if (isset($errors['location'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['location']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 4. Description (Required) -->
                <div class="form-group-full">
                    <label for="description" class="form-label">
                        Attraction Description <span class="required-star">*</span>
                    </label>
                    <textarea id="description" name="description" rows="5" class="form-input <?php echo isset($errors['description']) ? 'has-error' : ''; ?>" required><?php echo htmlspecialchars($description); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['description']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 5. Latitude (Optional) -->
                <div class="form-group-half">
                    <label for="latitude" class="form-label">
                        Latitude Coordinates (GPS)
                    </label>
                    <input type="text" id="latitude" name="latitude" class="form-input <?php echo isset($errors['latitude']) ? 'has-error' : ''; ?>" value="<?php echo htmlspecialchars($latitude); ?>">
                    <?php if (isset($errors['latitude'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['latitude']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 6. Longitude (Optional) -->
                <div class="form-group-half">
                    <label for="longitude" class="form-label">
                        Longitude Coordinates (GPS)
                    </label>
                    <input type="text" id="longitude" name="longitude" class="form-input <?php echo isset($errors['longitude']) ? 'has-error' : ''; ?>" value="<?php echo htmlspecialchars($longitude); ?>">
                    <?php if (isset($errors['longitude'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['longitude']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- 7. Opening Hours (Optional) -->
                <div class="form-group-half">
                    <label for="opening_hours" class="form-label">
                        Opening Hours
                    </label>
                    <input type="text" id="opening_hours" name="opening_hours" class="form-input" value="<?php echo htmlspecialchars($openingHours); ?>">
                </div>

                <!-- 8. Entrance Fee (Optional) -->
                <div class="form-group-half">
                    <label for="entrance_fee" class="form-label">
                        Entrance Fee
                    </label>
                    <input type="text" id="entrance_fee" name="entrance_fee" class="form-input" value="<?php echo htmlspecialchars($entranceFee); ?>">
                </div>

                <!-- 9. Image URL / Asset Path (Optional) -->
                <div class="form-group-full">
                    <label for="image_url" class="form-label">
                        Image URL / File Path
                    </label>
                    <input type="text" id="image_url" name="image_url" class="form-input" value="<?php echo htmlspecialchars($imageUrl); ?>">
                </div>

                <!-- 10. Facilities & Amenities (Optional) -->
                <div class="form-group-full">
                    <label for="facilities" class="form-label">
                        Facilities & Amenities (Comma Separated)
                    </label>
                    <textarea id="facilities" name="facilities" rows="2" class="form-input"><?php echo htmlspecialchars($facilities); ?></textarea>
                </div>

                <!-- 11. Travel Tips (Optional) -->
                <div class="form-group-full">
                    <label for="travel_tips" class="form-label">
                        Travel Tips & Best Time to Visit
                    </label>
                    <textarea id="travel_tips" name="travel_tips" rows="3" class="form-input"><?php echo htmlspecialchars($travelTips); ?></textarea>
                </div>

                <!-- 12. Safety Guidelines (Optional) -->
                <div class="form-group-full">
                    <label for="safety_guidelines" class="form-label">
                        Safety Guidelines
                    </label>
                    <textarea id="safety_guidelines" name="safety_guidelines" rows="3" class="form-input"><?php echo htmlspecialchars($safetyGuidelines); ?></textarea>
                </div>

                <!-- Form Action Buttons -->
                <div class="form-actions-full">
                    <button type="submit" class="btn-primary-action">
                        <i class="fas fa-arrows-rotate"></i> Update Attraction
                    </button>
                    <a href="attractions.php" class="quick-action-btn outline">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

    </div>
</main>

<?php
// Include Footer Component
include_once __DIR__ . '/../includes/footer.php';
?>
