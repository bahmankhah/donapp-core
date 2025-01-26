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
                $cartDecoded = json_decode($cart['cart']); // Decode stored products
                $dnpUser = sanitize_text_field($_GET['dnpuser']);

                $productsAdded = false;

                // Loop through the products and add them to the WooCommerce cart
                foreach ($cartDecoded as $productId) {
                    $result = \WC()->cart->add_to_cart($productId, 1, 0, [], ['dnpuser' => $dnpUser]);

                    // Check if the product was successfully added to the cart
                    if ($result) {
                        $productsAdded = true;
                    }
                }

                // If products were added, recalculate totals and persist the cart
                if ($productsAdded) {
                    WC()->cart->calculate_totals(); // Recalculate cart totals
                    WC()->cart->set_session();     // Save cart to session
                }

                // Now delete the processed cart row from the database
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
