<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\BlogService;
use App\Services\GiftService;
use App\Services\Modules\Proxy\ProxyService;
use App\Services\ProductService;
use App\Services\TransactionService;
use App\Services\VideoService;
use App\Services\WalletService;
use App\Services\WooService;
use Kernel\Container;

class AppServiceProvider
{

    public function register()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_wallets          = $wpdb->prefix . 'dnp_user_wallets';
        $table_wallet_tx        = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $table_carts = $wpdb->prefix . 'dnp_user_carts';

        // Table 1: dnp_user_carts
        $sql_carts = "CREATE TABLE IF NOT EXISTS $table_carts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL UNIQUE,
            cart JSON DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expired_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // 2) wallets table
        $sql_wallets = "CREATE TABLE IF NOT EXISTS $table_wallets (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        identifier VARCHAR(255) NOT NULL,
        type VARCHAR(255)   NOT NULL,
        balance BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        params JSON DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_identifier_type (identifier, type)
        ) $charset_collate;";

        // 3) new wallet_transactions table definition
        $sql_wallet_tx = "CREATE TABLE IF NOT EXISTS $table_wallet_tx (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wallet_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(255) DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        credit BIGINT(20) UNSIGNED DEFAULT NULL,
        debit  BIGINT(20) UNSIGNED DEFAULT NULL,
        remain BIGINT(20) UNSIGNED DEFAULT 0,
        params JSON DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wallet_id (wallet_id),
        CONSTRAINT fk_wallet_tx_wallet
          FOREIGN KEY (wallet_id)
          REFERENCES $table_wallets(id)
          ON DELETE CASCADE
        ) $charset_collate;";

        // Load WordPress's dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Execute both table creations
        dbDelta( $sql_carts );
        dbDelta( $sql_wallets );
        dbDelta( $sql_wallet_tx );
    }


    public function boot()
    {
        Container::bind('AuthService', function () {
            return new AuthService();
        });
        Container::bind('ProductService', function () {
            return new ProductService();
        });
        Container::bind('BlogService', function () {
            return new BlogService();
        });

        Container::bind('VideoService', function () {
            return new VideoService();
        });

        Container::bind('WooService', function () {
            return new WooService();
        });
        Container::bind('ProxyService', function () {
            return new ProxyService();
        });
        Container::bind('TransactionService', function () {
            return new TransactionService();
        });
        Container::bind('WalletService', function () {
            return new WalletService();
        });
        
        Container::bind('GiftService', function () {
            return new GiftService();
        });
    }
}
