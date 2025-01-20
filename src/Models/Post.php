<?php
namespace App\Models;

use Kernel\Model;

class Post extends Model {
    protected $postType = 'post';
    protected $primaryKey = 'ID';
    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'posts';
    }

    public function test(){
        return $this->hasMany($this->wpdb->prefix.'postmeta', 'post_id', 'ID');
    }
    public function views(){
        return $this->hasOneMeta($this->wpdb->prefix.'postmeta', 'post_views_count','post_id', 'ID');
    }

}
