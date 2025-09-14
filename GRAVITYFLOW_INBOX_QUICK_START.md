# Gravity Flow Inbox Shortcode - Quick Start

## Simple Usage Examples

### 1. Basic Inbox Table
```
[donap_gravityflow_inbox]
```
This displays a full-featured inbox with all default settings.

### 2. Compact View
```
[donap_gravityflow_inbox per_page="10" show_stats="false" show_filters="false"]
```
Shows a minimal table with just the entries, 10 per page.

### 3. Dashboard View
```
[donap_gravityflow_inbox per_page="15" show_stats="true" show_filters="true"]
```
Perfect for dashboard pages with statistics and filtering.

### 4. Mobile-Optimized
```
[donap_gravityflow_inbox mobile_responsive="true" per_page="8"]
```
Optimized for mobile users with fewer items per page.

### 5. Complete Dashboard Page
```
<h2>صندوق ورودی گردش کاری</h2>
[donap_gravityflow_inbox per_page="20" show_stats="true" show_filters="true"]

<div style="margin-top: 20px;">
[donap_gravityflow_inbox_export style="buttons" align="center" show_csv="true" show_excel="true" show_pdf="true" button_text="صادرات داده‌ها"]
</div>
```
Complete dashboard with inbox table and export options.

## WordPress Integration

### In Posts/Pages
Simply add the shortcode to any post or page content:

```
<h2>صندوق ورودی گردش کاری</h2>
[donap_gravityflow_inbox per_page="20" show_stats="true"]

<h3>دکمه‌های صادرات</h3>
[donap_gravityflow_inbox_export style="buttons" show_csv="true" show_excel="true" show_pdf="true"]
```

### Export Buttons Usage
The export shortcode supports different styles and options:

#### Button Style (Default)
```
[donap_gravityflow_inbox_export style="buttons" align="right" show_csv="true" show_excel="true" show_pdf="true"]
```

#### Dropdown Style
```
[donap_gravityflow_inbox_export style="dropdown" button_text="صادرات صندوق ورودی" show_csv="true" show_excel="true" show_pdf="true"]
```

#### Export Parameters
| Parameter | Default | Description |
|-----------|---------|-------------|
| `style` | `buttons` | Display style: `buttons` or `dropdown` |
| `align` | `right` | Alignment: `left`, `center`, or `right` |
| `show_csv` | `true` | Show CSV export button |
| `show_excel` | `true` | Show Excel export button |
| `show_pdf` | `true` | Show PDF export button |
| `button_text` | `صادرات صندوق ورودی` | Text label for buttons |
| `user_id` | current user | User ID for export (auto-detected) |

### Export API Endpoints
The following REST API endpoints are available for programmatic access:

- **CSV Export**: `GET /donapp-api/gravity/inbox/export-csv?uid={user_id}`
- **Excel Export**: `GET /donapp-api/gravity/inbox/export-xlsx?uid={user_id}`  
- **PDF Export**: `GET /donapp-api/gravity/inbox/export-pdf?uid={user_id}`

### In Widgets
Use the Text/HTML widget to add the shortcode to sidebars or widget areas.

### In Page Builders
- **Elementor**: Use the Shortcode widget
- **Gutenberg**: Use the Shortcode block
- **Page Builders**: Add through shortcode elements

### In Theme Files
```php
<?php echo do_shortcode('[donap_gravityflow_inbox per_page="15"]'); ?>
<?php echo do_shortcode('[donap_gravityflow_inbox_export style="dropdown"]'); ?>
```

## Sample Output

The inbox shortcode will display:
- 📊 Statistics cards (total, pending, in progress)
- 🔍 Filter dropdowns (status, priority)
- 📋 Responsive data table with:
  - Form name and details
  - Current workflow step
  - Submitter information
  - Creation date
  - Status badges
  - Priority indicators
  - Due dates
  - Entry summaries
  - Action buttons
- 📄 Pagination controls

The export buttons shortcode will display:
- 📥 **CSV Export**: Spreadsheet format with all data
- 📊 **Excel Export**: XLSX format with multiple worksheets (data + statistics)  
- 📄 **PDF Export**: Professional formatted report with charts and statistics
- 🎨 **Multiple Styles**: Button layout or dropdown menu
- 📱 **Responsive Design**: Mobile-optimized interface
- ⚡ **Direct Download**: Files generated and served instantly

## Features Highlight

### Inbox Display
✅ **Fully Responsive** - Works on all devices
✅ **RTL Support** - Persian language optimized  
✅ **Real-time Filtering** - Interactive status and priority filters
✅ **Action Buttons** - Direct integration with Gravity Flow admin
✅ **Beautiful Design** - Modern gradient styling
✅ **Performance Optimized** - Pagination and efficient queries
✅ **Accessibility Ready** - Screen reader friendly
✅ **Error Handling** - Graceful fallbacks and sample data

### Export Capabilities  
✅ **Multiple Formats** - CSV, Excel (XLSX), PDF support
✅ **Rich Excel Export** - Multiple worksheets with data and statistics
✅ **Professional PDF** - Formatted reports with charts and styling
✅ **User-based Export** - Respects user permissions and data access
✅ **Instant Download** - Files generated and served immediately
✅ **Flexible UI** - Button or dropdown display styles
✅ **Mobile Optimized** - Export buttons work on all devices
✅ **Error Handling** - Graceful failures with user-friendly messages

## Next Steps

1. Add the inbox shortcode to your desired page
2. Add export buttons shortcode below or above the table  
3. Test with different parameter combinations
4. Test export functionality with sample data
5. Customize the styling if needed
6. Set up user permissions in Gravity Flow
7. Configure export access controls
8. Train users on the interface and export features

## Export Format Details

### CSV Export
- Single spreadsheet with all inbox entries
- Includes: Entry ID, Form details, Step info, Submitter, Status, Priority, Due dates
- Perfect for: Data analysis, importing to other systems
- File name: `gravity-flow-inbox-YYYY-MM-DD-HH-MM-SS.csv`

### Excel Export  
- **Worksheet 1**: Complete inbox data with all columns
- **Worksheet 2**: Statistics summary (totals, status breakdown, priority analysis, form breakdown)
- Enhanced formatting with colors and styling
- Perfect for: Management reports, detailed analysis
- File name: `gravity-flow-inbox-YYYY-MM-DD-HH-MM-SS.xlsx`

### PDF Export
- Professional report format with header and statistics
- Color-coded status badges and priority indicators  
- Responsive table layout that works on A4/Letter formats
- Statistics cards at the top with visual breakdown
- Perfect for: Print reports, presentations, archiving
- File name: `gravity-flow-inbox-YYYY-MM-DD-HH-MM-SS.pdf`

## URL Examples

Direct export URLs (replace `{user_id}` with actual user ID):
- `https://yoursite.com/donapp-api/gravity/inbox/export-csv?uid={user_id}`
- `https://yoursite.com/donapp-api/gravity/inbox/export-xlsx?uid={user_id}`
- `https://yoursite.com/donapp-api/gravity/inbox/export-pdf?uid={user_id}`

For detailed documentation, see `GRAVITYFLOW_INBOX_SHORTCODE.md`.
