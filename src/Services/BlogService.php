<?php

namespace Donapp\Services;

use Donapp\Models\Post;
use Kernel\DB;

class BlogService{
    public function list(array $data){

        $orderBy = isset($data['orderBy']) ? $data['orderBy'] : 'post_date';
        $orderDirection = isset($data['orderDirection']) ? $data['orderDirection'] : 'DESC';

        if(!in_array($orderBy, ['post_date', 'views'])) $orderBy = 'post_date';
        if(!in_array($orderDirection, ['ASC', 'DESC', 'asc', 'desc'])) $data['orderDirection'] = 'DESC';

        $limit = $data['limit'] ?? 10;
        
        return (new Post())
        ->setTableAlias('p')
        ->with('image_url', function ($row) {
            return get_the_post_thumbnail_url($row['ID']);
        })->with('content', function ($row) {
            return wp_strip_all_tags($row['content']);
        })
        ->select(['ID','content','post_title',"MAX(CASE WHEN pm.meta_key = 'views' THEN pm.meta_value END) AS 'views'"])
        ->limit($limit)
        // ->views()
        ->where('p.post_status','=','publish')
        ->join(DB::wpdb()->prefix.'postmeta as pm', 'p.ID', '=', 'pm.post_id')
        ->orderBy($orderBy, $orderDirection)
        ->get();
    }


}