
jQuery(document).ready(function($) {
    $('#image-gen-mockup-generate-btn').click(function() {
        var prompt = $('#image-gen-mockup-prompt').val();
        var data = {
            'action': 'generate_image_with_sdxl',
            'prompt': prompt
        };

        // First, send the prompt to the custom SDXL model to generate the image
        $.post(ajaxurl, data, function(response) {
            // Once the image is generated, send it to the rembg model to remove the background
            var rembgData = {
                'action': 'remove_bg_with_rembg',
                'image_url': response.generated_image_url
            };

            $.post(ajaxurl, rembgData, function(rembgResponse) {
                // Display the processed image on top of the hoodie mockup
                var canvas = document.getElementById('image-gen-mockup-canvas');
                var ctx = canvas.getContext('2d');
                var hoodieMockup = new Image();
                var generatedImage = new Image();

                hoodieMockup.onload = function() {
                    canvas.width = hoodieMockup.width;
                    canvas.height = hoodieMockup.height;
                    ctx.drawImage(hoodieMockup, 0, 0, hoodieMockup.width, hoodieMockup.height);

                    generatedImage.onload = function() {
                        // Adjust these values as needed to position and scale the generated image on the mockup
                        var offsetX = (canvas.width - generatedImage.width) / 2;
                        var offsetY = (canvas.height - generatedImage.height) / 2;
                        ctx.drawImage(generatedImage, offsetX, offsetY, generatedImage.width, generatedImage.height);
                    };

                    // Update the generatedImage source with the URL from the rembg model response
                    generatedImage.src = rembgResponse.processed_image_url;
                };

                // Update the hoodieMockup source to the path of the transparent hoodie mockup PNG
                hoodieMockup.src = 'path_to_hoodie_mockup_png';
            });
        });
    });
});
