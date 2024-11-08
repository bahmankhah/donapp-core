<?php

namespace Donapp\Providers;

use Donapp\Controllers\ProductService;
use Donapp\Services\AuthService;
use Donapp\Services\BlogService;
use Kernel\Container;

class AppServiceProvider {

    public function register() {
                
        Container::bind('AuthService', function() {
            return new AuthService();
        });
        Container::bind('ProductService', function() {
            return new ProductService();
        });
        Container::bind('BlogService', function() {
            return new BlogService();
        });
    }
}