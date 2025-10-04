<?php
/**
 * Session Scores Table View
 * Displays a table of form entries with calculated scores and export functionality
 */

// Ensure we have the required data
if (!isset($entries) || !isset($columns)) {
    echo '<div class="donap-error">خطا در نمایش جدول: داده‌های مورد نیاز در دسترس نیست</div>';
    return;
}
?>

<div class="donap-session-scores-wrapper" dir="rtl">
    <!-- Table Header with Title and Export Controls -->
    <div class="donap-scores-header">
        <h3 class="donap-scores-title"><?php echo esc_html($form_title); ?></h3>
        
        <?php if ($columns['checkbox']): ?>
        <div class="donap-export-controls">
            <div class="donap-selection-info">
                <span id="donap-selected-count">0</span> مورد انتخاب شده
            </div>
            <div class="donap-export-buttons">
                <button type="button" id="donap-select-all" class="donap-btn donap-btn-secondary">
                    انتخاب همه
                </button>
                <button type="button" id="donap-export-selected" class="donap-btn donap-btn-primary" disabled>
                    اکسپورت موارد انتخاب شده
                </button>
                <button type="button" id="donap-export-all" class="donap-btn donap-btn-outline">
                    اکسپورت همه
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Statistics Summary -->
    <?php if (!empty($entries)): ?>
    <div class="donap-stats-summary">
        <div class="donap-stat">
            <span class="donap-stat-label">تعداد کل:</span>
            <span class="donap-stat-value"><?php echo $pagination['total_items']; ?></span>
        </div>
        <?php if (isset($atts['show_sum_column']) && $atts['show_sum_column'] === 'true'): ?>
        <div class="donap-stat">
            <span class="donap-stat-label">میانگین امتیاز:</span>
            <span class="donap-stat-value">
                <?php 
                $total_sum = array_sum(array_column($entries, 'sum_score'));
                $average = count($entries) > 0 ? round($total_sum / count($entries), 2) : 0;
                echo $average;
                ?>
            </span>
        </div>
        <div class="donap-stat">
            <span class="donap-stat-label">بالاترین امتیاز:</span>
            <span class="donap-stat-value">
                <?php echo count($entries) > 0 ? max(array_column($entries, 'sum_score')) : 0; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Main Table -->
    <div class="donap-table-container">
        <?php if (empty($entries)): ?>
            <div class="donap-no-data">
                <p>هیچ داده‌ای برای نمایش وجود ندارد.</p>
            </div>
        <?php else: ?>
            <form id="donap-scores-form">
                <table class="donap-scores-table" cellspacing="0">
                    <thead>
                        <tr>
                            <?php if ($columns['checkbox']): ?>
                            <th class="donap-checkbox-col">
                                <input type="checkbox" id="donap-select-all-checkbox" title="انتخاب همه">
                            </th>
                            <?php endif; ?>
                            
                            <th>ردیف</th>
                            <th>تاریخ ایجاد</th>
                            
                            <?php foreach ($columns as $column_key => $is_visible): ?>
                                <?php if ($is_visible && $column_key !== 'checkbox'): ?>
                                <th class="donap-col-<?php echo sanitize_html_class($column_key); ?>">
                                    <?php echo esc_html($column_key); ?>
                                    <?php if ($column_key === 'جمع امتیازها'): ?>
                                        <span class="donap-sort-indicator" title="مرتب شده بر اساس این ستون">↓</span>
                                    <?php endif; ?>
                                </th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $row_index = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                        foreach ($entries as $entry): 
                        ?>
                        <tr class="donap-entry-row" data-entry-id="<?php echo esc_attr($entry['id']); ?>">
                            <?php if ($columns['checkbox']): ?>
                            <td class="donap-checkbox-col">
                                <input type="checkbox" name="selected_entries[]" value="<?php echo esc_attr($entry['id']); ?>" class="donap-entry-checkbox">
                            </td>
                            <?php endif; ?>
                            
                            <td class="donap-row-number"><?php echo $row_index++; ?></td>
                            <td class="donap-date"><?php echo esc_html($entry['date_created']); ?></td>
                            
                            <?php foreach ($columns as $column_key => $is_visible): ?>
                                <?php if ($is_visible && $column_key !== 'checkbox'): ?>
                                <td class="donap-col-<?php echo sanitize_html_class($column_key); ?>">
                                    <?php 
                                    $value = isset($entry['entry_data'][$column_key]) ? $entry['entry_data'][$column_key] : '';
                                    
                                    // Special formatting for score columns
                                    if (in_array($column_key, ['بهسازی سالن', 'جلسه والدین', 'غنی سازی زنگ تفریح', 'جمع امتیازها'])) {
                                        $score = floatval($value);
                                        echo '<span class="donap-score-value">' . esc_html($score) . '</span>';
                                        
                                        // Add score badge for sum column
                                        if ($column_key === 'جمع امتیازها') {
                                            $badge_class = '';
                                            if ($score >= 240) $badge_class = 'donap-badge-excellent';
                                            elseif ($score >= 200) $badge_class = 'donap-badge-good';
                                            elseif ($score >= 150) $badge_class = 'donap-badge-average';
                                            else $badge_class = 'donap-badge-low';
                                            
                                            echo '<span class="donap-score-badge ' . $badge_class . '"></span>';
                                        }
                                    } else {
                                        echo esc_html($value);
                                    }
                                    ?>
                                </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($atts['show_pagination'] === 'true' && $pagination['total_pages'] > 1): ?>
    <div class="donap-pagination">
        <div class="donap-pagination-info">
            صفحه <?php echo $pagination['current_page']; ?> از <?php echo $pagination['total_pages']; ?>
            (<?php echo $pagination['total_items']; ?> مورد)
        </div>
        
        <div class="donap-pagination-links">
            <?php
            $current_url = add_query_arg(null, null);
            $current_url = remove_query_arg('paged', $current_url);
            
            // Previous page link
            if ($pagination['current_page'] > 1):
                $prev_url = add_query_arg('paged', $pagination['current_page'] - 1, $current_url);
            ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="donap-page-link donap-prev">« قبلی</a>
            <?php endif; ?>
            
            <?php
            // Page number links
            $start_page = max(1, $pagination['current_page'] - 2);
            $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            for ($page = $start_page; $page <= $end_page; $page++):
                if ($page == $pagination['current_page']):
            ?>
                    <span class="donap-page-link donap-current"><?php echo $page; ?></span>
                <?php else:
                    $page_url = add_query_arg('paged', $page, $current_url);
                ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="donap-page-link"><?php echo $page; ?></a>
                <?php endif;
            endfor; ?>
            
            <?php
            // Next page link
            if ($pagination['current_page'] < $pagination['total_pages']):
                $next_url = add_query_arg('paged', $pagination['current_page'] + 1, $current_url);
            ?>
                <a href="<?php echo esc_url($next_url); ?>" class="donap-page-link donap-next">بعدی »</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Column Totals Summary Table -->
    <?php if (isset($atts['show_summary_table']) && $atts['show_summary_table'] === 'true' && !empty($column_totals) && !empty($summable_fields)): ?>
        <div class="donap-summary-section">
            <div class="donap-summary-header">
                <h4 class="donap-summary-title">خلاصه مجموع ستون‌ها (کل <?php echo esc_html($total_entries_count); ?> ورودی)</h4>
                
                <!-- Summary Export Controls -->
                <div class="donap-summary-export-controls">
                    <div class="donap-summary-selection-info">
                        <span id="donap-summary-selected-count">0</span> ستون انتخاب شده
                    </div>
                    <div class="donap-summary-export-buttons">
                        <button type="button" id="donap-summary-select-all" class="donap-btn donap-btn-secondary">
                            انتخاب همه ستون‌ها
                        </button>
                        <button type="button" id="donap-summary-export-selected" class="donap-btn donap-btn-primary" disabled>
                            اکسپورت ستون‌های انتخاب شده
                        </button>
                        <button type="button" id="donap-summary-export-all" class="donap-btn donap-btn-outline">
                            اکسپورت کل خلاصه
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="donap-summary-table-container">
                <table class="donap-summary-table">
                    <thead>
                        <tr>
                            <th class="donap-summary-checkbox-header">
                                <input type="checkbox" id="donap-summary-select-all-checkbox" title="انتخاب همه ستون‌ها">
                            </th>
                            <th class="donap-summary-header">نام ستون</th>
                            <th class="donap-summary-header">مجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summable_fields as $field_info): ?>
                            <tr class="donap-summary-row" data-field-name="<?php echo esc_attr($field_info['field_label']); ?>">
                                <td class="donap-summary-checkbox-cell">
                                    <input type="checkbox" 
                                           class="donap-summary-row-checkbox" 
                                           value="<?php echo esc_attr($field_info['field_label']); ?>"
                                           data-field-total="<?php echo esc_attr($column_totals[$field_info['field_label']] ?? 0); ?>">
                                </td>
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
                        
                        <?php if (isset($atts['show_sum_column']) && $atts['show_sum_column'] === 'true' && isset($column_totals['جمع کل'])): ?>
                            <tr class="donap-summary-row donap-grand-total-row" data-field-name="جمع کل امتیازها">
                                <td class="donap-summary-checkbox-cell">
                                    <input type="checkbox" 
                                           class="donap-summary-row-checkbox" 
                                           value="جمع کل امتیازها"
                                           data-field-total="<?php echo esc_attr($column_totals['جمع کل']); ?>">
                                </td>
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

    <!-- Hidden fields for AJAX -->
    <input type="hidden" id="donap-nonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" id="donap-form-id" value="<?php echo esc_attr($atts['form_id']); ?>">
</div>

<style>
.donap-session-scores-wrapper {
    font-family: 'IRANSans', Tahoma, Arial, sans-serif;
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.donap-scores-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
    background: #f8f9fa;
}

.donap-scores-title {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: bold;
}

.donap-export-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.donap-selection-info {
    font-size: 14px;
    color: #666;
}

.donap-export-buttons {
    display: flex;
    gap: 10px;
}

.donap-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.donap-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.donap-btn-primary {
    background: #007cba;
    color: white;
}

.donap-btn-primary:hover:not(:disabled) {
    background: #005a87;
}

.donap-btn-secondary {
    background: #666;
    color: white;
}

.donap-btn-secondary:hover {
    background: #555;
}

.donap-btn-outline {
    background: transparent;
    border: 1px solid #007cba;
    color: #007cba;
}

.donap-btn-outline:hover {
    background: #007cba;
    color: white;
}

.donap-stats-summary {
    display: flex;
    gap: 30px;
    padding: 15px 20px;
    background: #f0f8ff;
    border-bottom: 1px solid #e1e1e1;
    flex-wrap: wrap;
}

.donap-stat {
    display: flex;
    align-items: center;
    gap: 8px;
}

.donap-stat-label {
    font-weight: 600;
    color: #555;
}

.donap-stat-value {
    font-weight: bold;
    color: #2c3e50;
    background: white;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.donap-table-container {
    overflow-x: auto;
}

.donap-scores-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.donap-scores-table th {
    background: #f8f9fa;
    padding: 12px 8px;
    text-align: center;
    font-weight: bold;
    color: #2c3e50;
    border-bottom: 2px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 1;
}

.donap-scores-table td {
    padding: 10px 8px;
    text-align: center;
    border-bottom: 1px solid #e9ecef;
}

.donap-entry-row:hover {
    background: #f8f9fa;
}

.donap-entry-row:nth-child(even) {
    background: #fafbfc;
}

.donap-entry-row:nth-child(even):hover {
    background: #f0f2f5;
}

.donap-checkbox-col {
    width: 40px;
}

.donap-row-number {
    font-weight: bold;
    color: #666;
    width: 60px;
}

.donap-score-value {
    font-weight: bold;
    color: #2c3e50;
}

.donap-score-badge {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
}

.donap-badge-excellent { background: #28a745; }
.donap-badge-good { background: #17a2b8; }
.donap-badge-average { background: #ffc107; }
.donap-badge-low { background: #dc3545; }

.donap-sort-indicator {
    font-size: 12px;
    margin-right: 4px;
    color: #007cba;
}

.donap-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-top: 1px solid #e1e1e1;
    background: #f8f9fa;
}

.donap-pagination-info {
    color: #666;
    font-size: 14px;
}

.donap-pagination-links {
    display: flex;
    gap: 5px;
}

.donap-page-link {
    padding: 6px 12px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #007cba;
    background: white;
    transition: all 0.2s ease;
}

.donap-page-link:hover {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.donap-page-link.donap-current {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.donap-no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.donap-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    margin: 10px 0;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .donap-scores-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .donap-export-controls {
        flex-direction: column;
        width: 100%;
    }
    
    .donap-export-buttons {
        justify-content: center;
    }
    
    .donap-stats-summary {
        flex-direction: column;
        gap: 10px;
    }
    
    .donap-scores-table {
        font-size: 12px;
    }
    
    .donap-scores-table th,
    .donap-scores-table td {
        padding: 8px 4px;
    }
    
    .donap-pagination {
        flex-direction: column;
        gap: 10px;
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

.donap-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.donap-summary-title {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
}

.donap-summary-export-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.donap-summary-selection-info {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.donap-summary-export-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
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

.donap-summary-checkbox-header,
.donap-summary-checkbox-cell {
    width: 50px;
    text-align: center;
    padding: 8px;
}

.donap-summary-row-checkbox,
#donap-summary-select-all-checkbox {
    transform: scale(1.2);
    cursor: pointer;
}

.donap-summary-row-checkbox:focus,
#donap-summary-select-all-checkbox:focus {
    outline: 2px solid #007cba;
    outline-offset: 2px;
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
    
    .donap-summary-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .donap-summary-title {
        font-size: 16px;
        text-align: center;
    }
    
    .donap-summary-export-controls {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .donap-summary-export-buttons {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .donap-summary-table {
        font-size: 12px;
    }
    
    .donap-summary-table th,
    .donap-summary-table td {
        padding: 8px 4px;
    }
    
    .donap-summary-checkbox-header,
    .donap-summary-checkbox-cell {
        width: 40px;
        padding: 4px;
    }
}
</style>

<script type="text/javascript">
// Extend the donapSessionScores object with view-specific data
if (typeof donapSessionScores !== 'undefined') {
    donapSessionScores.viewId = '<?php echo esc_js($view_id); ?>';
}
</script>