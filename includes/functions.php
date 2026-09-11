<?php
/**
 * ===================================================================
 * NEARTRIP - LOCAL TOURIST DAY-VISIT PLANNER
 * Global Helper Functions (includes/functions.php)
 * ===================================================================
 */

if (!function_exists('render_no_results')) {
    /**
     * Renders a user-friendly "No Results" / "Not Found" UI Component (FR-PL-04).
     *
     * @param array $options Configuration options:
     *  - title: Heading text
     *  - message: Description/body text
     *  - icon: FontAwesome icon class
     *  - action_url: Primary button URL
     *  - action_text: Primary button label
     *  - action_icon: Primary button icon class
     *  - secondary_url: Optional secondary button URL
     *  - secondary_text: Optional secondary button label
     *  - secondary_icon: Optional secondary button icon class
     *  - compact: Boolean for compact card view
     */
    function render_no_results($options = []) {
        // Expose $options to included component file
        include __DIR__ . '/no_results.php';
    }
}
