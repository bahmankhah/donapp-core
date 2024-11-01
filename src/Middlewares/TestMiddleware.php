<?php

namespace Donapp\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;

class TestMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        // die('amir');
        return $pipeline->next($request);
    }
}