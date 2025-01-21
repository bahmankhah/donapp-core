<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey = $this->config['key'];
        $api_url = $this->config['access_url'];
        logger('URL: '. $api_url);
        logger('KEY: '. $apiKey);
        logger('KEY2: '. getenv('DONAPP_EXT_API_KEY'));
        logger(json_encode($_ENV));
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

        logger(json_encode($response));
        if (is_wp_error($response)) {
            logger('API Error: ' . $response->get_error_message());
        } else {
            logger('Access granted successfully for User ID: ' . $dnpId);
        }
    }

    public function getUrl(){
        return $this->config['main_url'];
    }
}