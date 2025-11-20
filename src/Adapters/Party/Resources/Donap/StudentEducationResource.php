<?php

namespace App\Adapters\Party\Resources\Donap;


use Kernel\JsonResource;

class StudentEducationResource extends JsonResource
{
    public function toArray()
    {
        $e = $this->data['studentEducation'] ?? [];

        return [
            'educationalGradeId' => $e['educationalGradeId'] ?? null,
            'educationalYearId'  => $e['educationalYearId'] ?? null,
            'partyId'            => $e['partyId'] ?? null,
            'organization'       => [
                'id'   => $e['organization']['organizationId'] ?? null,
                'name' => $e['organization']['organizationName'] ?? null,
            ],
        ];
    }
}
