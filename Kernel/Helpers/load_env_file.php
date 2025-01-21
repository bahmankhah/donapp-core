<?php
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
            donappLog("No .env file found at {$file_path}");
        }
    }
}