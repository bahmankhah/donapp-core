<?php

return [
    'vendor' => [
        'default' => 'donap',
        'contexts' => [
            'donap' => [
                'context' => App\Adapters\Vendor\Contexts\Donap::class,
                'key' => getenv('DONAPP_EXT_API_KEY'),
                'access_url'=>'https://api.nraymanstage.donap.ir/external-services/donap-payment-status/',
            ]
        ]
    ]
];
