<?php

return [
    'vendor' => [
        'default' => 'donap',
        'contexts' => [
            'donap' => [
                'context' => App\Adapters\Vendor\Contexts\Donap::class,
                'key' => getenv('DONAPP_EXT_API_KEY'),
                'access_url' => 'https://api.rayman.donap.ir/external-services/donap-payment-status/',
                'purchased_redirect_url' => 'https://rayman.donap.ir/myProducts/{slug}',
                'product_page' => 'https://rayman.donap.ir/products/details/{slug}',
                'main_url' => 'https://rayman.donap.ir'
            ]
        ]
    ],

    'auth' => [
        'default' => 'sso',
        'contexts' => [
            'sso' => [
                'context' => Kernel\Auth\Guards\SSOGuard::class,
                'login_url' => 'https://auth.platform.donap.ir/realms/donap/protocol/openid-connect/auth?client_id={clientId}&response_type=code&scope=openid profile&redirectUri={redirectUri}',
                'client_id' => 'platform',
                'validate_url'=>'https://auth.platform.donap.ir/realms/donap/protocol/openid-connect/token'
            ]
        ]
    ],
    // 'wallet'=>[
    //     'default'=>'credit',
    //     'contexts'=>[
    //         'credit'=>[
    //             'context'=>App\Adapters\Wallet\Contexts\Credit::class,
    //             'type'=>App\Helpers\WalletType::CREDIT,
    //         ],
    //         'coin'=>[
    //             'context'=>App\Adapters\Wallet\Contexts\Coin::class,
    //             'type'=>App\Helpers\WalletType::COIN,
    //         ],
    //         'cash'=>[
    //             'context'=>App\Adapters\Wallet\Contexts\Cash::class,
    //             'type'=>App\Helpers\WalletType::CASH,
    //         ],
    //         'virtualCreditCash'=>[
    //             'context'=>App\Adapters\Wallet\Contexts\VirtualCreditCash::class,
    //             'type'=> null, // virtual
    //         ]
    //     ]
    // ]
];
