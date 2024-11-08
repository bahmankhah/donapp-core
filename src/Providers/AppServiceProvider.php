<?php

namespace Donapp\Providers;

use Donapp\Controllers\ProductService;
use Donapp\Routes\RouteServiceProvider;
use Donapp\Services\AuthService;
use Kernel\Container;

class AppServiceProvider {

    public function register() {
                
        // Register services in the container
        Container::bind('AuthService', function() {
            return new AuthService();
        });
        Container::bind('ProductService', function() {
            return new ProductService();
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
    }
}