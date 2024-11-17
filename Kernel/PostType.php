<?php

namespace Kernel;

class PostType
{

    public function __construct()
    {
        $this->register();
    }

    public function register()
    {
        $args = array(
            'labels' => array(
                'name' => 'Videos',
                'singular_name' => 'Video',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Video',
                'edit_item' => 'Edit Video',
                'new_item' => 'New Video',
                'view_item' => 'View Video',
                'all_items' => 'All Videos',
                'search_items' => 'Search Videos',
                'not_found' => 'No videos found',
                'not_found_in_trash' => 'No videos found in Trash',
                'menu_name' => 'Videos',
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,  // Adjust this based on where you want the menu to appear
            'supports' => array('title', 'thumbnail'), // Title and featured image support
            'has_archive' => true,
            'rewrite' => array('slug' => 'videos'), // You can change the URL slug
            'show_in_rest' => true, // Enable Gutenberg editor
        );

        register_post_type('dnp-video', $args);
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'video_url',
                'Video URL',
                function ($post) {
                    $video_url = get_post_meta($post->ID, '_video_url', true);

                    // Display the URL input field
                    echo '<label for="video_url">Video URL: </label>';
                    echo '<input type="text" id="video_url" name="video_url" value="' . esc_attr($video_url) . '" style="width: 100%;" />';
                },
                'dnp-video',
                'normal',
                'high'
            );
        });


        add_action( 'save_post', function($post_id){
            if ( ! isset( $_POST['video_url_nonce'] ) ) {
                return $post_id;
            }
            $nonce = $_POST['video_url_nonce'];
        
            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $nonce, 'save_video_url' ) ) {
                return $post_id;
            }
        
            // Save the custom field data
            if ( isset( $_POST['video_url'] ) ) {
                update_post_meta( $post_id, '_video_url', sanitize_text_field( $_POST['video_url'] ) );
            }
        
            return $post_id;
        } );
    }
}
