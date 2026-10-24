<?php
/**
 * General helper functions for NearTrip.
 */

if (!function_exists('render_no_results')) {
    /**
     * Displays a friendly message when no search results or items are found.
     */
    function render_no_results($options = []) {
        // Expose $options to included component file
        include __DIR__ . '/no_results.php';
    }
}
