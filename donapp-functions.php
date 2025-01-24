<?php


function enqueue_waveform_player() {
    // Enqueue WaveSurfer.js (UMD version)
    wp_enqueue_script('wavesurfer-js', 'https://unpkg.com/wavesurfer.js', array(), null, true);

    // Inline JavaScript for handling the waveform functionality
    $inline_js = "
    jQuery(document).ready(function ($) {
        // Wait for the page to fully load using a timeout
        setTimeout(function () {
            // Get the audio element's source
            var audioFileUrl = $('audio').attr('src');
            console.log('Audio file URL:', audioFileUrl);

            if (!audioFileUrl) {
                console.error('Audio file URL is undefined. Please check the <audio> element.');
                return;
            }

            // Check if the HTML structure exists
            if ($('.jp-progress').length > 0) {
                // Create a WaveSurfer instance
                var wavesurfer = WaveSurfer.create({
                    container: '.jp-seek-bar', // Use jp-seek-bar as the waveform container
                    waveColor: 'rgba(200, 200, 200, 0.5)', // Waveform color
                    progressColor: '#00f', // Progress bar color
                    height: 50, // Adjust height as needed
                    responsive: true // Make responsive
                });

                // Load the audio file into WaveSurfer
                wavesurfer.load(audioFileUrl);

                // Hide the old progress bar
                $('.jp-play-bar').css('display', 'none');

                // Play/Pause toggle
                $('.jp-progress').on('click', function () {
                    wavesurfer.playPause(); // Toggle play/pause on click
                });

                // Sync waveform with playback progress
                wavesurfer.on('audioprocess', function () {
                    var currentTime = wavesurfer.getCurrentTime();
                    var duration = wavesurfer.getDuration();
                    var progress = (currentTime / duration) * 100;
                    $('.jp-play-bar').css('width', progress + '%');
                });

                // Seek audio file when clicking the waveform
                wavesurfer.on('seek', function (progress) {
                    var audio = $('#audio-element')[0]; // Your existing audio element
                    audio.currentTime = progress * audio.duration;
                });
            }
        }, 1000); // Delay of 1 second to ensure the page is fully loaded
    });
";

    wp_add_inline_script('wavesurfer-js', $inline_js);

    // Inline CSS for styling the waveform and progress bar
    $inline_css = "
        .jp-seek-bar {
            height: 100% !important; /* Match WaveSurfer height */
			background: #EFF3F5 !important
            background-color: rgba(239, 243, 245, 1) !important; /* Optional: Add a subtle background */
            background: rgba(239, 243, 245, 1) !important; /* Optional: Add a subtle background */
            position: relative;
        }

        .jp-progress {
            cursor: pointer; /* Allow clicking for seek functionality */
        }

        .jp-play-bar {
            display: none; /* Hide old progress bar */
        }
    ";
    wp_add_inline_style('wp-block-library', $inline_css); // Enqueue inline CSS
}
add_action('wp_enqueue_scripts', 'enqueue_waveform_player');



function add_custom_jquery_script() {
    // Ensure jQuery is loaded (WordPress includes jQuery by default)
    wp_enqueue_script('jquery');
    
    // Add custom jQuery script
    $custom_js = "
        jQuery(document).ready(function($) {
            // Select all elements with ids matching the pattern 'element_{n}', where {n} is a number
            $('.jp-controls').each(function() {
                // This will target each element with the matching class pattern 
                $(this).append('<div class=\"jp-speed-controls\">\
                                    <select class=\"jp-speed\" tabindex=\"0\">\
                                        <option value=\"0.5\">x0.5</option>\
                                        <option value=\"1\" selected=\"selected\">x1</option>\
                                        <option value=\"1.5\">x1.5</option>\
                                        <option value=\"2\">x2</option>\
                                    </select>\
                                </div>');
                $('.jp-speed').on('change', function (e) {
                    const speed = $(this).val();
					console.log('changed');
                    $('audio').each(function() {
						console.log('found');
						console.log(this);
                        this.playbackRate = parseFloat(speed);
                    });
                });
            });
        });
    ";

    // Add the inline script to the footer, after jQuery
    wp_add_inline_script('jquery', $custom_js);
}
add_action('wp_enqueue_scripts', 'add_custom_jquery_script');

