<?php

namespace App\Services;

use App\Facades\Wallet as FacadesWallet;
use App\Core\TransactionType;
use App\Core\WalletType;
use App\Models\Wallet;
use Exception;
use Kernel\Container;

class WalletService{
    public function settlementRequest($identifier){
        $balance = FacadesWallet::cash()->getBalance($identifier);
        if($balance <= 0){
            throw new Exception('Wallet balance is zero', 400);
        }
        $this->updateBalance($identifier, WalletType::CASH, -$balance, TransactionType::SETTLEMENT_REQUEST);
        $this->updateBalance($identifier, WalletType::SUSPENDED, $balance, TransactionType::SETTLEMENT_REQUEST);
        return $balance;
    }

    public function findUserWallets($identifier)
    {
        $wallets = (new Wallet())->where('identifier', '=', $identifier)->get();
        return $wallets;
    }

    public function getAvailableCredit($identifier, $useCash = true)
    {
        if(!$useCash) {
            return FacadesWallet::credit()->getBalance($identifier);
        }
        return FacadesWallet::virtualCreditCash()->getBalance($identifier);

    }

    public function increaseCredit($identifier, $amount){
        return $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CREDIT_CHARGE);
    }

    public function addGift($identifier, $amount){
        return $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CHARGE_GIFT);
    }

    public function decreaseCredit($identifier, $amount, $useCash = true){
        if(!$useCash) {
            return FacadesWallet::credit()->updateBalance($identifier, -$amount);
        }
        return FacadesWallet::virtualCreditCash()->decreaseBalance($identifier, $amount);
    }

    public function updateBalance($identifier, $walletType, $amount, $transactionType = null){
        if(!in_array($walletType, ['coin', 'credit', 'cash', 'suspended'])){
            throw new \Exception('allowed wallets: coin, credit, cash', 400);
        }
        $updated = FacadesWallet::$walletType()->updateBalance($identifier, $amount, $transactionType);
        return $updated;
    }

}