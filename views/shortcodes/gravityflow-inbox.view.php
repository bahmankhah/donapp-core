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
?>

<div class="donap-gravityflow-inbox-wrapper" id="donap-gravityflow-inbox">
    <!-- Styles -->
    <style>
        .donap-gravityflow-inbox-wrapper {
            font-family: 'Vazir', sans-serif;
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
            }
            
            .donap-gravity-flow-table td {
                display: block;
                border: none;
                padding: 5px 0;
                text-align: right;
            }
            
            .donap-gravity-flow-table td:before {
                content: attr(data-label) ": ";
                font-weight: bold;
                color: #667eea;
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
        <table class="<?php echo esc_attr($table_class); ?>">
            <thead>
                <tr>
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
                                        <?php appLogger(json_encode($action)) ?>
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
                                                data-action="<?php echo esc_attr($action['type']); ?>">
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
        
        params.set('gf_page', 1); // Reset to first page when filtering
        
        window.location.search = params.toString();
    }
    
    // Action buttons
    document.querySelectorAll('.donap-action-btn[data-entry-id]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const entryId = this.dataset.entryId;
            const action = this.dataset.action;
            const actionLabel = this.textContent.trim();
            
            if (confirm(`آیا مطمئن هستید که می‌خواهید "${actionLabel}" را انجام دهید؟`)) {
                // Here you would typically send an AJAX request to process the action
                // For now, we'll just show an alert
                alert(`عملیات "${actionLabel}" برای ورودی ${entryId} در حال پردازش است...`);
                
                // You can implement the actual AJAX call here:
                /*
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'process_gravity_flow_action',
                        entry_id: entryId,
                        flow_action: action,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطا: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('خطا در اتصال به سرور');
                });
                */
            }
        });
    });
});
</script>
