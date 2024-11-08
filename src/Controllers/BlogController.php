<?php
namespace Donapp\Controllers;

use Exception;
use Kernel\Container;

class BlogController
{
    private $blogService;
    public function __construct()
    {
        $this->blogService = Container::resolve('BlogService');
    }
    public function index($request)
    {
        try{
            return res($this->blogService->list($request->get_query_params()));
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
}