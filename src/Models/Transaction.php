<?php
namespace App\Models;

use Kernel\Model;

class Transaction extends Model {
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'dnp_user_wallet_transactions';
    }

    public function wallet(){
        return $this->hasOne($this->wpdb->prefix.'dnp_user_wallets', 'wallet_id', 'id');
    }
   
}
