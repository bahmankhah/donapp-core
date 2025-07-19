<div class="wrap donap-admin-page">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <!-- Analytics Overview Cards -->
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'Total Revenue',
            'value' => number_format($total_revenue),
            'suffix' => 'Toman',
            'class' => 'revenue-card'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'Conversion Rate',
            'value' => $conversion_rate,
            'suffix' => '%',
            'class' => 'conversion-card'
        ]);
        
        echo view('admin/components/stat-card', [
            'title' => 'Active Users',
            'value' => $active_users,
            'class' => 'users-card'
        ]);
        ?>
    </div>

    <!-- Charts Section -->
    <div class="donap-charts-section">
        <h2>Revenue Trend</h2>
        <div class="donap-chart-container">
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Top Products Section -->
    <div class="donap-top-products">
        <h2>Top Selling Products</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Sales Count</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?php echo esc_html($product['name']); ?></td>
                        <td><?php echo $product['sales']; ?></td>
                        <td>
                            <a href="#" class="button button-small">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Export Actions -->
    <div class="donap-export-actions">
        <h2>Export Options</h2>
        <p>Export analytics data for further analysis:</p>
        <button class="button button-primary" onclick="exportAnalytics('csv')">Export as CSV</button>
        <button class="button button-secondary" onclick="exportAnalytics('pdf')">Export as PDF</button>
        <button class="button button-secondary" onclick="exportAnalytics('excel')">Export as Excel</button>
    </div>
</div>

<script>
// Sample chart implementation (requires Chart.js)
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chart_data, 'date')); ?>,
                datasets: [{
                    label: 'Daily Revenue (Toman)',
                    data: <?php echo json_encode(array_column($chart_data, 'revenue')); ?>,
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat().format(value) + ' T';
                            }
                        }
                    }
                }
            }
        });
    }
});

function exportAnalytics(format) {
    // This would typically make an AJAX request to export the data
    alert('Exporting analytics data as ' + format.toUpperCase() + '...');
    
    // Example AJAX call:
    // jQuery.post(ajaxurl, {
    //     action: 'export_donap_analytics',
    //     format: format,
    //     nonce: '<?php echo wp_create_nonce("export_analytics"); ?>'
    // }, function(response) {
    //     // Handle response
    // });
}
</script>

<style>
.donap-charts-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-chart-container {
    max-width: 800px;
    margin: 20px 0;
}

.donap-top-products {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-export-actions {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
}

.donap-export-actions .button {
    margin-right: 10px;
}

/* Custom card styling */
.revenue-card .donap-stat {
    color: #46b450;
}

.conversion-card .donap-stat {
    color: #ffb900;
}

.users-card .donap-stat {
    color: #0073aa;
}
</style>
