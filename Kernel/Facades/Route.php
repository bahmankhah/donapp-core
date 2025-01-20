<?php

namespace Kernel\Facades;

class Route extends Facade{

    protected static function getFacadeAccessor() {
        return \Kernel\Route::class;
    }
}