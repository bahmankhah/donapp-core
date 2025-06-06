<?php

namespace Kernel\Facades;

class View extends Facade{

    protected static function getFacadeAccessor() {
        return \Kernel\ViewManager::class;
    }
}