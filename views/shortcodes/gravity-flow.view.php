<div class="donap-gravity-flow-shortcode">
    <?php if (isset($warning_message) && !empty($warning_message)): ?>
        <div class="donap-notice donap-warning">
            <p><?php echo esc_html($warning_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show_stats && !empty($stats)): ?>
        <!-- Statistics Cards -->
        <!-- <div class="donap-stats-grid">
            <div class="donap-stat-card">
                <h3>تعداد کل فرم‌های تأیید شده</h3>
                <div class="donap-stat-value"><?php echo esc_html($stats['total_entries'] ?? 0); ?></div>
            </div>
            <div class="donap-stat-card">
                <h3>تعداد فرم‌های مختلف</h3>
                <div class="donap-stat-value"><?php echo esc_html($stats['forms_count'] ?? 0); ?></div>
            </div>
            <div class="donap-stat-card">
                <h3>فرم‌های این ماه</h3>
                <div class="donap-stat-value"><?php echo esc_html($stats['this_month'] ?? 0); ?></div>
            </div>
            <div class="donap-stat-card">
                <h3>فرم‌های این هفته</h3>
                <div class="donap-stat-value"><?php echo esc_html($stats['this_week'] ?? 0); ?></div>
            </div>
        </div> -->
    <?php endif; ?>

    <?php if ($show_filters || $show_export): ?>
        <!-- Filters and Export Section -->
        <div class="donap-controls-section">
            <?php if ($show_export): ?>
                <div class="donap-export-section">
                <a target="_blank" href="<?php echo rest_url('dnp/v1/gravity/export-csv?uid=' . get_current_user_id()); ?>" 
                       class="donap-btn donap-btn-primary">
                        <span class="donap-icon">⬇</span>
                        خروجی CSV
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($show_filters): ?>
                <form method="get" action="" class="donap-filters-form">
                    <div class="donap-filter-row">
                        <div class="donap-filter-item">
                            <label for="form_filter">فیلتر بر اساس فرم:</label>
                            <select name="form_filter" id="form_filter">
                                <option value="">همه فرم‌ها</option>
                                <?php if (!empty($entries)): ?>
                                    <?php 
                                    $unique_forms = [];
                                    foreach ($entries as $entry) {
                                        if (!isset($unique_forms[$entry['form_id']])) {
                                            $unique_forms[$entry['form_id']] = $entry['form_title'];
                                        }
                                    }
                                    foreach ($unique_forms as $form_id => $form_title): 
                                    ?>
                                        <option value="<?php echo esc_attr($form_id); ?>" 
                                                <?php selected($current_filters['form_filter'], $form_id); ?>>
                                            <?php echo esc_html($form_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="donap-filter-item">
                            <label for="start_date">از تاریخ:</label>
                            <input type="date" name="start_date" id="start_date" 
                                   value="<?php echo esc_attr($current_filters['start_date']); ?>" />
                        </div>
                        
                        <div class="donap-filter-item">
                            <label for="end_date">تا تاریخ:</label>
                            <input type="date" name="end_date" id="end_date" 
                                   value="<?php echo esc_attr($current_filters['end_date']); ?>" />
                        </div>
                        
                        <div class="donap-filter-actions">
                            <input type="submit" class="donap-btn donap-btn-secondary" value="اعمال فیلتر" />
                            <a href="<?php echo esc_url($base_url); ?>" class="donap-btn donap-btn-light">پاک کردن فیلتر</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Entries Table -->
    <div class="donap-table-wrapper">
        <table class="donap-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان فرم</th>
                    <th>تاریخ ایجاد</th>
                    <th>وضعیت</th>
                    <th>اطلاعات فرم</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($entries)): ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($entry['id']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html($entry['form_title']); ?></strong>
                                <br>
                                <small>فرم شماره: <?php echo esc_html($entry['form_id']); ?></small>
                            </td>
                            <td>
                                <?php echo esc_html($entry['date_created']); ?>
                            </td>
                            <td>
                                <span class="donap-status-badge donap-status-approved">
                                    تأیید شده
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($entry['entry_data'])): ?>
                                    <div class="donap-entry-data">
                                        <?php foreach (array_slice($entry['entry_data'], 0, 2) as $field_data): ?>
                                            <div class="donap-field-item">
                                                <strong><?php echo esc_html($field_data['label']); ?>:</strong>
                                                <span><?php echo wp_kses_post($field_data['value']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($entry['entry_data']) > 2): ?>
                                            <small>
                                                و <?php echo count($entry['entry_data']) - 2; ?> فیلد دیگر...
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Hidden full data for modal -->
                                    <div class="donap-full-entry-data" style="display: none;">
                                        <?php foreach ($entry['entry_data'] as $field_data): ?>
                                            <div class="donap-modal-field-item">
                                                <span class="donap-modal-field-label"><?php echo esc_html($field_data['label']); ?></span>
                                                <div class="donap-modal-field-value"><?php echo wp_kses_post($field_data['value']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <em>بدون اطلاعات</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="donap-btn donap-btn-small donap-view-details" 
                                        data-entry-id="<?php echo esc_attr($entry['id']); ?>">
                                    مشاهده
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="donap-no-data">
                            هیچ فرم تأیید شده‌ای یافت نشد.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="donap-pagination">
            <?php
            $current_page = $pagination['current_page'];
            $total_pages = $pagination['total_pages'];
            $base_pagination_url = add_query_arg($current_filters, $base_url);
            
            // Previous page
            if ($current_page > 1):
                $prev_url = add_query_arg('paged', $current_page - 1, $base_pagination_url);
            ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="donap-pagination-link">« قبلی</a>
            <?php endif; ?>
            
            <!-- Page numbers -->
            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="donap-pagination-current"><?php echo $i; ?></span>
                <?php else: ?>
                    <?php $page_url = add_query_arg('paged', $i, $base_pagination_url); ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="donap-pagination-link"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <!-- Next page -->
            <?php if ($current_page < $total_pages): 
                $next_url = add_query_arg('paged', $current_page + 1, $base_pagination_url);
            ?>
                <a href="<?php echo esc_url($next_url); ?>" class="donap-pagination-link">بعدی »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Entry Details Modal -->
    <div id="donap-entry-modal" class="donap-modal" style="display: none;">
        <div class="donap-modal-content">
            <div class="donap-modal-header">
                <h3>جزئیات فرم</h3>
                <span class="donap-modal-close">&times;</span>
            </div>
            <div class="donap-modal-body">
                <div id="donap-entry-details" class="donap-modal-scrollable"></div>
            </div>
        </div>
    </div>
</div>

<style>
.donap-gravity-flow-shortcode {
    direction: rtl;
    text-align: right;
    font-family: Tahoma, Arial, sans-serif;
}

.donap-notice {
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    border-left: 4px solid #ffba00;
}

.donap-warning {
    background: #fff3cd;
    border-left-color: #ffba00;
    color: #856404;
}

.donap-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.donap-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.donap-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.donap-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.donap-controls-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.donap-export-section {
    margin-bottom: 15px;
}

.donap-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.donap-filter-item {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.donap-filter-item label {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 13px;
}

.donap-filter-item select,
.donap-filter-item input {
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

.donap-filter-actions {
    display: flex;
    gap: 10px;
}

.donap-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.donap-btn-primary {
    background: #2271b1;
    color: white;
}

.donap-btn-primary:hover {
    background: #135e96;
    color: white;
}

.donap-btn-secondary {
    background: #50575e;
    color: white;
}

.donap-btn-secondary:hover {
    background: #3c434a;
    color: white;
}

.donap-btn-light {
    background: #f0f0f1;
    color: #50575e;
    border: 1px solid #c3c4c7;
}

.donap-btn-light:hover {
    background: #e0e0e0;
    color: #50575e;
}

.donap-btn-small {
    padding: 4px 8px;
    font-size: 12px;
}

.donap-table-wrapper {
    overflow-x: auto;
    margin: 20px 0;
}

.donap-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.donap-table th,
.donap-table td {
    padding: 12px;
    text-align: right;
    border-bottom: 1px solid #e0e0e0;
}

.donap-table th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.donap-table tr:hover {
    background: #f8f9fa;
}

.donap-status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.donap-status-approved {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.donap-entry-data {
    max-width: 250px;
}

.donap-field-item {
    margin-bottom: 6px;
    padding: 3px 0;
    border-bottom: 1px solid #f0f0f0;
}

.donap-field-item:last-child {
    border-bottom: none;
}

.donap-field-item strong {
    display: block;
    font-size: 11px;
    margin-bottom: 2px;
    color: #666;
}

.donap-no-data {
    text-align: center;
    padding: 40px;
    font-style: italic;
    color: #666;
}

.donap-pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin: 20px 0;
}

.donap-pagination-link,
.donap-pagination-current {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #2271b1;
    border-radius: 3px;
}

.donap-pagination-link:hover {
    background: #f0f0f1;
    color: #2271b1;
}

.donap-pagination-current {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.donap-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.donap-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 5px;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
}

.donap-modal-header {
    padding: 15px 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.donap-modal-header h3 {
    margin: 0;
}

.donap-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.donap-modal-close:hover {
    color: #000;
}

.donap-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.donap-modal-scrollable {
    max-height: 55vh;
    overflow-y: auto;
}

.donap-modal-field-item {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    background: #f9f9f9;
}

.donap-modal-field-item:last-child {
    margin-bottom: 0;
}

.donap-modal-field-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: block;
}

.donap-modal-field-value {
    color: #666;
    word-wrap: break-word;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .donap-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .donap-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .donap-filter-item {
        min-width: auto;
    }
    
    .donap-table-wrapper {
        font-size: 14px;
    }
    
    .donap-table th,
    .donap-table td {
        padding: 8px 4px;
    }
    
    .donap-pagination {
        flex-wrap: wrap;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view details button
    var detailButtons = document.querySelectorAll('.donap-view-details');
    detailButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var entryId = this.getAttribute('data-entry-id');
            var entryRow = this.closest('tr');
            
            // Get full entry data from hidden div
            var fullEntryData = entryRow.querySelector('.donap-full-entry-data');
            var fullEntryDataHtml = fullEntryData ? fullEntryData.innerHTML : '';
            
            if (fullEntryDataHtml && fullEntryDataHtml.trim() !== '') {
                document.getElementById('donap-entry-details').innerHTML = fullEntryDataHtml;
            } else {
                // Fallback to visible data if no full data available
                var entryData = entryRow.querySelector('.donap-entry-data');
                var entryDataHtml = entryData ? entryData.innerHTML : 'بدون اطلاعات اضافی';
                document.getElementById('donap-entry-details').innerHTML = entryDataHtml;
            }
            
            document.getElementById('donap-entry-modal').style.display = 'block';
        });
    });
    
    // Handle modal close
    var modal = document.getElementById('donap-entry-modal');
    var closeBtn = document.querySelector('.donap-modal-close');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Prevent modal content click from closing modal
    var modalContent = document.querySelector('.donap-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
