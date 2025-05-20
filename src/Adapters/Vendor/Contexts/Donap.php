<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey = $this->config['key'];
        $api_url = $this->config['access_url'];
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
        appLogger('API Response: $response = wp_remote_post($api_url, [
            'body' => [
                'id' => $dnpId,
                'products' => $productIds,
            ],
            'headers' => [
                // 'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
            ],
        ]);' . wp_remote_retrieve_body( $response ));

        if (is_wp_error($response)) {
            appLogger('API Error: ' . $response->get_error_message());
        } else {
            appLogger('Access granted successfully for User ID: ' . $dnpId);
        }
    }

    public function getPurchasedProductUrl(string $slug){
        return replacePlaceholders($this->config['purchased_redirect_url'],['slug'=>$slug]);
    }

    public function getProductPageUrl(string $slug){
        return replacePlaceholders($this->config['product_page'],['slug'=>$slug]);
    }
}