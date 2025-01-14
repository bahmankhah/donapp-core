<?php

namespace Donapp\Services;
use Exception;
class ProductService{

    public function createProduct(array $data) {
        $product = new \WC_Product_Simple();
        $attachmentId = upload_image_from_url($data['image_url']);
        $product->set_name($data['name']);  // Product title
        $product->set_regular_price($data['price']);  // Product price
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description']);
        $product->set_sku($data['sku']);  // SKU (unique identifier)
        $product->set_stock_quantity($data['quantity']);    // Inventory quantity
        $product->set_manage_stock(true);     // Enable stock management
        $product->set_stock_status('instock'); // Stock status (instock/outofstock)
        $product->set_category_ids($data['categories']); // Array of category IDs
        if($attachmentId){
            $product->set_image_id($attachmentId); // Attach an image (function to handle it below)
            $product->set_gallery_image_ids( array( $attachmentId ) );
        }
    
        // Set product visibility (e.g., 'visible', 'catalog', 'search', 'hidden')
        $product->set_catalog_visibility('visible');
    
        // Save the product to the database
        $product_id = $product->save();
    
        if ( $product_id ) {
            return wc_get_product($product);
        } else {
            throw new Exception('Failed to create product.', 406);
        }
    }
}