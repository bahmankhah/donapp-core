<?php
namespace Donapp\Models;

use Kernel\Model;

class UserCart extends Model {
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'dnp_user_carts';
    }

    

}
