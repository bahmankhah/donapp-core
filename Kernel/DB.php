<?php

namespace Kernel;

abstract class DBI {}
class DB
{
    private $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public static function __callStatic($name, $arguments)
    {
        return (new DB())->{$name . 'Main'}(...$arguments);
    }

    public function wpdbMain()
    {
        return $this->wpdb;
    }

    public function getCategoryId($slug)
    {
        $category_id = $this->wpdbMain()->get_var($this->wpdbMain()->prepare("
            SELECT term_id 
            FROM {$this->wpdbMain()->terms} 
            WHERE slug = %s
        ", $slug));
        return $category_id;
    }

    public static function select($query) {}

    public static function wpQuery($args)
    {
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $list = array();

            while ($query->have_posts()) {
                $query->the_post();
                $list[] = array(
                    'ID'    => get_the_ID(),
                );
            }

            wp_reset_postdata();
            return $list;
        }

        return array(); // Return an empty array if no products match
    }
}
