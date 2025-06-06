<?php

namespace App\Adapters\Wallet;

use App\Models\Wallet as ModelsWallet;
use App\Services\TransactionService;
use Exception;
use Kernel\Adapters\Adapter;

abstract class Wallet extends Adapter {
    private TransactionService $trasnactionService;
    public function __construct(array $config = []){
        parent::__construct($config);
        $this->trasnactionService = $this->getAdapter('TransactionService');
    }

    public function getBlance($identifier){
        $wallet = $this->findWallet($identifier, $this->config['type']);
        if(!$wallet){
            return 0;
        }
        return $wallet['balance'];
    }
    public function createWalllet($identifier, $walletType){
        (new ModelsWallet())->create([
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
    public function findWallet($identifier, $walletType){
        $wallet = (new ModelsWallet())->where('identifiter', '=', $identifier)
        ->where('type', '=', $walletType)->first();
        return $wallet;
    }
    public function findOrCreateWallet($identifier){
        $wallet = $this->findWallet($identifier, $this->config['type']);
        if(!$wallet){
            $wallet = $this->createWalllet($identifier, $this->config['type']);
        }
        return $wallet;
    }

    public function updateBalance($identifier, $amount, $transactionType = null){
        $amount = intval($amount);
        if($amount == 0){
            return;
        }
        $wallet = $this->findOrCreateWallet($identifier);
        $updatedBalance = $wallet['balance'] + $amount;
        if($updatedBalance < 0){
            throw new Exception('Wallet can not have negative balance', 400);
        }
        (new ModelsWallet())->update(
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