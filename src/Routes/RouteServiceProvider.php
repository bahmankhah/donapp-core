<?php
namespace Donapp\Routes;

use Donapp\Controllers\AuthController;
use Donapp\Controllers\BlogController;
use Donapp\Controllers\ProductController;
use Donapp\Controllers\VideoController;
use Donapp\Controllers\WooController;
use Donapp\Middlewares\ApiKeyMiddleware;
use Kernel\Route;

class RouteServiceProvider {
    public function register() {
        (new Route())->get('auth-check', [AuthController::class, 'checkAuth'])->make();

        (new Route())->post('product', [AuthController::class, 'product'])->make();
        (new Route())->get('blog', [BlogController::class, 'index'])->make();
        (new Route())->get('video', [VideoController::class, 'index'])->make();
        (new Route())->get('blog/video', [BlogController::class, 'videoIndex'])->make();

        (new Route())->post('cart', [WooController::class, 'addToCart'])->middleware(ApiKeyMiddleware::class)->make();
    }
}
