<?php

namespace App\Adapters\Vendor\Contexts;

use App\Adapters\Vendor\Vendor;

class Donap extends Vendor{
    /**
     * Grant product access for a user via the external Donap API.
     *
     * @return bool True only when the API confirms success (HTTP 2xx).
     */
    public function giveAccess($dnpId, array $productIds)
    {
        $apiKey   = $this->config['key'];
        $api_url  = $this->config['access_url'];
        $timeout  = isset($this->config['access_timeout']) ? (int) $this->config['access_timeout'] : 30;
        $attempts = isset($this->config['access_retries']) ? max(1, (int) $this->config['access_retries']) : 3;

        $lastError = 'unknown error';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = wp_remote_post($api_url, [
                'timeout' => $timeout,
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

            if (is_wp_error($response)) {
                $lastError = $response->get_error_message();
                appLogger("API Error (attempt {$attempt}/{$attempts}) for User ID {$dnpId}: " . $lastError);
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            appLogger("API Response (attempt {$attempt}/{$attempts}, HTTP {$code}) for User ID {$dnpId}: " . $body);

            if ($code >= 200 && $code < 300) {
                appLogger('Access granted successfully for User ID: ' . $dnpId);
                return true;
            }

            $lastError = "unexpected HTTP status {$code}";
        }

        appLogger("Failed to grant access for User ID {$dnpId} after {$attempts} attempt(s). Last error: {$lastError}");
        return false;
    }

    public function getPurchasedProductUrl(string $slug){
        return replacePlaceholders($this->config['purchased_redirect_url'],['slug'=>$slug]);
    }

    public function getProductPageUrl(string $slug){
        return replacePlaceholders($this->config['product_page'],['slug'=>$slug]);
    }
}