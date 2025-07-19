<?php
/**
 * Test script to check if the Donap Wallet Gateway is registered properly
 * Add this to your WordPress theme's functions.php temporarily or run as standalone
 */

// Hook to check available payment gateways
add_action('wp_loaded', function() {
    if (class_exists('WC_Payment_Gateways')) {
        $gateways = WC_Payment_Gateways::instance();
        $available_gateways = $gateways->get_available_payment_gateways();
        
        error_log('=== DONAP GATEWAY TEST ===');
        error_log('Total available gateways: ' . count($available_gateways));
        
        foreach ($available_gateways as $gateway_id => $gateway) {
            error_log('Gateway ID: ' . $gateway_id . ' - Title: ' . $gateway->get_title());
        }
        
        if (isset($available_gateways['donap_wallet'])) {
            error_log('SUCCESS: Donap Wallet Gateway is available!');
            $donap_gateway = $available_gateways['donap_wallet'];
            error_log('Gateway Title: ' . $donap_gateway->get_title());
            error_log('Gateway Enabled: ' . ($donap_gateway->enabled === 'yes' ? 'yes' : 'no'));
        } else {
            error_log('ERROR: Donap Wallet Gateway NOT FOUND in available gateways');
            
            // Check if it's registered at all
            $all_gateways = $gateways->payment_gateways();
            if (isset($all_gateways['donap_wallet'])) {
                error_log('Gateway is registered but not available');
                $donap_gateway = $all_gateways['donap_wallet'];
                error_log('Gateway Enabled Setting: ' . $donap_gateway->enabled);
                error_log('is_available() returns: ' . ($donap_gateway->is_available() ? 'true' : 'false'));
            } else {
                error_log('Gateway is not registered at all');
            }
        }
        error_log('=== END GATEWAY TEST ===');
    }
});
