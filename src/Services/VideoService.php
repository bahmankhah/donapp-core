<?php

namespace App\Services;

use App\Models\Post;
use App\Models\VideoPost;
use Kernel\DB;

class VideoService{
    public function list(array $data){

        $orderBy = isset($data['orderBy']) ? $data['orderBy'] : 'post_date';
        $orderDirection = isset($data['orderDirection']) ? $data['orderDirection'] : 'DESC';

        if(!in_array($orderBy, ['post_date'])) $orderBy = 'post_date';
        if(!in_array($orderDirection, ['ASC', 'DESC', 'asc', 'desc'])) $data['orderDirection'] = 'DESC';

        $limit = $data['limit'] ?? 10;
        
        return (new VideoPost())
        ->setTableAlias('p')
        ->with('image_url', function ($row) {
            return get_the_post_thumbnail_url($row['ID']);
        })
        ->select(['ID','post_date','post_title',"MAX(CASE WHEN pm.meta_key = '_video_url' THEN pm.meta_value END) AS 'video_url'"])
        ->limit($limit)
        ->where('p.post_status','=','publish')
        ->join(DB::wpdb()->prefix.'postmeta as pm', 'p.ID', '=', 'pm.post_id')
        ->orderBy($orderBy, $orderDirection)
        ->groupBy(['p.ID'])
        ->get();

        
    }

    
}