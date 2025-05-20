<?php

namespace App\Providers;

use DateTime;
use App\Models\UserCart;
use App\Services\AuthService;
use App\Services\BlogService;
use App\Services\ProductService;
use App\Services\VideoService;
use App\Services\WooService;
use Kernel\Container;
use Kernel\Facades\Auth;
use Kernel\PostType;

class SSOServiceProvider
{

    public function register() {}

    public function boot()
    {
        if (isset($_GET['code'])) {
            Auth::sso()->attempt(['code'=>$_GET['code']]);
        }
    }
    
}
