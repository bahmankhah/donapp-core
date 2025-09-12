<?php
// Enhanced Gravity Flow Inbox Shortcode View
// File: views/shortcodes/gravity-flow-inbox.view.php

$mobile_responsive = ($attributes['mobile_responsive'] === 'true');
$show_bulk_actions = ($attributes['show_bulk_actions'] === 'true');
$show_filters = ($attributes['show_filters'] === 'true');
$show_pagination = ($attributes['show_pagination'] === 'true');
$table_class = $attributes['table_class'];

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

    <?php if ($show_filters): ?>
        <!-- Filters Section -->
        <div class="gravity-flow-filters">
            <form method="get" class="filter-form">
                <div class="filter-row">
                    <div class="filter-item">
                        <label for="gf_status_filter">وضعیت:</label>
                        <select name="gf_status" id="gf_status_filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending" <?= ($_GET['gf_status'] ?? '') === 'pending' ? 'selected' : '' ?>>در انتظار بررسی</option>
                            <option value="in_progress" <?= ($_GET['gf_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>در حال بررسی</option>
                            <option value="completed" <?= ($_GET['gf_status'] ?? '') === 'completed' ? 'selected' : '' ?>>تکمیل شده</option>
                            <option value="rejected" <?= ($_GET['gf_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>رد شده</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="gf_form_filter">فرم:</label>
                        <select name="gf_form_id" id="gf_form_filter">
                            <option value="">همه فرم‌ها</option>
                            <?php if (isset($available_forms)): ?>
                                <?php foreach ($available_forms as $form): ?>
                                    <option value="<?= $form['id'] ?>" <?= ($_GET['gf_form_id'] ?? '') == $form['id'] ? 'selected' : '' ?>>
                                        <?= esc_html($form['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="gf_search">جستجو:</label>
                        <input type="text" name="gf_search" id="gf_search" value="<?= esc_attr($_GET['gf_search'] ?? '') ?>" placeholder="نام ارسال کننده یا محتوای فرم...">
                    </div>

                    <div class="filter-item">
                        <button type="submit" class="button">اعمال فیلتر</button>
                        <a href="<?= remove_query_arg(['gf_status', 'gf_form_id', 'gf_search', 'gf_page']) ?>" class="button">حذف فیلتر</a>
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
                            <a href="<?= add_query_arg(['sort' => 'form_name', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                                نام فرم
                                <?php if (($_GET['sort'] ?? '') === 'form_name'): ?>
                                    <span class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="column-status sortable">
                            <a href="<?= add_query_arg(['sort' => 'status', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                                وضعیت
                                <?php if (($_GET['sort'] ?? '') === 'status'): ?>
                                    <span class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="column-submitter sortable">
                            <a href="<?= add_query_arg(['sort' => 'submitter', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                                ارسال کننده
                                <?php if (($_GET['sort'] ?? '') === 'submitter'): ?>
                                    <span class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="column-date sortable">
                            <a href="<?= add_query_arg(['sort' => 'date_created', 'order' => ($_GET['order'] ?? 'desc') === 'desc' ? 'asc' : 'desc']) ?>">
                                زمان ارسال
                                <?php if (($_GET['sort'] ?? '') === 'date_created'): ?>
                                    <span class="sort-indicator <?= ($_GET['order'] ?? 'desc') === 'desc' ? 'desc' : 'asc' ?>"></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="column-actions">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr class="entry-row" data-entry-id="<?= $entry['id'] ?>" data-form-id="<?= $entry['form_id'] ?>">
                            <?php if ($show_bulk_actions): ?>
                                <th class="check-column">
                                    <input type="checkbox" name="entry_ids[]" value="<?= $entry['id'] ?>" class="entry-checkbox" />
                                </th>
                            <?php endif; ?>

                            <!-- Form Name Column -->
                            <td class="column-form-name" data-colname="نام فرم">
                                <strong class="form-title">
                                    <a href="#" class="entry-view-link" data-entry-id="<?= $entry['id'] ?>">
                                        <?= esc_html($entry['form_name']) ?>
                                    </a>
                                </strong>
                                <?php if ($mobile_responsive): ?>
                                    <div class="mobile-meta">
                                        <span class="mobile-status" style="color: <?= $status_colors[$entry['status']] ?? '#666' ?>">
                                            <?= $status_labels[$entry['status']] ?? $entry['status'] ?>
                                        </span>
                                        <span class="mobile-submitter">توسط: <?= esc_html($entry['submitter']['name']) ?></span>
                                        <span class="mobile-date"><?= $entry['date_created_formatted'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Status Column -->
                            <td class="column-status <?= !$mobile_responsive ? '' : 'mobile-hidden' ?>" data-colname="وضعیت">
                                <span class="status-badge status-<?= $entry['status'] ?>" style="color: <?= $status_colors[$entry['status']] ?? '#666' ?>">
                                    <?= $status_labels[$entry['status']] ?? $entry['status'] ?>
                                </span>
                            </td>

                            <!-- Submitter Column -->
                            <td class="column-submitter <?= !$mobile_responsive ? '' : 'mobile-hidden' ?>" data-colname="ارسال کننده">
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
                                        <a href="#" class="entry-view-action" data-entry-id="<?= $entry['id'] ?>">نمایش</a> |
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
                                            <a href="#" class="entry-export-action" data-entry-id="<?= $entry['id'] ?>" data-form-id="<?= $entry['form_id'] ?>">صادرات</a>
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
                    <a href="<?= add_query_arg('gf_page', $current_page - 1, $base_url) ?>" class="page-link prev-page">‹ قبلی</a>
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
                    <a href="<?= add_query_arg('gf_page', $current_page + 1, $base_url) ?>" class="page-link next-page">بعدی ›</a>
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

    /* Filters */
    .gravity-flow-filters {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
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
    }

    .filter-item select,
    .filter-item input[type="text"] {
        min-width: 150px;
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    /* Bulk Actions */
    .gravity-flow-bulk-actions {
        margin-bottom: 10px;
    }

    .bulk-actions-bar {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }

    .bulk-select label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .bulk-actions-dropdown {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .results-count {
        margin-right: auto;
        color: #666;
        font-size: 13px;
    }

    /* Enhanced Table */
    .gravity-flow-table-container {
        overflow-x: auto;
    }

    .donap-gravity-flow-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .donap-gravity-flow-table th,
    .donap-gravity-flow-table td {
        padding: 12px 8px;
        text-align: right;
        border-bottom: 1px solid #ddd;
    }

    .donap-gravity-flow-table th {
        background: #f1f1f1;
        font-weight: 600;
    }

    .donap-gravity-flow-table th.sortable a {
        color: #23282d;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .donap-gravity-flow-table th.sortable:hover {
        background: #e1e1e1;
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
        border-top: 4px solid #666;
    }

    .sort-indicator.asc::after {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 4px solid #666;
    }

    /* Status Badges */
    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.status-pending {
        background: rgba(245, 110, 40, 0.1);
    }

    .status-badge.status-in_progress {
        background: rgba(0, 115, 170, 0.1);
    }

    .status-badge.status-completed {
        background: rgba(70, 180, 80, 0.1);
    }

    .status-badge.status-rejected {
        background: rgba(220, 50, 50, 0.1);
    }

    /* Submitter Info */
    .submitter-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .submitter-name {
        font-weight: 600;
    }

    .submitter-email {
        font-size: 12px;
        color: #666;
    }

    /* Row Actions */
    .row-actions {
        font-size: 13px;
    }

    .row-actions a {
        color: #0073aa;
        text-decoration: none;
    }

    .row-actions a:hover {
        color: #005177;
    }

    /* No Entries */
    .no-entries {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }

    .no-entries-icon {
        font-size: 48px;
        margin-bottom: 20px;
    }

    /* Pagination */
    .gravity-flow-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }

    .pagination-links {
        display: flex;
        gap: 5px;
    }

    .page-link {
        padding: 6px 12px;
        background: #f7f7f7;
        border: 1px solid #ddd;
        color: #0073aa;
        text-decoration: none;
        border-radius: 3px;
    }

    .page-link:hover {
        background: #e1e1e1;
    }

    .page-link.current-page {
        background: #0073aa;
        color: #fff;
        border-color: #0073aa;
    }

    /* Mobile Responsive */
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
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            background: #fff;
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
            margin-top: 8px;
            font-size: 13px;
        }

        .mobile-responsive .mobile-meta span {
            display: block;
            margin-bottom: 3px;
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
        }

        .gravity-flow-pagination {
            flex-direction: column;
            gap: 15px;
            text-align: center;
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
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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

                if (!confirm(`آیا مطمئن هستید که می‌خواهید این عملیات را روی ${selectedEntries.length} ورودی اعمال کنید؟`)) {
                    e.preventDefault();
                    return;
                }
            });
        }

        // Entry action handlers
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('entry-view-action') || e.target.classList.contains('entry-view-link')) {
                e.preventDefault();
                const entryId = e.target.dataset.entryId;
                // Handle entry view - you can integrate with your existing modal or redirect
                console.log('View entry:', entryId);
            }

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

            if (e.target.classList.contains('entry-export-action')) {
                e.preventDefault();
                const entryId = e.target.dataset.entryId;
                const formId = e.target.dataset.formId;
                // Handle entry export
                console.log('Export entry:', entryId, 'Form:', formId);
            }
        });
    });
</script>