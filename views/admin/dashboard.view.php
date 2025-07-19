<div class="wrap donap-admin-page">
    <h1>Donap Dashboard</h1>
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'Total Users',
            'value' => $total_users
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'Total Wallet Balance',
            'value' => number_format($total_balance),
            'suffix' => 'Toman'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'Total Transactions',
            'value' => $total_transactions
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'System Status',
            'value' => 'Active',
            'stat_class' => 'donap-status-active'
        ]);
        ?>
    </div>
    
    <div class="donap-recent-activity">
        <h2>Recent Activity</h2>
        <div class="donap-activity-list">
            <?php if (!empty($recent_activity)): ?>
                <ul class="donap-activity-list">
                    <?php foreach ($recent_activity as $transaction): ?>
                        <li class="donap-activity-item">
                            <span class="activity-type"><?php echo ucfirst($transaction->type); ?></span>
                            <span class="activity-amount"><?php echo number_format($transaction->amount); ?> Toman</span>
                            <span class="activity-date"><?php echo date('Y-m-d H:i', strtotime($transaction->created_at)); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No recent activity found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
