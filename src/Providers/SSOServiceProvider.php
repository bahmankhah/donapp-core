<?php

namespace App\Providers;

use DateTime;
use App\Models\UserCart;
use App\Services\AuthService;
use App\Services\BlogService;
use App\Services\ProductService;
use App\Services\VideoService;
use App\Services\WooService;
use Kernel\Container;
use Kernel\Facades\Auth;
use Kernel\PostType;

class SSOServiceProvider
{

    public function register() {}

    public function boot()
    {
        // appLogger($_GET['code'] ?? 'no state');
        if (isset($_GET['code'])) {
            Auth::sso()->attempt(['code'=>$_GET['code'], 'session_state'=>$_GET['session_state'] ?? null]);
            $this->remove_code_param_redirect();
        }

        // Add SSO Global ID field to user profile - use admin_init for wp-admin hooks
        add_action('admin_init', [$this, 'addSSOFieldsToUserProfile']);
    }
    
    public function remove_code_param_redirect() {
        $current_url = $_SERVER['REQUEST_URI'];

        $url_parts = parse_url($current_url);
        parse_str($url_parts['query'] ?? '', $query_params);

        unset($query_params['code']);

        $new_query_string = http_build_query($query_params);
        $new_url = $url_parts['path'] . ($new_query_string ? '?' . $new_query_string : '');

        wp_redirect($new_url);
        exit;
    }

    /**
     * Add SSO Global ID field to user profile as readonly
     */
    public function addSSOFieldsToUserProfile()
    {
        // Only add field to admin user edit page for administrators
        add_action('edit_user_profile', [$this, 'showSSOFields'], 10);
        add_action('show_user_profile', [$this, 'showSSOFields'], 10);
    }

    /**
     * Display SSO Global ID field in user profile
     */
    public function showSSOFields($user)
    {
        // Only show to administrators in wp-admin
        if (!current_user_can('manage_options')) {
            return;
        }

        $sso_global_id = get_user_meta($user->ID, 'sso_global_id', true);
        $sso_mobile_number = get_user_meta($user->ID, 'sso_mobile_number', true);
        $sso_national_id = get_user_meta($user->ID, 'sso_national_id', true);
        
        // Only show the section if user has SSO data
        if (empty($sso_global_id) && empty($sso_mobile_number) && empty($sso_national_id)) {
            return;
        }
        ?>
        <h3>اطلاعات SSO</h3>
        <table class="form-table" role="presentation">
            <?php if (!empty($sso_global_id)): ?>
            <tr>
                <th><label>شناسه جهانی SSO</label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_global_id); ?>" class="regular-text" readonly disabled style="background-color: #f9f9f9;" />
                    <p class="description">این شناسه منحصر به فرد از سرویس‌دهنده SSO دریافت شده و قابل تغییر نیست.</p>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($sso_mobile_number)): ?>
            <tr>
                <th><label>شماره موبایل SSO</label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_mobile_number); ?>" class="regular-text" readonly disabled style="background-color: #f9f9f9;" />
                    <p class="description">شماره موبایل دریافت شده از سرویس‌دهنده SSO.</p>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($sso_national_id)): ?>
            <tr>
                <th><label>کد ملی SSO</label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_national_id); ?>" class="regular-text" readonly disabled style="background-color: #f9f9f9;" />
                    <p class="description">کد ملی دریافت شده از سرویس‌دهنده SSO.</p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
}
