<?php

/**
 * Plugin Name: Donapp Core
 * Description: Donapp Core.
 * Version: 1.0
 * Author: Hesam
 */

if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering early to prevent header issues
if (!defined('DONAPP_OB_STARTED')) {
    ob_start();
    define('DONAPP_OB_STARTED', true);
}

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ElementorServiceProvider;
use App\Providers\GravityServiceProvider;
use App\Providers\HookFilterServiceProvider;
use App\Providers\SessionScoresServiceProvider;
use App\Providers\ShortcodeServiceProvider;
use App\Providers\SSOServiceProvider;
use App\Providers\WooServiceProvider;
use App\Providers\WorkflowServiceProvider;
use App\Routes\RouteServiceProvider;
use Kernel\Facades\Auth;


require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

require_once(__DIR__ . '/Kernel/autoload.php');
require_once(__DIR__ . '/src/Helpers/helper.php');



spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});


register_activation_hook(__FILE__, function () {
    (new AppServiceProvider())->register();
});

function securityCheck()
{
    remove_action('wp_head', 'wp_generator');
    add_filter( 'pre_comment_content', 'wp_specialchars' );
    add_filter('the_generator', '__return_empty_string');
    if (!is_user_logged_in())
        return;

    $timeout = 120 * 60; // 120 minutes
    $key = 'last_activity_ts';

    $last = (int) get_user_meta(get_current_user_id(), $key, true);
    $now = time();

    if ($last && ($now - $last) > $timeout) {
        wp_logout();
        wp_redirect(wp_login_url());
        exit;
    }

    update_user_meta(get_current_user_id(), $key, $now);
}

// Function to check and redirect login attempts
function donapp_check_login_redirect()
{
    // Skip if user is already logged in
    if (is_user_logged_in()) {
        return;
    }

    // Skip admin area
    if (is_admin()) {
        return;
    }

    // Skip logout actions
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        return;
    }

    // Check for login page access or login parameter
    $should_redirect = false;

    // Check for wp-login.php
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        $should_redirect = true;
    }

    // Check for ?login=true
    if (strpos($_SERVER['REQUEST_URI'], '?login=true') !== false) {
        $should_redirect = true;
    }

    // Check for login action parameter (but not logout)
    if (isset($_GET['action']) && $_GET['action'] === 'login') {
        $should_redirect = true;
    }

    if ($should_redirect) {
        wp_redirect(Auth::sso()->getLoginUrl());
        exit;
    }
}

add_action('plugins_loaded', function () {
    // Initialize core services first
    (new AppServiceProvider())->boot();

    // Make sure WooCommerce is active before registering the gateway
    if (class_exists('WooCommerce') && class_exists('WC_Payment_Gateway')) {
        add_filter('woocommerce_payment_gateways', function ($gateways) {
            $gateways[] = \App\Core\WCDonapGateway::class;
            return $gateways;
        });
    }
}, 10);

add_action('init', function () {
    // Initialize providers that need WordPress to be fully loaded
    // Using priority 99 to ensure all translation domains are loaded first
    (new ElementorServiceProvider())->boot();
    (new RouteServiceProvider())->boot();
    (new ShortcodeServiceProvider())->boot();
    (new SessionScoresServiceProvider())->register();
    (new SessionScoresServiceProvider())->boot();
    (new SSOServiceProvider())->boot();
    (new WooServiceProvider())->boot();
    (new AdminServiceProvider())->boot();
    (new GravityServiceProvider())->boot();
    // (new WorkflowServiceProvider())->boot();
    (new HookFilterServiceProvider())->boot();

    // Add login redirect check in init
    securityCheck();
    donapp_check_login_redirect();
}, 99);

// Multiple hooks to ensure we catch all login attempts
add_action('plugins_loaded', 'donapp_check_login_redirect', 999);
add_action('setup_theme', 'donapp_check_login_redirect', 999);
add_action('after_setup_theme', 'donapp_check_login_redirect', 999);
add_action('login_init', 'donapp_check_login_redirect', 1);
add_action('wp_loaded', 'donapp_check_login_redirect', 999);
add_action('wp', 'donapp_check_login_redirect', 999);
add_action('template_redirect', 'donapp_check_login_redirect', 1);



// function custom_footer_script()
// {
//     // Register the script
//     wp_register_script(
//         'custom-audioplayer', // Handle
//         WP_PLUGIN_DIR . '/' . appConfig('app.name') . '/' . 'resources/js/audioplayer.js', // Path to the script
//         array('jquery'), // Dependencies (e.g., jQuery)
//         time(), // Version
//         true // Load in footer
//     );
//     wp_enqueue_script('custom-audioplayer');
// }
// add_action('wp_enqueue_scripts', 'custom_footer_script');