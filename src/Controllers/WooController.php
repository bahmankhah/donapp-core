<?php
namespace Donapp\Controllers;

use Donapp\Services\WooService;
use Kernel\Container;

class WooController{

    private WooService $wooService;
    public function __construct()
    {
        $this->wooService = Container::resolve('WooService');
        
    }

    public function addToCart($request){
        $result = $this->wooService->addToCart($request->get_json_params());
        return res($result);

    }


}