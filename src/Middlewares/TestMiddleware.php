<?php

namespace Donapp\Middlewares;
use Kernel\Middleware;
class TestMiddleware implements Middleware{
    public function handle($pipeline){
        // die('amir');
        return $pipeline->next();
    }
}