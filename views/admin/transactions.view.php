<div class="wrap donap-admin-page">
    <h1>تراکنش‌ها</h1>
    
    <!-- Statistics Cards -->
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل تراکنش‌ها',
            'value' => $transaction_stats['total_transactions']
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'تراکنش‌های امروز',
            'value' => $transaction_stats['today_transactions']
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'حجم کل تراکنش‌ها',
            'value' => number_format($transaction_stats['total_volume']),
            'suffix' => 'تومان'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'حجم تراکنش‌های امروز',
            'value' => number_format($transaction_stats['today_volume']),
            'suffix' => 'تومان'
        ]);
        ?>
    </div>

    <!-- Filter Form -->
    <div class="donap-transaction-filters">
        <h2>فیلتر تراکنش‌ها</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="donap-transactions">
            
            <table class="form-table">
                <tr>
                    <th scope="row">انتخاب کاربر SSO</th>
                    <td>
                        <select name="user_filter" id="sso_user_select_filter" class="regular-text">
                            <option value="">همه کاربران...</option>
                            <?php if (!empty($sso_users)): ?>
                                <?php foreach ($sso_users as $user): ?>
                                    <option value="<?php echo esc_attr($user->sso_global_id); ?>" 
                                            <?php selected($current_filters['user_filter'] ?? '', $user->sso_global_id); ?>>
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
                    <th scope="row">نوع تراکنش</th>
                    <td>
                        <select name="type_filter">
                            <option value="">همه</option>
                            <option value="credit_charge" <?php selected($current_filters['type_filter'] ?? '', 'credit_charge'); ?>>شارژ اعتبار</option>
                            <option value="charge_gift" <?php selected($current_filters['type_filter'] ?? '', 'charge_gift'); ?>>هدیه شارژ</option>
                            <option value="admin" <?php selected($current_filters['type_filter'] ?? '', 'admin'); ?>>مدیریتی</option>
                            <option value="settlement_request" <?php selected($current_filters['type_filter'] ?? '', 'settlement_request'); ?>>درخواست تسویه</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">بازه تاریخ</th>
                    <td>
                        <input type="date" name="start_date" value="<?php echo esc_attr($current_filters['start_date'] ?? ''); ?>" />
                        تا
                        <input type="date" name="end_date" value="<?php echo esc_attr($current_filters['end_date'] ?? ''); ?>" />
                    </td>
                </tr>
            </table>
            
            <?php submit_button('اعمال فیلتر', 'secondary', 'filter_transactions'); ?>
        </form>
    </div>

    <!-- Transactions List -->
    <div class="donap-transactions-list">
        <h2>لیست تراکنش‌ها</h2>
        <?php if (!empty($transactions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>شناسه کاربر</th>
                        <th>نوع کیف پول</th>
                        <th>نوع تراکنش</th>
                        <th>بستانکار</th>
                        <th>بدهکار</th>
                        <th>مقدار</th>
                        <th>موجودی باقیمانده</th>
                        <th>توضیحات</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($transaction->identifier); ?></strong>
                            </td>
                            <td>
                                <span class="wallet-type wallet-type-<?php echo esc_attr($transaction->wallet_type); ?>">
                                    <?php 
                                    switch($transaction->wallet_type) {
                                        case 'credit': echo 'اعتبار'; break;
                                        case 'cash': echo 'نقد'; break;
                                        case 'coin': echo 'سکه'; break;
                                        case 'suspended': echo 'معلق'; break;
                                        default: echo $transaction->wallet_type;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="transaction-type transaction-type-<?php echo esc_attr($transaction->type); ?>">
                                    <?php 
                                    switch($transaction->type) {
                                        case 'credit_charge': echo 'شارژ اعتبار'; break;
                                        case 'charge_gift': echo 'هدیه شارژ'; break;
                                        case 'admin': echo 'مدیریتی'; break;
                                        case 'settlement_request': echo 'درخواست تسویه'; break;
                                        default: echo $transaction->type;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="amount-cell">
                                <?php if ($transaction->credit): ?>
                                    <span class="amount-positive">
                                        +<?php echo number_format($transaction->credit); ?> تومان
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="amount-cell">
                                <?php if ($transaction->debit): ?>
                                    <span class="amount-negative">
                                        -<?php echo number_format($transaction->debit); ?> تومان
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="amount-cell">
                                <span class="amount-<?php echo $transaction->amount >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo ($transaction->amount >= 0 ? '+' : '') . number_format($transaction->amount); ?> تومان
                                </span>
                            </td>
                            <td class="amount-cell">
                                <?php echo number_format($transaction->remain ?? 0); ?> تومان
                            </td>
                            <td>
                                <?php echo esc_html($transaction->description ?? '-'); ?>
                            </td>
                            <td>
                                <div class="transaction-date">
                                    <strong><?php echo date('Y/m/d', strtotime($transaction->created_at)); ?></strong>
                                    <small><?php echo date('H:i', strtotime($transaction->created_at)); ?></small>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هیچ تراکنشی یافت نشد.</p>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php 
        if (isset($pagination)) {
            echo view('admin/components/pagination', ['pagination' => $pagination]); 
        }
        ?>
    </div>

    <!-- Export Options -->
    <div class="donap-export-options">
        <h3>گزینه‌های خروجی</h3>
        <button class="button button-secondary" onclick="exportTransactions('csv')">خروجی CSV</button>
        <button class="button button-secondary" onclick="exportTransactions('excel')">خروجی Excel</button>
    </div>
</div>

<script>
function exportTransactions(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    // This would typically trigger a download
    alert('در حال آماده‌سازی فایل ' + format.toUpperCase() + '...');
    
    // Example implementation:
    // window.location.href = window.location.pathname + '?' + params.toString();
}
</script>

<style>
.donap-transaction-filters {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-transactions-list {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-export-options {
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

.wallet-type-coin {
    background: #fff3cd;
    color: #856404;
}

.wallet-type-suspended {
    background: #f8d7da;
    color: #721c24;
}

.transaction-type {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.transaction-type-credit_charge {
    background: #d4edda;
    color: #155724;
}

.transaction-type-charge_gift {
    background: #fff3cd;
    color: #856404;
}

.transaction-type-admin {
    background: #d1ecf1;
    color: #0c5460;
}

.transaction-type-settlement_request {
    background: #f8d7da;
    color: #721c24;
}

.amount-positive {
    color: #28a745;
    font-weight: bold;
}

.amount-negative {
    color: #dc3545;
    font-weight: bold;
}

.amount-cell {
    text-align: center;
    min-width: 100px;
}

.transaction-date {
    text-align: center;
}

.transaction-date small {
    display: block;
    color: #666;
}

.form-table th {
    width: 150px;
}

#sso_user_select_filter {
    min-width: 400px;
}

.wp-list-table td {
    vertical-align: middle;
}
</style>
