<div class="wrap donap-admin-page">
    <h1>گزارشات</h1>
    
    <div class="donap-reports-filters">
        <h2>تولید گزارش</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="donap-reports">
            
            <table class="form-table">
                <tr>
                    <th scope="row">نوع گزارش</th>
                    <td>
                        <select name="report_type" id="report_type">
                            <option value="transactions" <?php selected($report_type ?? '', 'transactions'); ?>>گزارش تراکنش‌ها</option>
                            <option value="wallets" <?php selected($report_type ?? '', 'wallets'); ?>>گزارش کیف پول‌ها</option>
                            <option value="gifts" <?php selected($report_type ?? '', 'gifts'); ?>>گزارش هدایا</option>
                            <option value="users" <?php selected($report_type ?? '', 'users'); ?>>گزارش کاربران</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">بازه تاریخ</th>
                    <td>
                        <input type="date" name="start_date" value="<?php echo esc_attr($start_date ?? ''); ?>" />
                        تا
                        <input type="date" name="end_date" value="<?php echo esc_attr($end_date ?? ''); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">فرمت</th>
                    <td>
                        <label>
                            <input type="radio" name="format" value="html" <?php checked($format ?? 'html', 'html'); ?> />
                            نمایش HTML
                        </label>
                        <label>
                            <input type="radio" name="format" value="csv" <?php checked($format ?? '', 'csv'); ?> />
                            خروجی CSV
                        </label>
                        <label>
                            <input type="radio" name="format" value="pdf" <?php checked($format ?? '', 'pdf'); ?> />
                            خروجی PDF
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('تولید گزارش', 'primary', 'generate_report'); ?>
        </form>
    </div>

    <?php if (!empty($report_data)): ?>
        <div class="donap-report-results">
            <h2>نتایج گزارش</h2>
            
            <div class="donap-report-summary">
                <div class="donap-dashboard-grid">
                    <div class="donap-card">
                        <h3>تعداد کل رکوردها</h3>
                        <p class="donap-stat"><?php echo count($report_data); ?></p>
                    </div>
                    <div class="donap-card">
                        <h3>مجموع مقدار</h3>
                        <p class="donap-stat"><?php echo number_format($total_amount ?? 0); ?> تومان</p>
                    </div>
                    <div class="donap-card">
                        <h3>بازه تاریخ</h3>
                        <p class="donap-stat"><?php echo ($start_date ?? 'نامشخص') . ' تا ' . ($end_date ?? 'نامشخص'); ?></p>
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
