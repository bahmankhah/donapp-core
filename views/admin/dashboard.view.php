<div class="wrap donap-admin-page">
    <h1>داشبورد دناپ</h1>
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل کاربران SSO',
            'value' => $total_users
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'موجودی کل کیف پول‌ها',
            'value' => number_format($total_balance),
            'suffix' => 'تومان'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل تراکنش‌ها',
            'value' => $total_transactions
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'وضعیت سیستم',
            'value' => 'فعال',
            'stat_class' => 'donap-status-active'
        ]);
        ?>
    </div>
    
    <div class="donap-recent-activity">
        <h2>فعالیت‌های اخیر</h2>
        <div class="donap-activity-list">
            <?php if (!empty($recent_activity)): ?>
                <ul class="donap-activity-list">
                    <?php foreach ($recent_activity as $transaction): ?>
                        <li class="donap-activity-item">
                            <span class="activity-type">
                                <?php 
                                switch($transaction->type) {
                                    case 'credit_charge': echo 'شارژ اعتبار'; break;
                                    case 'charge_gift': echo 'هدیه شارژ'; break;
                                    case 'admin': echo 'مدیریتی'; break;
                                    case 'settlement_request': echo 'درخواست تسویه'; break;
                                    default: echo ucfirst($transaction->type);
                                }
                                ?>
                            </span>
                            <span class="activity-amount"><?php echo number_format($transaction->amount); ?> تومان</span>
                            <span class="activity-date"><?php echo date('Y/m/d H:i', strtotime($transaction->created_at)); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>هیچ فعالیت اخیری یافت نشد.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
