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
    'party' => [
        'default' => 'donap',
        'contexts' => [
            'donap' => [
                'context' => App\Adapters\Party\Contexts\Donap::class,
                'main_url' => getenv('PARTY_MAIN_URL') ?? 'https://devapi.donap.ir'
            ]
        ]
    ],

    'auth' => [
        'default' => 'sso',
        'contexts' => [
            'sso' => [
                'context' => Kernel\Auth\Guards\SSOGuard::class,
                'login_url' => trim(getenv('AUTH_SSO_LOGIN_URL'), '"'),
                'logout_url' => trim(getenv('AUTH_SSO_LOGOUT_URL'), '"'),
                'client_id' => trim(getenv('AUTH_SSO_CLIENT_ID'), '"'),
                'redirect_url'=> trim(getenv('AUTH_SSO_REDIRECT_URL'), '"'),
                'logout_redirect_url'=> trim(getenv('AUTH_SSO_LOGOUT_REDIRECT_URL'), '"'),
                'validate_url'=> trim(getenv('AUTH_SSO_VALIDATE_URL'), '"'),
            ]
        ]
    ],
    'wallet'=>[
        'default'=>'credit',
        'contexts'=>[
            'credit'=>[
                'context'=>App\Adapters\Wallet\Contexts\Credit::class,
                'type'=>App\Core\WalletType::CREDIT,
            ],
            'coin'=>[
                'context'=>App\Adapters\Wallet\Contexts\Coin::class,
                'type'=>App\Core\WalletType::COIN,
            ],
            'cash'=>[
                'context'=>App\Adapters\Wallet\Contexts\Cash::class,
                'type'=>App\Core\WalletType::CASH,
            ],
            'suspended'=>[
                'context'=>App\Adapters\Wallet\Contexts\Suspended::class,
                'type'=>App\Core\WalletType::SUSPENDED,
            ],
            'virtualCreditCash'=>[
                'context'=>App\Adapters\Wallet\Contexts\VirtualCreditCash::class,
                'type'=> null, // virtual
            ]
        ]
    ]
];
