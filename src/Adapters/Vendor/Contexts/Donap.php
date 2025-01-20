<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey = $this->config['key'];
        $api_url = $this->config['access_url'];
        $response = wp_remote_post($api_url, [
            'body' => json_encode([
                'id' => $dnpId,
                'products' => $productIds,
            ]),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('API Error: ' . $response->get_error_message());
        } else {
            error_log('Access granted successfully for User ID: ' . $dnpId);
        }
    }
}