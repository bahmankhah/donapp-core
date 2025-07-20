    <?php
    // Get default amount from query parameter if exists
    $default_amount = isset($_GET['amount']) ? absint($_GET['amount']) : '';
    
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

<style>
    .wallet-topup-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Vazir', 'Tahoma', sans-serif;
        direction: rtl;
    }
    
    .wallet-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        color: white;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .wallet-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.9;
    }
    
    .wallet-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .wallet-subtitle {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .topup-form {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid #e1e8ed;
    }
    
    .form-title {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
        text-align: center;
    }
    
    .form-subtitle {
        color: #7f8c8d;
        text-align: center;
        margin-bottom: 30px;
        font-size: 14px;
    }
    
    .amount-section {
        margin-bottom: 25px;
    }
    
    .amount-label {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .amount-input {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        font-size: 18px;
        text-align: center;
        transition: all 0.3s ease;
        box-sizing: border-box;
        background: #f8f9fa;
    }
    
    .amount-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .quick-amounts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 10px;
        margin: 15px 0 25px 0;
    }
    
    .quick-amount-btn {
        padding: 12px 16px;
        border: 2px solid #e1e8ed;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        color: #2c3e50;
    }
    
    .quick-amount-btn:hover {
        border-color: #667eea;
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }
    
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .submit-btn:active {
        transform: translateY(0);
    }
    
    .info-box {
        background: #f8f9fa;
        border: 1px solid #e1e8ed;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .info-title {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .info-list {
        margin: 0;
        padding-right: 20px;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .info-list li {
        margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
        .wallet-topup-container {
            padding: 0 15px;
            margin: 20px auto;
        }
        
        .wallet-card {
            padding: 30px 20px;
        }
        
        .wallet-title {
            font-size: 24px;
        }
        
        .topup-form {
            padding: 25px 20px;
        }
        
        .quick-amounts {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="wallet-topup-container">
    <div class="wallet-card">
        <div class="wallet-icon">💳</div>
        <h1 class="wallet-title">کیف پول دیجیتال</h1>
        <p class="wallet-subtitle">افزایش موجودی به صورت آسان و سریع</p>
    </div>
    
    <div class="topup-form">
        <h2 class="form-title">افزایش موجودی کیف پول</h2>
        <p class="form-subtitle">مبلغ مورد نظر خود را انتخاب کنید</p>
        
        <form method="post">
            <div class="amount-section">
                <label for="wallet_amount" class="amount-label">
                    مبلغ افزایش موجودی (تومان)
                </label>
                
                <div class="quick-amounts">
                    <button type="button" class="quick-amount-btn" onclick="setAmount(10000)">۱۰,۰۰۰</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(20000)">۲۰,۰۰۰</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(50000)">۵۰,۰۰۰</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(100000)">۱۰۰,۰۰۰</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(200000)">۲۰۰,۰۰۰</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(500000)">۵۰۰,۰۰۰</button>
                </div>
                
                <input 
                    type="number" 
                    name="wallet_amount" 
                    id="wallet_amount"
                    class="amount-input"
                    required 
                    min="1000" 
                    step="1000" 
                    value="<?php echo esc_attr($default_amount); ?>"
                    placeholder="مبلغ دلخواه را وارد کنید..."
                >
            </div>
            
            <button type="submit" name="wallet_topup_submit" class="submit-btn">
                💰 افزایش موجودی
            </button>
        </form>
        
        <div class="info-box">
            <div class="info-title">📋 نکات مهم:</div>
            <ul class="info-list">
                <li>حداقل مبلغ افزایش موجودی ۱,۰۰۰ تومان است</li>
                <li>افزایش موجودی به صورت فوری انجام می‌شود</li>
                <li>امکان استفاده از درگاه‌های پرداخت معتبر</li>
                <li>تمامی تراکنش‌ها دارای رسید الکترونیکی هستند</li>
            </ul>
        </div>
    </div>
</div>

<script>
    function setAmount(amount) {
        document.getElementById('wallet_amount').value = amount;
        
        // Add visual feedback
        const buttons = document.querySelectorAll('.quick-amount-btn');
        buttons.forEach(btn => {
            btn.style.background = 'white';
            btn.style.color = '#2c3e50';
        });
        event.target.style.background = '#667eea';
        event.target.style.color = 'white';
        
        // Reset after a moment
        setTimeout(() => {
            event.target.style.background = 'white';
            event.target.style.color = '#2c3e50';
        }, 200);
    }
</script>