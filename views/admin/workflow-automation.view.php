<?php
// Admin view for workflow automation management
?>
<div class="wrap">
    <h1>مدیریت گردش کاری خودکار</h1>

    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="donap-stats-grid">
        <?php
        echo view('admin/components/stat-card', [
            'title' => 'کل گردش‌های کاری',
            'value' => $stats['total_workflows'] ?? 0,
            'icon' => 'dashicons-workflow'
        ]);
        echo view('admin/components/stat-card', [
            'title' => 'در انتظار تأیید',
            'value' => $stats['pending_approvals'] ?? 0,
            'icon' => 'dashicons-clock'
        ]);
        echo view('admin/components/stat-card', [
            'title' => 'تکمیل شده',
            'value' => $stats['completed_workflows'] ?? 0,
            'icon' => 'dashicons-yes'
        ]);
        echo view('admin/components/stat-card', [
            'title' => 'مدیران فعال',
            'value' => $stats['active_managers'] ?? 0,
            'icon' => 'dashicons-admin-users'
        ]);
        ?>
    </div>

    <div class="donap-workflow-sections">

        <!-- Manager Assignment Form -->
        <div class="donap-section">
            <h2>اختصاص مدیر جدید</h2>
            <form method="post" class="donap-manager-form">
                <?php wp_nonce_field('workflow_management', 'workflow_nonce'); ?>
                <input type="hidden" name="assign_manager" value="1">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_search">کاربر:</label>
                        </th>
                        <td>
                            <input type="text" id="user_search" placeholder="جستجوی کاربران..." class="regular-text">
                            <input type="hidden" id="user_id" name="user_id" required>
                            <div id="user_search_results" class="search-results"></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="manager_type">نوع مدیر:</label>
                        </th>
                        <td>
                            <select name="manager_type" id="manager_type" required>
                                <option value="">انتخاب کنید</option>
                                <option value="school_manager">مدیر مدرسه</option>
                                <option value="province_manager">مدیر استان</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="province">استان:</label>
                        </th>
                        <td>
                            <input type="text" name="province" id="province" class="regular-text" required>
                        </td>
                    </tr>
                    <tr id="city_row" style="display: none;">
                        <th scope="row">
                            <label for="city">شهر:</label>
                        </th>
                        <td>
                            <input type="text" name="city" id="city" class="regular-text">
                        </td>
                    </tr>
                    <tr id="school_row" style="display: none;">
                        <th scope="row">
                            <label for="school">نام مدرسه:</label>
                        </th>
                        <td>
                            <input type="text" name="school" id="school" class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button('اختصاص مدیر', 'primary', 'submit', true); ?>
            </form>
        </div>

        <!-- Current Managers List -->
        <div class="donap-section">
            <h2>مدیران فعلی</h2>
            <?php if (!empty($managers)): ?>
                <div class="managers-grid">
                    <?php foreach ($managers as $manager_data): ?>
                        <div class="manager-card">
                            <div class="manager-info">
                                <h3><?php echo esc_html($manager_data['user']->display_name); ?></h3>
                                <p class="manager-type">
                                    <?php echo $manager_data['type'] === 'school_manager' ? 'مدیر مدرسه' : 'مدیر استان'; ?>
                                </p>
                                <div class="manager-location">
                                    <strong>موقعیت:</strong>
                                    <?php if ($manager_data['type'] === 'school_manager'): ?>
                                        استان: <?php echo esc_html($manager_data['location']['province']); ?><br>
                                        شهر: <?php echo esc_html($manager_data['location']['city']); ?><br>
                                        مدرسه: <?php echo esc_html($manager_data['location']['school']); ?>
                                    <?php else: ?>
                                        استان: <?php echo esc_html($manager_data['location']['province']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="manager-actions">
                                    <button type="button" class="button button-secondary" onclick="deactivateManager(<?php echo $manager_data['user']->ID; ?>)">
                                        غیرفعال کردن
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>هیچ مدیری تعریف نشده است.</p>
            <?php endif; ?>
        </div>

        <!-- Workflow Settings -->
        <div class="donap-section">
            <h2>تنظیمات گردش کاری</h2>
            <form method="post" class="donap-settings-form">
                <?php wp_nonce_field('workflow_settings', 'workflow_settings_nonce'); ?>
                <input type="hidden" name="save_settings" value="1">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="auto_approval">تأیید خودکار:</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_approval" id="auto_approval">
                                فعال‌سازی تأیید خودکار برای فرم‌های دارای فیلدهای مکانی
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notification_email">ایمیل اعلان:</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="notification_email" id="notification_email">
                                ارسال ایمیل اعلان برای مدیران
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="approval_timeout">مهلت تأیید (روز):</label>
                        </th>
                        <td>
                            <input type="number" name="approval_timeout" id="approval_timeout" value="7" min="1" max="30" class="small-text">
                            <p class="description">مهلت زمانی برای تأیید یا رد درخواست‌ها</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('ذخیره تنظیمات', 'primary', 'submit', true); ?>
            </form>
        </div>
    </div>
</div>

<style>
    .donap-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .donap-workflow-sections {
        display: grid;
        gap: 30px;
    }

    .donap-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .donap-section h2 {
        margin-top: 0;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 10px;
        color: #333;
    }

    .managers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .manager-card {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
    }

    .manager-card h3 {
        margin-top: 0;
        color: #0073aa;
    }

    .manager-type {
        background: #0073aa;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        display: inline-block;
        margin-bottom: 10px;
    }

    .manager-location {
        background: #fff;
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
        font-size: 14px;
        line-height: 1.5;
    }

    .manager-actions {
        margin-top: 15px;
        text-align: right;
    }

    .search-results {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        width: 100%;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .search-result-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .search-result-item:hover {
        background: #f0f0f0;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manager type change handler
        const managerType = document.getElementById('manager_type');
        const cityRow = document.getElementById('city_row');
        const schoolRow = document.getElementById('school_row');
        const cityField = document.getElementById('city');
        const schoolField = document.getElementById('school');

        managerType.addEventListener('change', function() {
            if (this.value === 'school_manager') {
                cityRow.style.display = 'table-row';
                schoolRow.style.display = 'table-row';
                cityField.required = true;
                schoolField.required = true;
            } else {
                cityRow.style.display = 'none';
                schoolRow.style.display = 'none';
                cityField.required = false;
                schoolField.required = false;
            }
        });

        // User search functionality
        const userSearch = document.getElementById('user_search');
        const userResults = document.getElementById('user_search_results');
        const userIdField = document.getElementById('user_id');

        userSearch.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                userResults.style.display = 'none';
                return;
            }

            // Simulated AJAX search (you would implement actual AJAX here)
            setTimeout(() => {
                userResults.innerHTML = `
                <div class="search-result-item" onclick="selectUser(1, 'کاربر نمونه')">
                    کاربر نمونه (example@example.com)
                </div>
            `;
                userResults.style.display = 'block';
            }, 300);
        });

        // Close results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#user_search') && !e.target.closest('#user_search_results')) {
                userResults.style.display = 'none';
            }
        });
    });

    function selectUser(id, name) {
        document.getElementById('user_id').value = id;
        document.getElementById('user_search').value = name;
        document.getElementById('user_search_results').style.display = 'none';
    }

    function deactivateManager(managerId) {
        if (confirm('آیا از غیرفعال کردن این مدیر مطمئن هستید؟')) {
            // AJAX call to deactivate manager
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
</script>