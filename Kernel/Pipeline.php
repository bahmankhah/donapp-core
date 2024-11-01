<?php

namespace Kernel;

class Pipeline{
    private $middlewares = [];
    private $callIndex = 0;
    private $callable = [];   
    private $request; 
    public function call($request, $params){
        $this->middlewares = $params['middlewares'];
        $this->callable = $params['callable'];
        return $this->next($request);
    }

    public function next($request){
        if($this->callIndex == count($this->middlewares)){
            die($this->callIndex);
            (new $this->callable[0]())->{$this->callable[1]}($request);
        }else{
            (new $this->middlewares[$this->callIndex]())->handle($request, $this);
            $this->callIndex+=1;
        }
        die('amir');
    }
}