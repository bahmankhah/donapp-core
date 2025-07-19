# Donap Admin Menu System

This documentation explains how to use the Donap admin menu system and gift configuration features.

## Overview

The Donap admin menu system provides a structured way to manage the plugin's administrative interface with:

- Main Donap menu in WordPress admin
- Structured submenu system for easy extension
- Gift values configuration
- Dashboard with statistics
- Seamless integration with existing functionality

## Features

### 1. Main Admin Menu

The main Donap menu appears in the WordPress admin sidebar with the following default submenus:

- **Dashboard**: Overview of system statistics and recent activity
- **Settings**: Configuration options including gift values
- **Wallet Management**: Wallet-related administration (placeholder)
- **Reports**: System reports (placeholder)

### 2. Gift Values Configuration

The gift system allows you to configure percentage-based bonuses for different charge ranges:

- **Till 50,000 Charge**: Gift percentage for charges up to 50,000 Toman
- **From 50,000 Till 100,000 Charge**: Gift percentage for charges between 50,000-100,000 Toman
- **From 100,000 Till 200,000 Charge**: Gift percentage for charges between 100,000-200,000 Toman  
- **Above 200,000 Charge**: Gift percentage for charges above 200,000 Toman

## Installation

The admin menu system is automatically initialized when the plugin loads. No additional setup is required.

## Usage

### Accessing the Admin Menu

1. Go to WordPress Admin Dashboard
2. Look for "Donap" in the left sidebar menu
3. Click to expand and see all available submenus

### Configuring Gift Values

1. Navigate to **Donap → Settings**
2. Click on the **Gift Settings** tab
3. Enter percentage values for each charge range (0-100)
4. Click **Save Gift Settings**

### Adding Custom Submenus with Views

The preferred way to add new submenus is using the view system with .view.php files:

```php
// Add this to your theme's functions.php or in a plugin
add_action('admin_menu', function() {
    add_submenu_page(
        'donap-dashboard',           // Parent slug (always use this for Donap)
        'My Custom Page',            // Page title
        'Custom Page',               // Menu title
        'manage_options',            // Capability
        'donap-custom-page',         // Menu slug (prefix with donap-)
        function() {                 // Callback function
            // Prepare data for the view
            $data = [
                'page_title' => 'My Custom Page',
                'custom_data' => get_custom_data(),
                'stats' => [
                    'total' => 100,
                    'active' => 85
                ]
            ];
            
            // Use the view system
            echo view('admin/my-custom-page', $data);
        }
    );
}, 99); // Priority 99 to run after main menu is registered
```

Then create the view file at `views/admin/my-custom-page.view.php`:

```php
<div class="wrap donap-admin-page">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'Total Items',
            'value' => $stats['total']
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'Active Items',
            'value' => $stats['active']
        ]);
        ?>
    </div>
    
    <div class="donap-custom-content">
        <h2>Custom Content</h2>
        <p>Your custom functionality here.</p>
    </div>
</div>
```

### Available View Components

The admin system provides several reusable view components:

#### Stat Card Component
```php
echo view('admin/components/stat-card', [
    'title' => 'Card Title',
    'value' => 12345,
    'suffix' => 'Toman',           // Optional
    'stat_class' => 'custom-class', // Optional
    'description' => 'Description'  // Optional
]);
```

#### Tabs Component
```php
$tabs = [
    'tab1' => [
        'label' => 'Tab 1',
        'active' => true,
        'content' => '<p>Tab 1 content</p>'
    ],
    'tab2' => [
        'label' => 'Tab 2',
        'content' => '<p>Tab 2 content</p>'
    ]
];

echo view('admin/components/tabs', ['tabs' => $tabs]);
```

#### Gift Field Component
```php
echo view('admin/components/gift-field', [
    'field' => 'field_name',
    'label' => 'Field Label',
    'value' => 5.0,
    'description' => 'Field description'
]);
```

## API Reference

### GiftService Class

The `GiftService` class provides methods for working with gift calculations:

```php
use App\Services\GiftService;

$giftService = Container::resolve('GiftService');

// Calculate gift amount
$giftAmount = $giftService->calculateGift(75000); // Returns gift amount in Toman

// Get gift percentage
$percentage = $giftService->getGiftPercentage(75000); // Returns percentage value

// Get gift range description
$description = $giftService->getGiftRangeDescription(75000); // Returns range description

// Get all gift configuration
$config = $giftService->getGiftConfiguration(); // Returns array of all settings

// Update gift configuration
$newConfig = [
    'till_50k' => 5.0,
    '50k_to_100k' => 7.5,
    '100k_to_200k' => 10.0,
    'above_200k' => 15.0
];
$giftService->updateGiftConfiguration($newConfig);
```

### AdminServiceProvider Methods

The `AdminServiceProvider` class provides the following public methods:

- `add_submenu()`: Programmatically add submenus
- `get_gift_value()`: Static method to get gift values

## Integration with WooCommerce

The gift system is automatically integrated with WooCommerce wallet top-ups:

1. When a customer completes an order with wallet top-up products
2. The system automatically calculates the appropriate gift based on the amount
3. Both the charge and gift are added to the user's wallet
4. Order notes are updated with transaction details

## Styling

The admin pages use custom CSS classes for consistent styling:

- `.donap-admin-page`: Main page wrapper
- `.donap-card`: Card-style containers
- `.donap-dashboard-grid`: Grid layout for dashboard
- `.donap-activity-list`: Activity list styling

## Hooks and Filters

### Available Actions

- `donap_admin_menu_registered`: Fired after the main menu is registered
- `donap_gift_values_updated`: Fired when gift values are updated

### Available Filters

- `donap_gift_calculation`: Filter gift calculation logic
- `donap_admin_capability`: Filter required capability for admin access

## Troubleshooting

### Menu Not Appearing

1. Check user capabilities (must have `manage_options`)
2. Ensure AdminServiceProvider is properly loaded
3. Check for JavaScript errors in browser console

### Gift Calculations Not Working

1. Verify gift values are configured in settings
2. Check that GiftService is properly registered in container
3. Ensure WooCommerce integration is active

### Styling Issues

1. Check that admin CSS is loading properly
2. Verify plugin URL paths are correct
3. Clear browser cache

## Examples

See the `/examples/admin-menu-extension.php` file for practical examples of:

- Adding custom submenus
- Using the GiftService
- Implementing custom admin pages

## Security Considerations

- All admin pages check for `manage_options` capability
- Settings are sanitized before saving
- Nonce verification is used for form submissions
- Input validation is applied to all user inputs

## Performance

- Database queries are optimized and cached where possible
- Admin scripts and styles are only loaded on Donap admin pages
- Gift calculations are lightweight and efficient

## Future Enhancements

Planned features for future releases:

- Advanced reporting dashboard
- Export/import functionality for settings
- User role-based access control
- API endpoints for external integrations
- Bulk wallet operations
- Advanced gift rule configurations
