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

use Donapp\Container;
use Donapp\Services\AuthService;
use Donapp\Routes\RouteServiceProvider;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

require_once(__DIR__ . '/Kernel/autoload.php');
require_once(__DIR__ . '/src/Helpers/helper.php');

spl_autoload_register(function ($class) {
    $prefix = 'Donapp\\';
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

// Register services in the container
Container::bind('AuthService', function() {
    return new AuthService();
});

// Initialize routes
add_action('plugins_loaded', function() {
    (new RouteServiceProvider)->register();
});
add_action('init', 'custom_api_rewrite');
function custom_api_rewrite() {
    add_rewrite_rule('^api/(.*)?', 'index.php?rest_route=/$1', 'top');
    flush_rewrite_rules(false);
}