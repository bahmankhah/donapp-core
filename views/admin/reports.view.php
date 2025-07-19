<div class="wrap donap-admin-page">
    <h1>Reports</h1>
    
    <div class="donap-reports-filters">
        <h2>Generate Reports</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="donap-reports">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Report Type</th>
                    <td>
                        <select name="report_type" id="report_type">
                            <option value="transactions" <?php selected($report_type ?? '', 'transactions'); ?>>Transaction Report</option>
                            <option value="wallets" <?php selected($report_type ?? '', 'wallets'); ?>>Wallet Report</option>
                            <option value="gifts" <?php selected($report_type ?? '', 'gifts'); ?>>Gift Report</option>
                            <option value="users" <?php selected($report_type ?? '', 'users'); ?>>User Report</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Date Range</th>
                    <td>
                        <input type="date" name="start_date" value="<?php echo esc_attr($start_date ?? ''); ?>" />
                        to
                        <input type="date" name="end_date" value="<?php echo esc_attr($end_date ?? ''); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Format</th>
                    <td>
                        <label>
                            <input type="radio" name="format" value="html" <?php checked($format ?? 'html', 'html'); ?> />
                            HTML View
                        </label>
                        <label>
                            <input type="radio" name="format" value="csv" <?php checked($format ?? '', 'csv'); ?> />
                            CSV Export
                        </label>
                        <label>
                            <input type="radio" name="format" value="pdf" <?php checked($format ?? '', 'pdf'); ?> />
                            PDF Export
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Generate Report', 'primary', 'generate_report'); ?>
        </form>
    </div>

    <?php if (!empty($report_data)): ?>
        <div class="donap-report-results">
            <h2>Report Results</h2>
            
            <div class="donap-report-summary">
                <div class="donap-dashboard-grid">
                    <div class="donap-card">
                        <h3>Total Records</h3>
                        <p class="donap-stat"><?php echo count($report_data); ?></p>
                    </div>
                    <div class="donap-card">
                        <h3>Total Amount</h3>
                        <p class="donap-stat"><?php echo number_format($total_amount ?? 0); ?> Toman</p>
                    </div>
                    <div class="donap-card">
                        <h3>Date Range</h3>
                        <p class="donap-stat"><?php echo ($start_date ?? 'N/A') . ' to ' . ($end_date ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <div class="donap-report-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <?php foreach ($table_headers ?? [] as $header): ?>
                                <th><?php echo esc_html($header); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?php echo esc_html($cell); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
