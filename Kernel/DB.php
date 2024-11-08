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

    public static function select($query){
        
    }
}