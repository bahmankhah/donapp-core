<?php

return [
    'vendor' => [
        'default' => 'donap',
        'contexts' => [
            'donap' => [
                'context' => App\Adapters\Vendor\Contexts\Donap::class,
                'key' => getenv('DONAPP_EXT_API_KEY'),
                'access_url'=>'https://api.rayman.donap.ir/external-services/donap-payment-status/',
                'purchased_redirect_url'=>'https://rayman.donap.ir/myProducts/{slug}',
                'product_page'=>'https://rayman.donap.ir/products/details/{slug}',
                'main_url'=>'https://rayman.donap.ir'
            ]
        ]
    ]
];
