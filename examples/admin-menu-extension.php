<?php

/**
 * Simple Admin Menu Extension Example
 * 
 * This file demonstrates how to add new submenus to the Donap admin menu
 */

// Example of how to add a custom submenu to Donap
add_action('admin_menu', function() {
    // Make sure AdminServiceProvider exists and has registered the main menu first
    if (class_exists('App\Providers\AdminServiceProvider')) {
        // Add a custom submenu
        add_submenu_page(
            'donap-dashboard',           // Parent slug
            'Custom Feature',            // Page title
            'Custom Feature',            // Menu title
            'manage_options',            // Capability
            'donap-custom-feature',      // Menu slug
            function() {                 // Callback function
                ?>
                <div class="wrap donap-admin-page">
                    <h1>Custom Feature</h1>
                    <p>This is a custom feature page added to the Donap menu.</p>
                    <div class="donap-card">
                        <h3>Custom Settings</h3>
                        <p>You can add any custom functionality here.</p>
                    </div>
                </div>
                <?php
            }
        );
    }
}, 99); // Use priority 99 to ensure it runs after the main menu is registered

// Example of accessing gift values
function example_gift_calculation() {
    if (class_exists('App\Services\GiftService')) {
        $gift_service = new App\Services\GiftService();
        
        // Calculate gift for different amounts
        $amounts = [30000, 75000, 150000, 300000];
        
        foreach ($amounts as $amount) {
            $gift = $gift_service->calculateGift($amount);
            $percentage = $gift_service->getGiftPercentage($amount);
            $range = $gift_service->getGiftRangeDescription($amount);
            
            echo "Amount: {$amount} - Range: {$range} - Gift: {$gift} ({$percentage}%)<br>";
        }
    }
}
