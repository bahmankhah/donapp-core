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
        $data = $request->get_json_params();
        if(!isset($data['product']) || !isset($data['id'])){
            return res([], 'product and id are required', 400);
        }
        $result = $this->wooService->addToCart($data);
        return res($result);

    }


}