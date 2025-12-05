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

    public function register()
    {
    }

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

                // Explicitly load the current WooCommerce cart session
                WC()->cart->get_cart();

                // Loop through the products and add them to the WooCommerce cart
                foreach ($cartDecoded as $productId) {
                    // Add the product to the cart
                    $result = \WC()->cart->add_to_cart($productId, 1, 0, [], ['dnpuser' => $dnpUser]);

                    // Check if the product was successfully added
                    if ($result) {
                        $productsAdded = true;
                    }
                }

                // If products were added, recalculate totals and persist the cart session
                if ($productsAdded) {
                    WC()->cart->calculate_totals(); // Recalculate cart totals
                    WC()->cart->set_session();     // Save the cart session explicitly
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
        add_action('woocommerce_review_order_after_submit', [$this, 'add_return_button_to_checkout'], 5);

    }

    public function add_return_button_to_checkout()
    {
        // Check if the query parameter is present
        if (is_checkout() && isset($_GET['dnpuser']) && !empty($_GET['dnpuser'])) {
            // Button HTML with light green and rounded edges
            echo '<div style="text-align: center; margin-top: 20px; margin-bottom: 20px;">';
            echo '<a href="https://your-redirect-url.com" class="button alt" style="background-color: #58cc02; color: #fff; padding: 12px 24px; border-radius: 30px; text-decoration: none; font-size: 16px; display: inline-block;">بازگشت به رایمن</a>';
            echo '</div>';
        }

    }

}
