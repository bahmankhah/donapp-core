<?php

namespace Donapp\Services;

use Donapp\Models\Post;

class BlogService{
    public function list(array $data){
        $limit = $data['limit'] ?? 10;
        return (new Post())->limit($limit)->get();
    }


}