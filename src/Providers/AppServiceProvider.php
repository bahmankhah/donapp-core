<?php

namespace Donapp\Providers;

use Donapp\Services\AuthService;
use Donapp\Services\BlogService;
use Donapp\Services\ProductService;
use Donapp\Services\VideoService;
use Donapp\Services\WooService;
use Kernel\Container;
use Kernel\PostType;

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
    }
}
