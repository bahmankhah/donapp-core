<?php

namespace App\Controllers;

use App\Services\WalletService;
use Kernel\Container;

class WalletController{
    public WalletService $walletService;
    public function __construct()
    {
        $this->walletService = Container::resolve('WalletService');
    }

    public function addToWallet($request, $type){
        $data = $request->get_json_params();
        if(!isset($data['amount']) || !isset($data['identifier'])){
            throw new \Exception('amount and id are required', 400);
        }
        $updatedBalance = $this->walletService->updateBalance($data['identifier'], $data['amount'], $type);
        return [
            'balance'=>$updatedBalance
        ];
    }
}