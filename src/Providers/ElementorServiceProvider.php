<?php

namespace App\Providers;

use App\Elementor\WalletCashValue;
use App\Elementor\WalletCreditValue;

class ElementorServiceProvider
{
    public function register() {}

    public function boot()
    {
        add_action('elementor/dynamic_tags/register_tags', function ($dynamic_tags) {
            $dynamic_tags->register_tag(WalletCreditValue::class);
            $dynamic_tags->register_tag(WalletCashValue::class);
        });
    }
}
