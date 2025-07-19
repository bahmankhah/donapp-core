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



        Wordpress::filter('login_url', function ($login_url, $redirect, $force_reauth) {
            appLogger('setting login url');
            return Auth::sso()->getLoginUrl();
        }, 1, 3);
        Wordpress::action('wp_logout', function(){
            wp_safe_redirect( home_url() );
            exit;
        });

        add_action('woocommerce_checkout_create_order_line_item', [Container::resolve('WooService'), 'addUserIdToOrderItem'], 10, 4);
        add_action('woocommerce_payment_complete', [Container::resolve('WooService'), 'processUserIdAfterPayment'], 10, 1);
        add_action('woocommerce_after_add_to_cart_button', [Container::resolve('WooService'), 'productPageButton'], 35);
        add_action('woocommerce_check_cart_items', [Container::resolve('WooService'), 'beforeCheckout']);

        // Transfer wallet_topup meta from cart item to order item
        add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
            if (!empty($values['wallet_topup'])) {
                $item->add_meta_data('wallet_topup', true);
            }
        }, 10, 4);



        // add_filter('wp_nav_menu_items', function ($items, $args) {
        //     if (is_user_logged_in() && $args->theme_location === 'primary') {
        //         $user_id = get_donap_user_id();
        //         $balance = Container::resolve('WalletService')->getAvailableCredit($user_id);
        //         $wallet_display = view('components/wallet-navbar', ['balance' => $balance]);
        //         $items .= $wallet_display;
        //     }
        //     return $items;
        // }, 10, 2);


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
                    // Get the line total using array access method
                    $amount = $item['line_total'];
                    Container::resolve('WalletService')->increaseCredit($user_id, $amount);
                    
                    // Calculate gift amount using GiftService
                    $gift_amount = Container::resolve('GiftService')->calculateGift($amount);
                    if ($gift_amount > 0) {
                        Container::resolve('WalletService')->addGift($user_id, $gift_amount);
                        $gift_percentage = Container::resolve('GiftService')->getGiftPercentage($amount);
                        $order->add_order_note("مبلغ {$amount} ریال به کیف پول افزوده شد. هدیه {$gift_percentage}% ({$gift_amount} ریال) اعطا شد.");
                    } else {
                        $order->add_order_note("مبلغ {$amount} ریال به کیف پول افزوده شد.");
                    }
                }
            }
        });
    }
}
