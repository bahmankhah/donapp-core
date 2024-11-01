<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;
use Donapp\Middlewares\TestMiddleware;
use Kernel\Route;

class RouteServiceProvider {
    public function register() {
        // add_action('rest_api_init', function () {
        //     register_rest_route('donapp/v1', '/auth-check', [
        //         'methods' => 'GET',
        //         'callback' => [(new AuthController()), 'checkAuth'],
        //         'permission_callback' => '__return_true',
        //     ]);
        // });

        (new Route())->get('auth-check', [AuthController::class, 'checkAuth'])->middleware(TestMiddleware::class)->make();
    }
    
}
