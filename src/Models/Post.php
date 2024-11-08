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
        return $this->hasMany($this->wpdb->prefix.'postmeta', 'post_id', 'ID');
    }

}
