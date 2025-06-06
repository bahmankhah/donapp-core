<?php

namespace Kernel\Facades;

class Wordpress extends Facade{

    protected static function getFacadeAccessor() {
        return \Kernel\WordpressManager::class;
    }
}