<?php

namespace App\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;

class ResponseMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        $result = $pipeline->next($request);
        return res($result);
    }
}