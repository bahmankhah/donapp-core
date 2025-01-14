<?php
namespace Donapp\Controllers;

use Donapp\Services\WooService;
use Exception;
use Kernel\Container;

class WooController{

    private WooService $wooService;
    public function __construct()
    {
        $this->wooService = Container::resolve('WooService');
    }

    public function addToCart($request){
        try{
            $data = $request->get_json_params();
            if(!isset($data['product']) || !isset($data['id'])){
                throw new Exception('product and id are required', 400);
            }
            $result = $this->wooService->addToCart($data);
            return $result;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }


}