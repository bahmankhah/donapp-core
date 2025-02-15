<?php

namespace App\Adapters\Vendor;

use Kernel\Adapters\Adapter;

abstract class Vendor extends Adapter {
    abstract public function giveAccess($userId, array $productIds);
    abstract public function getPurchasedProductUrl(string $slug);
    abstract public function getProductPageUrl(string $slug);

}