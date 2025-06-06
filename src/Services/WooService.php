<?php

namespace App\Services;

use DateTime;
use App\Facades\Vendor;
use App\Helpers\TransactionType;
use App\Helpers\WalletType;
use App\Models\UserCart;
use Exception;
use Kernel\Container;
use App\Models\Wallet;
use Kernel\DB;

class WooService
{

    private WalletService $walletService;
    public function __construct()
    {
        $this->walletService = Container::resolve('WalletService');
    }

    public function beforeCheckout()
    {
        // Get the cart instance
        $cart = WC()->cart;

        // If cart is empty, nothing to do
        if ($cart->is_empty()) {
            appLogger('Empty Cart');
            return;
        }

        // Assume cart is free unless we find a paid item
        $has_paid_item = false;

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];

            // Get the product price as a float
            $price = (float) $product->get_price();

            // If any item has a price > 0, we allow checkout
            if ($price > 0) {
                $has_paid_item = true;
                break;
            }
        }

        // If no paid items found, block checkout and empty cart
        if (! $has_paid_item) {
            appLogger('no paid item');
            $productIds = [];
            $slug       = '';

            // 3) Build up the per‑user product list
            foreach ($cart->get_cart() as $cart_item) {
                appLogger('Cart Item: ' . json_encode($cart_item));
                // assume you added this when item was added to cart
                $dnpuser = isset($cart_item['dnpuser'])
                    ? $cart_item['dnpuser']
                    : '';

                appLogger('Donapp User: ' . $dnpuser);

                if ($dnpuser) {
                    $pid = $cart_item['product_id'];
                    appLogger('Product Id: ' . $dnpuser);

                    // pull your stored post‑meta
                    $slug         = get_post_meta($pid, '_dnp_product_slug', true);
                    $dnpProductId = get_post_meta($pid, '_dnp_product_id',   true);

                    if (! isset($productIds[$dnpuser])) {
                        $productIds[$dnpuser] = [(string) $dnpProductId];
                    } else {
                        $productIds[$dnpuser][] = (string) $dnpProductId;
                    }
                }
            }

            // if no dnpusers found, bail
            if (empty($productIds)) {
                return;
            }

            // 5) Grant access
            foreach ($productIds as $dnpuser => $products) {
                $order = $this->createWooOrder($dnpuser, $slug, $products);
                appLogger('Giving access to ' . $dnpuser . ' for products ' . json_encode($products));
                Vendor::donap()->giveAccess($dnpuser, $products);
            }

            // 6) Clear cart, redirect, and exit
            $cart->empty_cart();

            // if you want the last‐seen slug; adjust if you need to redirect per‐item or per‐user
            $redirect_url = Vendor::donap()->getPurchasedProductUrl($slug);
            wp_redirect($redirect_url);
            exit;
        }
        appLogger('Paying items');
    }

    private function createWooOrder($dnpuser, $slug, array $dnpProductIds)
    {
        $user_id = get_current_user_id();

        // If logged in, attach to that user; otherwise leave customer_id=0
        $order_args = ['status' => 'completed'];
        if ($user_id) {
            $order_args['customer_id'] = $user_id;
        }

        /** @var WC_Order $order */
        $order = wc_create_order($order_args);

        // Add the same products from the cart
        foreach (WC()->cart->get_cart() as $item) {
            $order->add_product($item['data'], $item['quantity']);
        }

        // Zero total, mark paid/completed
        $order->set_total(0);
        $order->calculate_totals();
        $order->payment_complete();

        // Store our DNP meta
        $order->update_meta_data('_dnp_user',          $dnpuser);
        $order->update_meta_data('_dnp_product_slug',  $slug);
        $order->update_meta_data('_dnp_product_ids',   wp_json_encode($dnpProductIds));
        if (! $user_id) {
            $order->update_meta_data('_dnp_guest_order', 'yes');
        }

        $order->add_order_note('Auto‑generated free digital order.');
        $order->save();

        return $order;
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

        $this->handleDonapCoin($data);

        $theCart = $userCart->where('identifier', '=', $data['id'])->first();
        return array_merge(
            $theCart,
            ['cart' => json_decode($theCart['cart'])]
        );
    }

    private function handleDonapCoin($data)
    {
        if (!empty($data['amount'])) {
            $wallet = $this->walletService->findOrCreateWallet($data['id'], WalletType::COIN);
            $params = json_decode($wallet['params']);
            $lastUpdateAmount = $params['last_donap_coin'] ?? 0;
            $updateAmount = intval($data['amount']) - intval($lastUpdateAmount);
            if ($updateAmount > 0) {
                $this->walletService->updateBalance($data['id'], 'coin', $updateAmount, TransactionType::DONAP_COIN);
                $params['last_donap_coin'] = $data['amount'];
                (new Wallet())->update(
                    [
                        'params' => json_encode($params),
                    ],
                    [
                        'id' => $wallet['id']
                    ]
                );
            }
        }
    }

    public function deleteExpiredCarts()
    {
        $table = DB::wpdb()->prefix . 'dnp_user_carts';
        DB::query("DELETE FROM $table  WHERE expired_at < now()");
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

            $category = get_term_by('slug', 'rayman', 'product_cat');
            if ($category) {
                wp_set_object_terms($product_id, array($category->term_id), 'product_cat');
            }
            // Save the custom meta field to track this product
            update_post_meta($product_id, '_dnp_product_id', $data['id']);
            update_post_meta($product_id, '_dnp_product_slug', $data['slug']);
            if (!empty($data['owner_identifier'])) {
                update_post_meta($product_id, '_dnp_product_owner_identifier', $data['owner_identifier']);
            }
            if (!empty($data['is_income_shared'])) {
                update_post_meta($product_id, '_dnp_product_is_income_shared', $data['is_income_shared']);
            }
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_manage_stock', 'no');
            update_post_meta($product_id, '_sold_individually', 'yes');


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
                $ownerId = get_post_meta($item->get_product_id(), '_dnp_product_owner_identifier');
                $isIncomeShared = get_post_meta($item->get_product_id(), '_dnp_product_is_income_shared');
                if ($ownerId && $isIncomeShared) {
                    $product = $item->get_product();
                    $regular_price = $product ? $product->get_regular_price() : 0;
                    $this->walletService->updateBalance($ownerId, WalletType::CREDIT, $regular_price * (2/10), TransactionType::SHARED_INCOME);
                }
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

        if (empty($productIds)) {
            return;
        }
        wp_redirect(Vendor::donap()->getPurchasedProductUrl($slug));
        exit;
    }


    public function isCartFree()
    {
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


    public function productPageButton()
    {
        global $product;

        $product_id = $product->get_id();
        $slug = get_post_meta($product_id, '_dnp_product_slug', true);
        if (!$slug) {
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
        href='" . $url . "' class=''>
            مشاهده این محصول در رایمن
        </a>";
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
