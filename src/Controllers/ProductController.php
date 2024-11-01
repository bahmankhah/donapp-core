<?php

namespace Donapp\Controllers;

use Donapp\Container;

class ProductController{

    private $productService;
    public function __construct() {
        $this->productService = Container::resolve('ProductService');
    }

    public function createProduct($request){
        $this->productService->createProduct();
    }
    
}