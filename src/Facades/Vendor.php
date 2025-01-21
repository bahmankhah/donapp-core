<?php

namespace App\Facades;

use App\Adapters\Vendor\VendorManager;
use Kernel\Facades\Facade;

/**
 * @method static \App\Adapters\Vendor\Vendor donap()
 * @method \App\Adapters\Vendor\Vendor giveAccess($userId, array $productIds)
 * @method \App\Adapters\Vendor\Vendor getUrl()
 */
class Vendor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return VendorManager::class;
    }
}