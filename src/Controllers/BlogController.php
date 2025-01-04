<?php
namespace Donapp\Controllers;

use Donapp\Models\Post;
use Donapp\Services\BlogService;
use Exception;
use Kernel\Container;

class BlogController
{
    private BlogService $blogService;
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

    public function videoIndex($request)
    {
        try{
            return res($this->blogService->videoList($request->get_query_params()));
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

    public function test(){
    }
}