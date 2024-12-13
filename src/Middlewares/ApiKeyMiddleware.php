<?php

namespace Donapp\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;

class ApiKeyMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        $headers = getallheaders();
        $key = 'amir';
        if (isset($headers['X-API-KEY'])) {
            if($headers['X-API-KEY'] !== $key){
                return res(null, 'unauthenticated', 401);
            }
        } else {
            return res(null, 'unauthenticated', 401);
        }
        return $pipeline->next($request);
    }
}