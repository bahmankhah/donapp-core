<?php
if (!function_exists('res')) {

    function res($result = null, $message = '', $status = 200): WP_REST_Response
    {
        return rest_ensure_response(
            [
                'result' => $result,
                'status' => $status,
                'message' => $message,
                'timestamp' => microtime(true),
                'version' => appConfig('app.version'),
                'path' => $_SERVER['REQUEST_URI'],
                'ok' => $status >= 200 && $status < 300
            ]
        );
    }
}

if (!function_exists('upload_image_from_url')) {
    function upload_image_from_url($image_url)
    {
        // Include the required WordPress file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download the image to the temporary directory
        $temp_file = download_url($image_url);

        if (is_wp_error($temp_file)) {
            // If there's an error, return false
            return false;
        }

        // Set up the file array for WordPress
        $file = array(
            'name'     => basename($image_url),
            'type'     => mime_content_type($temp_file),
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        // Upload the image to the WordPress media library
        $attachment_id = media_handle_sideload($file, 0);

        // Check for upload errors
        if (is_wp_error($attachment_id)) {
            @unlink($temp_file); // Remove the temporary file if there was an error
            return false;
        }

        return $attachment_id; // Return the attachment ID on success
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
            error_log("No .env file found at {$file_path}");
        }
    }
}

if ( ! function_exists( 'my_plugin_log_error' ) ) {
    function donappLog( $message ) {
        $log_file = plugin_dir_path( __FILE__ ) . 'my-plugin-errors.log';
        $time = date( 'Y-m-d H:i:s' );
        $formatted_message = "[{$time}] {$message}\n";
        
        // Write to the log file.
        file_put_contents( $log_file, $formatted_message, FILE_APPEND );
    }
}