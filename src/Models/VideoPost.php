<?php
namespace Donapp\Models;

use Kernel\Model;

class VideoPost extends Model {
    protected $postType = 'dnp-video';
    protected $primaryKey = 'ID';
    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'posts';
    }

    public function test(){
        return $this->hasMany($this->wpdb->prefix.'postmeta', 'post_id', 'ID');
    }


}
