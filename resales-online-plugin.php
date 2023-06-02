<?php
/*
Plugin Name: Resales Online Plugin
Description: Fetches data from Resales Online API and stores it as custom post types.
Author: Mansoor khan
Version: 1.0
BY: Facebook.com/mansoorkhan95
*/



function create_properties_post_type() {
    $labels = array(
        'name' => 'Properties',
        'singular_name' => 'Property',
        'menu_name' => 'Properties',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Property',
        'edit_item' => 'Edit Property',
        'new_item' => 'New Property',
        'view_item' => 'View Property',
        'view_items' => 'View Properties',
        'search_items' => 'Search Properties',
        'not_found' => 'No properties found',
        'not_found_in_trash' => 'No properties found in trash',
        'all_items' => 'All Properties',
        'archives' => 'Property Archives',
        'attributes' => 'Property Attributes',
        'insert_into_item' => 'Insert into property',
        'uploaded_to_this_item' => 'Uploaded to this property',
        'featured_image' => 'Featured Image',
        'set_featured_image' => 'Set featured image',
        'remove_featured_image' => 'Remove featured image',
        'use_featured_image' => 'Use as featured image',
        'filter_items_list' => 'Filter properties list',
        'items_list_navigation' => 'Properties list navigation',
        'items_list' => 'Properties list',
        'item_published' => 'Property published.',
        'item_published_privately' => 'Property published privately.',
        'item_reverted_to_draft' => 'Property reverted to draft.',
        'item_scheduled' => 'Property scheduled.',
        'item_updated' => 'Property updated.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    );

    register_post_type('property', $args);
}
add_action('init', 'create_properties_post_type');


function fetch_properties_from_api() {
    $api_endpoint = 'https://webapi.resales-online.com/V6/SearchProperties?p1=1014899&p2=a44a5e577f200bc9ca42a133d467b537f95bf174&P_sandbox=true&P_ApiId=38245&P_PageSize=40';

    $response = wp_remote_get($api_endpoint);

    if (!is_wp_error($response)) {
        $xml = wp_remote_retrieve_body($response);
        $properties = simplexml_load_string($xml);
        $fetched_count = 0;

        foreach ($properties->property as $property) {
            $property_reference = (string) $property->Reference;

            // Check if property with the same reference already exists
            $existing_property = get_page_by_title($property_reference, OBJECT, 'property');

            if ($existing_property) {
                // Property already exists, update the existing property
                $property_post = $existing_property->ID;

                // Update all post meta fields
                foreach ($property as $key => $value) {
                    update_post_meta($property_post, $key, (string) $value);
                }

                // Update property features meta field
                $property_features = $property->PropertyFeatures;
                if ($property_features) {
                    update_post_meta($property_post, 'PropertyFeatures', $property_features->asXML());
                }

                // Update image URLs
                $image_urls = array();
                $pictures = $property->Pictures;
                foreach ($pictures->Picture as $picture) {
                    $image_urls[] = (string) $picture->PictureURL;
                }
                update_post_meta($property_post, 'image_urls', $image_urls);

                $fetched_count++;
            } else {
                // Property doesn't exist, create a new post
                $post_title = $property_reference;
                $property_data = array(
                    'post_title' => $post_title,
                    'post_status' => 'publish',
                    'post_type' => 'property',
                );

                $property_post = wp_insert_post($property_data);

                // Define the property meta fields and their values
                $property_meta = array(
                    'Reference' => (string) $property->Reference,
                'AgencyRef' => (string) $property->AgencyRef,
                'Country' => (string) $property->Country,
                'Province' => (string) $property->Province,
                'Area' => (string) $property->Area,
                'Location' => (string) $property->Location,
                'Bedrooms' => (string) $property->Bedrooms,
                'Bathrooms' => (string) $property->Bathrooms,
                'Currency' => (string) $property->Currency,
                'Price' => (string) $property->Price,
                'OriginalPrice' => (string) $property->OriginalPrice,
                'Dimensions' => (string) $property->Dimensions,
                'Built' => (string) $property->Built,
                'Terrace' => (string) $property->Terrace,
                'GardenPlot' => (string) $property->GardenPlot,
                'CO2Rated' => (string) $property->CO2Rated,
                'EnergyRated' => (string) $property->EnergyRated,
                'OwnProperty' => (string) $property->OwnProperty,
                'Pool' => (string) $property->Pool,
                'Parking' => (string) $property->Parking,
                'Garden' => (string) $property->Garden,
                'Description' => (string) $property->Description,
                'PicturesCount' => (string) $property->Pictures['Count'],
                'Type' => (string) $property->PropertyType->Type,
                'NameType' => (string) $property->PropertyType->NameType,
                'Subtype1' => (string) $property->PropertyType->Subtype1,
                    'PropertyFeatures' => $property->PropertyFeatures->asXML(),
                );

                // Save the property meta fields
                foreach ($property_meta as $key => $value) {
                    update_post_meta($property_post, $key, $value);
                }

                // Save image URLs
                $image_urls = array();
                $pictures = $property->Pictures;
                foreach ($pictures->Picture as $picture) {
                    $image_urls[] = (string) $picture->PictureURL;
                }
                update_post_meta($property_post, 'image_urls', $image_urls);

                $fetched_count++;
            }
        }

        echo '<p>' . $fetched_count . ' properties fetched and updated successfully!</p>';
    }
}






// Step 2: Add fetch and delete buttons to the custom post type menu
add_action('admin_menu', 'add_fetch_and_delete_buttons_to_menu');
function add_fetch_and_delete_buttons_to_menu() {
    add_submenu_page(
        'edit.php?post_type=property', // Parent menu slug
        'Fetch Properties', // Page title
        'Fetch Properties', // Menu title
        'manage_options', // Capability required to access the page
        'fetch-properties', // Unique menu slug
        'render_fetch_properties_page' // Callback function to render the page content
    );
}

// Step 3: Render the fetch and delete buttons
function render_fetch_properties_page() {
    if (isset($_POST['delete-all-properties'])) {
        delete_all_properties();
        echo '<div class="notice notice-success"><p>All properties deleted successfully!</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Fetch Properties</h1>
        <button id="fetch-button">Fetch Properties</button>
        <button id="delete-all-button" onclick="confirmDeleteAll()">Delete All Properties</button>
        <div id="fetch-message"></div>
        <script>
            document.getElementById('fetch-button').addEventListener('click', function () {
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            document.getElementById('fetch-message').innerHTML = xhr.responseText;
                        } else {
                            document.getElementById('fetch-message').innerHTML = 'Error fetching properties. Please try again.';
                        }
                    }
                };
                xhr.open('GET', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=fetch_properties');
                xhr.send();
            });

            function confirmDeleteAll() {
                var confirmDelete = confirm("Are you sure you want to delete all properties?");
                if (confirmDelete) {
                    document.getElementById('delete-all-form').submit();
                }
            }
        </script>
    </div>
    <form id="delete-all-form" method="POST">
        <input type="hidden" name="delete-all-properties" value="1">
    </form>
    <?php
}

// Step 4: Create an AJAX callback for the fetch action
add_action('wp_ajax_fetch_properties', 'fetch_properties_ajax_callback');
add_action('wp_ajax_nopriv_fetch_properties', 'fetch_properties_ajax_callback');
function fetch_properties_ajax_callback() {
    fetch_properties_from_api();
    wp_die(); // Terminate the script after AJAX response
}

// Step 5: Delete all properties function
function delete_all_properties() {
    $args = array(
        'post_type' => 'property',
        'posts_per_page' => -1,
    );

    $properties = get_posts($args);

    foreach ($properties as $property) {
        wp_delete_post($property->ID, true);
    }
}



function enqueue_custom_styles() {
    wp_enqueue_style('lightslider', 'https://cdn.jsdelivr.net/npm/lightslider@1.1.6/dist/css/lightslider.min.css');
    wp_enqueue_style('custom-slider', plugin_dir_url(__FILE__) . 'css/custom-slider.css');
    wp_enqueue_script('lightslider', 'https://cdn.jsdelivr.net/npm/lightslider@1.1.6/dist/js/lightslider.min.js', array('jquery'));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');
require_once plugin_dir_path(__FILE__) . 'property-shortcodes.php';

function property_title_shortcode($atts) {
    // Get the property reference from the shortcode attribute, if specified
    $property_reference = isset($atts['reference']) ? sanitize_text_field($atts['reference']) : '';

    // If reference is not specified, try to get it from the current property page
    if (empty($property_reference) && is_singular('property')) {
        $property_reference = get_post_meta(get_the_ID(), 'Reference', true);
    }

    // Check if the property reference is available
    if (!empty($property_reference)) {
        // Retrieve the property data based on the reference
        $args = array(
            'post_type' => 'property',
            'meta_key' => 'Reference',
            'meta_value' => $property_reference,
            'posts_per_page' => 1,
        );

        $property_query = new WP_Query($args);

        if ($property_query->have_posts()) {
            $property_query->the_post();

            // Get the property data from post meta
            $reference = get_post_meta(get_the_ID(), 'Reference', true);
            $subtype1 = get_post_meta(get_the_ID(), 'Subtype1', true);
            $location = get_post_meta(get_the_ID(), 'Location', true);
            $price = get_post_meta(get_the_ID(), 'Price', true);

            // Build the customized title
            $title = $subtype1 . ' for sale in ' . $location ;

            wp_reset_postdata();

            return $title;
        }
    }

    return ''; // Return an empty string if the property data is missing or reference is not found
}

add_shortcode('property_title', 'property_title_shortcode');


function property_features_shortcode($atts) {
    $property_id = get_the_ID();
    $property_features = get_post_meta($property_id, 'PropertyFeatures', true);

    $output = '';

    if ($property_features) {
        $property_features = simplexml_load_string($property_features);

        $categories = $property_features->Category;

        if ($categories) {
            $output .= '<table class="property-features">';
            $output .= '<tbody>';

            foreach ($categories as $category) {
                $category_type = (string) $category['Type'];
                $output .= '<tr>';
                $output .= '<th>' . ucfirst($category_type) . '</th>';
                $output .= '<td>';

                foreach ($category->Value as $value) {
                    $output .= '<span>' . (string) $value . '</span>';
                }

                $output .= '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
        }
    }

    return $output;
}

add_shortcode('property_features', 'property_features_shortcode');
