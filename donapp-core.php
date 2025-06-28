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

use App\Providers\AppServiceProvider;
use App\Providers\AudioPlayerServiceProvider;
use App\Providers\HookFilterServiceProvider;
use App\Providers\ShortcodeServiceProvider;
use App\Providers\WooServiceProvider;
use App\Routes\RouteServiceProvider;

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
add_action('plugins_loaded', function () {});
add_action('init', function () {
    appLogger('init plugin');
    (new RouteServiceProvider())->boot();
    (new AppServiceProvider())->boot();
    (new WooServiceProvider())->boot();
    (new HookFilterServiceProvider())->boot();
    (new ShortcodeServiceProvider())->boot();
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


