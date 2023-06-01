<?php
function property_images_shortcode($atts) {
    $property_image_urls = get_post_meta(get_the_ID(), 'image_urls', true);

    if (!empty($property_image_urls)) {
        ob_start();
        include(plugin_dir_path(__FILE__) . 'property-slider.php');
        return ob_get_clean();
    }
}
add_shortcode('property_images', 'property_images_shortcode');

?>