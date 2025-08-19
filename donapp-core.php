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

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ElementorServiceProvider;
use App\Providers\HookFilterServiceProvider;
use App\Providers\ShortcodeServiceProvider;
use App\Providers\SSOServiceProvider;
use App\Providers\WooServiceProvider;
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
add_action('plugins_loaded', function () {
    (new AppServiceProvider())->boot();
    
    // Make sure WooCommerce is active before registering the gateway
    if (class_exists('WooCommerce') && class_exists('WC_Payment_Gateway')) {
        add_filter('woocommerce_payment_gateways', function ($gateways) {
            $gateways[] = \App\Core\WCDonapGateway::class;
            return $gateways;
        });
        
    }
    (new HookFilterServiceProvider())->boot();
});

add_action('init', function () {
    (new ElementorServiceProvider())->boot();
    (new RouteServiceProvider())->boot();
    (new ShortcodeServiceProvider())->boot();
    (new SSOServiceProvider())->boot();
    (new WooServiceProvider())->boot();
    (new AdminServiceProvider())->boot();
});

// Handle login page redirects - intercept wp-login.php specifically
add_action('login_init', function () {
    // Check if it's not a logout action
    if (!isset($_GET['action']) || $_GET['action'] !== 'logout') {
        // If user is not logged in, redirect to SSO
        if (!is_user_logged_in()) {
            wp_redirect(Auth::sso()->getLoginUrl());
            exit;
        }
    }
});

// Handle other login attempts (like ?login=true)
add_action('wp', function () {
    // Only on frontend (not admin)
    if (is_admin()) {
        return;
    }
    
    // Check for ?login=true parameter
    if (strpos($_SERVER['REQUEST_URI'], '?login=true') !== false && !is_user_logged_in()) {
        wp_redirect(Auth::sso()->getLoginUrl());
        exit;
    }
});



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
