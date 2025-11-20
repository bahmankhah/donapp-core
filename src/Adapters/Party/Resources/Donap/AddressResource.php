<?php

namespace App\Adapters\Party\Resources\Donap;

use Kernel\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray()
    {
        return array_map(function ($item) {
            return [
                'province' => $item['province'] ?? null,
                'city'     => $item['city'] ?? null,
                'postalCode' => $item['postalCode'] ?? null,
            ];
        }, $this->data['address'] ?? []);
    }
}
