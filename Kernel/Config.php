<?php

namespace Kernel;

class Config{
    public function __construct()
    {
        $files = glob(__DIR__ . '/../src/configs/*.php');
        $configs = [];
        foreach ($files as $file) {
            $configs[basename($file, '.php')] = require_once($file);
        }
        $GLOBALS['donapp_configs'] = $configs;
    }
}