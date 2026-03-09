<?php
/**
 * Plugin Name: Good Practices Map
 * Description: Displays a Google Map with pins based on the custom post type "good_practice" and its ACF field "town".
 * Version: 1.0
 * Author: FERI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register the shortcode
add_shortcode('good_practices_map', 'good_practices_map_shortcode');

function good_practices_map_shortcode() {
    // Enqueue Google Maps API and custom script
    add_action('wp_enqueue_scripts', 'enqueue_good_practices_map_scripts');

    // Generate the container for the map
    ob_start();
    ?>
    <div id="good-practices-map" style="width: 100%; height: 500px;"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initGoodPracticesMap === 'function') {
                initGoodPracticesMap();
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

function enqueue_good_practices_map_scripts() {
    // Enqueue custom script for handling the map
    wp_enqueue_script('good-practices-map', plugins_url('good-practices-map.js', __FILE__), ['jquery'], null, true);

    // Add Google Maps API with async and defer attributes
    $google_maps_api_key = '';


    wp_enqueue_script(
        'google-maps-api',
        'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&callback=initGoodPracticesMap',
        [],
        null,
        true
    );

    // Enqueue custom map script
    wp_enqueue_script('good-practices-map', plugins_url('good-practices-map.js', __FILE__), [], null, true);
      wp_enqueue_style('good-practices-map-css', plugins_url('good-practices-map.css', __FILE__));


    // Pass PHP data to JavaScript
    wp_localize_script('good-practices-map', 'GoodPracticesData', [
        'locations' => get_good_practices_locations()
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_good_practices_map_scripts');

function get_good_practices_locations() {
    $locations = [];

    // Query the "good_practice" custom post type
    $query = new WP_Query([
        'post_type' => 'good_practice',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    while ($query->have_posts()) {
        $query->the_post();

        // Get the ACF "town" field
        $location = get_field('town');
         if ($location && !empty($location['lat']) && !empty($location['lng'])) {
            $locations[] = [
                'title' => get_the_title(),
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'address' => $location['address'], // Include the address
                'content' => '<strong>' . esc_html(get_the_title()) . '</strong><br>' .
                    (!empty($location['address']) ? esc_html($location['address']) . '<br>' : '') .
                    '<a href="' . esc_url(get_the_permalink()) . '">View Details</a>'
            ];
        }
    }

    wp_reset_postdata();
    return $locations;
}

// Create the JavaScript file
add_action('wp_footer', 'add_good_practices_map_js');
function add_good_practices_map_js() {
    if (!did_action('wp_enqueue_scripts')) {
        return;
    }

    ?>
    <script>
      function initGoodPracticesMap() {
    const mapElement = document.getElementById('good-practices-map');
    if (!mapElement) {
        console.error('Map container not found!');
        return;
    }

    const locations = GoodPracticesData.locations;

    const map = new google.maps.Map(mapElement, {
        center: { lat: 0, lng: 0 }, // Default center
        zoom: 2
    });

    const bounds = new google.maps.LatLngBounds();

    locations.forEach((location) => {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(location.lat), lng: parseFloat(location.lng) },
            map: map,
            title: location.title
        });

        const infoWindow = new google.maps.InfoWindow({
            content: location.content
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        bounds.extend(marker.getPosition());
    });

    if (locations.length > 0) {
        map.fitBounds(bounds);
    }
}

    </script>
    <?php
}

