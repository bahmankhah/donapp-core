<?php
namespace App\Controllers;

use App\Models\Post;
use App\Services\BlogService;
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
            return $this->blogService->list($request->get_query_params());
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

    public function videoIndex($request)
    {
        try{
            return $this->blogService->videoList($request->get_query_params());
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

    public function test(){
    }
}