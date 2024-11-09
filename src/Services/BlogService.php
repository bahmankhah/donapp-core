<?php

namespace Donapp\Services;

use Donapp\Models\Post;
use Kernel\DB;

class BlogService{
    public function list(array $data){

        $orderBy = isset($data['orderBy']) ? $data['orderBy'] : 'post_date';
        $orderDirection = isset($data['orderDirection']) ? $data['orderDirection'] : 'DESC';

        if(!in_array($orderBy, ['post_date', 'post_views_count'])) $orderBy = 'post_date';
        if(!in_array($orderDirection, ['ASC', 'DESC'])) $data['orderDirection'] = 'DESC';

        $limit = $data['limit'] ?? 10;
        
        return (new Post())
        ->setTableAlias('p')
        ->with('image_url', function ($row) {
            return get_the_post_thumbnail_url($row['ID']);
        })
        ->with('post_url', function ($row) {
            return get_permalink($row['ID']);
        })
        ->select(['ID','post_title',"MAX(CASE WHEN pm.meta_key = 'post_views_count' THEN pm.meta_value END) AS 'post_views_count'"])
        ->limit($limit)
        // ->views()
        ->where('p.post_status','=','publish')
        ->join(DB::wpdb()->prefix.'postmeta as pm', 'p.ID', '=', 'pm.post_id')
        ->orderBy($orderBy, $orderDirection)
        ->get();
    }


}