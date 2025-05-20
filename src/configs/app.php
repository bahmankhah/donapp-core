<?php

use App\Middlewares\ExceptionMiddleware;
use App\Middlewares\ResponseMiddleware;
use Kernel\Middlewares\AppMiddleware;

return [
    'global_middlewares'=>[
        AppMiddleware::class,
        ExceptionMiddleware::class,
        ResponseMiddleware::class
    ],
    'version'=>'v1',
    'name'=>'donapp-core'
];