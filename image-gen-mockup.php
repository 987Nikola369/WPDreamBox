
<?php
/*
Plugin Name: Image Generation for Product Mockups
Plugin URI: http://example.com/plugin
Description: Generate images using Stable Diffusion and layer them onto product mockups.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPLv2 or later
Text Domain: image-gen-mockup
*/

// Activation hook
function image_gen_mockup_activate() {
    // Activation code here
}
register_activation_hook(__FILE__, 'image_gen_mockup_activate');

// Deactivation hook
function image_gen_mockup_deactivate() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'image_gen_mockup_deactivate');

// Enqueue JavaScript and CSS for the front-end
function image_gen_mockup_enqueue_scripts() {
    wp_enqueue_script('image-gen-mockup-js', plugins_url('/js/image-gen-mockup.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_enqueue_style('image-gen-mockup-css', plugins_url('/css/image-gen-mockup.css', __FILE__), array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'image_gen_mockup_enqueue_scripts');

// Shortcode for the UI
function image_gen_mockup_shortcode() {
    ob_start();
    ?>
    <!-- UI HTML here -->
    <?php
    return ob_get_clean();
}
add_shortcode('image_gen_mockup', 'image_gen_mockup_shortcode');
?>


// Add an action hook for admin menu to create a settings page in the dashboard
function image_gen_mockup_add_admin_menu() {
    add_options_page('Image Generation Settings', 'Image Gen Mockup', 'manage_options', 'image_gen_mockup', 'image_gen_mockup_options_page');
}
add_action('admin_menu', 'image_gen_mockup_add_admin_menu');

// Register plugin settings, a section, and a field for the API token
function image_gen_mockup_settings_init() {
    register_setting('imageGenMockup', 'image_gen_mockup_settings');

    add_settings_section(
        'image_gen_mockup_imageGenMockup_section', 
        __('Your section description', 'image-gen-mockup'), 
        'image_gen_mockup_settings_section_callback', 
        'imageGenMockup'
    );

    add_settings_field(
        'image_gen_mockup_text_field_0', 
        __('API Token', 'image-gen-mockup'), 
        'image_gen_mockup_text_field_0_render', 
        'imageGenMockup', 
        'image_gen_mockup_imageGenMockup_section'
    );
}
add_action('admin_init', 'image_gen_mockup_settings_init');

// Render the text field for the API token input
function image_gen_mockup_text_field_0_render() {
    $options = get_option('image_gen_mockup_settings');
    ?>
    <input type='text' name='image_gen_mockup_settings[image_gen_mockup_text_field_0]' value='<?= $options['image_gen_mockup_text_field_0']; ?>'>
    <?php
}

// Section callback function - for additional information or instructions
function image_gen_mockup_settings_section_callback() {
    echo __('Enter your API token for the Image Generation service.', 'image-gen-mockup');
}

// Options page rendering function
function image_gen_mockup_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Image Generation for Product Mockups</h2>
        <?php
        settings_fields('imageGenMockup');
        do_settings_sections('imageGenMockup');
        submit_button();
        ?>
    </form>
    <?php
}

// Function to send the prompt to the Replicate API using the new model and custom Lora file
function image_gen_mockup_generate_image($prompt) {
    $api_url = 'https://api.replicate.com/v1/predictions';
    
    // Retrieve the saved API token from the WordPress database
    $options = get_option('image_gen_mockup_settings');
    $api_token = $options['image_gen_mockup_text_field_0'];

    // cURL to make a POST request to the Replicate API with the custom model
    $ch = curl_init($api_url);
    $data = [
        'version' => 'https://replicate.com/zylim0702/sdxl-lora-customize-model',
        'input' => [
            'prompt' => $prompt,
            'lora' => 'https://replicate.delivery/pbxt/trawvUQ11EpXEJo0QC9R5HmH5AetoOYFcBSfYxLTAj8fxtUkA/trained_model.tar'
        ]
    ];
    $headers = [
        'Authorization: Token ' . $api_token,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        // Handle error, unable to complete the request
        return curl_error($ch);
    }
    curl_close($ch);
    
    // Decode the response and return the image data
    $decoded_response = json_decode($response, true);
    return $decoded_response['output']['url'] ?? null;
}

// Add a shortcode to handle image generation request via AJAX
function image_gen_mockup_shortcode() {
    ob_start();
    ?>
    <!-- UI HTML with input field and generate button here -->
    <div id="image-gen-mockup-container">
        <input type="text" id="image-gen-mockup-prompt" placeholder="Describe your design...">
        <button id="image-gen-mockup-generate-btn">Generate</button>
        <canvas id="image-gen-mockup-canvas"></canvas>
    </div>
    <script type="text/javascript">
        // JavaScript to handle the button click and make an AJAX request to this PHP function
        // This will be implemented in the image-gen-mockup.js file
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('image_gen_mockup', 'image_gen_mockup_shortcode');

// WordPress AJAX action for generating an image with the custom SDXL model
add_action('wp_ajax_generate_image_with_sdxl', 'handle_generate_image_with_sdxl');
function handle_generate_image_with_sdxl() {
    // Your code to handle the AJAX request, send the prompt to the SDXL model, and return the image URL
    // This will involve calling the image_gen_mockup_generate_image() function written earlier
}

// WordPress AJAX action for removing background with the rembg model
add_action('wp_ajax_remove_bg_with_rembg', 'handle_remove_bg_with_rembg');
function handle_remove_bg_with_rembg() {
    // Your code to handle the AJAX request, send the image to the rembg model, and return the processed image URL
}

// Handle AJAX request for generating image with the custom SDXL model
function handle_generate_image_with_sdxl() {
    // Get the prompt from the AJAX request
    $prompt = $_POST['prompt'];
    
    // Call the function that sends the prompt to the SDXL API
    $generated_image_url = image_gen_mockup_generate_image($prompt);
    
    // Return the URL of the generated image to the AJAX call
    echo json_encode(['generated_image_url' => $generated_image_url]);
    wp_die(); // Required to terminate immediately and return a proper response
}

// Handle AJAX request for removing background with the rembg model
function handle_remove_bg_with_rembg() {
    // Get the URL of the generated image from the AJAX request
    $image_url = $_POST['image_url'];
    
    // Call the function that sends the image URL to the rembg API
    $processed_image_url = remove_background_with_rembg($image_url);
    
    // Return the URL of the processed image to the AJAX call
    echo json_encode(['processed_image_url' => $processed_image_url]);
    wp_die(); // Required to terminate immediately and return a proper response
}

// Register the AJAX action handlers with WordPress
add_action('wp_ajax_generate_image_with_sdxl', 'handle_generate_image_with_sdxl');
add_action('wp_ajax_remove_bg_with_rembg', 'handle_remove_bg_with_rembg');

function image_gen_mockup_enqueue_scripts() {
    // Enqueue the newly written JavaScript for image processing
    wp_enqueue_script('image-processing-plugin-js', plugins_url('/js/image-processing-plugin.js', __FILE__), array('jquery'), null, true);
    // Assume that the CSS file is also enqueued here
}

add_action('wp_enqueue_scripts', 'image_gen_mockup_enqueue_scripts');
