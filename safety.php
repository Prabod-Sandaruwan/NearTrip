<?php
/**
 * Safety guidelines and travel advice page.
 * Displays emergency hotlines, general travel tips, and location-specific safety notes.
 */

// Load database configuration and helpers
require_once __DIR__ . '/includes/config.php';

// Page title
$pageTitle = "Safety Guidelines & Travel Tips - NearTrip Warakapola";

// Navigation header
include_once __DIR__ . '/includes/header.php';

// Fetch attractions and their safety tips from database
$attractionSafetyList = array();
$sql = "SELECT a.attraction_id, a.name, a.location, a.safety_guidelines, a.travel_tips, a.image_url, c.category_name 
        FROM `attraction` a 
        LEFT JOIN `category` c ON a.category_id = c.category_id 
        ORDER BY a.`attraction_id` ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attractionSafetyList[] = $row;
    }
}
?>

<!-- Hero banner -->
<section class="page-hero">
    <div>
        <h1 class="page-hero-title"><i class="fas fa-shield-halved"></i> Safety & Travel Guidelines</h1>
        <p class="page-hero-subtext">Essential tourist advice, emergency contact numbers, and location safety guidelines for Warakapola & Dorawaka.</p>
    </div>
</section>

<!-- Main safety section -->
<main class="why-choose-us" style="padding: 60px 20px;">
    <div class="section-container" style="max-width: 1200px;">

        <!-- Emergency hotlines and contact numbers -->
        <div class="safety-section-block">
            <div class="safety-section-header">
                <h2><i class="fas fa-phone-volume text-danger"></i> Emergency Hotlines & Support</h2>
                <p>Keep these important Sri Lanka emergency numbers saved when traveling around Dorawaka & Warakapola.</p>
            </div>

            <div class="emergency-grid">
                <!-- Police Emergency -->
                <div class="emergency-card danger">
                    <div class="emergency-icon"><i class="fas fa-shield-cat"></i></div>
                    <div class="emergency-info">
                        <span class="emergency-label">Police Emergency</span>
                        <h3 class="emergency-number">119</h3>
                        <p class="emergency-sub">Nationwide Police Assistance</p>
                    </div>
                    <a href="tel:119" class="btn-emergency-call"><i class="fas fa-phone"></i> Call Now</a>
                </div>

                <!-- Ambulance / Suwa Seriya -->
                <div class="emergency-card danger">
                    <div class="emergency-icon"><i class="fas fa-truck-medical"></i></div>
                    <div class="emergency-info">
                        <span class="emergency-label">Suwa Seriya Ambulance</span>
                        <h3 class="emergency-number">1990</h3>
                        <p class="emergency-sub">Free Emergency Medical Care</p>
                    </div>
                    <a href="tel:1990" class="btn-emergency-call"><i class="fas fa-phone"></i> Call Now</a>
                </div>

                <!-- Tourist Police Hotline -->
                <div class="emergency-card primary">
                    <div class="emergency-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="emergency-info">
                        <span class="emergency-label">Tourist Police Hotline</span>
                        <h3 class="emergency-number">1912</h3>
                        <p class="emergency-sub">Sri Lanka Tourism Helpline</p>
                    </div>
                    <a href="tel:1912" class="btn-emergency-call primary"><i class="fas fa-phone"></i> Call Hotline</a>
                </div>

                <!-- Warakapola Base Hospital -->
                <div class="emergency-card info">
                    <div class="emergency-icon"><i class="fas fa-hospital"></i></div>
                    <div class="emergency-info">
                        <span class="emergency-label">Warakapola Base Hospital</span>
                        <h3 class="emergency-number">035 226 7261</h3>
                        <p class="emergency-sub">Local Medical Center</p>
                    </div>
                    <a href="tel:+94352267261" class="btn-emergency-call info"><i class="fas fa-phone"></i> Call Hospital</a>
                </div>
            </div>
        </div>

        <!-- General visitor guidelines and advice -->
        <div class="safety-section-block" style="margin-top: 50px;">
            <div class="safety-section-header">
                <h2><i class="fas fa-lightbulb text-warning"></i> General Visitor Guidelines & Etiquette</h2>
                <p>Key cultural, weather, and environmental practices for a hassle-free visit to Sabaragamuwa region.</p>
            </div>

            <div class="tips-category-grid">
                
                <!-- Card 1: Temple & Heritage Etiquette -->
                <div class="tip-cat-card">
                    <div class="tip-cat-icon"><i class="fas fa-gopuram"></i></div>
                    <h3>Cultural & Temple Etiquette</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Wear respectful attire covering shoulders and knees when visiting Thalagama, Attanagalla, or Dedigama temples.</li>
                        <li><i class="fas fa-check"></i> Remove hats and footwear before stepping onto sacred temple courtyards.</li>
                        <li><i class="fas fa-check"></i> Avoid turning your back directly to Buddha statues when taking photographs.</li>
                    </ul>
                </div>

                <!-- Card 2: Waterfall & Water Safety -->
                <div class="tip-cat-card">
                    <div class="tip-cat-icon"><i class="fas fa-water"></i></div>
                    <h3>Waterfall & River Safety</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Rocks around Dunumala Ella, Theli Ella, and Algama Ella are extremely slippery due to moss.</li>
                        <li><i class="fas fa-check"></i> Watch out for sudden water level rises (flash floods) during heavy monsoon downpours.</li>
                        <li><i class="fas fa-check"></i> Avoid diving or jumping into unmeasured waterfall plunge pools.</li>
                    </ul>
                </div>

                <!-- Card 3: Hiking & Forest Trails -->
                <div class="tip-cat-card">
                    <div class="tip-cat-icon"><i class="fas fa-person-hiking"></i></div>
                    <h3>Hiking & Forest Trekking</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Apply lime, salt, or wear anti-leech socks when trekking through Salgala Forest trails.</li>
                        <li><i class="fas fa-check"></i> Carry a bright flashlight/torch when exploring the Dorawaka Prehistoric Cave interior.</li>
                        <li><i class="fas fa-check"></i> Stay strictly on marked trails and avoid disturbing wildlife or hermit monks.</li>
                    </ul>
                </div>

                <!-- Card 4: Weather & Clothing -->
                <div class="tip-cat-card">
                    <div class="tip-cat-icon"><i class="fas fa-cloud-sun-rain"></i></div>
                    <h3>Weather & Preparation</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Sabaragamuwa region receives heavy rainfall during South-West and North-East monsoons.</li>
                        <li><i class="fas fa-check"></i> Carry adequate drinking water, sunscreen, and an umbrella or rain poncho.</li>
                        <li><i class="fas fa-check"></i> Ensure your phone battery is charged for map navigation and emergency calls.</li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- Location-specific safety advisories -->
        <div class="safety-section-block" style="margin-top: 50px;">
            <div class="safety-section-header">
                <h2><i class="fas fa-triangle-exclamation text-danger"></i> Location-Specific Safety Advisories</h2>
                <p>Specific warnings and advice curated for individual attractions around Dorawaka & Warakapola.</p>
            </div>

            <div class="location-safety-grid">
                <?php foreach ($attractionSafetyList as $item): 
                    // Resolve image
                    $rawImg = !empty($item['image_url']) ? $item['image_url'] : 'assets/hero.webp';
                    $cleanImg = ltrim($rawImg, '/');
                    $imagePath = file_exists(__DIR__ . '/' . $cleanImg) ? $cleanImg : 'assets/hero.webp';
                ?>
                    <div class="location-safety-card">
                        
                        <!-- Header with Image & Title -->
                        <div class="loc-safety-header">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="loc-safety-img">
                            <div class="loc-safety-title-box">
                                <?php if (!empty($item['category_name'])): ?>
                                    <span class="loc-cat-badge"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?></span>
                                <?php endif; ?>
                                <h3>
                                    <a href="details.php?id=<?php echo $item['attraction_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                <p class="loc-address"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($item['location']); ?></p>
                            </div>
                        </div>

                        <!-- Card Body: Safety Guidelines & Travel Tips -->
                        <div class="loc-safety-body">
                            
                            <?php if (!empty($item['safety_guidelines'])): ?>
                                <div class="safety-box-alert">
                                    <h4 class="box-title danger"><i class="fas fa-shield-cat"></i> Safety Guidelines</h4>
                                    <p><?php echo nl2br(htmlspecialchars($item['safety_guidelines'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($item['travel_tips'])): ?>
                                <div class="safety-box-alert info" style="margin-top: 12px;">
                                    <h4 class="box-title info"><i class="fas fa-lightbulb"></i> Travel Tips</h4>
                                    <p><?php echo nl2br(htmlspecialchars($item['travel_tips'])); ?></p>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Link to full details -->
                        <div class="loc-safety-footer">
                            <a href="details.php?id=<?php echo $item['attraction_id']; ?>" class="btn-view-loc-details">
                                View Full Destination Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</main>

<?php
// Page footer
include_once __DIR__ . '/includes/footer.php';
?>
