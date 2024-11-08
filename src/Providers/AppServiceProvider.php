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


    }
}