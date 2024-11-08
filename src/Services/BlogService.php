<?php

namespace Donapp\Services;

use Donapp\Models\Post;

class BlogService{
    public function list(array $data){
        return (new Post())->get();
    }
}