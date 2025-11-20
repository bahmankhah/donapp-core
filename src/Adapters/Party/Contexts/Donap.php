<?php

namespace App\Adapters\Party\Contexts;

use App\Adapters\Party\Party;
use App\Adapters\Party\Resources\Donap\AddressResource;
use App\Adapters\Party\Resources\Donap\StudentContactResource;
use App\Adapters\Party\Resources\Donap\StudentEducationResource;
use App\Adapters\Party\Resources\Donap\StudentProfileResource;

class Donap extends Party {

    private function request($method, $endpoint)
    {
        $response = wp_remote_request($this->config['main_url'] . $endpoint, [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getStudentProfile($partyId): StudentProfileResource
    {
        $profile = $this->request('GET', "/employee/api/v1/persons/{$partyId}/profile");
        return StudentProfileResource::make($profile);
    }

    public function getStudentContact($partyId): StudentContactResource
    {
        $contact = $this->request('GET', "/student/api/v1/students/{$partyId}/contact");
        return StudentContactResource::make($contact);
    }

    public function getStudentEducation($partyId): StudentEducationResource
    {
        $edu = $this->request('GET', "/education/api/v1/StudentEducation/Party/{$partyId}");
        return StudentEducationResource::make($edu);
    }

    public function getStudentAddress($partyId): AddressResource
    {
        $address = $this->request('GET', "/employee/api/v1/addresses/party/{$partyId}");
        return AddressResource::make($address);
    }
}