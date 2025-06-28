<?php

namespace App\Providers;

use App\Core\WCDonapGateway;
use Kernel\Container;
use Kernel\Facades\Auth;
use Kernel\Facades\Wordpress;

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

        Wordpress::filter('login_url', function(){
            appLogger('setting login url');
            return Auth::sso()->getLoginUrl();
        });

        add_filter('login_url', function ($item_data, $cart_item) {
            appLogger('setting login url');
            return Auth::sso()->getLoginUrl();
        }, 10, 1);
        
        add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
            if (!empty($cart_item['wallet_topup'])) {
                $item_data[] = [
                    'name'  => 'نوع محصول',
                    'value' => 'افزایش موجودی کیف پول',
                ];
            }
            return $item_data;
        }, 10, 2);

        add_action('woocommerce_order_status_completed', function ($order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $is_wallet_topup = $item->get_meta('wallet_topup', true);
                if ($is_wallet_topup) {
                    $user_id = get_donap_user_id($order->get_user_id());
                    $amount = $item->get_total() ; // assuming wallet in rials
                    Container::resolve('WalletService')->increaseCredit($user_id, $amount);
                    $order->add_order_note("مبلغ {$amount} ریال به کیف پول افزوده شد.");
                }
            }
        });
    }
}
