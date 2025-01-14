<?php

namespace Donapp\Services\Modules\Proxy;

class ProxyService
{
    public function proxy()
    {
        $headers = getallheaders();
        $target_url = $headers['X-Target-URL'] ?? null;

        if (!$target_url) {
            http_response_code(400);
            // echo "Missing X-Target-URL header.";
            exit;
        }

        // Initialize cURL
        $ch = curl_init();

        // Get the original request method
        $method = $_SERVER['REQUEST_METHOD'];

        // Get the request body (if any)
        $input_data = file_get_contents('php://input');

        // Prepare headers to forward
        $proxy_headers = [];
        foreach ($headers as $key => $value) {
            if ($key !== 'X-Target-URL') { // Exclude the custom header
                $proxy_headers[] = "$key: $value";
            }
        }

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $proxy_headers);

        // Forward the body for POST, PUT, etc.
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input_data);
        }

        // Execute the request to the target URL
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            http_response_code(500);
            // echo "Proxy Error: " . curl_error($ch);
            curl_close($ch);
            exit;
        }

        // Get response details
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_headers = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);

        // Close cURL
        curl_close($ch);

        // // Forward response headers to the client
        // $response_headers_array = explode("\r\n", $response_headers);
        // foreach ($response_headers_array as $header) {
        //     if (!empty($header) && stripos($header, 'Content-Length') === false) {
        //         header($header);
        //     }
        // }

        // Forward the HTTP status code and response body
        // http_response_code($http_status);
        return $response_body;
    }
}
