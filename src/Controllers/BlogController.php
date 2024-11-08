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
    public function index()
    {
        try{
            return res($this->blogService->list([]));
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
}