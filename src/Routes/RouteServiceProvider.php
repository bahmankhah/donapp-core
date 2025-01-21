<?php
namespace App\Routes;

use App\Controllers\Modules\Proxy\ProxyController;
use App\Controllers\AuthController;
use App\Controllers\BlogController;
use App\Controllers\ProductController;
use App\Controllers\TestController;
use App\Controllers\VideoController;
use App\Controllers\WooController;
use App\Middlewares\ApiKeyMiddleware;
use Kernel\Facades\Route;

class RouteServiceProvider {
    public function boot() {
        Route::get('auth-check', [AuthController::class, 'checkAuth'])->make();

        Route::post('product', [AuthController::class, 'product'])->make();
        (new \Kernel\Route())->get('blog', [BlogController::class, 'index'])->make();
        Route::get('video', [VideoController::class, 'index'])->make();
        Route::get('blog/video', [BlogController::class, 'videoIndex'])->make();

        (new \Kernel\Route())->post('cart', [WooController::class, 'addToCart'])->middleware(ApiKeyMiddleware::class)->make();

        Route::get('test', [TestController::class, 'test'])->make();
    }
}
