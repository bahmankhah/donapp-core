<?php

namespace App\Adapters\Party\Resources\Donap;


use Kernel\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray()
    {
        $emp = $this->data['employee'][0] ?? [];

        return [
            'firstName' => $emp['firstName'] ?? null,
            'lastName'  => $emp['lastName'] ?? null,
            'mobile'    => $emp['mobile'] ?? null,
            'organizationName' => $emp['organizationName'] ?? null,
            'city'      => $emp['city'] ?? null,
            'province'  => $emp['province'] ?? null,
        ];
    }
}
