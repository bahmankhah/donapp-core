<?php

namespace App\Middlewares;

use Exception;
use Kernel\Middleware;
use Kernel\Pipeline;

class ExceptionMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        try{
            return $pipeline->next($request);
        }catch(Exception $e){
            appLogger("{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}");
            $code = $e->getCode() === 0 ? 500 : $e->getCode();
            return res(null, $e->getMessage(), $code);
        }
    }
}