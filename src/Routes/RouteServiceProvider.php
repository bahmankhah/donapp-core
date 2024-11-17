<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;
use Donapp\Controllers\BlogController;
use Donapp\Controllers\ProductController;
use Donapp\Controllers\VideoController;
use Donapp\Middlewares\TestMiddleware;
use Kernel\Route;

class RouteServiceProvider {
    public function register() {
        (new Route())->get('auth-check', [AuthController::class, 'checkAuth'])->middleware(TestMiddleware::class)->make();

        (new Route())->post('product', [AuthController::class, 'product'])->middleware(TestMiddleware::class)->make();
        (new Route())->get('blog', [BlogController::class, 'index'])->make();
        (new Route())->get('video', [VideoController::class, 'index'])->make();
        (new Route())->get('test', [BlogController::class, 'test'])->make();


    }
}
