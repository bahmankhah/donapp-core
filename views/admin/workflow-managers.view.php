<?php
// Admin view for workflow managers management
?>
<div class="wrap">
    <h1>مدیران گردش کاری</h1>

    <div class="donap-managers-header">
        <p>مدیریت مدیران مدارس و استان‌ها برای گردش کاری خودکار</p>

        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <form method="post" style="display: inline-block;">
                <?php wp_nonce_field('manager_assignment', 'manager_nonce'); ?>
                <input type="hidden" name="create_sample_managers" value="1">
                <button type="submit" class="button button-secondary">ایجاد مدیران نمونه (فقط در حالت توسعه)</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($managers)): ?>
        <div class="managers-overview">
            <h2>مدیران فعال</h2>

            <div class="managers-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col">نام</th>
                            <th scope="col">نوع مدیر</th>
                            <th scope="col">استان</th>
                            <th scope="col">شهر</th>
                            <th scope="col">مدرسه</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">تاریخ اختصاص</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managers as $manager_data): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($manager_data['user']->display_name); ?></strong><br>
                                    <small><?php echo esc_html($manager_data['user']->user_email); ?></small>
                                </td>
                                <td>
                                    <span class="manager-type-badge manager-type-<?php echo esc_attr($manager_data['type']); ?>">
                                        <?php echo $manager_data['type'] === 'school_manager' ? 'مدیر مدرسه' : 'مدیر استان'; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($manager_data['location']['province']); ?></td>
                                <td>
                                    <?php if ($manager_data['type'] === 'school_manager'): ?>
                                        <?php echo esc_html($manager_data['location']['city']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($manager_data['type'] === 'school_manager'): ?>
                                        <?php echo esc_html($manager_data['location']['school']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($manager_data['location']['status']); ?>">
                                        <?php echo $manager_data['location']['status'] === 'active' ? 'فعال' : 'غیرفعال'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $assignment_date = get_user_meta($manager_data['user']->ID, 'workflow_assignment_date', true);
                                    if ($assignment_date) {
                                        echo esc_html(date_i18n('Y/m/d', strtotime($assignment_date)));
                                    } else {
                                        echo '<span class="text-muted">—</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="manager-actions">
                                        <button type="button" class="button button-small" onclick="editManager(<?php echo $manager_data['user']->ID; ?>)">
                                            ویرایش
                                        </button>
                                        <?php if ($manager_data['location']['status'] === 'active'): ?>
                                            <button type="button" class="button button-small button-link-delete" onclick="deactivateManager(<?php echo $manager_data['user']->ID; ?>)">
                                                غیرفعال
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="button button-small button-secondary" onclick="activateManager(<?php echo $manager_data['user']->ID; ?>)">
                                                فعال
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Managers Statistics -->
        <div class="managers-stats">
            <h2>آمار مدیران</h2>
            <div class="stats-grid">
                <?php
                $school_managers = array_filter($managers, function ($m) {
                    return $m['type'] === 'school_manager';
                });
                $province_managers = array_filter($managers, function ($m) {
                    return $m['type'] === 'province_manager';
                });
                $active_managers = array_filter($managers, function ($m) {
                    return $m['location']['status'] === 'active';
                });
                ?>

                <div class="stat-card">
                    <div class="stat-number"><?php echo count($school_managers); ?></div>
                    <div class="stat-label">مدیر مدرسه</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo count($province_managers); ?></div>
                    <div class="stat-label">مدیر استان</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo count($active_managers); ?></div>
                    <div class="stat-label">مدیر فعال</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo count($managers) - count($active_managers); ?></div>
                    <div class="stat-label">مدیر غیرفعال</div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="no-managers-notice">
            <div class="notice notice-info">
                <p><strong>هیچ مدیری تعریف نشده است.</strong></p>
                <p>برای شروع استفاده از گردش کاری خودکار، ابتدا باید مدیران مدارس و استان‌ها را تعریف کنید.</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=donap-workflow-automation'); ?>" class="button button-primary">
                        اختصاص مدیر جدید
                    </a>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Manager Assignment Guide -->
    <div class="managers-guide">
        <h2>راهنمای اختصاص مدیران</h2>
        <div class="guide-content">
            <div class="guide-section">
                <h3>مدیر مدرسه</h3>
                <p>مدیران مدرسه مسئول تأیید فرم‌های مربوط به مدرسه خاص خود هستند.</p>
                <ul>
                    <li>باید استان، شهر و نام مدرسه مشخص شود</li>
                    <li>فقط می‌توانند فرم‌های مدرسه خود را تأیید کنند</li>
                    <li>پس از تأیید، فرم به مدیر استان ارسال می‌شود</li>
                </ul>
            </div>

            <div class="guide-section">
                <h3>مدیر استان</h3>
                <p>مدیران استان مسئول تأیید نهایی فرم‌های تمام مدارس استان هستند.</p>
                <ul>
                    <li>باید فقط استان مشخص شود</li>
                    <li>می‌توانند فرم‌های تمام مدارس استان را تأیید کنند</li>
                    <li>تأیید نهایی و اتمام گردش کاری</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Edit Manager Modal -->
<div id="edit-manager-modal" class="donap-modal" style="display: none;">
    <div class="donap-modal-content">
        <div class="donap-modal-header">
            <h2>ویرایش مدیر</h2>
            <span class="donap-modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="donap-modal-body">
            <form id="edit-manager-form">
                <input type="hidden" id="edit-manager-id" name="manager_id">
                <?php wp_nonce_field('edit_manager', 'edit_manager_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">استان:</th>
                        <td>
                            <input type="text" id="edit-province" name="province" class="regular-text" required>
                        </td>
                    </tr>
                    <tr id="edit-city-row">
                        <th scope="row">شهر:</th>
                        <td>
                            <input type="text" id="edit-city" name="city" class="regular-text">
                        </td>
                    </tr>
                    <tr id="edit-school-row">
                        <th scope="row">مدرسه:</th>
                        <td>
                            <input type="text" id="edit-school" name="school" class="regular-text">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="donap-modal-footer">
            <button type="button" class="button button-secondary" onclick="closeEditModal()">لغو</button>
            <button type="button" class="button button-primary" onclick="saveManagerEdit()">ذخیره</button>
        </div>
    </div>
</div>

<style>
    .donap-managers-header {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .managers-table-wrapper {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .manager-type-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .manager-type-school_manager {
        background: #e3f2fd;
        color: #1976d2;
    }

    .manager-type-province_manager {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-active {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .status-inactive {
        background: #ffebee;
        color: #c62828;
    }

    .text-muted {
        color: #999;
    }

    .manager-actions {
        display: flex;
        gap: 8px;
    }

    .manager-actions .button {
        padding: 4px 8px;
        line-height: 1.2;
    }

    .managers-stats {
        margin-top: 30px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .stat-card {
        text-align: center;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #0073aa;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    .no-managers-notice {
        text-align: center;
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .managers-guide {
        margin-top: 30px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .guide-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 15px;
    }

    .guide-section {
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
    }

    .guide-section h3 {
        margin-top: 0;
        color: #0073aa;
    }

    .guide-section ul {
        margin-bottom: 0;
    }

    .donap-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .donap-modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    .donap-modal-header {
        padding: 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .donap-modal-header h2 {
        margin: 0;
    }

    .donap-modal-close {
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .donap-modal-close:hover {
        color: #333;
    }

    .donap-modal-body {
        padding: 20px;
    }

    .donap-modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #ddd;
        text-align: right;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .guide-content {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .manager-actions {
            flex-direction: column;
        }
    }
</style>

<script>
    function editManager(managerId) {
        // Show modal
        document.getElementById('edit-manager-modal').style.display = 'flex';
        document.getElementById('edit-manager-id').value = managerId;

        // You would typically load the manager's current data via AJAX here
        // For now, just show the form
    }

    function closeEditModal() {
        document.getElementById('edit-manager-modal').style.display = 'none';
    }

    function saveManagerEdit() {
        const form = document.getElementById('edit-manager-form');
        const formData = new FormData(form);
        formData.append('action', 'edit_workflow_manager');

        fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('خطا در ویرایش مدیر: ' + result.data.message);
                }
            })
            .catch(error => {
                alert('خطا در ارتباط با سرور');
            });
    }

    function deactivateManager(managerId) {
        if (confirm('آیا از غیرفعال کردن این مدیر مطمئن هستید؟')) {
            const data = new FormData();
            data.append('action', 'deactivate_workflow_manager');
            data.append('manager_id', managerId);
            data.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('خطا در غیرفعال کردن مدیر');
                    }
                });
        }
    }

    function activateManager(managerId) {
        if (confirm('آیا از فعال کردن این مدیر مطمئن هستید؟')) {
            const data = new FormData();
            data.append('action', 'activate_workflow_manager');
            data.append('manager_id', managerId);
            data.append('nonce', '<?php echo esc_js($nonce); ?>');

            fetch(ajaxurl, {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('خطا در فعال کردن مدیر');
                    }
                });
        }
    }

    // Close modal when clicking outside
    document.getElementById('edit-manager-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>