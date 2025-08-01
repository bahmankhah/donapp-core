<div class="wrap donap-admin-page">
    <h1>Wallet Management</h1>
    
    <div class="donap-dashboard-grid">
        <div class="donap-card">
            <h3>Active Wallets</h3>
            <p class="donap-stat"><?php echo $active_wallets ?? 0; ?></p>
        </div>
        <div class="donap-card">
            <h3>Pending Transactions</h3>
            <p class="donap-stat"><?php echo $pending_transactions ?? 0; ?></p>
        </div>
        <div class="donap-card">
            <h3>Total Volume Today</h3>
            <p class="donap-stat"><?php echo number_format($daily_volume ?? 0); ?> Toman</p>
        </div>
    </div>

    <div class="donap-wallet-actions">
        <h2>Wallet Actions</h2>
        <div class="donap-action-buttons">
            <button class="button button-primary" onclick="openBulkCreditModal()">Bulk Credit</button>
            <button class="button button-secondary" onclick="exportWalletData()">Export Data</button>
            <button class="button button-secondary" onclick="openSearchModal()">Search Wallets</button>
        </div>
    </div>

    <div class="donap-wallet-list">
        <h2>Recent Wallet Activities</h2>
        <?php if (!empty($wallet_activities)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Wallet Type</th>
                        <th>Amount</th>
                        <th>Transaction Type</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallet_activities as $activity): ?>
                        <tr>
                            <td><?php echo esc_html($activity->identifier); ?></td>
                            <td><?php echo esc_html($activity->type); ?></td>
                            <td><?php echo number_format($activity->amount); ?> Toman</td>
                            <td><?php echo esc_html($activity->transaction_type ?? 'N/A'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($activity->created_at)); ?></td>
                            <td>
                                <a href="#" class="button button-small">View</a>
                                <a href="#" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No wallet activities found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function openBulkCreditModal() {
    alert('Bulk credit functionality will be implemented here.');
}

function exportWalletData() {
    alert('Export functionality will be implemented here.');
}

function openSearchModal() {
    alert('Search functionality will be implemented here.');
}
</script>
