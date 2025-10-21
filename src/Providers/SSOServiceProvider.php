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
            // Prevent page caching during the SSO callback handling.
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            if (!headers_sent()) {
                nocache_headers();
            }

            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $uri = $_SERVER['REQUEST_URI'];
            $current_uri = $scheme . '://' . $host . $uri;
            
            $url_parts = parse_url($current_uri);
            $uri_without_params = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
            
            appLogger('SSO callback redirect_url: ' . $uri_without_params);
            Auth::sso()->attempt([
                'code' => $_GET['code'],
                'session_state' => $_GET['session_state'] ?? null,
                'redirect_url' => $uri_without_params
            ]);
            $this->remove_code_param_redirect();
        }

        // Add SSO Global ID field to user profile
        add_action('edit_user_profile', [$this, 'showSSOFields'], 10);
        add_action('show_user_profile', [$this, 'showSSOFields'], 10);
        
        // Save SSO fields when user profile is updated
        add_action('edit_user_profile_update', [$this, 'saveSSOFields'], 10);
        add_action('personal_options_update', [$this, 'saveSSOFields'], 10);
    }
    
    public function remove_code_param_redirect() {
        $current_url = $_SERVER['REQUEST_URI'];

        $url_parts = parse_url($current_url);
        parse_str($url_parts['query'] ?? '', $query_params);

        if(isset($query_params['code'])){
            unset($query_params['code']);
        }
        if(isset($query_params['session_state'])){
            unset( $query_params['session_state']);
        }
        if(isset( $query_params['iss'])){
            unset( $query_params['iss']);
        }

        $new_query_string = http_build_query($query_params);
        $new_url = $url_parts['path'] . ($new_query_string ? '?' . $new_query_string : '');

    // Safe redirect after login; ensure cookies are already set
    wp_redirect($new_url, 302, 'DonappSSO');
        exit;
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
        
        ?>
        <h3>اطلاعات SSO</h3>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="sso_global_id">شناسه جهانی SSO</label></th>
                <td>
                    <input type="text" name="sso_global_id" id="sso_global_id" value="<?php echo esc_attr($sso_global_id ?? ''); ?>" class="regular-text" />
                    <p class="description">این شناسه منحصر به فرد از سرویس‌دهنده SSO دریافت شده.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="sso_mobile_number">شماره موبایل SSO</label></th>
                <td>
                    <input type="text" name="sso_mobile_number" id="sso_mobile_number" value="<?php echo esc_attr($sso_mobile_number ?? ''); ?>" class="regular-text" />
                    <p class="description">شماره موبایل دریافت شده از سرویس‌دهنده SSO.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="sso_national_id">کد ملی SSO</label></th>
                <td>
                    <input type="text" name="sso_national_id" id="sso_national_id" value="<?php echo esc_attr($sso_national_id ?? ''); ?>" class="regular-text" />
                    <p class="description">کد ملی دریافت شده از سرویس‌دهنده SSO.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save SSO fields when user profile is updated
     */
    public function saveSSOFields($user_id)
    {
        // Only allow administrators to save SSO fields
        if (!current_user_can('manage_options')) {
            return;
        }

        // Verify nonce for security (WordPress handles this automatically for user profile forms)
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'update-user_' . $user_id)) {
            return;
        }

        // Save SSO Global ID
        if (isset($_POST['sso_global_id'])) {
            $sso_global_id = sanitize_text_field($_POST['sso_global_id']);
            update_user_meta($user_id, 'sso_global_id', $sso_global_id);
        }

        // Save SSO Mobile Number
        if (isset($_POST['sso_mobile_number'])) {
            $sso_mobile_number = sanitize_text_field($_POST['sso_mobile_number']);
            update_user_meta($user_id, 'sso_mobile_number', $sso_mobile_number);
        }

        // Save SSO National ID
        if (isset($_POST['sso_national_id'])) {
            $sso_national_id = sanitize_text_field($_POST['sso_national_id']);
            update_user_meta($user_id, 'sso_national_id', $sso_national_id);
        }
    }
}
