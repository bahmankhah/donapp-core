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

use Donapp\Providers\AppServiceProvider;
use Donapp\Routes\RouteServiceProvider;
use Kernel\PostType;

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

register_activation_hook( __FILE__, function(){
    (new AppServiceProvider())->register();

});

(new AppServiceProvider())->boot();
add_action('plugins_loaded', function() {
    (new RouteServiceProvider())->register();
});
add_action('init', 'custom_api_rewrite');
function custom_api_rewrite() {
    // new PostType();
}