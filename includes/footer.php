<?php
/**
 * Global footer component.
 * Displays footer links, social links, and copyright info.
 */

$inSubfolderFooter = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false);
$footerPrefix = isset($prefix) ? $prefix : ($inSubfolderFooter ? '../' : '');
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            
            <div class="footer-grid">
                
                <!-- Brand and about -->
                <div class="footer-col">
                    <div class="footer-brand">
                        <img src="<?php echo $footerPrefix; ?>assets/logo.png" alt="NearTrip Logo">
                        <span>NearTrip</span>
                    </div>
                    <p class="footer-tagline">Centralizing reliable travel details for attractions within a 25 km radius of Dorawaka & Warakapola.</p>
                    <div class="footer-socials">
                        <a href="javascript:void(0);" class="social-link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="javascript:void(0);" class="social-link" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                        <a href="javascript:void(0);" class="social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="javascript:void(0);" class="social-link" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick navigation links -->
                <div class="footer-col">
                    <h4 class="footer-col-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo $footerPrefix; ?>index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>places.php"><i class="fas fa-chevron-right"></i> Destinations</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>plan.php"><i class="fas fa-chevron-right"></i> Itinerary Planner</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>map.php"><i class="fas fa-chevron-right"></i> Interactive Map</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>safety.php"><i class="fas fa-chevron-right"></i> Safety Guidelines</a></li>
                    </ul>
                </div>

                <!-- Attraction category links -->
                <div class="footer-col">
                    <h4 class="footer-col-title">Attractions</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo $footerPrefix; ?>places.php?category=heritage"><i class="fas fa-chevron-right"></i> Heritage Sites</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>places.php?category=nature"><i class="fas fa-chevron-right"></i> Nature & Parks</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>places.php?category=waterfalls"><i class="fas fa-chevron-right"></i> Waterfalls</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>places.php?category=religious"><i class="fas fa-chevron-right"></i> Cultural Spots</a></li>
                        <li><a href="<?php echo $footerPrefix; ?>places.php?category=family"><i class="fas fa-chevron-right"></i> Family Outings</a></li>
                    </ul>
                </div>

                <!-- Coverage area details -->
                <div class="footer-col">
                    <h4 class="footer-col-title">Local Coverage</h4>
                    <ul class="footer-info-list">
                        <li><i class="fas fa-location-dot"></i> Dorawaka & Warakapola (25 km Zone)</li>
                        <li><i class="fas fa-map-marked-alt"></i> Kegalle District, Sabaragamuwa</li>
                        <li><i class="fas fa-shield-halved"></i> 100% Verified Information</li>
                    </ul>
                </div>

            </div>

            <!-- Copyright notice -->
            <div class="footer-bottom">
                <p>&copy; 2026 NearTrip. All rights reserved. | Local Tourist Day-Visit Planner</p>
            </div>

        </div>
    </footer>

</body>
</html>
