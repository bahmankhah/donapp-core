<?php

namespace Donapp\Services;

use DateTime;
use Donapp\Models\UserCart;
use Kernel\DB;

class WooService
{

    public function addToCart($data){
        
        $productId = $this->createOrUpdateProduct($data['product']);
        
        $this->deleteExpiredCarts();
        $userCart = new UserCart();
        $currentCart = $userCart->where('identifier', '=',$data['id'])->first();
        if($currentCart){
            $cart = json_decode($currentCart['cart']);
            if(!in_array($productId, $cart)){
                $cart[] = $productId;
            }
            $result = $userCart->update([
                'cart'=>json_encode($cart),
                'expired_at'=>date('Y-m-d H:i:s'),
            ],[
                'identifier'=>$data['id'],
            ]);
            return $result;
        }
        $currentDate = new DateTime(); 
        $currentDate->modify('+5 days'); 
        $expireDate = $currentDate->format('Y-m-d H:i:s'); 
        $result = $userCart->create([
            'identifier'=> $data['id'],
            'cart'=>json_encode([$productId]),
            'created_at'=>date('Y-m-d H:i:s'),
            'expired_at'=>$expireDate
        ]);
        return $result;
        // WC()->cart->add_to_cart($productId);
    }

    public function deleteExpiredCarts(){
        $table = DB::wpdb()->prefix. 'dnp_user_carts';
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
            $product_id = $existing_product[0]->ID;
            var_dump($existing_product);
            die();
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
            var_dump('new');
            die();
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
    
}
