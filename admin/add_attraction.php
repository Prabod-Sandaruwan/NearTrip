<?php
/**
 * Add new attraction page.
 * Validates form inputs and inserts a new attraction record into the database.
 */

// Include database configuration and authentication helper
require_once __DIR__ . '/../includes/config.php';

// Ensure only logged-in administrators can access this page
requireAdmin();

// Initialize form fields and error list
$errors = [];
$name = '';
$categoryId = '';
$description = '';
$location = '';
$latitude = '';
$longitude = '';
$openingHours = '';
$entranceFee = '';
$facilities = '';
$travelTips = '';
$safetyGuidelines = '';
$imageUrl = '';

// Fetch all categories for the dropdown list
$categories = [];
$catSql = "SELECT * FROM `category` ORDER BY `category_name` ASC";
$catResult = $conn->query($catSql);
if ($catResult && $catResult->num_rows > 0) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and capture input values
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

    // Validate required and numeric form fields
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
        $errors['latitude'] = "Latitude must be a valid decimal number (e.g. 7.1592).";
    }

    if ($longitude !== null && !is_numeric($longitude)) {
        $errors['longitude'] = "Longitude must be a valid decimal number (e.g. 80.2020).";
    }

    // Save record to database if there are no validation errors
    if (empty($errors)) {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1;
        $latVal  = ($latitude !== null) ? (float)$latitude : null;
        $lngVal  = ($longitude !== null) ? (float)$longitude : null;

        $insertSql = "INSERT INTO `attraction` 
                      (`name`, `category_id`, `admin_id`, `description`, `location`, `latitude`, `longitude`, `opening_hours`, `entrance_fee`, `facilities`, `travel_tips`, `safety_guidelines`, `image_url`) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      
        $stmt = $conn->prepare($insertSql);
        if ($stmt) {
            $stmt->bind_param(
                "siissddssssss", 
                $name, 
                $categoryId, 
                $adminId, 
                $description, 
                $location, 
                $latVal, 
                $lngVal, 
                $openingHours, 
                $entranceFee, 
                $facilities, 
                $travelTips, 
                $safetyGuidelines, 
                $imageUrl
            );

            if ($stmt->execute()) {
                $stmt->close();
                // Set success message and redirect back to the attraction list
                $_SESSION['message'] = "Attraction '" . $name . "' added successfully!";
                $_SESSION['message_type'] = "success";
                header("Location: attractions.php");
                exit();
            } else {
                $errors['general'] = "Database Insert Error: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $errors['general'] = "Database Query Preparation Failed: " . $conn->error;
        }
    }
}

// Page title and header
$pageTitle = "Add New Attraction - NearTrip Admin";
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Add attraction form section -->
<main class="dashboard-page-section" style="padding: 130px 20px 80px 20px; background-color: #F8FAFC;">
    <div class="section-container" style="max-width: 900px; margin: 0 auto;">
        
        <!-- Page header banner -->
        <div class="admin-welcome-banner">
            <div>
                <span class="role-badge admin">
                    <i class="fas fa-plus-circle"></i> Create Destination Record
                </span>
                <h1 class="admin-welcome-title">Add New Attraction</h1>
                <p class="admin-welcome-subtext">Publish a new tourist spot in the Warakapola & Dorawaka 25km radius zone.</p>
            </div>
            <div class="admin-welcome-actions">
                <a href="attractions.php" class="quick-action-btn outline">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- General error message -->
        <?php if (!empty($errors['general'])): ?>
            <div class="admin-flash-banner alert-danger" style="margin-top: 20px;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($errors['general']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="admin-panel-card" style="margin-top: 24px;">
            <form action="add_attraction.php" method="POST" autocomplete="off" class="admin-form-grid">
                
                <!-- Attraction name -->
                <div class="form-group-full">
                    <label for="name" class="form-label">
                        Attraction Name <span class="required-star">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-input <?php echo isset($errors['name']) ? 'has-error' : ''; ?>" placeholder="e.g. Dunumala Waterfall (Dunumala Ella)" value="<?php echo htmlspecialchars($name); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Category -->
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

                <!-- Location address -->
                <div class="form-group-half">
                    <label for="location" class="form-label">
                        Location Address <span class="required-star">*</span>
                    </label>
                    <input type="text" id="location" name="location" class="form-input <?php echo isset($errors['location']) ? 'has-error' : ''; ?>" placeholder="e.g. Uduwaka village, Warakapola" value="<?php echo htmlspecialchars($location); ?>" required>
                    <?php if (isset($errors['location'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['location']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="form-group-full">
                    <label for="description" class="form-label">
                        Attraction Description <span class="required-star">*</span>
                    </label>
                    <textarea id="description" name="description" rows="5" class="form-input <?php echo isset($errors['description']) ? 'has-error' : ''; ?>" placeholder="Provide rich history, cultural significance, or natural beauty details..." required><?php echo htmlspecialchars($description); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['description']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Latitude -->
                <div class="form-group-half">
                    <label for="latitude" class="form-label">
                        Latitude Coordinates (GPS)
                    </label>
                    <input type="text" id="latitude" name="latitude" class="form-input <?php echo isset($errors['latitude']) ? 'has-error' : ''; ?>" placeholder="e.g. 7.148082" value="<?php echo htmlspecialchars($latitude); ?>">
                    <?php if (isset($errors['latitude'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['latitude']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Longitude -->
                <div class="form-group-half">
                    <label for="longitude" class="form-label">
                        Longitude Coordinates (GPS)
                    </label>
                    <input type="text" id="longitude" name="longitude" class="form-input <?php echo isset($errors['longitude']) ? 'has-error' : ''; ?>" placeholder="e.g. 80.201695" value="<?php echo htmlspecialchars($longitude); ?>">
                    <?php if (isset($errors['longitude'])): ?>
                        <span class="error-text"><?php echo htmlspecialchars($errors['longitude']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Opening hours -->
                <div class="form-group-half">
                    <label for="opening_hours" class="form-label">
                        Opening Hours
                    </label>
                    <input type="text" id="opening_hours" name="opening_hours" class="form-input" placeholder="e.g. Open 24 hours Daily" value="<?php echo htmlspecialchars($openingHours); ?>">
                </div>

                <!-- Entrance fee -->
                <div class="form-group-half">
                    <label for="entrance_fee" class="form-label">
                        Entrance Fee
                    </label>
                    <input type="text" id="entrance_fee" name="entrance_fee" class="form-input" placeholder="e.g. Free entry / LKR 500" value="<?php echo htmlspecialchars($entranceFee); ?>">
                </div>

                <!-- Image URL or path -->
                <div class="form-group-full">
                    <label for="image_url" class="form-label">
                        Image URL / File Path
                    </label>
                    <input type="text" id="image_url" name="image_url" class="form-input" placeholder="e.g. assets/dunumala.webp or images/hero.webp" value="<?php echo htmlspecialchars($imageUrl); ?>">
                </div>

                <!-- Facilities -->
                <div class="form-group-full">
                    <label for="facilities" class="form-label">
                        Facilities & Amenities (Comma Separated)
                    </label>
                    <textarea id="facilities" name="facilities" rows="2" class="form-input" placeholder="e.g. Parking available, Changing rooms, Local food stalls"><?php echo htmlspecialchars($facilities); ?></textarea>
                </div>

                <!-- Travel tips -->
                <div class="form-group-full">
                    <label for="travel_tips" class="form-label">
                        Travel Tips & Best Time to Visit
                    </label>
                    <textarea id="travel_tips" name="travel_tips" rows="3" class="form-input" placeholder="e.g. Visit during rainy season for maximum water flow. Wear comfortable footwear."><?php echo htmlspecialchars($travelTips); ?></textarea>
                </div>

                <!-- Safety guidelines -->
                <div class="form-group-full">
                    <label for="safety_guidelines" class="form-label">
                        Safety Guidelines
                    </label>
                    <textarea id="safety_guidelines" name="safety_guidelines" rows="3" class="form-input" placeholder="e.g. Exercise caution on slippery rocks. Do not leave plastic waste behind."><?php echo htmlspecialchars($safetyGuidelines); ?></textarea>
                </div>

                <!-- Form action buttons -->
                <div class="form-actions-full">
                    <button type="submit" class="btn-primary-action">
                        <i class="fas fa-floppy-disk"></i> Save Attraction
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
// Render footer
include_once __DIR__ . '/../includes/footer.php';
?>
