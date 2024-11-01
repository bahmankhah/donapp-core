<?php

namespace Kernel;

class Pipeline{
    private $middlewares = [];
    private $callIndex = 0;
    private $callable = [];   
    public function call($request, $params){
        $this->middlewares = $params['middlewares'];
        $this->callable = $params['callable'];
        return $this->next($request);
    }

    public function next($request){
        if($this->callIndex === count($this->middlewares)){
            return (new $this->callable[0]())->{$this->callable[1]}($request);
        }else{
            return (new $this->middlewares[$this->callIndex++]())->handle($request, $this);
        }
    }
}