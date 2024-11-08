<?php
namespace Donapp\Models;

use Kernel\Model;

class Post extends Model {
    protected $postType = 'post';
    protected $primaryKey = 'ID';
    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'posts';
    }

    public function test(){
        return $this->hasOneMeta($this->wpdb->prefix.'postmeta', '_post_views_count', 'post_id', 'ID');
    }

}
