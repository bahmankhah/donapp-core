<?php

namespace App\Providers;

use Kernel\Container;

class HookFilterServiceProvider
{

    public function register() {}

    public function boot()
    {
        add_action('woocommerce_checkout_create_order_line_item', [Container::resolve('WooService'), 'addUserIdToOrderItem'], 10, 4);
        add_action('woocommerce_payment_complete', [Container::resolve('WooService'), 'processUserIdAfterPayment'], 10, 1);
        add_action('woocommerce_after_add_to_cart_button', [Container::resolve('WooService'), 'productPageButton'], 35);

// function add_custom_button_to_product_page() {
//     echo '<a href="https://example.com/custom-link" class="button custom-button">Custom Button</a>';
// }
    }
}
