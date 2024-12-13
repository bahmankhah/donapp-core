<?php

namespace Donapp\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;

class ApiKeyMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        $headers = getallheaders();
        $apiKey = getenv( 'DONAPP_API_KEY' );
        if (isset($headers['X-API-KEY'])) {
            if($headers['X-API-KEY'] !== $apiKey){
                return res(null, 'unauthenticated', 401);
            }
        } else {
            return res(null, 'unauthenticated', 401);
        }
        return $pipeline->next($request);
    }
}