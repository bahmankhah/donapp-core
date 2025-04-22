<?php

namespace App\Providers;

class AudioPlayerServiceProvider
{
    public function register() {}

    public function boot()
    {
          
        add_action('wp_enqueue_scripts', function(){
            wp_register_script(
                'custom-audioplayer', // Handle
                WP_PLUGIN_DIR . '/' . appConfig('app.name') . '/'. 'resources/js/audioplayer.js', // Path to the script
                array('jquery'), // Dependencies (e.g., jQuery)
                time(), // Version
                true // Load in footer
            );
            wp_enqueue_script('custom-audioplayer');
        });
    }
}
