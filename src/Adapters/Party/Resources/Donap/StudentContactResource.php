<?php

namespace App\Adapters\Party\Resources\Donap;


use Kernel\JsonResource;

class StudentContactResource extends JsonResource
{
    public function toArray()
    {
        $c = $this->data['contact'] ?? [];

        return [
            'mobile' => $c['mobile'] ?? null,
            'phone' => $c['phone'] ?? null,
            'email' => $c['email'] ?? null,
            'supporterMobile' => $c['supporterMobile'] ?? null,
        ];
    }
}
