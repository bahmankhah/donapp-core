<?php

namespace App\Providers;

use Kernel\Facades\Wordpress;

class ShortcodeServiceProvider
{
    public function register() {}

    public function boot()
    {
        Wordpress::shortcode('donap_wallet_topup',function () {
            if (!is_user_logged_in()) {
                return '<p>برای شارژ کیف پول ابتدا وارد شوید.</p>';
            }
            return view('pages/wallet-topup');
        });
    }
}
