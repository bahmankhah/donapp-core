<?php

namespace Kernel\Middlewares;
use Kernel\Middleware;
use Kernel\Pipeline;
use Kernel\Facades\App;
class AppMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        App::setRequest($request);
        return $pipeline->next($request);
    }
}
