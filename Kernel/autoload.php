<?php
use Kernel\Config;

spl_autoload_register(function ($class) {
    $prefix = 'Kernel\\';
    $base_dir = __DIR__ . '/';

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

foreach (glob(__DIR__ . '/Helpers/*.php') as $file) {
    require_once $file;
}

new Config();