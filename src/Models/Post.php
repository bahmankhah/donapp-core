<?php
namespace Donapp\Models;

use Kernel\Model;

class Post extends Model {
    protected $postType = 'post';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'posts';
    }

    public function test(){
        echo 'test';
    }

}
