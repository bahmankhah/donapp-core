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
                            <input type="radio" name="action_type" value="increase" checked />
                            افزایش موجودی
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="action_type" value="decrease" />
                            کاهش موجودی
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('اعمال تغییرات', 'primary', 'modify_wallet'); ?>
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
            document.querySelector('input[name="action_type"][value="increase"]').checked = true;
        } else {
            document.querySelector('input[name="action_type"][value="decrease"]').checked = true;
        }
        
        // Scroll to form
        document.querySelector('.donap-wallet-modification').scrollIntoView({behavior: 'smooth'});
    }
}
</script>

<style>
.donap-wallet-modification {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
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
</style>
