<?php

namespace Donapp\Services\Modules\Proxy;

class ProxyService
{
    public function proxy()
    {
        $headers = getallheaders();
        $target_url = trim($headers['X-Target-URL'] ?? '');

        $input_data = file_get_contents('php://input');

        $proxy_headers = [];
        foreach ($headers as $key => $value) {
            if ($key !== 'X-Target-URL') { // Exclude the custom header
                $proxy_headers[] = "$key: $value";
            }
        }
var_dump($proxy_headers);
var_dump($input_data);
        $result = wp_remote_post($target_url, [
            'method' => 'POST',
            'body' => $input_data,
            'headers' => $proxy_headers,
        ]);
        // Forward the HTTP status code and response body
        // http_response_code($http_status);
        return json_decode(json_encode($result), true);
    }
}
