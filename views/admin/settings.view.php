<div class="wrap donap-admin-page">
    <h1>تنظیمات دناپ</h1>
    
    <div class="donap-settings-container">
        <form method="post" action="options.php">
            <?php
            settings_fields('donap_gift_settings');
            do_settings_sections('donap-gift-settings');
            submit_button('ذخیره تنظیمات هدیه');
            ?>
        </form>
    </div>
</div>
