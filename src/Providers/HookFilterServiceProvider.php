<?php

namespace App\Providers;

use App\Core\WCDonapGateway;
use Kernel\Container;

class HookFilterServiceProvider
{
    public function register() {}

    public function boot()
    {

        add_action('woocommerce_checkout_create_order_line_item', [Container::resolve('WooService'), 'addUserIdToOrderItem'], 10, 4);
        add_action('woocommerce_payment_complete', [Container::resolve('WooService'), 'processUserIdAfterPayment'], 10, 1);
        add_action('woocommerce_after_add_to_cart_button', [Container::resolve('WooService'), 'productPageButton'], 35);
        add_action('woocommerce_check_cart_items', [Container::resolve('WooService'), 'beforeCheckout']);

        add_filter('woocommerce_payment_gateways', function ($gateways) {
            $gateways[] = WCDonapGateway::class;
            return $gateways;
        });

        add_filter('wp_nav_menu_items', function ($items, $args) {
            if (is_user_logged_in() && $args->theme_location === 'primary') {
                $user_id = get_donap_user_id();
                $balance = Container::resolve('WalletService')->getAvailableCredit($user_id);
                $wallet_display = view('components/wallet-navbar', ['balance' => $balance]);
                $items .= $wallet_display;
            }
            return $items;
        }, 10, 2);
    }
}
