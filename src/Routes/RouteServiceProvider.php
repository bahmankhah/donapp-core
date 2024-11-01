<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;
use Donapp\Middlewares\TestMiddleware;
use Kernel\Route;

class RouteServiceProvider {
    public function register() {
        (new Route())->get('auth-check', [AuthController::class, 'checkAuth'])->middleware(TestMiddleware::class)->make();
    }
    
}
