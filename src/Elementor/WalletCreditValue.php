<?php

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;
use Kernel\Container;

class WalletCreditValue extends Tag
{

    public function get_name()
    {
        return 'donap-wallet-credit-value';
    }

    public function get_title()
    {
        return 'اعتبار کیف پول';
    }

    public function get_group()
    {
        return 'donap'; // or 'post', 'archive', 'author', or create your own group
    }

    public function get_categories()
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render()
    {
        if (is_user_logged_in()) {
            $user_id = get_donap_user_id();
            $balance = Container::resolve('WalletService')->getAvailableCredit($user_id);
            echo wc_price($balance);
        } else {
            echo 'وارد شوید';
        }
    }
}
