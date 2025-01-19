<?php

return [
    'vendor' => [
        'default' => 'donap',
        'adapters' => [
            'donap' => [
                'context' => Donapp\Adapters\Vendor\Contexts\Donap::class,
                'key' => getenv('DONAPP_EXT_API_KEY'),
            ]
        ]
    ]
];
