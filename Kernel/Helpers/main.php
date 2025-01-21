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

if (!function_exists('appLogger')) {
    function appLogger($message)
    {
        $message = (string) $message;
        $plugin_dir = WP_PLUGIN_DIR . '/' . appConfig('app.name');
        $log_file = $plugin_dir . '/logs/donapp-errors.log';
        // Ensure the directory exists
        $directory = dirname($log_file);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // Create the directory with permissions
        }

        $time = date('Y-m-d H:i:s');
        $formatted_message = "[{$time}] {$message}". PHP_EOL;

        // Write to the log file.
        file_put_contents($log_file, $formatted_message, FILE_APPEND);
    }
}

if (!function_exists('load_env_file')) {
    // Load .env file manually
    function load_env_file($file_path)
    {
        if (file_exists($file_path)) {
            $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // Ignore comments (lines starting with #)
                if (strpos($line, '#') === 0) {
                    continue;
                }

                // Split the line into key and value
                $parts = explode('=', $line, 2);

                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);

                    // Set the environment variable
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        } else {
            appLogger("No .env file found at {$file_path}");
        }
    }
}