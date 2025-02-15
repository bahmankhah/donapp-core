<?php

namespace App\Controllers;

use Kernel\Container;

class ProductController{

    private $productService;
    public function __construct() {
        $this->productService = Container::resolve('ProductService');
    }

    public function createProduct($request){
        $this->productService->createProduct();
    }
    
}