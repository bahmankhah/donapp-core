<?php
namespace App\Models;

use Kernel\Model;

class Wallet extends Model {
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'dnp_user_wallets';
    }

    public function transactions(){
        return $this->hasMany($this->wpdb->prefix.'dnp_user_wallet_transactions', 'wallet_id', 'id');
    }
   
}
