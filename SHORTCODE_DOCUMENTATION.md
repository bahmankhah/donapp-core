# Gravity Flow Shortcode Documentation

## Overview
The `[donap_gravity_flow]` shortcode allows you to display the complete Gravity Flow approved entries table on any WordPress page or post, with full functionality including filtering, pagination, and CSV export.

## Basic Usage

### Simple Implementation
```
[donap_gravity_flow]
```

### With Custom Parameters
```
[donap_gravity_flow per_page="20" show_filters="true" show_export="true" show_stats="true"]
```

## Shortcode Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `per_page` | `10` | Any number | Number of entries to show per page |
| `show_filters` | `true` | `true`, `false` | Show/hide the filtering section |
| `show_export` | `true` | `true`, `false` | Show/hide the CSV export button |
| `show_stats` | `true` | `true`, `false` | Show/hide the statistics cards |
| `user_only` | `false` | `true`, `false` | Show only current user's entries (future feature) |

## Examples

### Minimal Table (No Filters, No Stats)
```
[donap_gravity_flow show_filters="false" show_stats="false" per_page="15"]
```

### Export Only (No Filters)
```
[donap_gravity_flow show_filters="false" show_export="true" show_stats="true"]
```

### Custom Page Size
```
[donap_gravity_flow per_page="25"]
```

## Features Included

### ✅ Complete Table Display
- All approved Gravity Flow entries
- Entry ID, Form Title, Creation Date, Status
- Form data preview with expandable details
- Responsive table design

### ✅ Statistics Dashboard
- Total approved entries count
- Number of different forms
- Monthly and weekly statistics
- Clean card-based layout

### ✅ Advanced Filtering
- Filter by form type
- Date range filtering (start/end dates)
- Clear filters functionality
- URL-based filter persistence

### ✅ CSV Export
- Download all filtered entries
- Persian headers with proper UTF-8 encoding
- Excel-compatible format
- Timestamped filenames

### ✅ Pagination
- Customizable items per page
- Previous/Next navigation
- Page number links
- SEO-friendly URLs

### ✅ Modal Popups
- Detailed entry view
- Click to expand full form data
- Mobile-friendly modal design

## Styling

The shortcode includes complete CSS styling that:
- ✅ Works with any WordPress theme
- ✅ Fully responsive (mobile-friendly)
- ✅ RTL-ready for Persian content
- ✅ Matches WordPress admin styling
- ✅ Clean, professional appearance

## Requirements

### User Authentication
- Users must be logged in to view the table
- Shows "برای مشاهده فرم‌های گرویتی فلو باید وارد شوید" if not logged in

### Plugin Dependencies
- Works with or without Gravity Forms/Gravity Flow
- Shows sample data if plugins are not installed
- Displays warning message when plugins are missing

## URL Parameters

The shortcode respects URL parameters for:
- `paged` - Pagination
- `form_filter` - Form filtering
- `start_date` - Date range start
- `end_date` - Date range end
- `export_gravity_csv` - CSV export trigger

## Implementation Examples

### In a Page
1. Create a new page in WordPress
2. Add the shortcode: `[donap_gravity_flow]`
3. Publish the page

### In a Widget
```
[donap_gravity_flow per_page="5" show_stats="false"]
```

### In Theme Template
```php
echo do_shortcode('[donap_gravity_flow per_page="20"]');
```

## Security Features

- ✅ WordPress nonce verification for CSV exports
- ✅ Proper data escaping and sanitization
- ✅ User capability checks
- ✅ CSRF protection

## Performance Considerations

- Pagination prevents large data sets from slowing page load
- CSS/JS are inline to avoid additional HTTP requests
- Efficient database queries through the service layer
- Cached results where appropriate

## Troubleshooting

### Issue: Shortcode Shows Raw Text
**Solution**: Make sure the shortcode is properly registered. Check that ShortcodeServiceProvider is loaded.

### Issue: No Data Showing
**Solution**: 
1. Ensure user is logged in
2. Check if Gravity Forms/Flow are installed
3. Verify user has approved entries

### Issue: CSV Download Not Working
**Solution**: Check for PHP output before headers. Ensure no errors in the service layer.

### Issue: Styling Issues
**Solution**: The shortcode includes inline CSS. Check for theme CSS conflicts.

## Customization

### Custom Styling
You can override the default styles by adding CSS to your theme:

```css
.donap-gravity-flow-shortcode {
    /* Your custom styles */
}

.donap-table {
    /* Custom table styling */
}
```

### Hooks and Filters
The shortcode uses the same service layer as the admin interface, so any customizations to `GravityService` will apply to both.

## Integration with Themes

The shortcode is designed to work with any WordPress theme and includes:
- Responsive breakpoints
- Neutral color scheme
- Clean typography
- Proper spacing and layout

This makes it perfect for:
- User dashboard pages
- Member areas
- Custom post types
- Frontend form management
- Client portals
