<?php

use Donapp\Middlewares\ExceptionMiddleware;
use Donapp\Middlewares\ResponseMiddleware;

return [
    'global_middlewares'=>[
        ExceptionMiddleware::class,
        ResponseMiddleware::class
    ]
];