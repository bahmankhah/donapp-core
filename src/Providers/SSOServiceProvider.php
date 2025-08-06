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

        // Add SSO Global ID field to user profile
        $this->addSSOFieldsToUserProfile();
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
    private function addSSOFieldsToUserProfile()
    {
        // Add field to user profile page (front-end)
        add_action('show_user_profile', [$this, 'showSSOFields']);
        
        // Add field to admin user edit page (back-end)
        add_action('edit_user_profile', [$this, 'showSSOFields']);
    }

    /**
     * Display SSO Global ID field in user profile
     */
    public function showSSOFields($user)
    {
        $sso_global_id = get_user_meta($user->ID, 'sso_global_id', true);
        $sso_mobile_number = get_user_meta($user->ID, 'sso_mobile_number', true);
        $sso_national_id = get_user_meta($user->ID, 'sso_national_id', true);
        
        // Only show the section if user has SSO data
        if (empty($sso_global_id) && empty($sso_mobile_number) && empty($sso_national_id)) {
            return;
        }
        ?>
        <h3><?php esc_html_e('SSO Information', 'donapp-core'); ?></h3>
        <table class="form-table">
            <?php if (!empty($sso_global_id)): ?>
            <tr>
                <th><label><?php esc_html_e('SSO Global ID', 'donapp-core'); ?></label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_global_id); ?>" class="regular-text" readonly disabled />
                    <p class="description"><?php esc_html_e('This is the unique identifier from the SSO provider and cannot be modified.', 'donapp-core'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($sso_mobile_number)): ?>
            <tr>
                <th><label><?php esc_html_e('SSO Mobile Number', 'donapp-core'); ?></label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_mobile_number); ?>" class="regular-text" readonly disabled />
                    <p class="description"><?php esc_html_e('Mobile number from SSO provider.', 'donapp-core'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($sso_national_id)): ?>
            <tr>
                <th><label><?php esc_html_e('SSO National ID', 'donapp-core'); ?></label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($sso_national_id); ?>" class="regular-text" readonly disabled />
                    <p class="description"><?php esc_html_e('National ID from SSO provider.', 'donapp-core'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
}
