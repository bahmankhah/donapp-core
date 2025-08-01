<div class="wrap donap-admin-page">
    <h1>کاربران SSO</h1>
    
    <!-- Statistics Card -->
    <div class="donap-dashboard-grid">
        <?php 
        echo view('admin/components/stat-card', [
            'title' => 'تعداد کل کاربران SSO',
            'value' => $total_sso_users
        ]);
        ?>
    </div>

    <!-- Search Form -->
    <div class="donap-search-form">
        <h2>جستجوی کاربران</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="donap-sso-users">
            
            <table class="form-table">
                <tr>
                    <th scope="row">جستجو</th>
                    <td>
                        <input type="text" name="search" value="<?php echo esc_attr($current_search); ?>" 
                               class="regular-text" placeholder="نام کاربری، نام نمایشی، ایمیل یا SSO ID" />
                        <?php submit_button('جستجو', 'secondary', 'submit', false); ?>
                        <?php if (!empty($current_search)): ?>
                            <a href="<?php echo admin_url('admin.php?page=donap-sso-users'); ?>" class="button">پاک کردن فیلتر</a>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <!-- SSO Users Table -->
    <div class="donap-data-table">
        <h2>لیست کاربران SSO</h2>
        
        <?php if (!empty($sso_users)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">نام کاربری</th>
                    <th scope="col">نام نمایشی</th>
                    <th scope="col">ایمیل</th>
                    <th scope="col">SSO Global ID</th>
                    <th scope="col">تاریخ عضویت</th>
                    <th scope="col">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sso_users as $user): ?>
                <tr>
                    <td><?php echo esc_html($user->ID); ?></td>
                    <td>
                        <strong><?php echo esc_html($user->user_login); ?></strong>
                    </td>
                    <td><?php echo esc_html($user->display_name ?: '-'); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><code><?php echo esc_html($user->sso_global_id); ?></code></td>
                    <td><?php echo date('Y/m/d H:i', strtotime($user->user_registered)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" 
                           class="button button-small">ویرایش</a>
                        <a href="<?php echo admin_url('admin.php?page=donap-wallets&identifier_filter=' . urlencode($user->sso_global_id)); ?>" 
                           class="button button-small">مشاهده کیف پول</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="donap-pagination">
            <?php
            $current_page = $pagination['current_page'];
            $total_pages = $pagination['total_pages'];
            $base_url = admin_url('admin.php?page=donap-sso-users');
            
            if (!empty($current_search)) {
                $base_url .= '&search=' . urlencode($current_search);
            }
            
            // Previous page
            if ($current_page > 1):
                $prev_url = $base_url . '&paged=' . ($current_page - 1);
                echo '<a href="' . esc_url($prev_url) . '" class="button">« قبلی</a> ';
            endif;
            
            // Page numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
                if ($i == $current_page):
                    echo '<span class="button button-primary">' . $i . '</span> ';
                else:
                    $page_url = $base_url . '&paged=' . $i;
                    echo '<a href="' . esc_url($page_url) . '" class="button">' . $i . '</a> ';
                endif;
            endfor;
            
            // Next page
            if ($current_page < $total_pages):
                $next_url = $base_url . '&paged=' . ($current_page + 1);
                echo '<a href="' . esc_url($next_url) . '" class="button">بعدی »</a>';
            endif;
            ?>
            
            <div class="donap-pagination-info">
                صفحه <?php echo $current_page; ?> از <?php echo $total_pages; ?> 
                (مجموع <?php echo number_format($pagination['total_items']); ?> کاربر)
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="donap-no-data">
            <p>هیچ کاربر SSO یافت نشد.</p>
            <?php if (!empty($current_search)): ?>
                <p>ممکن است بخواهید جستجوی خود را تغییر دهید.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.donap-admin-page {
    direction: rtl;
    text-align: right;
}

.donap-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.donap-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.donap-card h3 {
    margin-top: 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.donap-stat {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
    margin: 10px 0;
}

.donap-search-form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.donap-search-form h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.donap-data-table {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.donap-data-table h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.donap-pagination {
    margin: 20px 0;
    text-align: center;
}

.donap-pagination .button {
    margin: 0 2px;
}

.donap-pagination-info {
    margin-top: 10px;
    font-size: 14px;
    color: #666;
}

.donap-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.wp-list-table th,
.wp-list-table td {
    text-align: right;
}

.wp-list-table code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.button-small {
    font-size: 11px;
    height: auto;
    line-height: 1.4;
    padding: 4px 8px;
    margin-left: 5px;
}
</style>
