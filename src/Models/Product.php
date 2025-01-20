<?php
namespace App\Models;

use Kernel\Model;

class Product extends Model {
    protected $postType = 'product';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'posts';
    }

}
