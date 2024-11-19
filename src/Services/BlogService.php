<?php

namespace Donapp\Services;

use Donapp\Models\Post;
use Kernel\DB;
use Kernel\Model;

class BlogService{
    public function list(array $data){

        $orderBy = isset($data['orderBy']) ? $data['orderBy'] : 'post_date';
        $orderDirection = isset($data['orderDirection']) ? $data['orderDirection'] : 'DESC';

        if(!in_array($orderBy, ['post_date', 'views'])) $orderBy = 'post_date';
        if(!in_array($orderDirection, ['ASC', 'DESC', 'asc', 'desc'])) $data['orderDirection'] = 'DESC';

        $limit = $data['limit'] ?? 10;
        
        $categotyId = (new DB())->getCategoryId('donap-videos');
        $whereSql = '';
        if($categotyId){
            $whereSql = (new Model)->select(['tr.object_id'])->setTable(DB::wpdb()->term_relationships, 'tr')
            ->join(DB::wpdb()->term_taxonomy.'as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tt.term_id', '=', $categotyId, '%d')
            ->where('tt.taxonomy', '=', 'category')->sql();
        }
        $query = (new Post())
        ->setTableAlias('p')
        ->with('image_url', function ($row) {
            return get_the_post_thumbnail_url($row['ID']);
        })
        ->with('post_url', function ($row) {
            return get_permalink($row['ID']);
        })
        ->with('excerpt', function ($row) {
            return get_the_excerpt($row['ID']);
        })
        ->select(['ID','post_date','post_title',"MAX(CASE WHEN pm.meta_key = 'views' THEN pm.meta_value END) AS 'views'"])
        ->limit($limit)
        // ->views()
        ->where('p.post_status','=','publish')
        ->join(DB::wpdb()->prefix.'postmeta as pm', 'p.ID', '=', 'pm.post_id')
        ->orderBy($orderBy, $orderDirection)
        ->groupBy(['p.ID']);

        if($categotyId){
            $query->where('p.ID', 'NOT IN', "($whereSql)", '%i');
        }

        return $query->get();

        
    }


    public function videoList(array $data){

        $orderBy = isset($data['orderBy']) ? $data['orderBy'] : 'post_date';
        $orderDirection = isset($data['orderDirection']) ? $data['orderDirection'] : 'DESC';

        if(!in_array($orderBy, ['post_date', 'views'])) $orderBy = 'post_date';
        if(!in_array($orderDirection, ['ASC', 'DESC', 'asc', 'desc'])) $data['orderDirection'] = 'DESC';

        $limit = $data['limit'] ?? 10;
        
        $categotyId = (new DB())->getCategoryId('donap-videos');
        if(!$categotyId) return [];

        return (new Post())
        ->setTableAlias('p')
        ->with('image_url', function ($row) {
            return get_the_post_thumbnail_url($row['ID']);
        })
        ->with('post_url', function ($row) {
            return get_permalink($row['ID']);
        })
        ->with('excerpt', function ($row) {
            return get_the_excerpt($row['ID']);
        })
        ->select(['ID','post_date','post_title',"MAX(CASE WHEN pm.meta_key = 'views' THEN pm.meta_value END) AS 'views'"])
        ->limit($limit)
        // ->views()
        ->where('p.post_status','=','publish')
        ->where('tt.term_id', '=', $categotyId, '%d')
        ->join(DB::wpdb()->prefix.'postmeta as pm', 'p.ID', '=', 'pm.post_id')
        ->join(DB::wpdb()->term_relationships.' as tr', 'p.ID', '=', 'tr.object_id')
        ->join(DB::wpdb()->term_taxonomy.' as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
        ->orderBy($orderBy, $orderDirection)
        ->groupBy(['p.ID'])
        ->get();

        
    }

    
}