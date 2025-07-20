<?php
require_once __DIR__ . '/donapp-core.php';

use App\Models\Wallet;
use App\Services\WalletService;
use Kernel\Container;

// Initialize the application
$app = new \Kernel\Application();
$app->boot();

echo "=== Testing Wallet Count ===\n";

// Test direct query
$walletModel = new Wallet();
$query = $walletModel->newQuery()->select('COUNT(*)');
echo "SQL Query: " . $query->sql() . "\n";

global $wpdb;
$count = $wpdb->get_var($query->sql());
echo "Direct count result: " . $count . "\n";

// Test manual count
$manual_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dnp_user_wallets");
echo "Manual count: " . $manual_count . "\n";

// Test service method
$walletService = Container::resolve('WalletService');
$stats = $walletService->getWalletStats();
echo "Stats from service:\n";
print_r($stats);
