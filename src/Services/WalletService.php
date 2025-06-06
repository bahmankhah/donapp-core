<?php

namespace App\Services;

use App\Facades\Wallet as FacadesWallet;
use App\Models\Wallet;
use Exception;
use Kernel\Container;

class WalletService{

    public function findUserWallets($identifier)
    {
        $wallets = (new Wallet())->where('identifiter', '=', $identifier)->get();
        return $wallets;
    }

    public function getAvailableCredit($identifier, $useCash = true)
    {
        if(!$useCash) {
            return FacadesWallet::credit()->getBalance($identifier);
        }
        return FacadesWallet::virtualCreditCash()->getBalance($identifier);

    }

    public function decreaseCredit($identifier, $amount, $useCash = true){
        if(!$useCash) {
            return FacadesWallet::credit()->updateBalance($identifier, -$amount);
        }
        return FacadesWallet::virtualCreditCash()->decreaseBalance($identifier, $amount);
    }

    public function updateBalance($identifier, $walletType, $amount, $transactionType = null){
        if(!in_array($walletType, ['coin', 'credit', 'cash'])){
            throw new \Exception('allowed wallets: coin, credit, cash', 400);
        }
        $updated = FacadesWallet::$walletType()->updateBalance($identifier, $amount, $transactionType);
        return $updated;
    }

}