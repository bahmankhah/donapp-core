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
define('WP_DEBUG_LOG', true);

use Donapp\Providers\AppServiceProvider;
use Donapp\Providers\HookFilterServiceProvider;
use Donapp\Providers\WooServiceProvider;
use Donapp\Routes\RouteServiceProvider;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

require_once(__DIR__ . '/Kernel/autoload.php');
require_once(__DIR__ . '/src/Helpers/helper.php');

load_env_file( __DIR__ . '/.env' );

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

register_activation_hook( __FILE__, function(){
    (new AppServiceProvider())->register();
});
add_action('plugins_loaded', function() {
});
add_action('init', function() {
    (new RouteServiceProvider())->boot();
    (new AppServiceProvider())->boot();
    (new WooServiceProvider())->boot();
    (new HookFilterServiceProvider())->boot();
});