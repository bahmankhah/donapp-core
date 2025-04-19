<?php

namespace App\Providers;

use Kernel\Container;

class HookFilterServiceProvider
{
    public function register() {}

    public function boot()
    {
        // 1) Register our Free Order gateway
        add_filter(
            'woocommerce_payment_gateways',
            [ Container::resolve('WooService'), 'addFreeGateway' ]
        );

        // 2) Skip payment validation on zero‑total carts/orders
        add_filter(
            'woocommerce_cart_needs_payment',
            [ Container::resolve('WooService'), 'allowFreeOrders' ],
            10, 2
        );
        add_filter(
            'woocommerce_order_needs_payment',
            [ Container::resolve('WooService'), 'allowFreeOrders' ],
            10, 2
        );
        // 3) Usual Donap hooks
        add_action(
            'woocommerce_checkout_create_order_line_item',
            [ Container::resolve('WooService'), 'addUserIdToOrderItem' ],
            10, 4
        );
        add_action(
            'woocommerce_payment_complete',
            [ Container::resolve('WooService'), 'processUserIdAfterPayment' ],
            10, 1
        );
        add_action(
            'woocommerce_after_add_to_cart_button',
            [ Container::resolve('WooService'), 'productPageButton' ],
            35
        );
    }
}
