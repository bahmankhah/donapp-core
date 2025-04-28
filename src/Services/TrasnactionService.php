<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;

class TrasnactionService{
    public function create($wallet, $amount, $remain, $type = null){
        $data = [];
        if(!is_numeric($amount) || intval($amount) == 0){
            return;
        }
        if(!is_numeric($remain)){
            return;
        }
        $amount = intval($amount);
        $remain = intval($remain);
        if($amount == 0){
            return;
        }elseif($amount > 0){
            $data['credit'] = $amount;
        }else{
            $data['debit'] = abs($amount);
        }
        $data['wallet_id'] = $wallet['id'];
        $data['remain'] = $remain;
        $data['type'] = $type;
        (new Transaction)->create($data);
    }
}