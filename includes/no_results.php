<?php
/**
 * Empty state component.
 * Displays a friendly message when no items or search results are found.
 */

// Default configuration with fallback values
$nrTitle        = isset($options['title']) ? $options['title'] : "No Results Found";
$nrMessage      = isset($options['message']) ? $options['message'] : "We couldn't find what you were looking for. Please check your search query or try again later.";
$nrIcon         = isset($options['icon']) ? $options['icon'] : "fas fa-compass-slash";
$nrActionUrl    = isset($options['action_url']) ? $options['action_url'] : "places.php";
$nrActionText   = isset($options['action_text']) ? $options['action_text'] : "Explore Destinations";
$nrActionIcon   = isset($options['action_icon']) ? $options['action_icon'] : "fas fa-compass";
$nrSecUrl       = isset($options['secondary_url']) ? $options['secondary_url'] : "";
$nrSecText      = isset($options['secondary_text']) ? $options['secondary_text'] : "";
$nrSecIcon      = isset($options['secondary_icon']) ? $options['secondary_icon'] : "fas fa-house";
$nrCompact      = !empty($options['compact']) ? true : false;
?>

<div class="no-results-wrapper <?php echo $nrCompact ? 'compact' : ''; ?>">
    <div class="no-results-card">
        <div class="no-results-icon-wrapper">
            <i class="<?php echo htmlspecialchars($nrIcon); ?> no-results-icon"></i>
            <div class="no-results-pulse"></div>
        </div>
        
        <h3 class="no-results-title"><?php echo htmlspecialchars($nrTitle); ?></h3>
        <p class="no-results-message"><?php echo htmlspecialchars($nrMessage); ?></p>
        
        <div class="no-results-actions">
            <?php if (!empty($nrActionUrl) && !empty($nrActionText)): ?>
                <a href="<?php echo htmlspecialchars($nrActionUrl); ?>" class="btn-no-results-primary">
                    <?php if (!empty($nrActionIcon)): ?>
                        <i class="<?php echo htmlspecialchars($nrActionIcon); ?>"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($nrActionText); ?></span>
                </a>
            <?php endif; ?>

            <?php if (!empty($nrSecUrl) && !empty($nrSecText)): ?>
                <a href="<?php echo htmlspecialchars($nrSecUrl); ?>" class="btn-no-results-secondary">
                    <?php if (!empty($nrSecIcon)): ?>
                        <i class="<?php echo htmlspecialchars($nrSecIcon); ?>"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($nrSecText); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
