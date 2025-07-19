    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_topup_submit'], $_POST['wallet_amount'])) {
        $amount = absint($_POST['wallet_amount']);

        if ($amount < 1000) {
            wc_add_notice('حداقل مبلغ افزایش موجودی ۱۰۰۰ تومان است.', 'error');
        } else {
            WC()->session->set('wallet_topup_amount', $amount);
            WC()->cart->empty_cart();

            // Create a virtual wallet top-up product
            $product = new WC_Product_Simple();
            $product->set_name('افزایش موجودی کیف پول');
            $product->set_price($amount);
            $product->set_regular_price($amount);
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_catalog_visibility('hidden');
            $product->set_status('publish');
            $product_id = $product->save();

            // Add item to cart with wallet_topup meta
            WC()->cart->add_to_cart($product_id, 1, 0, [], ['wallet_topup' => true]);

            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }
    ?>

    <form method="post">
        <label for="wallet_amount">مبلغ افزایش موجودی (تومان):</label>
        <input type="number" name="wallet_amount" required min="1000" step="1000">
        <button type="submit" name="wallet_topup_submit">افزایش موجودی</button>
    </form>