<?php
/**
 * Home page.
 * Displays hero banner, quick step guide, about section, and why choose us features.
 */

// Set page title and render header
$pageTitle = "NearTrip - Explore Beautiful Places Near Dorawaka & Warakapola";
include_once __DIR__ . '/includes/header.php';
?>

    <section class="hero">
        <div class="hero-container">
            
            <!-- Hero card -->
            <div class="hero-card">
                
                <!-- Greeting text -->
                <h1 class="greeting-heading"><?php echo htmlspecialchars($greeting); ?></h1>
                
                <!-- Main title and intro -->
                <h2 class="hero-title">Explore beautiful places in Warakapola with NearTrip</h2>
                <p class="hero-subtext">Discover amazing attractions near you. Plan your perfect day trip within 25km radius of Dorawaka & Warakapola!</p>

                <!-- Step highlights bar -->
                <div class="search-form-card steps-card-bar">
                    
                    <div class="form-field-group step-item">
                        <i class="fas fa-compass"></i>
                        <div class="field-content">
                            <span class="field-label">Explore</span>
                            <span class="step-subtext">Discover places</span>
                        </div>
                    </div>

                    <div class="field-divider"></div>

                    <div class="form-field-group step-item">
                        <i class="fas fa-route"></i>
                        <div class="field-content">
                            <span class="field-label">Plan</span>
                            <span class="step-subtext">Your day trip</span>
                        </div>
                    </div>

                    <div class="field-divider"></div>

                    <div class="form-field-group step-item">
                        <i class="fas fa-camera"></i>
                        <div class="field-content">
                            <span class="field-label">Visit</span>
                            <span class="step-subtext">Local spots</span>
                        </div>
                    </div>

                    <div class="field-divider"></div>

                    <div class="form-field-group step-item">
                        <i class="fas fa-heart"></i>
                        <div class="field-content">
                            <span class="field-label">Enjoy</span>
                            <span class="step-subtext">Great memories</span>
                        </div>
                    </div>

                    <!-- Button to view places -->
                    <a href="places.php" class="search-btn redirect-btn" title="Explore Places Now">
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

            </div>

        </div>
    </section>

    <!-- About section -->
    <section class="about-us" id="about">
        <div class="section-container">
            <div class="about-wrapper">
                
                <!-- Left column: Visual highlights -->
                <div class="about-image-column">
                    <div class="about-card-visual">
                        <div class="visual-badge-main">
                            <i class="fas fa-map-marked-alt"></i>
                            <span class="badge-number">25 km</span>
                            <span class="badge-label">Radius Exploration Zone</span>
                        </div>
                        <div class="visual-stats-grid">
                            <div class="stat-mini-card">
                                <i class="fas fa-landmark" style="color: var(--primary);"></i>
                                <span class="stat-title">Heritage Sites</span>
                                <span class="stat-sub">Dorawaka & Warakapola</span>
                            </div>
                            <div class="stat-mini-card">
                                <i class="fas fa-shield-alt" style="color: var(--accent-blue);"></i>
                                <span class="stat-title">Visitor Safety</span>
                                <span class="stat-sub">Verified Travel Details</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column: Text content -->
                <div class="about-content-column">
                    <div class="section-subtitle-tag">
                        <i class="fas fa-compass"></i> ABOUT NEARTRIP
                    </div>
                    <h2 class="about-title">Uncovering Sri Lanka's Hidden Local Destinations</h2>
                    
                    <p class="about-paragraph">
                        Many of Sri Lanka's breathtaking local destinations remain under-documented and difficult to discover online. The <strong>Local Tourist Day-Visit Planner</strong> was created to bridge this gap by centralizing reliable travel details for attractions within a 25 km radius of Dorawaka, Warakapola.
                    </p>
                    <p class="about-paragraph">
                        To eliminate the hassle of scattered and outdated information, our user-friendly platform provides structured attraction details, interactive maps, and a simple personalized one-day itinerary planner. Our mission is to simplify travel planning, improve visitor safety, and proudly increase the visibility of lesser-known local heritage sites.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Why choose us section -->
    <section class="why-choose-us">
        <div class="section-container">
            
            <!-- Section title -->
            <div class="section-header">
                <h2 class="section-title">WHY CHOOSE US?</h2>
            </div>

            <!-- Feature cards -->
            <div class="features-grid">
                
                <!-- Card 1: Verified Local Insights -->
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fas fa-search-location"></i>
                    </div>
                    <h3 class="feature-title">Verified Local Insights</h3>
                    <p class="feature-desc">Accurate, centralized information on hidden gems within a 25km radius of Warakapola without scattered sources.</p>
                </div>

                <!-- Card 2: Effortless Itinerary Planning -->
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3 class="feature-title">Effortless Itinerary Planning</h3>
                    <p class="feature-desc">Easily select attractions and build your personalized, hassle-free one-day trip schedule in minutes.</p>
                </div>

                <!-- Card 3: Interactive & Safe Exploration -->
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="feature-title">Interactive & Safe Exploration</h3>
                    <p class="feature-desc">Explore with interactive 25km maps, essential travel tips, facility details, and safety guidelines for peace of mind.</p>
                </div>

            </div>

        </div>
    </section>

<?php
// Include footer component
include_once __DIR__ . '/includes/footer.php';
?>
