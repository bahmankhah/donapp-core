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
        
        appLogger('=== DONAP GATEWAY TEST ===');
        appLogger('Total available gateways: ' . count($available_gateways));
        
        foreach ($available_gateways as $gateway_id => $gateway) {
            appLogger('Gateway ID: ' . $gateway_id . ' - Title: ' . $gateway->get_title());
        }
        
        if (isset($available_gateways['donap_wallet'])) {
            appLogger('SUCCESS: Donap Wallet Gateway is available!');
            $donap_gateway = $available_gateways['donap_wallet'];
            appLogger('Gateway Title: ' . $donap_gateway->get_title());
            appLogger('Gateway Enabled: ' . ($donap_gateway->enabled === 'yes' ? 'yes' : 'no'));
        } else {
            appLogger('ERROR: Donap Wallet Gateway NOT FOUND in available gateways');
            
            // Check if it's registered at all
            $all_gateways = $gateways->payment_gateways();
            if (isset($all_gateways['donap_wallet'])) {
                appLogger('Gateway is registered but not available');
                $donap_gateway = $all_gateways['donap_wallet'];
                appLogger('Gateway Enabled Setting: ' . $donap_gateway->enabled);
                appLogger('is_available() returns: ' . ($donap_gateway->is_available() ? 'true' : 'false'));
            } else {
                appLogger('Gateway is not registered at all');
            }
        }
        appLogger('=== END GATEWAY TEST ===');
    }
});
