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
        $updatedBalance = $this->walletService->updateBalance($data['identifier'], $data['amount'], $type);
        res([
            'balance'=>$updatedBalance
        ]);
    }
}