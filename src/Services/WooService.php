<?php

namespace App\Services;

use DateTime;
use App\Facades\Vendor;
use App\Models\UserCart;
use Exception;
use Kernel\DB;

class WooService
{

    public function addToCart($data)
    {

        $productId = $this->createOrUpdateProduct($data['product']);
        if (!$productId) {
            throw new Exception('Service Unavailable');
        }
        $this->deleteExpiredCarts();
        $userCart = new UserCart();
        $currentCart = $userCart->where('identifier', '=', $data['id'])->first();
        if ($currentCart) {
            $cart = json_decode($currentCart['cart']);
            if (!in_array($productId, $cart)) {
                $cart[] = $productId;
            }
            $userCart->update([
                'cart' => json_encode($cart),
                'expired_at' => date('Y-m-d H:i:s'),
            ], [
                'identifier' => $data['id'],
            ]);
        } else {
            $currentDate = new DateTime();
            $currentDate->modify('+5 days');
            $expireDate = $currentDate->format('Y-m-d H:i:s');
            $result = $userCart->create([
                'identifier' => $data['id'],
                'cart' => json_encode([$productId]),
                'created_at' => date('Y-m-d H:i:s'),
                'expired_at' => $expireDate
            ]);
        }

        $theCart = $userCart->where('identifier', '=', $data['id'])->first();
        return array_merge(
            $theCart,
            ['cart' => json_decode($theCart['cart'])]
        );
    }

    public function deleteExpiredCarts()
    {
        $table = DB::wpdb()->prefix . 'dnp_user_carts';
        DB::query("DELETE FROM '$table  WHERE expired_at < now()");
    }

    public function createOrUpdateProduct($data)
    {
        // Search for an existing product with the given _dnp_product_id
        $existing_product = DB::wpQuery([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 1, // We only need the first match
            'meta_query'     => [
                [
                    'key'   => '_dnp_product_id',    // Meta key to search
                    'value' => (string) $data['id'],  // Value to match
                    'compare' => '=',        // Comparison operator
                ],
            ],
        ]);

        if ($existing_product) {
            // Update the existing product with the new data
            $product_id = $existing_product[0]['ID'];
            $product = wc_get_product($product_id);

            if ($product) {
                $product->set_name($data['name']);
                $product->set_regular_price($data['price']);
                $product->set_description($data['description']);
                $product->set_short_description($data['short_description']);
                $product->save();
                return $product_id; // Return updated product ID
            }
        } else {
            // Create a new product
            $product = new \WC_Product_Simple();
            $product->set_name($data['name']);
            $product->set_regular_price($data['price']);
            $product->set_description($data['description'] ?? '');
            $product->set_short_description($data['short_description'] ?? '');
            $product->set_status('publish');
            $product->save();
            $product_id = $product->get_id();

            // Save the custom meta field to track this product
            update_post_meta($product_id, '_dnp_product_id', $data['id']);

            return $product_id; // Return new product ID
        }
    }

    public function addUserIdToOrderItem($item, $cart_item_key, $values, $order)
    {

        if (isset($values['dnpuser'])) {
            $item->add_meta_data('dnpuser', $values['dnpuser']);
        }
    }

    public function processUserIdAfterPayment($orderId)
    {
        donappLog('processUserIdAfterPayment: ' . $orderId);
        $order = wc_get_order($orderId);
        $productIds = [];
        foreach ($order->get_items() as $item_id => $item) {
            // Retrieve the 'dnpuser' metadata from the order item
            $dnpuser = $item->get_meta('dnpuser');
            if ($dnpuser) {
                $dnpProductId = get_post_meta($item->get_product_id(), '_dnp_product_id', true);
                if (empty($productIds[(string) $dnpuser])) {
                    $productIds[(string) $dnpuser] = [(string) $dnpProductId];
                } else {
                    $productIds[(string) $dnpuser][] = (string) $dnpProductId;
                }
            }
        }
        foreach ($productIds as $dnpuser => $products) {
            Vendor::donap()->giveAccess($dnpuser, $products);
            // $this->giveAccess($dnpuser, $productIds);
        }

        wp_redirect(Vendor::donap()->getUrl());
        exit;
    }



    // private function giveAccess(string $dnpId, array $productIds)
    // {
    //     $apiKey = getenv('DONAPP_EXT_API_KEY');
    //     $api_url = "https://api.nraymanstage.donap.ir/external-services/donap-payment-status/";
    //     $response = wp_remote_post($api_url, [
    //         'body' => json_encode([
    //             'id' => $dnpId,
    //             'products' => $productIds,
    //         ]),
    //         'headers' => [
    //             'Content-Type' => 'application/x-www-form-urlencoded',
    //             'Accept' => 'application/json',
    //             'x-api-key' => $apiKey,
    //         ],
    //     ]);

    //     if (is_wp_error($response)) {
    //         error_log('API Error: ' . $response->get_error_message());
    //     } else {
    //         error_log('Access granted successfully for User ID: ' . $dnpId);
    //     }
    // }
}
