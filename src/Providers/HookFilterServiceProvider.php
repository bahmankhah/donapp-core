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
    }
}
