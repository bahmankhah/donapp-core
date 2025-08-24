<?php

namespace App\Controllers;

use App\Services\WalletService;
use Kernel\Container;

class WalletController
{
    public WalletService $walletService;
    public function __construct()
    {
        $this->walletService = Container::resolve('WalletService');
    }

    public function addToWallet($request, $type)
    {
        $data = $request->get_json_params();
        if (!isset($data['amount']) || !isset($data['identifier'])) {
            throw new \Exception('amount and id are required', 400);
        }
        $updatedBalance = $this->walletService->updateBalance($data['identifier'], $type, $data['amount']);
        return [
            'balance' => $updatedBalance
        ];
    }

    public function getWallet($request)
    {
        $data = $request->get_query_params();
        if (!isset($data['identifier'])) {
            throw new \Exception('id is required', 400);
        }
        try {
            $balance = $this->walletService->getAvailableCredit($data['identifier']);
            return [
                'balance' => $balance
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error fetching wallet balance: ' . $e->getMessage(), 500);
        }
    }
}