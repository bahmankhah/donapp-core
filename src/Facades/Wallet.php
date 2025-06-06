<?php

namespace App\Facades;

use App\Adapters\Wallet\WalletManager;
use Kernel\Facades\Facade;

/**
 * @method \App\Adapters\Wallet\Wallet updateBalance()
 *  
 */
class Wallet extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WalletManager::class;
    }
}