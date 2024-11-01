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
        $this->request = $request;
        return $this->next($this->request);
    }

    public function next(){
        if($this->callIndex == count($this->middlewares)){
            $this->request = (new $this->callable[0]())->{$this->callable[1]}($this->request);
        }else{
            $this->request = (new $this->middlewares[++$this->callIndex]())->handle($this);
        }
    }
}