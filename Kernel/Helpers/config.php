<?php
if (!function_exists('appConfig')) {
    function appConfig($key = null, $default = null)
    {
        global $donapp_configs;
        if ($key === null) {
            return $donapp_configs;
        }

        $keys = explode('.', $key);
        $value = $donapp_configs;

        foreach ($keys as $keyPart) {
            if (is_array($value) && array_key_exists($keyPart, $value)) {
                $value = $value[$keyPart];
            } else {
                return $default; 
            }
        }
        return $value;
    }
}