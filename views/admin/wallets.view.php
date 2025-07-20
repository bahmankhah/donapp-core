<div class="wrap donap-admin-page">
    <h1>مدیریت کیف پول‌ها</h1>
    
    <!-- Statistics Cards -->
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل کیف پول‌ها',
            'value' => $wallet_stats['total_wallets']
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'کیف پول‌های فعال',
            'value' => $wallet_stats['active_wallets']
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'موجودی کل',
            'value' => number_format($wallet_stats['total_balance']),
            'suffix' => 'تومان'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'میانگین موجودی',
            'value' => number_format($wallet_stats['avg_balance']),
            'suffix' => 'تومان'
        ]);
        ?>
    </div>

    <!-- Wallet Modification Form -->
    <div class="donap-wallet-modification">
        <h2>تغییر موجودی کیف پول</h2>
        <form method="post" action="">
            <?php wp_nonce_field('modify_wallet_action', 'wallet_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">شناسه کاربر</th>
                    <td>
                        <input type="text" name="user_id" class="regular-text" placeholder="شناسه کاربر را وارد کنید" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">مقدار (تومان)</th>
                    <td>
                        <input type="number" name="amount" class="regular-text" min="1" placeholder="مقدار به تومان" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">نوع عملیات</th>
                    <td>
                        <label>
                            <input type="radio" name="action_type" value="add" checked />
                            افزایش موجودی
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="action_type" value="subtract" />
                            کاهش موجودی
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('اعمال تغییرات', 'primary', 'modify_wallet'); ?>
        </form>
    </div>

    <!-- Create Wallet Form -->
    <div class="donap-wallet-create">
        <h2>ایجاد کیف پول جدید</h2>
        <?php if (isset($message)): ?>
            <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('create_wallet_action', 'wallet_create_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">انتخاب کاربر SSO</th>
                    <td>
                        <select name="selected_user_id" id="sso_user_select" class="regular-text" required>
                            <option value="">کاربر مورد نظر را انتخاب کنید...</option>
                            <?php if (!empty($sso_users)): ?>
                                <?php foreach ($sso_users as $user): ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" 
                                            data-sso-id="<?php echo esc_attr($user->sso_global_id); ?>">
                                        <?php echo esc_html($user->display_name ?: $user->user_login); ?> 
                                        (<?php echo esc_html($user->user_email); ?>) 
                                        - SSO: <?php echo esc_html($user->sso_global_id); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="description">
                            یا <a href="<?php echo admin_url('admin.php?page=donap-sso-users'); ?>">از لیست کاربران SSO جستجو کنید</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">موجودی اولیه (تومان)</th>
                    <td>
                        <input type="number" name="initial_amount" class="regular-text" min="0" value="0" 
                               placeholder="موجودی اولیه (می‌تواند صفر باشد)" />
                        <p class="description">مقدار اولیه‌ای که در کیف پول قرار می‌گیرد. می‌تواند صفر باشد.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('ایجاد کیف پول', 'primary', 'create_wallet'); ?>
        </form>
    </div>

    <!-- Filter Form for Wallets -->
    <div class="donap-wallet-filters">
        <h2>فیلتر کیف پول‌ها</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="donap-wallets">
            
            <table class="form-table">
                <tr>
                    <th scope="row">شناسه کاربر</th>
                    <td>
                        <input type="text" name="identifier_filter" value="<?php echo esc_attr($current_filters['identifier'] ?? ''); ?>" 
                               class="regular-text" placeholder="شناسه کاربر" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">نوع کیف پول</th>
                    <td>
                        <select name="type_filter">
                            <option value="">همه</option>
                            <option value="credit" <?php selected($current_filters['type'] ?? '', 'credit'); ?>>اعتبار</option>
                            <option value="cash" <?php selected($current_filters['type'] ?? '', 'cash'); ?>>نقد</option>
                            <option value="suspended" <?php selected($current_filters['type'] ?? '', 'suspended'); ?>>معلق</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">حداقل موجودی</th>
                    <td>
                        <input type="number" name="min_balance" value="<?php echo esc_attr($current_filters['min_balance'] ?? ''); ?>" 
                               class="regular-text" placeholder="حداقل موجودی به تومان" min="0" />
                    </td>
                </tr>
            </table>
            
            <?php submit_button('اعمال فیلتر', 'secondary', 'filter_wallets'); ?>
        </form>
    </div>

    <!-- Wallets List -->
    <div class="donap-wallets-list">
        <h2>لیست کیف پول‌ها</h2>
        <?php if (!empty($wallets)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>شناسه کاربر</th>
                        <th>نوع کیف پول</th>
                        <th>موجودی</th>
                        <th>پارامترها</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallets as $wallet): ?>
                        <tr>
                            <td><?php echo esc_html($wallet->identifier); ?></td>
                            <td>
                                <span class="wallet-type wallet-type-<?php echo esc_attr($wallet->type); ?>">
                                    <?php 
                                    switch($wallet->type) {
                                        case 'credit': echo 'اعتبار'; break;
                                        case 'cash': echo 'نقد'; break;
                                        case 'suspended': echo 'معلق'; break;
                                        default: echo $wallet->type;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="amount-cell">
                                <strong><?php echo number_format($wallet->balance); ?> تومان</strong>
                            </td>
                            <td>
                                <?php 
                                if ($wallet->params) {
                                    $params = json_decode($wallet->params, true);
                                    if ($params) {
                                        echo '<small>' . implode(', ', array_map(function($k, $v) {
                                            return "$k: $v";
                                        }, array_keys($params), $params)) . '</small>';
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo date('Y/m/d H:i', strtotime($wallet->created_at)); ?></td>
                            <td>
                                <button class="button button-small" onclick="modifyWalletQuick('<?php echo $wallet->identifier; ?>', '<?php echo $wallet->type; ?>')">
                                    تغییر سریع
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هیچ کیف پولی یافت نشد.</p>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php 
        if (isset($pagination)) {
            echo view('admin/components/pagination', ['pagination' => $pagination]); 
        }
        ?>
    </div>
</div>

<script>
function modifyWalletQuick(userId, walletType) {
    const amount = prompt('مقدار تغییر را وارد کنید (تومان):');
    if (amount && parseInt(amount) > 0) {
        const action = confirm('آیا می‌خواهید موجودی افزایش یابد؟\nOK = افزایش\nCancel = کاهش');
        
        // Fill the form with these values
        document.querySelector('input[name="user_id"]').value = userId;
        document.querySelector('input[name="amount"]').value = amount;
        
        if (action) {
            document.querySelector('input[name="action_type"][value="add"]').checked = true;
        } else {
            document.querySelector('input[name="action_type"][value="subtract"]').checked = true;
        }
        
        // Scroll to form
        document.querySelector('.donap-wallet-modification').scrollIntoView({behavior: 'smooth'});
    }
}
</script>

<style>
.donap-wallet-modification,
.donap-wallet-create {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-wallet-create h2 {
    color: #0073aa;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.donap-wallet-filters {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-wallets-list {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.wallet-type {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.wallet-type-credit {
    background: #d4edda;
    color: #155724;
}

.wallet-type-cash {
    background: #d1ecf1;
    color: #0c5460;
}

.wallet-type-suspended {
    background: #f8d7da;
    color: #721c24;
}

.amount-cell {
    font-family: 'Courier New', monospace;
}

.form-table th {
    width: 150px;
}

#sso_user_select {
    min-width: 400px;
}

.notice {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
}

.notice-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.notice-error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}
</style>
