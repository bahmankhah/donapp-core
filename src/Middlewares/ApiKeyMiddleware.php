<?php

namespace App\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;
use Exception;
class ApiKeyMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        $headers = getallheaders();
        $apiKey = getenv( 'DONAPP_API_KEY' );

        if (isset($headers['X-Api-Key'])) {
            if($headers['X-Api-Key'] !== $apiKey){
                throw new Exception('unauthenticated', 401);
            }
        } else {
            throw new Exception('unauthenticated', 401);
        }
        return $pipeline->next($request);
    }
}
