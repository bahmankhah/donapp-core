<?php

namespace Donapp\Services;

use Kernel\DB;

class WooService
{

    public function addToCart($data) {
        $productId = $this->createProduct($data['product']);
        
    }

    public function createProduct($data)
    {
        $result = DB::wpQuery([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // Retrieve all matching products
            'meta_query'     => [
                [
                    'key'   => '_dnp_product_id',    // Meta key to search
                    'value' => (string) $data['id'],  // Value to match
                    'compare' => '=',        // Comparison operator (can be '=', '!=', 'LIKE', etc.)
                ],
            ],
        ]);
        if($result){
            return $result[0];
        }
        $product = new WC_Product_Simple();
        $product->set_name($data['name']);
        $product->set_regular_price($data['price']);
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description']);
        // $product->set_sku( 'meta-product-sku' );
        // $product->set_stock_quantity( 10 );
        // $product->set_manage_stock( true );
        $product->set_status('publish');
        $product->save();
        $product_id = $product->get_id();
        update_post_meta($product_id, '_dnp_product_id', $data['id']);

        return $product_id;
    }
}
