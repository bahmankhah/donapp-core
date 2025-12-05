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
        add_action('woocommerce_before_checkout_form', [$this, 'add_return_button_to_checkout'], 5);

    }

    public function add_return_button_to_checkout()
    {
        // Check if the query parameter is present
        if (isset($_GET['dnpuser']) && !empty($_GET['dnpuser'])) {
            // Create the button HTML
            echo '<div style="text-align: center; margin-bottom: 20px;">';
            echo '<a href="https://rayman.donap.ir" class="button alt" style="padding: 10px 20px; background-color: #FF6600; color: white; text-decoration: none;">بازگشت به رایمن</a>';
            echo '</div>';
        }
    }

}
