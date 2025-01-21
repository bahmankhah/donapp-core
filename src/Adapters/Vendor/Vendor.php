<?php

namespace App\Adapters\Vendor;

use Kernel\Adapters\Adapter;

abstract class Vendor extends Adapter {
    abstract public function giveAccess($userId, array $productIds);
    abstract public function getUrl();
}