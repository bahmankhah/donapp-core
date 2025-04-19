<?php

namespace App\Services;

use DateTime;
use App\Facades\Vendor;
use App\Models\UserCart;
use Exception;
use Kernel\DB;
use WC_Payment_Gateway;   // make sure WooCommerce is loaded first

/**
 * ------------------------------------------------------------
 * 0) Define the Free‑Order Gateway at top‑level (not inside a method)
 * ------------------------------------------------------------
 */
if ( ! class_exists( 'WC_Gateway_Free_Order' ) ) {

    class WC_Gateway_Free_Order extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id                 = 'free_order';
            $this->method_title       = __( 'Free Order', 'woocommerce' );
            $this->has_fields         = false;
            $this->method_description = __( 'Allows checkout when order total is zero.', 'woocommerce' );

            $this->init_form_fields();
            $this->init_settings();

            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                [ $this, 'process_admin_options' ]
            );
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Free Order gateway', 'woocommerce' ),
                    'default' => 'yes',
                ],
            ];
        }

        public function is_available()
        {
            // only show when cart total is exactly zero
            return ( WC()->cart && WC()->cart->get_total( 'edit' ) == 0 );
        }

        public function process_payment( $order_id )
        {
            $order = wc_get_order( $order_id );
            $order->payment_complete(); // mark as paid
            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            ];
        }
    }
}
class WooService
{

    public function addFreeGateway( $gateways )
    {
        $gateways[] = 'WC_Gateway_Free_Order';
        return $gateways;
    }

    // ─────────────────────────────────────────────────────────
    // 2) SKIP PAYMENT VALIDATION ON ZERO‑TOTAL
    // ─────────────────────────────────────────────────────────

    /**
     * @param bool                   $needs_payment
     * @param \WC_Order|null         $order
     * @return bool
     */
    public function allowFreeOrders( $needs_payment, $order = null )
    {
        // Cart‑level check
        if ( WC()->cart && WC()->cart->get_total( 'edit' ) == 0 ) {
            return false;
        }
        // Order‑level check
        if ( $order && $order->get_total() == 0 ) {
            return false;
        }
        return $needs_payment;
    }



    public function addToCart($data)
    {
        appLogger(json_encode($data));
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
            // Add product image
            if (isset($data['Image_url'])) {
                $attachmentId = upload_image_from_url($data['Image_url']);
                if ($attachmentId) {
                    $product->set_image_id($attachmentId);
                }
            }
            $product->set_name($data['name']);
            $product->set_category_ids(array());
            
            $price = isset($data['price']) && is_numeric($data['price']) ? floatval($data['price']) : 0;
            if ($price == 0) {
                $product->set_regular_price(''); // Empty string for free products
                $product->set_sale_price(''); // Clear sale price
            } else {
                $product->set_regular_price($price);
            }
            
            $product->set_description($data['description'] ?? '');
            $product->set_short_description($data['short_description'] ?? '');
            $product->set_status('publish');
            $product->save();
            $product_id = $product->get_id();

            $category = get_term_by( 'slug', 'rayman', 'product_cat' );
            if($category){
                wp_set_object_terms( $product_id, array( $category->term_id ), 'product_cat' );
            }
            // Save the custom meta field to track this product
            update_post_meta($product_id, '_dnp_product_id', $data['id']);
            update_post_meta($product_id, '_dnp_product_slug', $data['slug']);
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_stock_status', 'instock' );
            update_post_meta($product_id, '_manage_stock', 'no' );
            update_post_meta($product_id, '_sold_individually', 'yes' );


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
        $order = wc_get_order($orderId);
        $productIds = [];
        $slug = '';
        foreach ($order->get_items() as $item_id => $item) {
            // Retrieve the 'dnpuser' metadata from the order item
            $dnpuser = $item->get_meta('dnpuser');
            if ($dnpuser) {
                $slug = get_post_meta($item->get_product_id(), '_dnp_product_slug', true);
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

        WC()->cart->empty_cart();

        if(empty($productIds)){
            return;
        }
        wp_redirect(Vendor::donap()->getPurchasedProductUrl($slug));
        exit;
    }


    public function isCartFree() {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        $cart = WC()->cart;
        $cart_items = $cart->get_cart();
        if (empty($cart_items)) {
            return false;
        }
        foreach ($cart_items as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if ($product && $product->get_price() > 0) {
                return false; // Non-free product found
            }
        }
        return true; // All products are free
    }

    // New method to handle free order processing
    public function handleFreeCheckout() {
        if (!$this->isCartFree()) {
            return; // Only process if cart is free
        }

        // Create an order
        $cart = WC()->cart;
        $checkout = WC()->checkout();
        $order_id = $checkout->create_order([
            'billing_email' => wp_get_current_user()->user_email ?: 'free@order.com',
            'payment_method' => 'none',
        ]);

        if (is_wp_error($order_id)) {
            return; // Handle error if order creation fails
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Copy cart items to order
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $item = new WC_Order_Item_Product();
            $item->set_product(wc_get_product($cart_item['product_id']));
            $item->set_quantity($cart_item['quantity']);
            $item->set_subtotal($cart_item['line_subtotal']);
            $item->set_total($cart_item['line_total']);
            // Copy dnpuser meta if it exists
            if (isset($cart_item['dnpuser'])) {
                $item->add_meta_data('dnpuser', $cart_item['dnpuser']);
            }
            $order->add_item($item);
        }

        // Set order as completed
        $order->set_status('completed');
        $order->set_total(0);
        $order->save();

        // Trigger woocommerce_payment_complete hook
        do_action('woocommerce_payment_complete', $order_id);

        // Clear notices and redirect (handled by processUserIdAfterPayment)
        wc_clear_notices();
    }


    public function productPageButton(){
        global $product;  
    
        $product_id = $product->get_id(); 
        $slug = get_post_meta($product_id, '_dnp_product_slug', true);
        if(!$slug){
            return;
        }
        $url = Vendor::donap()->getProductPageUrl($slug);
        echo "<a 
            target='_blank' 
            style='
            background-color: #4fc800;
            color: #fff;
            flex: none;
            text-align: center;
            width: 210px;
            display: flex; /* Change to flexbox */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            border-radius: 5px;
            height: 50px; /* Optional: Define height if needed */
            text-decoration: none; /* Remove underline */
        '
        href='".$url."' class=''>
            مشاهده این محصول در رایمن
        </a>";
    }

}
