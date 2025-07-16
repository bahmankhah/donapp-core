<?php

namespace App\Core;

use App\Services\WalletService;
use Kernel\Container;
use WC_Order;

class WCDonapGateway extends \WC_Payment_Gateway {

    private WalletService $walletService;

    public function __construct() {
        $this->walletService = Container::resolve('WalletService');

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
        $this->description = $this->get_option('description', 'پرداخت سریع با موجودی کیف پول شما');

        // Handle saving admin settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
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
                'default'     => 'پرداخت سریع با موجودی کیف پول شما',
            ],
        ];
    }

    /**
     * Determine if the gateway is available.
     */
    public function is_available() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_donap_user_id();
        if (!$user_id) {
            return false;
        }

        $balance = $this->walletService->getAvailableCredit($user_id);
        return $balance > 0;
    }

    /**
     * Process the payment and return the result.
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $amount = $order->get_total() * 100; // Assuming wallet is in smallest currency unit

        $identifier = get_donap_user_id();
        $balance = $this->walletService->getAvailableCredit($identifier);

        if ($balance < $amount) {
            wc_add_notice('موجودی کافی نیست.', 'error');
            return ['result' => 'failure'];
        }

        $success = $this->walletService->decreaseCredit($identifier, $amount);
        if (!$success) {
            wc_add_notice('خطا در کسر موجودی کیف پول.', 'error');
            return ['result' => 'failure'];
        }

        $order->payment_complete();
        $order->add_order_note('پرداخت با کیف پول انجام شد.');

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
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
