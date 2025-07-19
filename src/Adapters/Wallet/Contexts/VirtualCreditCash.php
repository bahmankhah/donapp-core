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
        return intval($creditAmount + $cashAmount);
    }
    public function decreaseBalance($identifier, $amount, $transactionType = null)
    {
        // Debug: Log input parameters
        appLogger("decreaseBalance called with identifier: $identifier, amount: $amount, transactionType: " . var_export($transactionType, true));

        $creditWallet = FacadesWallet::credit()->findWallet($identifier);
        $cashWallet = FacadesWallet::cash()->findWallet($identifier);

        // Debug: Log wallet states
        appLogger("Credit wallet: " . json_encode($creditWallet));
        appLogger("Cash wallet: " . json_encode($cashWallet));

        $totalBalance = ($cashWallet['amount'] ?? 0) + ($creditWallet['amount'] ?? 0);

        // Debug: Log total balance
        appLogger("Total balance: $totalBalance");

        if ($totalBalance < $amount) {
            appLogger("Insufficient balance for identifier: $identifier");
            throw new \Exception('Insufficient balance', 400);
        }

        if (($creditWallet['amount'] ?? 0) >= $amount) {
            $updatedBalance = FacadesWallet::credit()->updateBalance($identifier, -$amount, $transactionType);
            // Debug: Log deduction from credit
            appLogger("Deducted $amount from credit wallet. Updated balance: $updatedBalance");
        } else {
            $creditAmount = $creditWallet['amount'] ?? 0;
            $cashAmount = $cashWallet['amount'] ?? 0;
            $remainingAmount = $amount - $creditAmount;

            // Debug: Log split deduction
            appLogger("Deducting $creditAmount from credit and $remainingAmount from cash.");

            // Deduct all available credit
            FacadesWallet::credit()->updateBalance($identifier, -$creditAmount, $transactionType);
            // Deduct the rest from cash
            $updatedBalance = FacadesWallet::cash()->updateBalance($identifier, -$remainingAmount, $transactionType);

            // Debug: Log deduction from cash
            appLogger("Deducted $remainingAmount from cash wallet. Updated balance: $updatedBalance");
        }
        return intval($updatedBalance);
    }
}