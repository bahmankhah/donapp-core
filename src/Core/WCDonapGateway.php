<?php

namespace App\Core;

use App\Services\WalletService;
use Kernel\Container;
use WC_Order;
use Exception;

class WCDonapGateway extends \WC_Payment_Gateway {

    private WalletService $walletService;

    public function __construct() {
        
        try {
            $this->walletService = Container::resolve('WalletService');
        } catch (Exception $e) {
            // Don't return here, continue with basic setup
        }

        // Gateway ID and metadata
        $this->id                 = 'donap_wallet';
        $this->method_title       = 'کیف پول';
        $this->method_description = 'پرداخت با موجودی کیف پول';
        $this->has_fields         = false;
        $this->supports           = ['products'];

        // Load the form fields and settings
        $this->init_form_fields();
        $this->init_settings();

        // Load settings
        $this->title   = $this->get_option('title', 'پرداخت با کیف پول');
        $this->enabled = $this->get_option('enabled', 'yes');
        $balance = $this->getBalance();
        $formattedBalance = number_format($balance);
        $this->description = $this->get_option(
            'description',
            'پرداخت سریع با موجودی کیف پول شما - ' . $formattedBalance . ' تومان'
        );

        // Handle saving admin settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        
    }

    private function getBalance(){
        $user_id = get_donap_user_id();
        if (!$user_id || !isset($this->walletService)) {
            return 0;
        }
        try {
            return $this->walletService->getAvailableCredit($user_id);
        } catch (Exception $e) {
            appLogger('WCDonapGateway::getBalance() failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Define settings fields in admin panel.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'       => 'فعال‌سازی',
                'label'       => 'فعال‌سازی پرداخت با کیف پول',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ],
            'title' => [
                'title'       => 'عنوان',
                'type'        => 'text',
                'description' => 'عنوانی که در صفحه پرداخت نمایش داده می‌شود.',
                'default'     => 'پرداخت با کیف پول',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'توضیحات',
                'type'        => 'textarea',
                'description' => 'توضیحاتی برای مشتریان که در هنگام انتخاب درگاه کیف پول نمایش داده می‌شود.',
                'default'     => $this->description,
            ],
        ];
    }

    /**
     * Determine if the gateway is available.
     */
    public function is_available() {
        
        if ($this->enabled !== 'yes') {
            appLogger('WCDonapGateway: Gateway not enabled');
            return false;
        }

        appLogger('WCDonapGateway: Gateway is enabled, checking conditions...');
        
        // Check if cart contains wallet top-up items - if so, hide this gateway
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (!empty($cart_item['wallet_topup'])) {
                    appLogger('WCDonapGateway: Cart contains wallet top-up item, hiding gateway');
                    return false;
                }
            }
        }
        
        if (!is_user_logged_in()) {
            appLogger('WCDonapGateway: User not logged in');
            return false;
        }

        $user_id = get_donap_user_id();
        if (!$user_id) {
            appLogger('WCDonapGateway: No donap user ID found');
            return false;
        }

        appLogger('WCDonapGateway: User ID found: ' . $user_id);

        if (!isset($this->walletService)) {
            appLogger('WCDonapGateway: WalletService not available');
            return false;
        }

        // Add timeout protection
        set_time_limit(5); // 5 seconds max
        
        try {
            $balance = $this->walletService->getAvailableCredit($user_id);
            appLogger('WCDonapGateway: Available balance: ' . $balance);
            $isAvailable = $balance > 0;
            appLogger('WCDonapGateway: Gateway available: ' . ($isAvailable ? 'yes' : 'no'));
            return $isAvailable;
        } catch (Exception $e) {
            appLogger('WCDonapGateway: getAvailableCredit failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process the payment and return the result.
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $amount = $order->get_total();

        appLogger('WCDonapGateway: Processing payment for order ' . $order_id . ' with amount: ' . $amount);


        $identifier = get_donap_user_id();
        
        appLogger('WCDonapGateway: About to check balance for payment');
        
        try {
            $balance = $this->walletService->getAvailableCredit($identifier);
            appLogger('WCDonapGateway: Balance for payment: ' . $balance);
            
            if ($balance < $amount) {
                appLogger('WCDonapGateway: Insufficient balance');
                wc_add_notice('موجودی کافی نیست.', 'error');
                return ['result' => 'failure'];
            }

            appLogger('WCDonapGateway: About to decrease credit');
            $success = $this->walletService->decreaseCredit($identifier, $amount);
            
            if (!$success) {
                appLogger('WCDonapGateway: Failed to decrease credit');
                wc_add_notice('خطا در کسر موجودی کیف پول.', 'error');
                return ['result' => 'failure'];
            }

            appLogger('WCDonapGateway: Payment completed successfully');
            $order->payment_complete();
            $order->add_order_note('پرداخت با کیف پول انجام شد.');

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        } catch (Exception $e) {
            appLogger('WCDonapGateway: Exception in process_payment: ' . $e->getMessage());
            wc_add_notice('خطا در پردازش پرداخت.', 'error');
            return ['result' => 'failure'];
        }
    }

    /**
     * Show description on checkout page (optional override).
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
    }
}
