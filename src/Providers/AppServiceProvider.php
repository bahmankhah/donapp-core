<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\BlogService;
use App\Services\Modules\Proxy\ProxyService;
use App\Services\ProductService;
use App\Services\VideoService;
use App\Services\WooService;
use Kernel\Container;

class AppServiceProvider
{

    public function register()
    {
        global $wpdb;
    
        $charset_collate = $wpdb->get_charset_collate();
    
        // Table 1: dnp_user_carts
        $table_carts = $wpdb->prefix . 'dnp_user_carts';
        $sql_carts = "CREATE TABLE IF NOT EXISTS $table_carts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL UNIQUE,
            cart JSON DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expired_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
    
        // Table 2: dnp_user_transactions
        $table_transactions = $wpdb->prefix . 'dnp_user_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL,
            type VARCHAR(255) NOT NULL,
            credit BIGINT(20) UNSIGNED DEFAULT NULL,
            debit BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_identifier (identifier)
        ) $charset_collate;";
    
        // Load WordPress's dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
        // Execute both table creations
        dbDelta($sql_carts);
        dbDelta($sql_transactions);
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
    }
}
