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

        // Define the table name
        $table_name = $wpdb->prefix . 'dnp_user_carts';

        // Define the SQL for table creation
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, -- Primary ID
            identifier VARCHAR(255) NOT NULL UNIQUE,       -- Unique string identifier
            cart JSON DEFAULT NULL,                        -- JSON column for cart data
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Timestamp for record creation
            expired_at DATETIME DEFAULT NULL,              -- Timestamp for record expiration
            PRIMARY KEY (id)
        ) $charset_collate";


        // Load WordPress's dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Execute the SQL
        dbDelta($sql);
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
