<?php

namespace Donapp\Providers;

use DateTime;
use Donapp\Models\UserCart;
use Donapp\Services\AuthService;
use Donapp\Services\BlogService;
use Donapp\Services\ProductService;
use Donapp\Services\VideoService;
use Donapp\Services\WooService;
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
                foreach ($cartDecoded as $productId) {
                    WC()->cart->add_to_cart($productId);
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
