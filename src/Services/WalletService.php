<?php

namespace App\Services;

use App\Models\Wallet;
use Exception;
use Kernel\Container;

class WalletService{

    private TrasnactionService $trasnactionService;
    public function __construct()
    {
        $this->trasnactionService = Container::resolve('TrasnactionService');
    }

    public function createWalllet($identifier, $walletType){
        (new Wallet())->create([
            'identifiter' => $identifier,
            'type' => $walletType,
            'balance' => 0,
        ]);
        $wallet = $this->findWallet($identifier, $walletType);
        if(!$wallet){
            throw new Exception('Could not create Wallet at this moment', 406);
        }
        return $wallet;
    }

    public function findOrCreateWallet($identifier, $walletType){
        $wallet = $this->findWallet($identifier, $walletType);
        if(!$wallet){
            $wallet = $this->createWalllet($identifier, $walletType);
        }
        return $wallet;
    }

    public function findWallet($identifier, $walletType){
        $wallet = (new Wallet())->where('identifiter', '=', $identifier)
        ->where('type', '=', $walletType)->first();
        return $wallet;
    }

    public function findUserWallets($identifier)
    {
        $wallets = (new Wallet())->where('identifiter', '=', $identifier)->get();
        return $wallets;
    }

    public function updateBalance($identifier, $walletType, $amount, $transactionType = null){
        $amount = intval($amount);
        if($amount == 0){
            return;
        }
        $wallet = $this->findOrCreateWallet($identifier, $walletType);
        $updatedBalance = $wallet['balance'] + $amount;
        if($updatedBalance < 0){
            throw new Exception('Wallet can not have negative balance', 400);
        }
        (new Wallet())->update(
            [
                'balance' => $updatedBalance,
            ],
            [
                'id'=>$wallet['id']
            ]
        );
        $this->trasnactionService->create($wallet, $amount, $updatedBalance, $transactionType);
        return $updatedBalance;
    }
    
}