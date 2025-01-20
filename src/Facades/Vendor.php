<?php

namespace App\Facades;

use App\Adapters\Vendor\VendorManager;
use Kernel\Facades\Facade;

class Vendor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return VendorManager::class;
    }
}