<?php

namespace Kernel;
abstract class DBI {
}
class DB {
    private $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public static function __callStatic($name, $arguments)
    {
        return (new DB())->{$name.'Main'}(...$arguments);
    }

    public function wpdbMain(){
        return $this->wpdb;
    }

    public function getCategoryId($slug){
        $category_id = $this->wpdbMain()->get_var($this->wpdbMain()->prepare("
            SELECT term_id 
            FROM {$this->wpdbMain()->terms} 
            WHERE slug = %s
        ", $slug));
        return $category_id;
    }

    public static function select($query){
        
    }
}