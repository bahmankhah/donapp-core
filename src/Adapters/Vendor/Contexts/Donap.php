<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey = $this->config['key'];
        $api_url = $this->config['access_url'];
        donappLog('URL: '. $api_url);
        donappLog('KEY: '. $apiKey);
        $response = wp_remote_post($api_url, [
            'body' => [
                'id' => $dnpId,
                'products' => $productIds,
            ],
            'headers' => [
                // 'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
            ],
        ]);

        donappLog(json_encode($response));
        if (is_wp_error($response)) {
            donappLog('API Error: ' . $response->get_error_message());
        } else {
            donappLog('Access granted successfully for User ID: ' . $dnpId);
        }
    }

    public function getUrl(){
        return $this->config['main_url'];
    }
}