<?php

use App\Middlewares\ExceptionMiddleware;
use App\Middlewares\ResponseMiddleware;

return [
    'global_middlewares'=>[
        ExceptionMiddleware::class,
        ResponseMiddleware::class
    ],
    'version'=>'v1',
    'name'=>'donapp-core'
];