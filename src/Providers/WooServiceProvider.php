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
        add_action('woocommerce_checkout_after_order_review', [$this, 'add_return_button_to_checkout'], 5);
        add_action('wp_ajax_nopriv_clear_cart', [$this, 'clear_cart_ajax_handler']);
    }

    public function clear_cart_ajax_handler()
    {
        die('ssss');
        if (!WC()->cart) {
            // Make sure the cart is initialized before calling empty_cart()
            wc_load_cart();
        }

        // Clear the WooCommerce cart
        WC()->cart->empty_cart();

        // Send a success response
        wp_send_json_success();
    }
    public function add_return_button_to_checkout()
    {
        // Check if the query parameter is present
        if (is_checkout() && isset($_GET['dnpuser']) && !empty($_GET['dnpuser'])) {
            ?>
            <button type="button" id="cancel-purchase-btn" class="button alt"
                style="background-color: #f44336; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; border-radius: 12px; margin-top: 20px;"
                onclick="cancelPurchase()">انصراف از خرید</button>

            <script type="text/javascript">
                function cancelPurchase() {
                    if (confirm("آیا از انصراف از خرید مطمئن هستید؟")) {
                        // Clear the WooCommerce cart using AJAX
                        jQuery.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'POST',
                            data: {
                                action: 'clear_cart'
                            },
                            success: function (response) {
                                // Redirect after clearing the cart
                                if (response.success) {
                                    window.location.href = "https://rayman.donap.ir"; // Replace with your custom URL
                                } else {
                                    alert("خطا در انصراف از خرید. لطفاً دوباره تلاش کنید.");
                                }
                            },
                            error: function (xhr, status, error) {
                                console.log("AJAX error: " + status + "\n" + error);
                            }
                        });
                    }
                }
            </script>
            <?php
        }
    }

}
