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
use Kernel\PostType;

class WooServiceProvider
{

    public function register() {}

    public function boot()
    {
        if (isset($_GET['dnpuser'])) {
            /** @var WooService $wooService */
            $wooService = Container::resolve('WooService');
            $wooService->deleteExpiredCarts();
            $userCart = new UserCart();
            $cart = $userCart->where('identifier', '=', $_GET['dnpuser'])->first();
            if ($cart) {
                $cartDecoded = json_decode($cart['cart']);
                $dnpUser = sanitize_text_field($_GET['dnpuser']); 
                foreach ($cartDecoded as $productId) {
                    \WC()->cart->add_to_cart($productId, 1, 0, [], ['dnpuser' => $dnpUser]);
                }
                $userCart->delete(
                    [
                        'id' => $cart['id']
                    ],
                    ['%d']
                );
            }
        }
    }
}
