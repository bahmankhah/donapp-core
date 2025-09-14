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
use App\Controllers\WorkflowController;
use App\Middlewares\ApiKeyMiddleware;
use App\Services\WalletService;
use Kernel\Facades\Route;

class RouteServiceProvider
{
    public function boot()
    {
        // Route::get('auth-check', [AuthController::class, 'checkAuth'])->make();

        Route::post('product', [AuthController::class, 'product'])->make();
        Route::get('blog', [BlogController::class, 'index'])->make();
        // Route::get('video', [VideoController::class, 'index'])->make();
        Route::get('blog/video', [BlogController::class, 'videoIndex'])->make();

        Route::post('cart', [WooController::class, 'addToCart'])->middleware(ApiKeyMiddleware::class)->make();

        Route::post('wallet/{type}', [WalletController::class, 'addToWallet'])->middleware(ApiKeyMiddleware::class)->make()->name('wallet-post');
        Route::get('wallet/credit', [WalletController::class, 'getWallet'])->middleware(ApiKeyMiddleware::class)->make()->name('wallet-get');

        // Gravity Flow routes
        Route::get('gravity/export-csv', [GravityController::class, 'exportApprovedEntriesCSV'])->make()->name('gravity-export-csv');
        Route::get('gravity/export-xlsx', [GravityController::class, 'exportApprovedEntriesXLSX'])->make()->name('gravity-export-xlsx');
        // Route::get('gravity/export-pdf', [GravityController::class, 'exportPDF'])->make()->name('gravity-export-pdf');
        // Route::get('gravity/entries', [GravityController::class, 'getApprovedEntries'])->make()->name('gravity-entries-api');

        // Single entry export routes
        Route::get('gravity/entry/export-pdf', [GravityController::class, 'exportSingleEntryPDF'])->make()->name('gravity-entry-export-pdf');
        Route::get('gravity/entry/export-excel', [GravityController::class, 'exportSingleEntryExcel'])->make()->name('gravity-entry-export-excel');

        // Enhanced Gravity Flow inbox bulk actions
        Route::post('gravity/bulk-action', [GravityController::class, 'handleBulkAction'])->make()->name('gravity-bulk-action');

        // Workflow automation API routes
        Route::get('workflow/dashboard', [WorkflowController::class, 'getDashboardData'])->make()->name('workflow-dashboard-api');
        Route::post('workflow/task-action', [WorkflowController::class, 'handleTaskAction'])->make()->name('workflow-task-action');
        Route::get('workflow/my-tasks', [WorkflowController::class, 'getMyTasks'])->make()->name('workflow-my-tasks');
        Route::get('workflow/history', [WorkflowController::class, 'getEntryWorkflowHistory'])->make()->name('workflow-entry-history');
        Route::post('workflow/test', [WorkflowController::class, 'createTestWorkflow'])->make()->name('workflow-test');
    }
}
