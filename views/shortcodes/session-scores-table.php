<?php
/**
 * Session Scores Table Shortcode Template
 * 
 * Dynamic table that shows columns configured in GravityView
 * and calculates sum from summable fields only
 * 
 * @var array $entries The processed entries data
 * @var array $pagination Pagination information
 * @var string $form_title The form title
 * @var array $atts Shortcode attributes
 * @var string $nonce Security nonce
 * @var string $view_id GravityView ID
 * @var array $visible_fields All visible fields from GravityView
 * @var array $summable_fields Fields that can be summed
 * @var bool $show_checkboxes Whether to show checkboxes
 * @var bool $show_sum_column Whether to show sum column
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$unique_id = 'donap-session-scores-' . uniqid();
?>

<div id="<?php echo esc_attr($unique_id); ?>" class="donap-session-scores-wrapper">
    
    <!-- Header with title and export controls -->
    <div class="donap-header">
        <h3 class="donap-table-title"><?php echo esc_html($form_title); ?></h3>
        
        <?php if ($show_checkboxes && !empty($entries)): ?>
        <div class="donap-export-controls">
            <div class="donap-select-all-wrapper">
                <button type="button" id="donap-select-all" class="donap-select-all-btn">
                    <input type="checkbox" id="donap-select-all-checkbox" />
                    <span id="donap-select-all-text">انتخاب همه</span>
                </button>
            </div>
            
            <button type="button" id="donap-export-selected" class="donap-export-btn">
                <span class="donap-export-icon">📥</span>
                اکسپورت انتخاب شده
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($entries)): ?>
        <div class="donap-no-entries">
            <p>هیچ داده‌ای برای نمایش یافت نشد.</p>
        </div>
    <?php else: ?>
        
        <!-- Main Table -->
        <div class="donap-table-container">
            <table class="donap-session-scores-table">
                <thead>
                    <tr>
                        <?php if ($show_checkboxes): ?>
                            <th class="donap-checkbox-col">
                                <span>انتخاب</span>
                            </th>
                        <?php endif; ?>
                        
                        <?php foreach ($visible_fields as $field): ?>
                            <th class="donap-field-<?php echo esc_attr($field['field_id']); ?>">
                                <?php echo esc_html($field['field_label']); ?>
                                <?php if ($field['is_summable']): ?>
                                    <span class="donap-summable-indicator" title="قابل جمع">*</span>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        
                        <?php if ($show_sum_column): ?>
                            <th class="donap-sum-col">جمع امتیازها</th>
                        <?php endif; ?>
                        
                        <th class="donap-date-col">تاریخ ایجاد</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr class="donap-entry-row" data-entry-id="<?php echo esc_attr($entry['id']); ?>">
                            
                            <?php if ($show_checkboxes): ?>
                                <td class="donap-checkbox-cell">
                                    <input type="checkbox" 
                                           class="donap-entry-checkbox" 
                                           value="<?php echo esc_attr($entry['id']); ?>"
                                           name="selected_entries[]" />
                                </td>
                            <?php endif; ?>
                            
                            <?php foreach ($visible_fields as $field): ?>
                                <td class="donap-field-cell donap-field-<?php echo esc_attr($field['field_id']); ?>">
                                    <?php 
                                    $field_value = $entry['entry_data'][$field['field_label']] ?? '';
                                    echo esc_html($field_value);
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            
                            <?php if ($show_sum_column): ?>
                                <td class="donap-sum-cell">
                                    <strong><?php echo esc_html($entry['sum_score']); ?></strong>
                                </td>
                            <?php endif; ?>
                            
                            <td class="donap-date-cell">
                                <?php echo esc_html($entry['date_created']); ?>
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
                <?php if (!empty($summable_fields) && $show_sum_column): ?>
                <tfoot>
                    <tr class="donap-totals-row">
                        
                        <?php if ($show_checkboxes): ?>
                            <td class="donap-total-label">مجموع:</td>
                        <?php endif; ?>
                        
                        <?php foreach ($visible_fields as $field): ?>
                            <td class="donap-total-cell">
                                <?php if ($field['is_summable']): ?>
                                    <?php 
                                    $field_total = 0;
                                    foreach ($entries as $entry) {
                                        $field_value = $entry['entry_data'][$field['field_label']] ?? '';
                                        if (is_numeric($field_value)) {
                                            $field_total += floatval($field_value);
                                        }
                                    }
                                    echo '<strong>' . esc_html($field_total) . '</strong>';
                                    ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        
                        <?php if ($show_sum_column): ?>
                            <td class="donap-grand-total">
                                <strong>
                                    <?php 
                                    $grand_total = 0;
                                    foreach ($entries as $entry) {
                                        $grand_total += $entry['sum_score'];
                                    }
                                    echo esc_html($grand_total);
                                    ?>
                                </strong>
                            </td>
                        <?php endif; ?>
                        
                        <td></td> <!-- Date column -->
                        
                    </tr>
                </tfoot>
                <?php endif; ?>
                
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($atts['show_pagination'] === 'true' && $pagination['total_pages'] > 1): ?>
            <div class="donap-pagination">
                <?php
                $current_page = $pagination['current_page'];
                $total_pages = $pagination['total_pages'];
                $base_url = add_query_arg(array());
                
                // Previous page
                if ($current_page > 1) {
                    $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
                    echo '<a href="' . esc_url($prev_url) . '" class="donap-page-link donap-prev">« قبلی</a>';
                }
                
                // Page numbers
                for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                    if ($i == $current_page) {
                        echo '<span class="donap-page-current">' . $i . '</span>';
                    } else {
                        $page_url = add_query_arg('paged', $i, $base_url);
                        echo '<a href="' . esc_url($page_url) . '" class="donap-page-link">' . $i . '</a>';
                    }
                }
                
                // Next page
                if ($current_page < $total_pages) {
                    $next_url = add_query_arg('paged', $current_page + 1, $base_url);
                    echo '<a href="' . esc_url($next_url) . '" class="donap-page-link donap-next">بعدی »</a>';
                }
                ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Column Totals Summary Table -->
    <?php if ($atts['show_summary_table'] === 'true' && !empty($column_totals) && !empty($summable_fields)): ?>
        <div class="donap-summary-section">
            <h4 class="donap-summary-title">خلاصه مجموع ستون‌ها (کل <?php echo esc_html($total_entries_count); ?> ورودی)</h4>
            <div class="donap-summary-table-container">
                <table class="donap-summary-table">
                    <thead>
                        <tr>
                            <th class="donap-summary-header">نام ستون</th>
                            <th class="donap-summary-header">مجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summable_fields as $field_info): ?>
                            <tr class="donap-summary-row">
                                <td class="donap-summary-field-name">
                                    <?php echo esc_html($field_info['field_label']); ?>
                                </td>
                                <td class="donap-summary-field-total">
                                    <strong>
                                        <?php 
                                        $total = $column_totals[$field_info['field_label']] ?? 0;
                                        echo esc_html(number_format($total, 2));
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($atts['show_sum_column'] === 'true' && isset($column_totals['جمع کل'])): ?>
                            <tr class="donap-summary-row donap-grand-total-row">
                                <td class="donap-summary-field-name">
                                    <strong>جمع کل امتیازها</strong>
                                </td>
                                <td class="donap-summary-field-total">
                                    <strong class="donap-grand-total-value">
                                        <?php echo esc_html(number_format($column_totals['جمع کل'], 2)); ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loading overlay -->
    <div id="donap-loading-overlay" class="donap-loading-overlay" style="display: none;">
        <div class="donap-spinner"></div>
        <p>در حال اکسپورت...</p>
    </div>

</div>

<style>
.donap-session-scores-wrapper {
    direction: rtl;
    text-align: right;
    font-family: tahoma, arial, sans-serif;
    margin: 20px 0;
    position: relative;
}

.donap-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.donap-table-title {
    margin: 0;
    color: #333;
    font-size: 1.4em;
}

.donap-export-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.donap-select-all-wrapper {
    display: flex;
    align-items: center;
}

.donap-select-all-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.donap-select-all-btn:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.donap-export-btn {
    background: linear-gradient(135deg, #007cba 0%, #0073aa 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.donap-export-btn:hover {
    background: linear-gradient(135deg, #005a87 0%, #004973 100%);
    transform: translateY(-1px);
}

.donap-export-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.donap-table-container {
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

.donap-session-scores-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.donap-session-scores-table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #333;
    font-weight: bold;
    padding: 12px 10px;
    text-align: center;
    border-bottom: 2px solid #007cba;
    white-space: nowrap;
}

.donap-session-scores-table td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.donap-entry-row:nth-child(even) {
    background-color: #f9f9f9;
}

.donap-entry-row:hover {
    background-color: #e3f2fd;
}

.donap-checkbox-col {
    width: 60px;
}

.donap-sum-col,
.donap-sum-cell {
    background-color: rgba(40, 167, 69, 0.1);
    color: #155724;
    font-weight: bold;
}

.donap-date-col {
    width: 120px;
}

.donap-summable-indicator {
    color: #28a745;
    font-weight: bold;
    margin-left: 4px;
}

.donap-totals-row {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    font-weight: bold;
}

.donap-totals-row td {
    border-top: 2px solid #28a745;
    border-bottom: none;
    padding: 12px 10px;
}

.donap-grand-total {
    background-color: rgba(40, 167, 69, 0.2);
    font-size: 1.1em;
}

.donap-pagination {
    margin-top: 20px;
    text-align: center;
}

.donap-page-link,
.donap-page-current {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.donap-page-link:hover {
    background-color: #f5f5f5;
}

.donap-page-current {
    background-color: #007cba;
    color: white;
    border-color: #007cba;
}

.donap-no-entries {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}

.donap-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.donap-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007cba;
    border-radius: 50%;
    animation: donap-spin 1s linear infinite;
}

@keyframes donap-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.donap-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    margin: 10px 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .donap-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .donap-export-controls {
        justify-content: space-between;
    }
    
    .donap-table-container {
        font-size: 12px;
    }
    
    .donap-session-scores-table th,
    .donap-session-scores-table td {
        padding: 8px 5px;
    }
}

/* Summary Table Styles */
.donap-summary-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.donap-summary-title {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
}

.donap-summary-table-container {
    overflow-x: auto;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.donap-summary-table {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    border-collapse: collapse;
    background: white;
    font-size: 14px;
}

.donap-summary-table th {
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: white;
    font-weight: bold;
    padding: 12px 15px;
    text-align: center;
    border-bottom: 2px solid #004973;
}

.donap-summary-table td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.donap-summary-row:nth-child(even) {
    background-color: #f8f9fa;
}

.donap-summary-row:hover {
    background-color: #e3f2fd;
    transition: background-color 0.2s ease;
}

.donap-summary-field-name {
    font-weight: 500;
    color: #495057;
    text-align: right;
    width: 60%;
}

.donap-summary-field-total {
    font-size: 16px;
    color: #28a745;
    width: 40%;
}

.donap-grand-total-row {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%) !important;
    border-top: 2px solid #28a745;
}

.donap-grand-total-row .donap-summary-field-name {
    color: #155724;
    font-weight: bold;
}

.donap-grand-total-value {
    color: #155724 !important;
    font-size: 18px;
    font-weight: bold;
}

/* Responsive design for summary table */
@media (max-width: 768px) {
    .donap-summary-section {
        margin-top: 20px;
        padding: 15px;
    }
    
    .donap-summary-title {
        font-size: 16px;
    }
    
    .donap-summary-table {
        font-size: 12px;
    }
    
    .donap-summary-table th,
    .donap-summary-table td {
        padding: 10px 8px;
    }
}
</style>

<script>
// Pass view_id to JavaScript via inline script
window.donapSessionScoresData = window.donapSessionScoresData || {};
window.donapSessionScoresData.viewId = '<?php echo esc_js($view_id); ?>';
window.donapSessionScoresData.tableId = '<?php echo esc_js($unique_id); ?>';
</script>