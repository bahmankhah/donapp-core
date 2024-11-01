<?php

namespace Donapp\Controllers;

use WC_Product_Simple;

class ProductService{

    public function createProduct() {
    
        $product = new WC_Product_Simple();
    
        $product->set_name('Sample Product');  // Product title
        $product->set_regular_price('19.99');  // Product price
        $product->set_description('This is a sample product description.');
        $product->set_short_description('This is a short description.');
        $product->set_sku('sample-sku-123');  // SKU (unique identifier)
        $product->set_stock_quantity(100);    // Inventory quantity
        $product->set_manage_stock(true);     // Enable stock management
        $product->set_stock_status('instock'); // Stock status (instock/outofstock)
        $product->set_category_ids([15, 23]); // Array of category IDs
        $product->set_image_id(attach_image_to_product()); // Attach an image (function to handle it below)
    
        // Set product visibility (e.g., 'visible', 'catalog', 'search', 'hidden')
        $product->set_catalog_visibility('visible');
    
        // Save the product to the database
        $product_id = $product->save();
    
        if ( $product_id ) {
            echo 'Product created successfully with ID: ' . $product_id;
        } else {
            echo 'Product creation failed.';
        }
    }
}