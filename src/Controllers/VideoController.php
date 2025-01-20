<?php
namespace App\Controllers;

use Exception;
use Kernel\Container;

class VideoController
{
    private $videoService;
    public function __construct()
    {
        $this->videoService = Container::resolve('VideoService');
    }
    public function index($request)
    {
        try{
            return res($this->videoService->list($request->get_query_params()));
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
}