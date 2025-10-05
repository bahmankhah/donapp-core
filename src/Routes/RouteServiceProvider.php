<?php
namespace App\Routes;

use App\Controllers\Modules\Proxy\ProxyController;
use App\Controllers\AuthController;
use App\Controllers\BlogController;
use App\Controllers\GravityController;
use App\Controllers\ProductController;
use App\Controllers\TestController;
use App\Controllers\VideoController;
use App\Controllers\WalletController;
use App\Controllers\WooController;
use App\Middlewares\ApiKeyMiddleware;
use App\Services\WalletService;
use Kernel\Facades\Route;

class RouteServiceProvider {
    public function boot() {
        // Route::get('auth-check', [AuthController::class, 'checkAuth'])->make();

        Route::post('product', [AuthController::class, 'product'])->make();
        Route::get('blog', [BlogController::class, 'index'])->make();
        // Route::get('video', [VideoController::class, 'index'])->make();
        Route::get('blog/video', [BlogController::class, 'videoIndex'])->make();

        Route::post('cart', [WooController::class, 'addToCart'])->middleware(ApiKeyMiddleware::class)->make();

        Route::post('wallet/{type}', [WalletController::class, 'addToWallet'])->middleware(ApiKeyMiddleware::class)->make()->name('wallet-post');
        Route::get('wallet/credit', [WalletController::class, 'getWallet'])->middleware(ApiKeyMiddleware::class)->make()->name('wallet-get');

        // Gravity Flow routes
        Route::get('gravity/export-csv', [GravityController::class, 'exportCSV'])->make()->name('gravity-export-csv');
        Route::get('gravity/export-xlsx', [GravityController::class, 'exportXLSX'])->make()->name('gravity-export-xlsx');
        Route::get('gravity/entries', [GravityController::class, 'getApprovedEntries'])->make()->name('gravity-entries-api');

    }
}
