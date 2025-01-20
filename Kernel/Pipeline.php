<?php

namespace Kernel;

use Kernel\Facades\App;

class Pipeline{
    private $middlewares = [];
    private $callIndex = 0;
    private $callable = [];   
    public function call($request, $params){
        $this->middlewares = appConfig('app.global_middlewares', []);
        $this->middlewares = array_merge($this->middlewares, $params['middlewares']);
        $this->callable = $params['callable'];
        return $this->next($request);
    }

    public function next($request){
        if($this->callIndex === count($this->middlewares)){
            $controller = App::make($this->callable[0]);
            return $controller->{$this->callable[1]}($request);
        }else{
            return (new $this->middlewares[$this->callIndex++]())->handle($request, $this);
        }
    }
}