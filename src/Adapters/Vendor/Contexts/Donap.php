<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey = $this->config['key'];
        $api_url = $this->config['access_url'];
        appLogger('URL: '. $api_url);
        appLogger('KEY: '. $apiKey);
        appLogger('KEY2: '. getenv('DONAPP_EXT_API_KEY'));
        appLogger(json_encode($_ENV));
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

        appLogger(json_encode($response));
        if (is_wp_error($response)) {
            appLogger('API Error: ' . $response->get_error_message());
        } else {
            appLogger('Access granted successfully for User ID: ' . $dnpId);
        }
    }

    public function getUrl(){
        return $this->config['main_url'];
    }
}