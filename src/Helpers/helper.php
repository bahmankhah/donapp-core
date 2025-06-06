<?php

use Kernel\Facades\Route;
use Kernel\Facades\View;

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

if(!function_exists('get_donap_user_id')){
    function get_donap_user_id($wp_user_id = null){
        if($wp_user_id === null){
            $user_id = get_current_user_id();
        }else{
            $user_id = $wp_user_id;
        }
        if($user_id){
            $ssoId = get_user_meta( $user_id, 'sso_global_id', true );
            if($ssoId){
                return $ssoId;
            }
        }
        return null;
    }
}

