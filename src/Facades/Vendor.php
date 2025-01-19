<?php

namespace Donapp\Facades;

use Donapp\Adapters\Vendor\VendorManager;
use Kernel\Facades\Facade;

class Vendor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return VendorManager::class;
    }
}