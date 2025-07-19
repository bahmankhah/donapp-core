<div class="wrap donap-admin-page">
    <h1>Donap Settings</h1>
    
    <?php 
    $tabs = [
        'gift-settings' => [
            'label' => 'Gift Settings',
            'active' => true,
            'content' => '
                <form method="post" action="options.php">
                    ' . wp_nonce_field('donap_gift_settings-options', '_wpnonce', true, false) . '
                    <input type="hidden" name="option_page" value="donap_gift_settings" />
                    <input type="hidden" name="action" value="update" />
                    ' . do_settings_sections('donap-gift-settings') . '
                    ' . submit_button('Save Gift Settings', 'primary', 'submit', false) . '
                </form>'
        ],
        'general-settings' => [
            'label' => 'General Settings',
            'content' => '
                <h2>General Settings</h2>
                <p>General settings will be implemented here.</p>'
        ],
        'api-settings' => [
            'label' => 'API Settings', 
            'content' => '
                <h2>API Settings</h2>
                <p>API settings will be implemented here.</p>'
        ]
    ];
    
    // Capture the settings fields output
    ob_start();
    settings_fields('donap_gift_settings');
    do_settings_sections('donap-gift-settings');
    $gift_settings_form = ob_get_clean();
    
    $tabs['gift-settings']['content'] = '
        <form method="post" action="options.php">
            ' . $gift_settings_form . '
            ' . submit_button('Save Gift Settings', 'primary', 'submit', false) . '
        </form>';
    
    echo view('admin/components/tabs', ['tabs' => $tabs]);
    ?>
</div>
