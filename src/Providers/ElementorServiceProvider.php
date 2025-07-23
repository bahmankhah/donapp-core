<?php

namespace App\Providers;

use App\Elementor\WalletCashValue;
use App\Elementor\WalletCreditValue;

class ElementorServiceProvider
{
    public function register() {}

    public function boot()
    {
        // Register custom group first
        // add_action('elementor/dynamic_tags/register_tags', function ($dynamic_tags) {
        //     // Register the custom group
        //     $dynamic_tags->register_group('donap', [
        //         'title' => 'دناپ' // Persian title for your group
        //     ]);
            
        //     // Then register the tags
        //     $dynamic_tags->register_tag(WalletCreditValue::class);
        //     $dynamic_tags->register_tag(WalletCashValue::class);
        // });
    }
}
