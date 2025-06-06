<?php

namespace App\Adapters\Wallet;
use Kernel\Adapters\AdapterManager;

class WalletManager extends AdapterManager{
    
    public function getKey(): string{
        return 'wallet';
    }
    
}