<?php

namespace App\Adapters\Wallet\Contexts;

use App\Adapters\Wallet\Wallet;
use App\Facades\Wallet as FacadesWallet;

class VirtualCreditCash extends Wallet{

    public function updateBalance($identifier, $amount, $transactionType = null)
    {
        return;
    }
    public function getBalance($identifier)
    {
        $creditAmount = FacadesWallet::credit()->getBalance($identifier);
        $cashAmount = FacadesWallet::cash()->getBalance($identifier);
        return $creditAmount + $cashAmount;
    }
    public function decreaseBalance($identifier, $amount, $transactionType = null)
    {
        $creditWallet = FacadesWallet::credit()->findWallet($identifier);
        $cashWallet = FacadesWallet::cash()->findWallet($identifier);
        if ($cashWallet['amount'] ?? 0 + $creditWallet['amount'] ?? 0 < $amount) {
            throw new \Exception('Insufficient balance', 400);
        }

        if (($creditWallet['amount'] ?? 0) >= $amount) {
            $updatedBalance = FacadesWallet::credit()->updateBalance($identifier, -$amount, $transactionType);
        } else {
            $creditAmount = $creditWallet['amount'] ?? 0;
            $cashAmount = $cashWallet['amount'] ?? 0;
            $remainingAmount = $amount - $creditAmount;
            // Deduct all available credit
            FacadesWallet::credit()->updateBalance($identifier, -$creditAmount, $transactionType);
            // Deduct the rest from cash
            $updatedBalance = FacadesWallet::cash()->updateBalance($identifier, -$remainingAmount, $transactionType);
        }
        return $updatedBalance;

    }
}