<?php
// Enhanced Gravity Flow Inbox Shortcode View with Integrated Export Buttons
// File: views/shortcodes/gravity-flow-inbox.view.php

$mobile_responsive = ($attributes['mobile_responsive'] === 'true');
$show_bulk_actions = ($attributes['show_bulk_actions'] === 'true');
$show_filters = ($attributes['show_filters'] === 'true');
$show_pagination = ($attributes['show_pagination'] === 'true');
$show_export_buttons = ($attributes['show_export_buttons'] ?? 'true') === 'true';
$table_class = $attributes['table_class'];

// Get current user ID for export URLs
$current_user = wp_get_current_user();
$user_id = $current_user ? $current_user->ID : 0;

// Get current page URL for view links
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$current_url = remove_query_arg(['view', 'id', 'lid'], $current_url);

// Export URLs
$base_url = home_url('donapp-api');
$inbox_csv_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-csv');
$inbox_excel_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-xlsx');
$inbox_pdf_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-pdf');

// Status translations
$status_labels = [
    'pending' => 'در انتظار بررسی',
    'in_progress' => 'در حال بررسی',
    'completed' => 'تکمیل شده',
    'rejected' => 'رد شده',
    'approved' => 'تأیید شده'
];

// Status colors
$status_colors = [
    'pending' => '#f56e28',
    'in_progress' => '#0073aa',
    'completed' => '#46b450',
    'rejected' => '#dc3232',
    'approved' => '#46b450'
];
?>

<div class="donap-gravity-flow-inbox-wrapper" dir="rtl">
    
    <!-- Header with Export Buttons -->
    <div class="inbox-header">
        <div class="inbox-title">
            <h3>
                <i class="fas fa-inbox"></i>
                صندوق ورودی گردش کار
            </h3>
            <span class="entry-count"><?= $pagination['total_items'] ?? 0 ?> ورودی</span>
        </div>
        <?php echo $show_export_buttons ?>
        <?php if ($show_export_buttons && !empty($entries)): ?>
        <div class="inbox-export-buttons">
            <div class="export-dropdown">
                <button class="export-btn-main" type="button" onclick="toggleExportDropdown()">
                    <i class="fas fa-download"></i>
                    صادرات فایل
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="export-dropdown-content" id="exportDropdown">
                    <a href="<?= esc_url($inbox_csv_url) ?>" 
                       class="export-option csv" 
                       target="_blank"
                       onclick="handleExport('CSV')">
                        <i class="fas fa-file-csv"></i>
                        <span>صادرات CSV</span>
                        <small>برای Excel و صفحات گسترده</small>
                    </a>
                    <a href="<?= esc_url($inbox_excel_url) ?>" 
                       class="export-option excel" 
                       target="_blank"
                       onclick="handleExport('Excel')">
                        <i class="fas fa-file-excel"></i>
                        <span>صادرات Excel</span>
                        <small>فایل کامل Excel با فرمت‌بندی</small>
                    </a>
                    <a href="<?= esc_url($inbox_pdf_url) ?>" 
                       class="export-option pdf" 
                       target="_blank"
                       onclick="handleExport('PDF')">
                        <i class="fas fa-file-pdf"></i>
                        <span>صادرات PDF</span>
                        <small>فایل قابل چاپ و ارسال</small>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($show_filters): ?>
    <!-- Filters Section -->
    <div class="gravity-flow-filters">
        <form method="get" class="filter-form">
            <div class="filter-row">
                <div class="filter-item">
                    <label for="gf_status_filter">وضعیت:</label>
                    <select name="gf_status" id="gf_status_filter">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending" <?= ($_GET['gf_status'] ?? '') === 'pending' ? 'selected' : '' ?>>در
                            انتظار بررسی</option>
                        <option value="in_progress"
                            <?= ($_GET['gf_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>در حال بررسی</option>
                        <option value="completed" <?= ($_GET['gf_status'] ?? '') === 'completed' ? 'selected' : '' ?>>
                            تکمیل شده</option>
                        <option value="rejected" <?= ($_GET['gf_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>رد
                            شده</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="gf_form_filter">فرم:</label>
                    <select name="gf_form_id" id="gf_form_filter">
                        <option value="">همه فرم‌ها</option>
                        <?php if (isset($available_forms)): ?>
                        <?php foreach ($available_forms as $form): ?>
                        <option value="<?= $form['id'] ?>"
                            <?= ($_GET['gf_form_id'] ?? '') == $form['id'] ? 'selected' : '' ?>>
                            <?= esc_html($form['title']) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="gf_search">جستجو:</label>
                    <input type="text" name="gf_search" id="gf_search" value="<?= esc_attr($_GET['gf_search'] ?? '') ?>"
                        placeholder="نام ارسال کننده یا محتوای فرم...">
                </div>

                <div class="filter-item">
                    <button type="submit" class="button">اعمال فیلتر</button>
                    <a href="<?= remove_query_arg(['gf_status', 'gf_form_id', 'gf_search', 'gf_page']) ?>"
                        class="button">حذف فیلتر</a>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Bulk Actions -->
    <?php if ($show_bulk_actions && !empty($entries)): ?>
    <div class="gravity-flow-bulk-actions">
        <form method="post" id="bulk-action-form">
            <input type="hidden" name="action" value="gravity_flow_bulk_action">
            <input type="hidden" name="_wpnonce" value="<?= $nonce ?>">

            <div class="bulk-actions-bar">
                <div class="bulk-select">
                    <label>
                        <input type="checkbox" id="select-all-entries" />
                        انتخاب همه
                    </label>
                </div>

                <div class="bulk-actions-dropdown">
                    <select name="bulk_action" id="bulk_action">
                        <option value="">عملیات دسته‌جمعی</option>
                        <option value="approve">تأیید</option>
                        <option value="reject">رد</option>
                        <option value="delete">حذف</option>
                        <option value="export">صادرات</option>
                    </select>
                    <button type="submit" class="button" id="bulk-action-submit">اعمال</button>
                </div>

                <div class="results-count">
                    <?= $pagination['total_items'] ?> نتیجه یافت شد
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Enhanced Table -->
    <div class="gravity-flow-table-container <?= $mobile_responsive ? 'mobile-responsive' : '' ?>">
        <?php if (!empty($entries)): ?>
        <table class="<?= $table_class ?> wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <?php if ($show_bulk_actions): ?>
                    <th class="check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </th>
                    <?php endif; ?>
                    <th class="column-form-name sortable">
                        <a
                            href="<?= add_query_arg(['sort' => 'form_name', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                            نام فرم
                            <?php if (($_GET['sort'] ?? '') === 'form_name'): ?>
                            <span
                                class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="column-status sortable">
                        <a
                            href="<?= add_query_arg(['sort' => 'status', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                            وضعیت
                            <?php if (($_GET['sort'] ?? '') === 'status'): ?>
                            <span
                                class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="column-submitter sortable">
                        <a
                            href="<?= add_query_arg(['sort' => 'submitter', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                            ارسال کننده
                            <?php if (($_GET['sort'] ?? '') === 'submitter'): ?>
                            <span
                                class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="column-date sortable">
                        <a
                            href="<?= add_query_arg(['sort' => 'date_created', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                            زمان ارسال
                            <?php if (($_GET['sort'] ?? '') === 'date_created'): ?>
                            <span
                                class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="column-actions">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                <?php
                    // Generate view URL for this entry
                    $view_url = add_query_arg([
                        'view' => 'entry',
                        'id' => $entry['form_id'],
                        'lid' => $entry['id']
                    ], $current_url);
                ?>
                                <tr class="entry-row" data-entry-id="<?= $entry['id'] ?>" data-form-id="<?= $entry['form_id'] ?>">>
                    <?php if ($show_bulk_actions): ?>
                    <th class="check-column">
                        <input type="checkbox" name="entry_ids[]" value="<?= $entry['id'] ?>" class="entry-checkbox" />
                    </th>
                    <?php endif; ?>

                    <!-- Form Name Column -->
                    <td class="column-form-name" data-colname="نام فرم">
                        <strong class="form-title">
                            <a href="<?= esc_url($view_url) ?>" class="entry-view-link">
                                <?= esc_html($entry['form_name']) ?>
                            </a>
                        </strong>
                        <?php if ($mobile_responsive): ?>
                        <div class="mobile-meta">
                            <span class="mobile-status"
                                style="color: <?= $status_colors[$entry['status']] ?? '#666' ?>">
                                <?= $status_labels[$entry['status']] ?? $entry['status'] ?>
                            </span>
                            <span class="mobile-submitter">توسط: <?= esc_html($entry['submitter']['name']) ?></span>
                            <span class="mobile-date"><?= $entry['date_created_formatted'] ?></span>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Status Column -->
                    <td class="column-status <?= !$mobile_responsive ? '' : 'mobile-hidden' ?>" data-colname="وضعیت">
                        <span class="status-badge status-<?= $entry['status'] ?>"
                            style="color: <?= $status_colors[$entry['status']] ?? '#666' ?>">
                            <?= $status_labels[$entry['status']] ?? $entry['status'] ?>
                        </span>
                    </td>

                    <!-- Submitter Column -->
                    <td class="column-submitter <?= !$mobile_responsive ? '' : 'mobile-hidden' ?>"
                        data-colname="ارسال کننده">
                        <div class="submitter-info">
                            <span class="submitter-name"><?= esc_html($entry['submitter']['name']) ?></span>
                            <?php if (!empty($entry['submitter']['email'])): ?>
                            <span class="submitter-email"><?= esc_html($entry['submitter']['email']) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Date Column -->
                    <td class="column-date <?= !$mobile_responsive ? '' : 'mobile-hidden' ?>" data-colname="زمان ارسال">
                        <span class="entry-date" title="<?= esc_attr($entry['date_created']) ?>">
                            <?= $entry['date_created_formatted'] ?>
                        </span>
                    </td>

                    <!-- Actions Column -->
                    <td class="column-actions" data-colname="عملیات">
                        <div class="row-actions">
                            <span class="view">
                                <a href="<?= esc_url($view_url) ?>" class="entry-view-action">نمایش</a> |
                            </span>
                            <?php if (in_array('approve', $entry['actions'] ?? [])): ?>
                            <span class="approve">
                                <a href="#" class="entry-approve-action" data-entry-id="<?= $entry['id'] ?>">تأیید</a> |
                            </span>
                            <?php endif; ?>
                            <?php if (in_array('reject', $entry['actions'] ?? [])): ?>
                            <span class="reject">
                                <a href="#" class="entry-reject-action" data-entry-id="<?= $entry['id'] ?>">رد</a> |
                            </span>
                            <?php endif; ?>
                            <?php if (in_array('export', $entry['actions'] ?? [])): ?>
                            <span class="export">
                                <div class="entry-export-dropdown">
                                    <a href="#" class="entry-export-toggle" data-entry-id="<?= $entry['id'] ?>">
                                        صادرات <i class="fas fa-chevron-down"></i>
                                    </a>
                                    <div class="entry-export-options">
                                        <a href="<?= add_query_arg(['entry_id' => $entry['id'], 'form_id' => $entry['form_id']], $base_url . '/gravity/entry/export-pdf') ?>" 
                                           target="_blank"
                                           class="export-option-item pdf">
                                            <i class="fas fa-file-pdf"></i>
                                            PDF
                                        </a>
                                        <a href="<?= add_query_arg(['entry_id' => $entry['id'], 'form_id' => $entry['form_id']], $base_url . '/gravity/entry/export-excel') ?>" 
                                           target="_blank"
                                           class="export-option-item excel">
                                            <i class="fas fa-file-excel"></i>
                                            Excel
                                        </a>
                                    </div>
                                </div>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-entries">
            <div class="no-entries-icon">📋</div>
            <h3>هیچ ورودی یافت نشد</h3>
            <p>در حال حاضر هیچ ورودی در صندوق گردش کاری وجود ندارد.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($show_pagination && $pagination['total_pages'] > 1): ?>
    <div class="gravity-flow-pagination">
        <div class="pagination-info">
            نمایش <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> تا
            <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_items']) ?> از
            <?= $pagination['total_items'] ?> ورودی
        </div>

        <div class="pagination-links">
            <?php
                $base_url = remove_query_arg('gf_page');
                $current_page = $pagination['current_page'];
                $total_pages = $pagination['total_pages'];
                ?>

            <?php if ($current_page > 1): ?>
            <a href="<?= add_query_arg('gf_page', 1, $base_url) ?>" class="page-link first-page">« اول</a>
            <a href="<?= add_query_arg('gf_page', $current_page - 1, $base_url) ?>" class="page-link prev-page">‹
                قبلی</a>
            <?php endif; ?>

            <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
            <?php if ($i == $current_page): ?>
            <span class="page-link current-page"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= add_query_arg('gf_page', $i, $base_url) ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
            <a href="<?= add_query_arg('gf_page', $current_page + 1, $base_url) ?>" class="page-link next-page">بعدی
                ›</a>
            <a href="<?= add_query_arg('gf_page', $total_pages, $base_url) ?>" class="page-link last-page">آخر »</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
/* Enhanced Gravity Flow Inbox Styles */
.donap-gravity-flow-inbox-wrapper {
    direction: rtl;
    font-family: 'Vazir', 'IRANSans', Tahoma, Arial, sans-serif;
}

/* Header with Export Buttons */
.inbox-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
}

.inbox-title h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.inbox-title h3 i {
    font-size: 28px;
    opacity: 0.9;
}

.entry-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    margin-top: 5px;
    display: inline-block;
}

/* Export Buttons Styles */
.inbox-export-buttons {
    position: relative;
}

.export-dropdown {
    position: relative;
    display: inline-block;
}

.export-btn-main {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.export-btn-main:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.export-dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 280px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    z-index: 1000;
    margin-top: 8px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.export-dropdown-content.show {
    display: block;
    animation: dropdownFadeIn 0.3s ease-out;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.export-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
    position: relative;
}

.export-option:last-child {
    border-bottom: none;
}

.export-option:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateX(-2px);
}

.export-option i {
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.export-option.csv i { color: #10b981; }
.export-option.excel i { color: #059669; }
.export-option.pdf i { color: #dc2626; }

.export-option span {
    font-weight: 600;
    font-size: 14px;
}

.export-option small {
    color: #64748b;
    font-size: 12px;
    display: block;
    margin-top: 2px;
}

.export-option::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: transparent;
    transition: background 0.2s ease;
}

.export-option.csv:hover::before { background: #10b981; }
.export-option.excel:hover::before { background: #059669; }
.export-option.pdf:hover::before { background: #dc2626; }

/* Entry Export Dropdown */
.entry-export-dropdown {
    position: relative;
    display: inline-block;
}

.entry-export-toggle {
    color: #0073aa;
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.entry-export-toggle:hover {
    color: #005177;
}

.entry-export-options {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 120px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    z-index: 100;
    margin-top: 4px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.entry-export-dropdown:hover .entry-export-options {
    display: block;
}

.export-option-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    color: #374151;
    text-decoration: none;
    font-size: 12px;
    transition: background 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
}

.export-option-item:last-child {
    border-bottom: none;
}

.export-option-item:hover {
    background: #f8fafc;
}

.export-option-item.pdf i { color: #dc2626; }
.export-option-item.excel i { color: #059669; }

/* Loading States */
.export-loading {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 20px 30px;
    border-radius: 8px;
    z-index: 10000;
    backdrop-filter: blur(5px);
}

.export-loading.show {
    display: flex;
    align-items: center;
    gap: 10px;
}

.export-loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Filters */
.gravity-flow-filters {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-item label {
    font-weight: 600;
    font-size: 13px;
    color: #374151;
}

.filter-item select,
.filter-item input[type="text"] {
    min-width: 150px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.filter-item button {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s ease;
}

.filter-item button:hover {
    background: #5a6fd8;
}

.filter-item a.button {
    background: #6b7280;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    transition: background 0.2s ease;
}

.filter-item a.button:hover {
    background: #4b5563;
}

/* Bulk Actions */
.gravity-flow-bulk-actions {
    margin-bottom: 15px;
}

.bulk-actions-bar {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 15px 0;
    border-bottom: 1px solid #e5e7eb;
}

.bulk-select label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #374151;
}

.bulk-actions-dropdown {
    display: flex;
    gap: 10px;
    align-items: center;
}

.bulk-actions-dropdown select {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.bulk-actions-dropdown button {
    background: #667eea;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.results-count {
    margin-right: auto;
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
}

/* Enhanced Table */
.gravity-flow-table-container {
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.donap-gravity-flow-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.donap-gravity-flow-table th,
.donap-gravity-flow-table td {
    padding: 16px 12px;
    text-align: right;
    border-bottom: 1px solid #f3f4f6;
}

.donap-gravity-flow-table th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.donap-gravity-flow-table th.sortable a {
    color: #374151;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.2s ease;
}

.donap-gravity-flow-table th.sortable:hover {
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
}

.donap-gravity-flow-table th.sortable a:hover {
    color: #1f2937;
}

.sort-indicator::after {
    content: '';
    display: inline-block;
    width: 0;
    height: 0;
    margin-right: 5px;
    vertical-align: middle;
}

.sort-indicator.desc::after {
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 4px solid #6b7280;
}

.sort-indicator.asc::after {
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 4px solid #6b7280;
}

/* Status Badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge.status-pending {
    background: rgba(245, 110, 40, 0.1);
    color: #f56e28;
}

.status-badge.status-in_progress {
    background: rgba(0, 115, 170, 0.1);
    color: #0073aa;
}

.status-badge.status-completed {
    background: rgba(70, 180, 80, 0.1);
    color: #46b450;
}

.status-badge.status-rejected {
    background: rgba(220, 50, 50, 0.1);
    color: #dc3232;
}

/* Submitter Info */
.submitter-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.submitter-name {
    font-weight: 600;
    color: #374151;
}

.submitter-email {
    font-size: 12px;
    color: #6b7280;
}

/* Row Actions */
.row-actions {
    font-size: 13px;
}

.row-actions a {
    color: #0073aa;
    text-decoration: none;
    transition: color 0.2s ease;
}

.row-actions a:hover {
    color: #005177;
}

/* Entry Row Hover Effect */
.entry-row:hover {
    background: #f8fafc;
}

.form-title a {
    color: #374151;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.form-title a:hover {
    color: #667eea;
}

/* No Entries */
.no-entries {
    text-align: center;
    padding: 80px 20px;
    color: #6b7280;
}

.no-entries-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-entries h3 {
    color: #374151;
    margin-bottom: 10px;
}

/* Pagination */
.gravity-flow-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 20px 0;
    border-top: 1px solid #e5e7eb;
}

.pagination-info {
    color: #6b7280;
    font-size: 14px;
}

.pagination-links {
    display: flex;
    gap: 4px;
}

.page-link {
    padding: 8px 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.page-link:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.page-link.current-page {
    background: #667eea;
    color: #fff;
    border-color: #667eea;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .inbox-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .export-dropdown-content {
        right: 50%;
        transform: translateX(50%);
        min-width: 260px;
    }

    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-item {
        margin-bottom: 10px;
    }

    .bulk-actions-bar {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
        text-align: center;
    }

    .gravity-flow-pagination {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

@media (max-width: 782px) {

    .mobile-responsive .donap-gravity-flow-table,
    .mobile-responsive .donap-gravity-flow-table tbody,
    .mobile-responsive .donap-gravity-flow-table th,
    .mobile-responsive .donap-gravity-flow-table td,
    .mobile-responsive .donap-gravity-flow-table tr {
        display: block;
    }

    .mobile-responsive .donap-gravity-flow-table thead tr {
        position: absolute;
        top: -9999px;
        right: -9999px;
    }

    .mobile-responsive .donap-gravity-flow-table tr {
        border: 1px solid #e5e7eb;
        margin-bottom: 15px;
        padding: 20px;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .mobile-responsive .donap-gravity-flow-table td {
        border: none;
        padding: 8px 0;
        position: relative;
    }

    .mobile-responsive .donap-gravity-flow-table td.mobile-hidden {
        display: none;
    }

    .mobile-responsive .mobile-meta {
        margin-top: 12px;
        font-size: 13px;
    }

    .mobile-responsive .mobile-meta span {
        display: block;
        margin-bottom: 4px;
        padding: 2px 0;
    }
}

@media (max-width: 480px) {
    .donap-gravity-flow-inbox-wrapper {
        margin: 0 -10px;
    }

    .gravity-flow-filters,
    .gravity-flow-bulk-actions,
    .gravity-flow-table-container {
        margin: 0 10px;
    }

    .inbox-header {
        margin: 0 10px 20px 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export dropdown functionality
    window.toggleExportDropdown = function() {
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('show');
    };

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.export-dropdown')) {
            document.getElementById('exportDropdown').classList.remove('show');
        }
    });

    // Handle export button clicks with loading states
    window.handleExport = function(type) {
        const loadingElement = document.createElement('div');
        loadingElement.className = 'export-loading show';
        loadingElement.innerHTML = `<i class="fas fa-spinner"></i> در حال تولید فایل ${type}...`;
        document.body.appendChild(loadingElement);

        // Hide loading after 3 seconds
        setTimeout(() => {
            document.body.removeChild(loadingElement);
            document.getElementById('exportDropdown').classList.remove('show');
        }, 3000);
    };

    // Entry export toggle functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('entry-export-toggle')) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close other open dropdowns
            document.querySelectorAll('.entry-export-options').forEach(dropdown => {
                if (dropdown !== e.target.nextElementSibling) {
                    dropdown.style.display = 'none';
                }
            });
            
            // Toggle current dropdown
            const dropdown = e.target.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    });

    // Close entry export dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.entry-export-dropdown')) {
            document.querySelectorAll('.entry-export-options').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });

    // Select All functionality
    const selectAllMain = document.getElementById('select-all-entries');
    const selectAllTable = document.getElementById('cb-select-all');
    const entryCheckboxes = document.querySelectorAll('.entry-checkbox');

    function toggleAllCheckboxes(checked) {
        entryCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        if (selectAllMain) selectAllMain.checked = checked;
        if (selectAllTable) selectAllTable.checked = checked;
    }

    if (selectAllMain) {
        selectAllMain.addEventListener('change', function() {
            toggleAllCheckboxes(this.checked);
        });
    }

    if (selectAllTable) {
        selectAllTable.addEventListener('change', function() {
            toggleAllCheckboxes(this.checked);
        });
    }

    // Individual checkbox change
    entryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(entryCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(entryCheckboxes).some(cb => cb.checked);

            if (selectAllMain) {
                selectAllMain.checked = allChecked;
                selectAllMain.indeterminate = someChecked && !allChecked;
            }

            if (selectAllTable) {
                selectAllTable.checked = allChecked;
                selectAllTable.indeterminate = someChecked && !allChecked;
            }
        });
    });

    // Bulk action form submission
    const bulkForm = document.getElementById('bulk-action-form');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const selectedEntries = Array.from(entryCheckboxes).filter(cb => cb.checked);
            const bulkAction = document.getElementById('bulk_action').value;

            if (!bulkAction) {
                e.preventDefault();
                alert('لطفاً یک عملیات انتخاب کنید');
                return;
            }

            if (selectedEntries.length === 0) {
                e.preventDefault();
                alert('لطفاً حداقل یک ورودی انتخاب کنید');
                return;
            }

            if (!confirm(
                    `آیا مطمئن هستید که می‌خواهید این عملیات را روی ${selectedEntries.length} ورودی اعمال کنید؟`
                )) {
                e.preventDefault();
                return;
            }
        });
    }

    // Entry action handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('entry-approve-action')) {
            e.preventDefault();
            const entryId = e.target.dataset.entryId;
            if (confirm('آیا می‌خواهید این ورودی را تأیید کنید؟')) {
                // Handle entry approval
                console.log('Approve entry:', entryId);
            }
        }

        if (e.target.classList.contains('entry-reject-action')) {
            e.preventDefault();
            const entryId = e.target.dataset.entryId;
            const reason = prompt('لطفاً دلیل رد را وارد کنید:');
            if (reason !== null && reason.trim() !== '') {
                // Handle entry rejection
                console.log('Reject entry:', entryId, 'Reason:', reason);
            }
        }

        // Handle export option clicks with loading
        if (e.target.closest('.export-option-item')) {
            const exportType = e.target.closest('.export-option-item').classList.contains('pdf') ? 'PDF' : 'Excel';
            
            const loadingElement = document.createElement('div');
            loadingElement.className = 'export-loading show';
            loadingElement.innerHTML = `<i class="fas fa-spinner"></i> در حال تولید فایل ${exportType}...`;
            document.body.appendChild(loadingElement);

            setTimeout(() => {
                document.body.removeChild(loadingElement);
            }, 3000);
        }
    });
});
</script>