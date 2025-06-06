<?php

namespace App\Adapters\Wallet;

use App\Models\Wallet as ModelsWallet;
use App\Services\TransactionService;
use Exception;
use Kernel\Adapters\Adapter;
use Kernel\Container;

abstract class Wallet extends Adapter {
    private TransactionService $trasnactionService;
    public function __construct(array $config = []){
        parent::__construct($config);
        $this->trasnactionService = Container::resolve('TransactionService');
    }

    public function getBalance($identifier){
        $wallet = $this->findWallet($identifier);
        if(!$wallet){
            return 0;
        }
        return $wallet['balance'];
    }
    public function createWalllet($identifier){
        (new ModelsWallet())->create([
            'identifier' => $identifier,
            'type' => $this->config['type'],
            'balance' => 0,
        ]);
        $wallet = $this->findWallet($identifier);
        if(!$wallet){
            throw new Exception('Could not create Wallet at this moment', 406);
        }
        return $wallet;
    }
    public function findWallet($identifier){
        $wallet = (new ModelsWallet())->where('identifier', '=', $identifier)
        ->where('type', '=', $this->config['type'])->first();
        return $wallet;
    }
    public function findOrCreateWallet($identifier){
        $wallet = $this->findWallet($identifier);
        if(!$wallet){
            $wallet = $this->createWalllet($identifier);
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