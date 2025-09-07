<div class="wrap donap-admin-page">
    <h1>فرم‌های تأیید شده گرویتی فلو</h1>
    
    <?php if (isset($error)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($warning_message) && !empty($warning_message)): ?>
        <div class="notice notice-warning">
            <p><?php echo esc_html($warning_message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="donap-dashboard-grid">
        <?php
        /* 
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل فرم‌های تأیید شده',
            'value' => $stats['total_entries'] ?? 0
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'تعداد فرم‌های مختلف',
            'value' => $stats['forms_count'] ?? 0
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'فرم‌های این ماه',
            'value' => $stats['this_month'] ?? 0
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'فرم‌های این هفته',
            'value' => $stats['this_week'] ?? 0
        ]);
        */
        ?>
    </div>

    <!-- Export and Filter Section -->
    <div class="donap-filters-section">
        <div class="donap-filters-header">
            <h2>فیلترها و عملیات</h2>
            <div class="donap-export-section">
                <a href="<?php echo rest_url('dnp/v1/gravity/export-csv?nonce=' . urlencode($export_nonce)); ?>" 
                   class="button button-primary donap-export-btn">
                    <span class="dashicons dashicons-download"></span>
                    خروجی CSV
                </a>
            </div>
        </div>
        
        <form method="get" action="" class="donap-filters-form">
            <input type="hidden" name="page" value="donap-gravity-flow" />
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
                    <input type="submit" class="button" value="اعمال فیلتر" />
                    <a href="<?php echo admin_url('admin.php?page=donap-gravity-flow'); ?>" 
                       class="button">پاک کردن فیلتر</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Entries Table -->
    <div class="donap-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">شناسه</th>
                    <th scope="col" class="manage-column">عنوان فرم</th>
                    <th scope="col" class="manage-column">تاریخ ایجاد</th>
                    <th scope="col" class="manage-column">وضعیت</th>
                    <th scope="col" class="manage-column">اطلاعات فرم</th>
                    <th scope="col" class="manage-column">عملیات</th>
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
                                        <?php foreach (array_slice($entry['entry_data'], 0, 3) as $field_data): ?>
                                            <div class="donap-field-item">
                                                <strong><?php echo esc_html($field_data['label']); ?>:</strong>
                                                <span><?php echo wp_kses_post($field_data['value']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($entry['entry_data']) > 3): ?>
                                            <small>
                                                و <?php echo count($entry['entry_data']) - 3; ?> فیلد دیگر...
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
                                <div class="donap-entry-actions">
                                    <button type="button" class="button button-small donap-view-details" 
                                            data-entry-id="<?php echo esc_attr($entry['id']); ?>">
                                        مشاهده جزئیات
                                    </button>
                                </div>
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
        <?php 
        echo view('admin/components/pagination', [
            'pagination' => $pagination,
            'base_url' => admin_url('admin.php?page=donap-gravity-flow'),
            'current_filters' => $current_filters
        ]);
        ?>
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

</div><style>
.donap-filters-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.donap-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.donap-filters-header h2 {
    margin: 0;
}

.donap-export-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
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
}

.donap-filter-actions {
    display: flex;
    gap: 10px;
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
    max-width: 300px;
}

.donap-field-item {
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}

.donap-field-item:last-child {
    border-bottom: none;
}

.donap-field-item strong {
    display: block;
    font-size: 12px;
    margin-bottom: 2px;
}

.donap-entry-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.donap-no-data {
    text-align: center;
    padding: 40px;
    font-style: italic;
    color: #666;
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
    .donap-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .donap-filter-item {
        min-width: auto;
    }
    
    .donap-filters-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle view details button
    $('.donap-view-details').on('click', function() {
        var entryId = $(this).data('entry-id');
        var entryRow = $(this).closest('tr');
        
        // Get full entry data from hidden div
        var fullEntryData = entryRow.find('.donap-full-entry-data').html();
        
        if (fullEntryData && fullEntryData.trim() !== '') {
            $('#donap-entry-details').html(fullEntryData);
        } else {
            // Fallback to visible data if no full data available
            var entryData = entryRow.find('.donap-entry-data').html();
            $('#donap-entry-details').html(entryData || 'بدون اطلاعات اضافی');
        }
        
        $('#donap-entry-modal').show();
    });
    
    // Handle modal close
    $('.donap-modal-close, .donap-modal').on('click', function(e) {
        if (e.target === this) {
            $('#donap-entry-modal').hide();
        }
    });
    
    // Prevent modal content click from closing modal
    $('.donap-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
