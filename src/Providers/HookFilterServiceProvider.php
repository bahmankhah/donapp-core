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

        add_action( 'woocommerce_check_cart_items', [Container::resolve('WooService'), 'beforeCheckout']);
    }
}
