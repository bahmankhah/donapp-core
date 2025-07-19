<?php
/**
 * Example: How to add a custom admin page to the Donap menu using views
 * 
 * Add this code to your theme's functions.php or in a custom plugin
 */

// Add custom submenu to Donap admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        'donap-dashboard',                    // Parent slug (always use this for Donap)
        'Custom Analytics',                   // Page title
        'Analytics',                         // Menu title
        'manage_options',                    // Capability
        'donap-analytics',                   // Menu slug
        'donap_analytics_page_callback'      // Callback function
    );
}, 99);

/**
 * Analytics page callback function
 */
function donap_analytics_page_callback() {
    // Prepare data for the view
    $data = [
        'page_title' => 'Analytics Dashboard',
        'total_revenue' => 1500000,
        'conversion_rate' => 12.5,
        'active_users' => 150,
        'top_products' => [
            ['name' => 'Wallet Credit 50K', 'sales' => 45],
            ['name' => 'Wallet Credit 100K', 'sales' => 32],
            ['name' => 'Wallet Credit 200K', 'sales' => 18]
        ],
        'chart_data' => [
            ['date' => '2025-07-15', 'revenue' => 85000],
            ['date' => '2025-07-16', 'revenue' => 92000],
            ['date' => '2025-07-17', 'revenue' => 78000],
            ['date' => '2025-07-18', 'revenue' => 105000],
            ['date' => '2025-07-19', 'revenue' => 95000],
            ['date' => '2025-07-20', 'revenue' => 110000]
        ]
    ];
    
    // Use the view system to render the page
    echo view('admin/custom-analytics', $data);
}

// Create the view file at: views/admin/custom-analytics.view.php
// This demonstrates the structure and how to pass data to views
?>
