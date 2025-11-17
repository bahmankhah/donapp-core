<?php
/**
 * Gravity Flow Inbox Shortcode Template
 * Displays a beautiful and responsive table of inbox entries
 */

$show_stats = $attributes['show_stats'] === 'true';
$show_filters = $attributes['show_filters'] === 'true';
$show_pagination = $attributes['show_pagination'] === 'true';
$mobile_responsive = $attributes['mobile_responsive'] === 'true';
$table_class = $attributes['table_class'] ?? 'donap-gravity-flow-table';
$show_export_buttons = ($attributes['show_export_buttons'] ?? 'true') === 'true';

?>

<div class="donap-gravityflow-inbox-wrapper" id="donap-gravityflow-inbox">
    <!-- Styles -->
    <style>

        <?php if(isset($_GET['page']) && isset($_GET['view']) && isset($_GET['id']) && isset($_GET['lid'])): ?>
            #donap-gravityflow-inbox{
                display: none;
            }
        <?php endif; ?>
        .gravityflow_wrap{
            display:none;
        }
        .donap-gravityflow-inbox-wrapper {
            font-family: 'iransans', sans-serif;
            direction: rtl;
            margin: 20px 0;
        }
        
        .donap-inbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .donap-inbox-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .donap-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            min-width: 100px;
        }
        
        .donap-stat-card.pending {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        
        .donap-stat-card.in-progress {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        
        .donap-stat-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .donap-stat-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .donap-stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .donap-inbox-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .donap-filter-select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s ease;
        }
        
        .donap-filter-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .donap-gravity-flow-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }
        
        .donap-gravity-flow-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: right;
            font-weight: 600;
            font-size: 14px;
        }
        
        .donap-gravity-flow-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .donap-gravity-flow-table tr:hover td {
            background-color: #f8fafc;
        }
        
        .donap-status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #60a5fa;
        }
        
        .status-user-input {
            background: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #c4b5fd;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .status-complete {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .donap-priority-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 8px;
        }
        
        .priority-1 { background: #10b981; }
        .priority-2 { background: #f59e0b; }
        .priority-3 { background: #ef4444; }
        
        .donap-entry-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .donap-action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .donap-action-btn.view {
            background: #6366f1;
        }
        
        .donap-action-btn.approve {
            background: #10b981;
        }
        
        .donap-action-btn.reject {
            background: #ef4444;
        }
        
        .donap-action-btn.complete {
            background: #8b5cf6;
        }
        
        .donap-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .donap-action-btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Bulk Actions Styles */
        .donap-bulk-actions-form {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        .donap-bulk-actions-form select {
            min-width: 200px;
        }

        #select-all {
            cursor: pointer;
        }

        .entry-checkbox {
            cursor: pointer;
            transform: scale(1.2);
        }

        #selected-count {
            font-weight: 500;
        }

        .donap-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .donap-pagination-btn {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .donap-pagination-btn:hover {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .donap-pagination-btn.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .donap-pagination-info {
            font-size: 14px;
            color: #64748b;
            margin: 0 15px;
        }
        
        .donap-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .donap-empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
            display: block;
        }
        
        .donap-error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #fecaca;
            margin: 20px 0;
            text-align: center;
        }
        
        .donap-error-message i {
            margin-left: 8px;
        }
        
        /* Export Buttons Styles */
        .inbox-export-buttons {
            display: flex;
            align-items: center;
        }
        
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .export-btn-main {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .export-btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .export-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 280px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            z-index: 1000;
            top: 100%;
            right: 0;
            margin-top: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .export-dropdown-content.show {
            display: block;
        }
        
        .export-option {
            display: block;
            padding: 14px 18px;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .export-option:last-child {
            border-bottom: none;
        }
        
        .export-option:hover {
            background: #f8fafc;
            color: #1f2937;
        }
        
        .export-option i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }
        
        .export-option.csv i {
            color: #059669;
        }
        
        .export-option.excel i {
            color: #0ea5e9;
        }
        
        .export-option.pdf i {
            color: #dc2626;
        }
        
        .export-option span {
            font-weight: 500;
            display: block;
            margin-bottom: 2px;
        }
        
        .export-option small {
            color: #6b7280;
            font-size: 12px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .donap-inbox-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .donap-inbox-stats {
                justify-content: center;
            }
            
            .donap-stat-card {
                flex: 1;
                min-width: 80px;
            }
            
            .donap-bulk-actions-form > div {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .donap-bulk-actions-form select,
            .donap-bulk-actions-form button {
                width: 100%;
            }
            
            .donap-gravity-flow-table {
                font-size: 12px;
            }
            
            .donap-gravity-flow-table th,
            .donap-gravity-flow-table td {
                padding: 8px 6px;
            }
            
            .donap-entry-actions {
                flex-direction: column;
                gap: 4px;
            }
            
            .donap-action-btn {
                font-size: 10px;
                padding: 4px 8px;
            }
            
            .donap-pagination {
                justify-content: center;
            }
            
            .donap-pagination-info {
                order: -1;
                margin: 0 0 10px 0;
            }
        }
        
        /* Smaller mobile screens */
        @media (max-width: 480px) {
            .donap-gravity-flow-table thead {
                display: none;
            }
            
            .donap-gravity-flow-table tr {
                display: block;
                margin-bottom: 15px;
                background: white;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                position: relative;
            }
            
            .donap-gravity-flow-table td {
                display: block;
                border: none;
                padding: 5px 0;
                text-align: right;
            }
            
            .donap-gravity-flow-table td:first-child {
                position: absolute;
                top: 15px;
                left: 15px;
            }
            
            .donap-gravity-flow-table td:not(:first-child):before {
                content: attr(data-label) ": ";
                font-weight: bold;
                color: #667eea;
            }
            
            .donap-bulk-actions-form {
                position: sticky;
                top: 10px;
                z-index: 100;
                margin-bottom: 20px;
            }
        }
    </style>
    
    <!-- Header with Stats and Filters -->
    <div class="donap-inbox-header">
        <?php if ($show_stats && !empty($stats)): ?>
            <div class="donap-inbox-stats">
                <div class="donap-stat-card total">
                    <span class="donap-stat-number"><?php echo intval($stats['total'] ?? 0); ?></span>
                    <span class="donap-stat-label">کل موارد</span>
                </div>
                <div class="donap-stat-card pending">
                    <span class="donap-stat-number"><?php echo intval($stats['pending'] ?? 0); ?></span>
                    <span class="donap-stat-label">در انتظار</span>
                </div>
                <div class="donap-stat-card in-progress">
                    <span class="donap-stat-number"><?php echo intval($stats['in_progress'] ?? 0); ?></span>
                    <span class="donap-stat-label">در حال پردازش</span>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($show_export_buttons && !empty($entries)): ?>
        <div class="inbox-export-buttons">
            <div class="export-dropdown">
                <button class="export-btn-main" type="button" onclick="toggleExportDropdown()">
                    <i class="fas fa-download"></i>
                    خروجی فایل
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="export-dropdown-content" id="exportDropdown">
                    <a href="<?= esc_url($inbox_csv_url) ?>" 
                       class="export-option csv" 
                       target="_blank"
                       onclick="handleExport('CSV')">
                        <i class="fas fa-file-csv"></i>
                        <span>خروجی CSV</span>
                        <small>برای Excel و صفحات گسترده</small>
                    </a>
                    <a href="<?= esc_url($inbox_excel_url) ?>" 
                       class="export-option excel" 
                       target="_blank"
                       onclick="handleExport('Excel')">
                        <i class="fas fa-file-excel"></i>
                        <span>خروجی Excel</span>
                        <small>فایل کامل Excel با فرمت‌بندی</small>
                    </a>
                    <a href="<?= esc_url($inbox_pdf_url) ?>" 
                       class="export-option pdf" 
                       target="_blank"
                       onclick="handleExport('PDF')">
                        <i class="fas fa-file-pdf"></i>
                        <span>خروجی PDF</span>
                        <small>فایل قابل چاپ و ارسال</small>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($show_filters): ?>
            <div class="donap-inbox-filters">
                <select class="donap-filter-select" id="status-filter">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending">در انتظار</option>
                    <option value="in_progress">در حال پردازش</option>
                    <option value="user_input">نیاز به ورودی کاربر</option>
                </select>
                
                <select class="donap-filter-select" id="priority-filter">
                    <option value="">همه اولویت‌ها</option>
                    <option value="3">اولویت بالا</option>
                    <option value="2">اولویت متوسط</option>
                    <option value="1">اولویت عادی</option>
                </select>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Table -->
    <?php if (!empty($entries)): ?>
        <!-- Bulk Actions Form -->
        <form id="bulk-action-form" class="donap-bulk-actions-form" style="margin-bottom: 15px;">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select id="bulk-action-select" name="bulk_action" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                    <option value="">انتخاب عملیات گروهی</option>
                    <option value="approve">تأیید</option>
                    <option value="reject">رد</option>
                    <option value="delete">حذف</option>
                    <option value="export">صادرات</option>
                </select>
                <button type="button" id="bulk-action-apply" class="donap-action-btn" style="background: #667eea; padding: 8px 16px; font-size: 14px;" disabled>
                    <i class="fas fa-check"></i>
                    اعمال
                </button>
                <span id="selected-count" style="color: #64748b; font-size: 14px;">0 مورد انتخاب شده</span>
            </div>
        </form>
        
        <table class="<?php echo esc_attr($table_class); ?>">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" title="انتخاب همه">
                    </th>
                    <th>فرم</th>
                    <th>مرحله</th>
                    <th>ارسال‌کننده</th>
                    <th>تاریخ ایجاد</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>مهلت</th>
                    <th>خلاصه</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td data-label="انتخاب">
                            <input type="checkbox" class="entry-checkbox" name="entry_ids[]" value="<?php echo intval($entry['id']); ?>">
                        </td>
                        <td data-label="فرم">
                            <strong><?php echo esc_html($entry['form_title']); ?></strong>
                        </td>
                        <td data-label="مرحله">
                            <?php echo esc_html($entry['step_name']); ?>
                            <br><small style="color: #64748b;"><?php echo esc_html($entry['step_type']); ?></small>
                        </td>
                        <td data-label="ارسال‌کننده">
                            <div>
                                <strong><?php echo esc_html($entry['submitter']['name']); ?></strong>
                                <?php if (!empty($entry['submitter']['email'])): ?>
                                    <br><small style="color: #64748b;"><?php echo esc_html($entry['submitter']['email']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="تاریخ ایجاد">
                            <?php echo esc_html($entry['date_created_formatted']); ?>
                        </td>
                        <td data-label="وضعیت">
                            <span class="donap-status-badge <?php echo esc_attr($entry['status_class']); ?>">
                                <?php echo esc_html($entry['status']); ?>
                            </span>
                        </td>
                        <td data-label="اولویت">
                            <span class="donap-priority-indicator priority-<?php echo intval($entry['priority']); ?>"></span>
                            <?php
                            $priority_labels = [1 => 'عادی', 2 => 'متوسط', 3 => 'بالا'];
                            echo esc_html($priority_labels[$entry['priority']] ?? 'عادی');
                            ?>
                        </td>
                        <td data-label="مهلت">
                            <?php echo $entry['due_date'] ? esc_html($entry['due_date']) : '<span style="color: #64748b;">-</span>'; ?>
                        </td>
                        <td data-label="خلاصه">
                            <small style="color: #64748b;">
                                <?php echo esc_html(wp_trim_words($entry['entry_summary'], 8)); ?>
                            </small>
                        </td>
                        <td data-label="عملیات">
                            <div class="donap-entry-actions">
                                <?php foreach ($entry['actions'] as $action): ?>
                                    <?php if ($action['type'] === 'view' && !empty($action['url'])): ?>
                                        <?php
                                            // Parse the action URL and get its query params
                                            $parsed_url = parse_url($action['url']);
                                            $query_params = [];
                                            if (!empty($parsed_url['query'])) {
                                                parse_str($parsed_url['query'], $query_params);
                                            }
                                            // Merge with current page's query params
                                            $current_params = $_GET;
                                            $merged_params = array_merge($current_params, $query_params);
                                            // Build the new URL (keep current page, update query params)
                                            $new_url = add_query_arg($merged_params, $_SERVER['REQUEST_URI']);
                                        ?>
                                        <a href="<?php echo esc_url($new_url); ?>" 
                                           class="donap-action-btn <?php echo esc_attr($action['type']); ?>"
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                            <?php echo esc_html($action['label']); ?>
                                        </a>
                                    <?php else: ?>
                                        <button type="button"
                                                class="donap-action-btn <?php echo esc_attr($action['type']); ?>"
                                                data-entry-id="<?php echo intval($entry['id']); ?>"
                                                data-action="<?php echo esc_attr($action['type']); ?>"
                                                data-action-label="<?php echo esc_attr($action['label']); ?>">
                                            <?php
                                            $icons = [
                                                'approve' => 'fa-check',
                                                'reject' => 'fa-times',
                                                'complete' => 'fa-check-circle',
                                                'acknowledge' => 'fa-thumbs-up'
                                            ];
                                            $icon = $icons[$action['type']] ?? 'fa-cog';
                                            ?>
                                            <i class="fas <?php echo esc_attr($icon); ?>"></i>
                                            <?php echo esc_html($action['label']); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="donap-empty-state">
            <i class="fas fa-inbox"></i>
            <h3>صندوق ورودی خالی است</h3>
            <p>در حال حاضر هیچ مورد در انتظار بررسی وجود ندارد.</p>
        </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($show_pagination && $pagination['total_pages'] > 1): ?>
        <div class="donap-pagination">
            <div class="donap-pagination-info">
                نمایش <?php echo intval($pagination['current_page']); ?> از <?php echo intval($pagination['total_pages']); ?> 
                (کل <?php echo intval($pagination['total_items']); ?> مورد)
            </div>
            
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?php echo add_query_arg('gf_page', 1); ?>" class="donap-pagination-btn">
                    <i class="fas fa-angle-double-right"></i>
                </a>
                <a href="<?php echo add_query_arg('gf_page', $pagination['current_page'] - 1); ?>" class="donap-pagination-btn">
                    <i class="fas fa-angle-right"></i>
                </a>
            <?php endif; ?>
            
            <?php
            // Show page numbers
            $start_page = max(1, $pagination['current_page'] - 2);
            $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i == $pagination['current_page']): ?>
                    <span class="donap-pagination-btn current"><?php echo intval($i); ?></span>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('gf_page', $i); ?>" class="donap-pagination-btn">
                        <?php echo intval($i); ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="<?php echo add_query_arg('gf_page', $pagination['current_page'] + 1); ?>" class="donap-pagination-btn">
                    <i class="fas fa-angle-left"></i>
                </a>
                <a href="<?php echo add_query_arg('gf_page', $pagination['total_pages']); ?>" class="donap-pagination-btn">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const statusFilter = document.getElementById('status-filter');
    const priorityFilter = document.getElementById('priority-filter');

    const bulkActionUrl = <?php echo wp_json_encode(esc_url_raw($bulk_action_url ?? '')); ?>;

    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            applyFilters();
        });
    }
    
    if (priorityFilter) {
        priorityFilter.addEventListener('change', function() {
            applyFilters();
        });
    }
    
    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        
        if (statusFilter && statusFilter.value) {
            params.set('status_filter', statusFilter.value);
        } else {
            params.delete('status_filter');
        }
        
        if (priorityFilter && priorityFilter.value) {
            params.set('priority_filter', priorityFilter.value);
        } else {
            params.delete('priority_filter');
        }
        
        params.set('page', 1); // Reset to first page when filtering
        
        window.location.search = params.toString();
    }

    // Bulk Actions Functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkActionApply = document.getElementById('bulk-action-apply');
    const selectedCount = document.getElementById('selected-count');

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            entryCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionState();
        });
    }

    // Individual checkbox functionality
    entryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionState();
            
            // Update select-all state
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.entry-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === entryCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < entryCheckboxes.length;
            }
        });
    });

    // Bulk action apply button
    if (bulkActionApply) {
        bulkActionApply.addEventListener('click', function() {
            const selectedEntries = Array.from(document.querySelectorAll('.entry-checkbox:checked')).map(cb => cb.value);
            const action = bulkActionSelect?.value;

            if (!action || selectedEntries.length === 0) {
                alert('لطفاً ابتدا عملیات و موارد مورد نظر را انتخاب کنید.');
                return;
            }

            processBulkAction(action, selectedEntries);
        });
    }

    function updateBulkActionState() {
        const checkedCount = document.querySelectorAll('.entry-checkbox:checked').length;
        
        if (selectedCount) {
            selectedCount.textContent = checkedCount + ' مورد انتخاب شده';
        }
        
        if (bulkActionApply) {
            bulkActionApply.disabled = checkedCount === 0 || !bulkActionSelect?.value;
        }
    }

    // Update bulk action button state when action is selected
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', updateBulkActionState);
    }

    function processBulkAction(action, entryIds) {
        if (!bulkActionUrl) {
            alert('آدرس سرویس عملیات در دسترس نیست.');
            return;
        }

        const actionNames = {
            'approve': 'تأیید',
            'reject': 'رد', 
            'delete': 'حذف',
            'export': 'صادرات'
        };

        const actionName = actionNames[action] || 'پردازش';
        
        if (!confirm(`آیا مطمئن هستید که می‌خواهید "${actionName}" را برای ${entryIds.length} مورد انجام دهید؟`)) {
            return;
        }

        if (bulkActionApply.disabled) {
            return;
        }

        bulkActionApply.disabled = true;
        bulkActionApply.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال انجام...';

        const formData = new URLSearchParams();
        formData.append('bulk_action', action);
        entryIds.forEach(entryId => {
            formData.append('entry_ids[]', entryId);
        });

        fetch(bulkActionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString(),
            credentials: 'same-origin'
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (jsonError) {
                console.warn('Failed to parse JSON response:', jsonError);
            }

            if (response.ok && data && data.success !== false) {
                const message = (data.data && data.data.message) ? data.data.message : `عملیات ${actionName} با موفقیت انجام شد.`;
                alert(message);
                window.location.reload();
                return;
            }

            const errorMessage = (data && data.data && data.data.message) || (data && data.message) || `خطا در انجام عملیات ${actionName}.`;
            throw new Error(errorMessage);
        })
        .catch(error => {
            console.error('Bulk action failed:', error);
            alert(error.message || 'خطا در برقراری ارتباط با سرور.');
            
            // Restore button state
            bulkActionApply.disabled = false;
            bulkActionApply.innerHTML = '<i class="fas fa-check"></i> اعمال';
        });
    }

    function restoreActionButton(button) {
        if (!button) {
            return;
        }

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }

        button.disabled = false;
    }

    function processInboxAction(button, entryId, action, actionLabel) {
        if (!bulkActionUrl) {
            alert('آدرس سرویس عملیات در دسترس نیست.');
            return;
        }

        if (!entryId || !action) {
            alert('اطلاعات عملیات معتبر نیست.');
            return;
        }

        if (!confirm(`آیا مطمئن هستید که می‌خواهید "${actionLabel}" را انجام دهید؟`)) {
            return;
        }

        if (button.disabled) {
            return;
        }

        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال انجام...';

        const formData = new URLSearchParams();
        formData.append('bulk_action', action);
        formData.append('entry_ids[]', entryId);

        fetch(bulkActionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString(),
            credentials: 'same-origin'
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (jsonError) {
                console.warn('Failed to parse JSON response:', jsonError);
            }

            if (response.ok && data && data.success !== false) {
                const message = (data.data && data.data.message) ? data.data.message : 'عملیات با موفقیت انجام شد.';
                alert(message);
                window.location.reload();
                return;
            }

            const errorMessage = (data && data.data && data.data.message) || (data && data.message) || 'خطا در انجام عملیات.';
            throw new Error(errorMessage);
        })
        .catch(error => {
            console.error('Individual action failed:', error);
            alert(error.message || 'خطا در برقراری ارتباط با سرور.');
            restoreActionButton(button);
        });
    }

    // Action buttons
    document.querySelectorAll('.donap-action-btn[data-entry-id]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const entryId = this.dataset.entryId;
            const action = this.dataset.action;
            const actionLabel = this.dataset.actionLabel || this.textContent.trim();

            processInboxAction(this, entryId, action, actionLabel);
        });
    });

    // Export dropdown functionality
    window.toggleExportDropdown = function() {
        const dropdown = document.getElementById('exportDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    };
    
    // Handle export action
    window.handleExport = function(type) {
        console.log('Exporting ' + type + '...');
        // Close dropdown after selection
        const dropdown = document.getElementById('exportDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    };
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('exportDropdown');
        const button = document.querySelector('.export-btn-main');
        
        if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});
</script>
