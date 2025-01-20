<?php

namespace App\Adapters\Vendor;
use Kernel\Adapters\AdapterManager;

class VendorManager extends AdapterManager{
    
    public function getKey(): string{
        return 'vendor';
    }
    
}