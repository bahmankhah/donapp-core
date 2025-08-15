<?php

namespace App\Providers;

use App\Core\WCDonapGateway;
use Kernel\Container;
use Kernel\Facades\Auth;
use Kernel\Facades\Wordpress;
use Exception;

class HookFilterServiceProvider
{
    public function register() {}

    public function boot()
    {



        Wordpress::filter('login_url', function ($login_url, $redirect, $force_reauth) {
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

        // Function to process wallet topup
        $processWalletTopup = function ($order_id, $hook_name = '') {
            appLogger("{$hook_name} hook triggered for order ID: {$order_id}");
            
            $order = wc_get_order($order_id);
            if (!$order) {
                appLogger("Order not found for ID: {$order_id}");
                return;
            }
            
            // Check if wallet topup was already processed to avoid duplicates
            $already_processed = $order->get_meta('_wallet_topup_processed');
            if ($already_processed) {
                appLogger("Wallet topup already processed for order {$order_id}, skipping");
                return;
            }
            
            appLogger("Order found, processing items. Order user ID: " . $order->get_user_id() . ", Order Status: " . $order->get_status());
            
            foreach ($order->get_items() as $item_id => $item) {
                appLogger("Processing item ID: {$item_id}, Item name: " . $item->get_name());
                
                $is_wallet_topup = $item->get_meta('wallet_topup', true);
                appLogger("Item wallet_topup meta: " . ($is_wallet_topup ? 'true' : 'false'));
                
                if ($is_wallet_topup) {
                    $wordpress_user_id = $order->get_user_id();
                    $user_id = get_donap_user_id($wordpress_user_id);
                    $amount = $item['line_total'];
                    
                    appLogger("Wallet topup detected! WordPress User ID: {$wordpress_user_id}, Donap User ID: {$user_id}, Amount: {$amount}");
                    
                    try {
                        Container::resolve('WalletService')->increaseCredit($user_id, $amount);
                        appLogger("Successfully called increaseCredit for user {$user_id} with amount {$amount}");
                        
                        // Calculate gift amount using GiftService
                        $gift_amount = Container::resolve('GiftService')->calculateGift($amount);
                        appLogger("Gift amount calculated: {$gift_amount}");
                        
                        if ($gift_amount > 0) {
                            Container::resolve('WalletService')->addGift($user_id, $gift_amount);
                            $gift_percentage = Container::resolve('GiftService')->getGiftPercentage($amount);
                            $order->add_order_note("مبلغ {$amount} ریال به کیف پول افزوده شد. هدیه {$gift_percentage}% ({$gift_amount} ریال) اعطا شد.");
                            appLogger("Gift added successfully. Percentage: {$gift_percentage}%");
                        } else {
                            $order->add_order_note("مبلغ {$amount} ریال به کیف پول افزوده شد.");
                            appLogger("No gift amount, only main credit added");
                        }
                        
                        // Mark as processed to avoid duplicates
                        $order->update_meta_data('_wallet_topup_processed', true);
                        $order->save();
                        appLogger("Marked order {$order_id} as wallet topup processed");
                        
                    } catch (Exception $e) {
                        appLogger("Error processing wallet topup: " . $e->getMessage());
                        $order->add_order_note("خطا در افزایش موجودی کیف پول: " . $e->getMessage());
                    }
                } else {
                    appLogger("Item is not a wallet topup, skipping");
                }
            }
        };

        // Hook into multiple payment completion events
        add_action('woocommerce_payment_complete', function($order_id) use ($processWalletTopup) {
            $processWalletTopup($order_id, 'woocommerce_payment_complete');
        });
        
        add_action('woocommerce_order_status_completed', function($order_id) use ($processWalletTopup) {
            $processWalletTopup($order_id, 'woocommerce_order_status_completed');
        });
        
        add_action('woocommerce_order_status_processing', function($order_id) use ($processWalletTopup) {
            $processWalletTopup($order_id, 'woocommerce_order_status_processing');
        });
        
        // Also hook into when order status changes to paid statuses
        add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) use ($processWalletTopup) {
            appLogger("Order {$order_id} status changed from {$old_status} to {$new_status}");
            if (in_array($new_status, ['completed', 'processing'])) {
                $processWalletTopup($order_id, 'woocommerce_order_status_changed');
            }
        }, 10, 3);
    }
}
