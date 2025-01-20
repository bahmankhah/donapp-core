<?php

namespace App\Controllers\Modules\Proxy;

use App\Services\Modules\Proxy\ProxyService;
use Exception;
use Kernel\Container;

class ProxyController{

    private ProxyService $proxyService;
    public function __construct()
    {
        $this->proxyService = Container::resolve('ProxyService');
        
    }
    
    public function proxy(){
        return $this->proxyService->proxy();
    }
}