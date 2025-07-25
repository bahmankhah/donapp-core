<?php

namespace App\Providers;

use App\Elementor\WalletCashValue;
use App\Elementor\WalletCreditValue;

class ElementorServiceProvider
{
    public function register() {}

    public function boot()
    {
        add_action('elementor/dynamic_tags/register_tags', function ($dynamic_tags_manager) {
            // Register the custom group
            $dynamic_tags_manager->register_group('donap', [
                'title' => 'دناپ' // Persian title for your group
            ]);
            
            // Then register the tags
            $dynamic_tags_manager->register(new WalletCreditValue());
            $dynamic_tags_manager->register(new WalletCashValue());
        });
    }
}
