<?php

namespace Doanpp\Controllers\Modules\Proxy;

use Donapp\Services\Modules\Proxy\ProxyService;
use Kernel\Container;

class ProxyController{

    private ProxyService $proxyService;
    public function __construct()
    {
        $this->proxyService = Container::resolve('ProxyService');
        
    }
    
    public function proxy(){
        return res($this->proxyService->proxy());
    }
}