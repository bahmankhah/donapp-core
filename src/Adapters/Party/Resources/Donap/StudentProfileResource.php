<?php

namespace App\Adapters\Party\Resources\Donap;


use Kernel\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray()
    {
        return [
            'partyId'   => $this->data['profile']['partyId'] ?? null,
            'firstName' => $this->data['profile']['firstName'] ?? null,
            'lastName'  => $this->data['profile']['lastName'] ?? null,
            'nationalId' => $this->data['profile']['nationalId'] ?? null,
            'gender'     => $this->data['profile']['gender'] ?? null,
            'genderDescription' => $this->data['profile']['genderDescription'] ?? null,
            'fatherName' => $this->data['profile']['fatherName'] ?? null,
            'fileId'     => $this->data['profile']['fileId'] ?? null,
        ];
    }
}
