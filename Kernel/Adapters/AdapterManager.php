<?php 

namespace Kernel\Adapters;

use Kernel\Facades\App;

abstract class AdapterManager{

    abstract public function getKey(): string;
    public function __call($method, $args){
        if(!method_exists($this, $method)){
            if (!appConfig("services.{$this->getKey()}.adapters.{$method}")) {
                $defaultAdapter = appConfig("services.{$this->getKey()}.default");
                $instance = $this->use($defaultAdapter);
                if(!method_exists($instance, $method)){
                    throw new \InvalidArgumentException("Message adapter [{$defaultAdapter}] does not have method [{$method}].");
                }
                return call_user_func_array([$instance, $method], $args);
            }
            return $this->use($method);
        }else{
            return call_user_func_array([$this, $method], $args);
        }
    }
    public function use(string $adapter = null){
        
        if (!appConfig("services.{$this->getKey()}.adapters.{$adapter}")) {
            throw new \InvalidArgumentException("Message adapter [{$adapter}] is not defined.");
        }
        return App::make(appConfig("services.{$this->getKey()}.adapters.{$adapter}.context"),['config'=>appConfig("services.{$this->getKey()}.adapters.{$adapter}")]);
    }
}