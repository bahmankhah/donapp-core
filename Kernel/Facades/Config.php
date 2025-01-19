<?php

namespace Kernel\Facades;

class Config extends Facade{

    protected static function getFacadeAccessor() {
        return \Kernel\Config::class;
    }
}