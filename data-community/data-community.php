<?php
/**
 * Plugin Name:       Data Community
 * Plugin URI:        https://example.com/plugins/data-community/
 * Description:       Manages community data items comparing key vs baseline JSON data, calculating differences, and displaying data as a Highcharts chart via shortcode.
 * Version:           1.3.0
 * Author:            Jure Trilar
 * Author URI:        https://smart-alps.eu/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       data-community
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'DC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DC_VERSION', '1.3.0' ); // Incremented version

/**
 * Register the "Data Community Item" Custom Post Type.
 * (No changes needed here from v1.2.0)
 */
function dc_register_post_type() {
    $labels = array(
        'name'                  => _x( 'Community Items', 'Post type general name', 'data-community' ),
        'singular_name'         => _x( 'Community Item', 'Post type singular name', 'data-community' ),
        'menu_name'             => _x( 'Data Community', 'Admin Menu text', 'data-community' ),
        'name_admin_bar'        => _x( 'Community Item', 'Add New on Toolbar', 'data-community' ),
        'add_new'               => __( 'Add New Item', 'data-community' ),
        'add_new_item'          => __( 'Add New Community Item', 'data-community' ),
        'new_item'              => __( 'New Community Item', 'data-community' ),
        'edit_item'             => __( 'Edit Community Item', 'data-community' ),
        'view_item'             => __( 'View Community Item', 'data-community' ),
        'all_items'             => __( 'All Community Items', 'data-community' ),
        'search_items'          => __( 'Search Community Items', 'data-community' ),
        'parent_item_colon'     => __( 'Parent Community Items:', 'data-community' ),
        'not_found'             => __( 'No community items found.', 'data-community' ),
        'not_found_in_trash'    => __( 'No community items found in Trash.', 'data-community' ),
        'featured_image'        => _x( 'Community Item Cover Image', 'Overrides the "Featured Image" phrase for this post type.', 'data-community' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase for this post type.', 'data-community' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase for this post type.', 'data-community' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase for this post type.', 'data-community' ),
        'archives'              => _x( 'Community Item archives', 'The post type archive label used in nav menus.', 'data-community' ),
        'insert_into_item'      => _x( 'Insert into community item', 'Overrides the "Insert into post"/"Insert into page" phrase.', 'data-community' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this community item', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase.', 'data-community' ),
        'filter_items_list'     => _x( 'Filter community items list', 'Screen reader text for the filter links.', 'data-community' ),
        'items_list_navigation' => _x( 'Community Items list navigation', 'Screen reader text for the pagination.', 'data-community' ),
        'items_list'            => _x( 'Community Items list', 'Screen reader text for the items list.', 'data-community' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'community-item' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-analytics', // Changed icon for relevance
        'supports'           => array( 'title', 'editor', 'revisions' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'dc_item', $args );
}
add_action( 'init', 'dc_register_post_type' );

/**
 * Flush rewrite rules on plugin activation/deactivation.
 * (No changes needed here)
 */
function dc_rewrite_flush() {
    dc_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dc_rewrite_flush' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );


/**
 * Enqueue admin scripts and styles for the meta box.
 */
function dc_admin_enqueue_scripts( $hook ) {
    global $post_type;
    // Only load on the edit screen for our CPT
    if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && 'dc_item' === $post_type ) {
        // Enqueue WP media uploader script
        wp_enqueue_media();

        // Enqueue our custom admin script
        wp_enqueue_script(
            'dc-admin-script',
            DC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-mediaelement' ), // Depends on jQuery and media uploader
            DC_VERSION,
            true // Load in footer
        );

        // Enqueue admin CSS for hiding/showing fields
        wp_enqueue_style(
            'dc-admin-style',
            DC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DC_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'dc_admin_enqueue_scripts' );


/**
 * Add Meta Boxes for Community Item Details.
 * (No changes needed here)
 */
function dc_add_meta_boxes() {
    add_meta_box(
        'dc_item_details',                  // Unique ID
        __( 'Community Item Details & Data', 'data-community' ), // Box title
        'dc_render_meta_box_content',       // Content callback function
        'dc_item',                          // Post type SLUG
        'normal',                           // Context
        'high'                              // Priority
    );
}
add_action( 'add_meta_boxes_dc_item', 'dc_add_meta_boxes' );

/**
 * Render Meta Box Content.
 *
 * @param WP_Post $post The post object.
 */
function dc_render_meta_box_content( $post ) {
    // Add a nonce field.
    wp_nonce_field( 'dc_save_meta_box_data', 'dc_meta_box_nonce' );

    // Retrieve existing values.
    $key_json_url = get_post_meta( $post->ID, '_dc_key_json_url', true );
    $baseline_json_url = get_post_meta( $post->ID, '_dc_baseline_json_url', true );
    $cumulative_diff = get_post_meta( $post->ID, '_dc_cumulative_difference', true );

    // --- Criteria Data ---
    $criteria = ['social', 'economic', 'environmental'];
    $criteria_data = [];
    foreach ($criteria as $crit) {
        $criteria_data[$crit] = [
            'source_type' => get_post_meta($post->ID, '_dc_' . $crit . '_source_type', true) ?: 'static', // Default to static
            'static_value' => get_post_meta($post->ID, '_dc_' . $crit . '_static_value', true),
            'factor_value' => get_post_meta($post->ID, '_dc_' . $crit . '_factor_value', true),
            'unit' => get_post_meta($post->ID, '_dc_' . $crit . '_criteria_unit', true), // Keep old key for unit
            'comment' => get_post_meta($post->ID, '_dc_' . $crit . '_criteria_comment', true) // Keep old key for comment
        ];
    }
    ?>
    <style>
        /* Simple styling for meta box layout */
        .dc-meta-section { margin-bottom: 25px; border-bottom: 1px solid #ddd; padding-bottom: 20px; }
        .dc-meta-field-group { margin-bottom: 15px; padding-left: 10px; }
        .dc-meta-field-group label,
        .dc-meta-field-group .radio-label { display: block; font-weight: bold; margin-bottom: 5px; }
        .dc-meta-field-group input[type="text"],
        .dc-meta-field-group input[type="url"],
        .dc-meta-field-group input[type="number"], /* Added number type for factor */
        .dc-meta-field-group textarea { width: 98%; margin-bottom: 5px; }
        .dc-meta-field-group em { color: #777; font-size: 0.9em; display: block; margin-top: -3px; margin-bottom: 5px; }
        .dc-meta-field-group .unit-input { width: 30%; display: inline-block; margin-right: 2%; vertical-align: top; }
        .dc-meta-field-group .value-input { width: 66%; display: inline-block; vertical-align: top; }
        .dc-meta-field-group .radio-options label { font-weight: normal; margin-right: 15px; }
        .dc-meta-field-group .radio-options input[type="radio"] { margin-right: 5px; }
        .dc-meta-field-group .value-source-inputs { margin-top: 10px; border-left: 3px solid #eee; padding-left: 10px; }
        .dc-meta-field-group .value-source-inputs .input-wrapper { margin-bottom: 8px; }

        /* Styles for Upload Buttons (can be enhanced) */
        .dc-upload-button { margin-left: 10px; vertical-align: middle; }
        .dc-url-input-group { display: flex; align-items: center; }
        .dc-url-input-group input[type="url"] { flex-grow: 1; margin-right: 5px; }

        /* Hiding/Showing fields based on admin.css */
        .dc-hidden-field { display: none; }
    </style>

    <div class="dc-meta-section">
        <h4><?php _e( 'Data Sources', 'data-community' ); ?></h4>
        <div class="dc-meta-field-group">
            <label for="dc_key_json_url"><?php _e( 'Key Data JSON URL:', 'data-community' ); ?></label>
            <div class="dc-url-input-group">
                 <input type="url" id="dc_key_json_url" name="dc_key_json_url" value="<?php echo esc_url( $key_json_url ); ?>" size="50" placeholder="https://example.com/path/to/key-data.json" class="dc-url-input"/>
                 <button type="button" class="button dc-upload-button" data-target-input="#dc_key_json_url"><?php _e('Upload', 'data-community'); ?></button>
            </div>
             <em><?php _e( 'Enter the URL or upload the primary JSON dataset.', 'data-community' ); ?></em>
        </div>

         <div class="dc-meta-field-group">
            <label for="dc_baseline_json_url"><?php _e( 'Baseline Data JSON URL:', 'data-community' ); ?></label>
             <div class="dc-url-input-group">
                <input type="url" id="dc_baseline_json_url" name="dc_baseline_json_url" value="<?php echo esc_url( $baseline_json_url ); ?>" size="50" placeholder="https://example.com/path/to/baseline-data.json" class="dc-url-input"/>
                <button type="button" class="button dc-upload-button" data-target-input="#dc_baseline_json_url"><?php _e('Upload', 'data-community'); ?></button>
             </div>
             <em><?php _e( 'Enter the URL or upload the comparison JSON dataset. Timestamps should align with Key Data.', 'data-community' ); ?></em>
        </div>
         <div class="dc-meta-field-group">
             <label><?php _e('Calculated Cumulative Difference:', 'data-community'); ?></label>
             <p>
                 <?php
                 if (is_numeric($cumulative_diff)) {
                     echo '<strong>' . esc_html(number_format_i18n($cumulative_diff, 2)) . '</strong>'; // Format number
                 } elseif ($cumulative_diff === 'error' || $cumulative_diff === 'mismatch' || $cumulative_diff === 'nodata') {
                      echo '<em>' . esc_html__('Could not calculate (check URLs and data format).', 'data-community') . '</em>';
                 } else {
                      echo '<em>' . esc_html__('Save post to calculate.', 'data-community') . '</em>';
                 }
                 ?>
             </p>
              <em><?php _e( 'This value (Key Data Sum - Baseline Data Sum) is calculated when you save the post. Criteria below can use this value.', 'data-community' ); ?></em>
         </div>
    </div>

    <div class="dc-meta-section">
        <h4><?php _e( 'Benefits Criteria:', 'data-community' ); ?></h4>
        <?php
        $crit_labels = [
            'social' => __('Social', 'data-community'),
            'economic' => __('Economic', 'data-community'),
            'environmental' => __('Environmental', 'data-community')
        ];

        foreach ($criteria as $crit):
            $data = $criteria_data[$crit];
        ?>
        <div class="dc-meta-field-group dc-criterion-group" data-criterion="<?php echo esc_attr($crit); ?>">
             <label class="criterion-main-label"><?php echo esc_html($crit_labels[$crit]); ?> <?php _e('Criteria:', 'data-community'); ?></label>

            <div class="radio-options dc-source-type-selector">
                 <span class="radio-label"><?php _e('Value Source:', 'data-community'); ?></span>
                 <label>
                     <input type="radio" name="dc_<?php echo esc_attr($crit); ?>_source_type" value="static" <?php checked($data['source_type'], 'static'); ?>>
                     <?php _e('Static Value', 'data-community'); ?>
                 </label>
                 <label>
                     <input type="radio" name="dc_<?php echo esc_attr($crit); ?>_source_type" value="diff" <?php checked($data['source_type'], 'diff'); ?>>
                     <?php _e('Cumulative Difference', 'data-community'); ?>
                 </label>
                 <label>
                     <input type="radio" name="dc_<?php echo esc_attr($crit); ?>_source_type" value="factor" <?php checked($data['source_type'], 'factor'); ?>>
                     <?php _e('Factor x Difference', 'data-community'); ?>
                 </label>
            </div>

            <div class="value-source-inputs">
                 <div class="input-wrapper dc-static-value-field <?php echo ($data['source_type'] !== 'static') ? 'dc-hidden-field' : ''; ?>">
                     <label for="dc_<?php echo esc_attr($crit); ?>_static_value"><?php _e( 'Static Value:', 'data-community' ); ?></label>
                     <input type="text" id="dc_<?php echo esc_attr($crit); ?>_static_value" name="dc_<?php echo esc_attr($crit); ?>_static_value" value="<?php echo esc_attr( $data['static_value'] ); ?>" placeholder="e.g., 85 or 'High Impact'"/>
                 </div>
                 <div class="input-wrapper dc-factor-value-field <?php echo ($data['source_type'] !== 'factor') ? 'dc-hidden-field' : ''; ?>">
                      <label for="dc_<?php echo esc_attr($crit); ?>_factor_value"><?php _e( 'Factor:', 'data-community' ); ?></label>
                      <input type="number" step="any" id="dc_<?php echo esc_attr($crit); ?>_factor_value" name="dc_<?php echo esc_attr($crit); ?>_factor_value" value="<?php echo esc_attr( $data['factor_value'] ); ?>" placeholder="e.g., 1.5 or 0.8"/>
                 </div>
                  <div class="input-wrapper dc-diff-display-field <?php echo ($data['source_type'] !== 'diff') ? 'dc-hidden-field' : ''; ?>">
                     <label><?php _e('Using Calculated Difference:', 'data-community'); ?></label>
                      <em><?php _e('The calculated cumulative difference will be used directly.', 'data-community'); ?></em>
                 </div>
            </div>

             <div class="value-input"> <label for="dc_<?php echo esc_attr($crit); ?>_criteria_unit" style="font-weight: normal;"><?php _e( 'Unit:', 'data-community' ); ?></label>
                <input type="text" id="dc_<?php echo esc_attr($crit); ?>_criteria_unit" name="dc_<?php echo esc_attr($crit); ?>_criteria_unit" value="<?php echo esc_attr( $data['unit'] ); ?>" placeholder="e.g., /100 or Index"/>
            </div>
             <div class="comment-input">
                <label for="dc_<?php echo esc_attr($crit); ?>_criteria_comment"><?php _e( 'Comment:', 'data-community' ); ?></label>
                <textarea id="dc_<?php echo esc_attr($crit); ?>_criteria_comment" name="dc_<?php echo esc_attr($crit); ?>_criteria_comment" rows="2"><?php echo esc_textarea( $data['comment'] ); ?></textarea>
                <em><?php _e( 'Optional: Add context or notes.', 'data-community' ); ?></em>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Save Meta Box Data.
 * Calculates cumulative difference if possible.
 *
 * @param int $post_id The post ID.
 */
function dc_save_meta_box_data( $post_id ) {

    // Check nonce
    if ( ! isset( $_POST['dc_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['dc_meta_box_nonce'], 'dc_save_meta_box_data' ) ) {
        return;
    }

    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check permissions
    if ( ! isset( $_POST['post_type'] ) || 'dc_item' !== $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    /* OK, it's safe for us to save the data now. */

    // --- Save URLs ---
    $key_url = isset( $_POST['dc_key_json_url'] ) ? esc_url_raw( $_POST['dc_key_json_url'] ) : '';
    $baseline_url = isset( $_POST['dc_baseline_json_url'] ) ? esc_url_raw( $_POST['dc_baseline_json_url'] ) : '';
    update_post_meta( $post_id, '_dc_key_json_url', $key_url );
    update_post_meta( $post_id, '_dc_baseline_json_url', $baseline_url );


    // --- Save Criteria Data ---
    $criteria = ['social', 'economic', 'environmental'];
    $valid_source_types = ['static', 'diff', 'factor'];

    foreach ($criteria as $crit) {
        // Source Type
        $source_type = isset( $_POST['dc_' . $crit . '_source_type'] ) ? sanitize_key( $_POST['dc_' . $crit . '_source_type'] ) : 'static';
        if ( ! in_array( $source_type, $valid_source_types ) ) {
            $source_type = 'static'; // Default to static if invalid
        }
        update_post_meta( $post_id, '_dc_' . $crit . '_source_type', $source_type );

        // Static Value
        if ( isset( $_POST['dc_' . $crit . '_static_value'] ) ) {
            update_post_meta( $post_id, '_dc_' . $crit . '_static_value', sanitize_text_field( $_POST['dc_' . $crit . '_static_value'] ) );
        } else {
             delete_post_meta( $post_id, '_dc_' . $crit . '_static_value' );
        }

         // Factor Value (sanitize as float)
        if ( isset( $_POST['dc_' . $crit . '_factor_value'] ) ) {
            $factor = filter_var( $_POST['dc_' . $crit . '_factor_value'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
            update_post_meta( $post_id, '_dc_' . $crit . '_factor_value', $factor );
        } else {
             delete_post_meta( $post_id, '_dc_' . $crit . '_factor_value' );
        }

        // Unit
        if ( isset( $_POST['dc_' . $crit . '_criteria_unit'] ) ) {
            update_post_meta( $post_id, '_dc_' . $crit . '_criteria_unit', sanitize_text_field( $_POST['dc_' . $crit . '_criteria_unit'] ) );
        } else {
            delete_post_meta( $post_id, '_dc_' . $crit . '_criteria_unit' );
        }

         // Comment
        if ( isset( $_POST['dc_' . $crit . '_criteria_comment'] ) ) {
            update_post_meta( $post_id, '_dc_' . $crit . '_criteria_comment', sanitize_textarea_field( $_POST['dc_' . $crit . '_criteria_comment'] ) );
        } else {
             delete_post_meta( $post_id, '_dc_' . $crit . '_criteria_comment' );
        }
    }

    // --- Calculate and Save Cumulative Difference ---
    if ( ! empty($key_url) && ! empty($baseline_url) ) {
        $calculation_result = dc_calculate_cumulative_difference($key_url, $baseline_url);

        if ( is_numeric($calculation_result) ) {
            update_post_meta( $post_id, '_dc_cumulative_difference', $calculation_result );
        } else {
            // Store the error string ('error', 'mismatch', 'nodata') or just delete if calculation failed
            update_post_meta( $post_id, '_dc_cumulative_difference', $calculation_result ?: 'error' );
            // Optional: Log detailed error somewhere if needed
             // error_log("Data Community Calc Error (Post $post_id): $calculation_result");
        }
    } else {
        // If URLs are missing, remove the stored difference
        delete_post_meta( $post_id, '_dc_cumulative_difference' );
    }
}
add_action( 'save_post_dc_item', 'dc_save_meta_box_data' );


/**
 * Helper function to fetch and parse JSON data from a URL.
 * Includes basic validation and identifies time/value keys.
 *
 * @param string $url The URL to fetch.
 * @param int $post_id For transient key generation.
 * @return array ['data' => array|null, 'error' => string|null, 'time_key' => string|null, 'value_key' => string|null, 'y_axis_label' => string|null]
 */
function dc_fetch_and_parse_json($url, $post_id) {
    $transient_key = 'dc_data_' . $post_id . '_' . md5($url);
    $cached_data = get_transient($transient_key);
    $default_y_label = __('Value', 'data-community');

    $result = [
        'data' => null,
        'error' => null,
        'time_key' => null,
        'value_key' => null,
        'y_axis_label' => $default_y_label
    ];

    if (false !== $cached_data) {
        if (isset($cached_data['error'])) {
            $result['error'] = $cached_data['error'];
        } else {
            $result['data'] = $cached_data['data'] ?? null;
            $result['time_key'] = $cached_data['time_key'] ?? null;
            $result['value_key'] = $cached_data['value_key'] ?? null;
            $result['y_axis_label'] = $cached_data['y_axis_label'] ?? $default_y_label;
        }
        return $result;
    }

    $response = wp_remote_get(esc_url_raw($url), array('timeout' => 15));

    if (is_wp_error($response)) {
        $result['error'] = sprintf(esc_html__('Error fetching data: %s', 'data-community'), esc_html($response->get_error_message()));
        set_transient($transient_key, ['error' => $result['error']], 15 * MINUTE_IN_SECONDS);
        return $result;
    }

    $body = wp_remote_retrieve_body($response);
    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code !== 200) {
        $result['error'] = sprintf(esc_html__('Error: Received HTTP status %d when fetching data from %s', 'data-community'), intval($status_code), esc_url($url));
        set_transient($transient_key, ['error' => $result['error']], 15 * MINUTE_IN_SECONDS);
        return $result;
    }

    $decoded_data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_data)) {
        $result['error'] = sprintf(esc_html__('Error decoding JSON data or data is not an array from %s. Error: %s', 'data-community'), esc_url($url), esc_html(json_last_error_msg()));
        set_transient($transient_key, ['error' => $result['error']], 15 * MINUTE_IN_SECONDS);
        return $result;
    }

    if (empty($decoded_data)) {
         $result['error'] = esc_html__('JSON data source is empty.', 'data-community');
         set_transient($transient_key, ['error' => $result['error']], HOUR_IN_SECONDS); // Cache empty result longer
         return $result;
    }

    // --- Identify Keys from the first valid item ---
    foreach ($decoded_data as $item) {
         if (is_array($item)) {
            // Time keys
            if (isset($item['time'])) $result['time_key'] = 'time';
            elseif (isset($item['date'])) $result['time_key'] = 'date';
            elseif (isset($item['timestamp'])) $result['time_key'] = 'timestamp';

            // Value keys and potential Y-axis label
            if (isset($item['value']) && is_numeric($item['value'])) {
                 $result['value_key'] = 'value';
                 $result['y_axis_label'] = __('Value', 'data-community');
            } elseif (isset($item['usage']) && is_numeric($item['usage'])) {
                 $result['value_key'] = 'usage';
                 $result['y_axis_label'] = __('Usage', 'data-community');
            } elseif (isset($item['cumulative_energy_usage_kwh']) && is_numeric($item['cumulative_energy_usage_kwh'])) {
                 $result['value_key'] = 'cumulative_energy_usage_kwh';
                 $result['y_axis_label'] = __('Cumulative Energy (kWh)', 'data-community');
            } elseif (isset($item['daily_energy_usage_kwh']) && is_numeric($item['daily_energy_usage_kwh'])) {
                 $result['value_key'] = 'daily_energy_usage_kwh';
                 $result['y_axis_label'] = __('Daily Energy (kWh)', 'data-community');
            }
             // Add more potential value keys here if needed

            // If both keys found, stop searching
            if ($result['time_key'] && $result['value_key']) {
                 break;
            }
         }
    }

     if (!$result['time_key'] || !$result['value_key']) {
         $result['error'] = esc_html__('Could not automatically identify compatible time and value keys in the JSON data.', 'data-community');
         // Don't cache this specific error long, maybe the data changes
         set_transient($transient_key, ['error' => $result['error']], 5 * MINUTE_IN_SECONDS);
         return $result;
     }


    // --- Data seems okay, store and cache ---
    $result['data'] = $decoded_data;
    set_transient($transient_key, $result, HOUR_IN_SECONDS); // Cache successful fetch

    return $result;
}

/**
 * Calculate cumulative difference between two datasets.
 * Assumes timestamps align.
 *
 * @param string $key_url
 * @param string $baseline_url
 * @return float|string Returns the cumulative difference, or an error string ('error', 'mismatch', 'nodata').
 */
function dc_calculate_cumulative_difference($key_url, $baseline_url) {
    // Use a dummy post_id for transient generation in this context as it's not tied to a specific post display
    $calc_transient_id = 0;
    $key_data_result = dc_fetch_and_parse_json($key_url, $calc_transient_id);
    $baseline_data_result = dc_fetch_and_parse_json($baseline_url, $calc_transient_id);

    // Check for fetch errors
    if ($key_data_result['error'] || $baseline_data_result['error']) {
        // Combine errors? For now, just signal a general error.
        return 'error'; // Indicates fetch/parse error on one or both
    }

    // Check if keys were identified
    if (!$key_data_result['time_key'] || !$key_data_result['value_key'] || !$baseline_data_result['time_key'] || !$baseline_data_result['value_key']) {
         return 'error'; // Key identification failed
    }

    // Check if data is present
    if (empty($key_data_result['data']) || empty($baseline_data_result['data'])) {
        return 'nodata'; // One or both datasets are empty
    }

    $key_data = $key_data_result['data'];
    $baseline_data = $baseline_data_result['data'];
    $tk_key = $key_data_result['time_key']; // time key for key data
    $vk_key = $key_data_result['value_key']; // value key for key data
    $tk_base = $baseline_data_result['time_key']; // time key for baseline
    $vk_base = $baseline_data_result['value_key']; // value key for baseline

    // --- Process data for calculation: Create lookup maps ---
    // We need to handle potential misaligned or missing timestamps robustly.
    // Create maps: timestamp => value
    $key_map = [];
    foreach ($key_data as $item) {
        if (isset($item[$tk_key]) && isset($item[$vk_key]) && is_numeric($item[$vk_key])) {
             $timestamp_s = strtotime($item[$tk_key]);
             if ($timestamp_s !== false) {
                 $key_map[$timestamp_s] = floatval($item[$vk_key]);
             }
        }
    }

    $baseline_map = [];
     foreach ($baseline_data as $item) {
        if (isset($item[$tk_base]) && isset($item[$vk_base]) && is_numeric($item[$vk_base])) {
             $timestamp_s = strtotime($item[$tk_base]);
             if ($timestamp_s !== false) {
                 $baseline_map[$timestamp_s] = floatval($item[$vk_base]);
             }
        }
    }

    if (empty($key_map) || empty($baseline_map)) {
        return 'nodata'; // No valid data points found after processing
    }

    // --- Calculate Difference only for matching timestamps ---
    $total_difference = 0;
    $matched_points = 0;

    // Iterate through key data timestamps
    foreach ($key_map as $timestamp => $key_value) {
        if (isset($baseline_map[$timestamp])) {
            $baseline_value = $baseline_map[$timestamp];
            $total_difference += ($key_value - $baseline_value);
            $matched_points++;
        }
    }

    // Check if any points actually matched
    // We could add a threshold, e.g., require at least 50% overlap? For now, require at least one match.
    if ($matched_points === 0) {
         // Consider if the datasets have completely different time ranges
         return 'mismatch'; // Indicates timestamps didn't align
    }

    // --- Alternative: Sum independently and subtract totals? ---
    // This might be preferred if the goal is total difference regardless of alignment.
    // Let's stick to point-wise difference summation for now as per "difference of it".
    // $key_total = array_sum($key_map);
    // $baseline_total = array_sum($baseline_map);
    // $total_difference = $key_total - $baseline_total;


    return $total_difference;
}


/**
 * Add custom columns to the Community Item list table.
 * (Removed JSON URL column, kept Shortcode)
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function dc_set_custom_edit_dc_item_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        // Remove the old single JSON URL column if it exists from previous versions
        // if ($key == 'dc_json_url') continue;

        $new_columns[$key] = $title;
        if ($key == 'title') {
            // $new_columns['dc_key_json_url'] = __('Key URL', 'data-community'); // Optionally add back
            // $new_columns['dc_baseline_json_url'] = __('Baseline URL', 'data-community'); // Optionally add back
            $new_columns['dc_shortcode'] = __('Shortcode', 'data-community');
        }
    }
    // Make sure date column is last (or near last)
    if (isset($new_columns['date'])) {
        $date_col = $new_columns['date'];
        unset($new_columns['date']);
        $new_columns['date'] = $date_col;
    }
    return $new_columns;
}
add_filter('manage_dc_item_posts_columns', 'dc_set_custom_edit_dc_item_columns');


/**
 * Populate custom columns with data.
 * (Removed JSON URL case, kept Shortcode)
 *
 * @param string $column The name of the column to display.
 * @param int    $post_id The current post ID.
 */
function dc_custom_dc_item_column($column, $post_id) {
    switch ($column) {
        // Optional: Add cases for dc_key_json_url and dc_baseline_json_url if added back above
        // case 'dc_key_json_url':
        //     $url = get_post_meta($post_id, '_dc_key_json_url', true);
        //     if ($url) { echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(wp_basename($url)) . '</a>'; }
        //     else { echo '<em>' . __('Not set', 'data-community') . '</em>'; }
        //     break;
        // case 'dc_baseline_json_url':
        //      $url = get_post_meta($post_id, '_dc_baseline_json_url', true);
        //     if ($url) { echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html(wp_basename($url)) . '</a>'; }
        //     else { echo '<em>' . __('Not set', 'data-community') . '</em>'; }
        //     break;

        case 'dc_shortcode':
            echo '<code>[data_community id="' . intval($post_id) . '"]</code>';
            break;
    }
}
add_action('manage_dc_item_posts_custom_column', 'dc_custom_dc_item_column', 10, 2);


/**
 * Register the shortcode [data_community id="POST_ID"].
 * Displays CPT content, criteria (calculated), and a Highcharts chart with Key vs Baseline data.
 */
function dc_display_community_item_shortcode( $atts ) {
    // Normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    // Override default attributes with user attributes
    $dc_atts = shortcode_atts( array( 'id' => 0 ), $atts );
    $post_id = intval( $dc_atts['id'] );

    // Validate ID and post type
    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'dc_item' ) {
        return '<p class="dc-error">' . esc_html__( 'Error: Invalid Community Item ID provided or post type mismatch.', 'data-community' ) . '</p>';
    }

    // Get post
    $post = get_post( $post_id );
    if ( ! $post || $post->post_status !== 'publish') {
        return '<p class="dc-error">' . esc_html__( 'Error: Community Item not found or not published.', 'data-community' ) . '</p>';
    }

    // --- Get Post Content ---
    $post_content_html = apply_filters('the_content', $post->post_content);

    // --- Get URLs ---
    $key_json_url = get_post_meta( $post_id, '_dc_key_json_url', true );
    $baseline_json_url = get_post_meta( $post_id, '_dc_baseline_json_url', true );
    $cumulative_diff = get_post_meta( $post_id, '_dc_cumulative_difference', true ); // Get pre-calculated difference

    // --- Fetch and Process Data for Chart ---
    $key_series_data = [];
    $baseline_series_data = [];
    $fetch_error = null;
    $y_axis_label = __('Value', 'data-community'); // Default label

    // Fetch Key Data
    if ($key_json_url) {
        $key_result = dc_fetch_and_parse_json($key_json_url, $post_id);
        if ($key_result['error']) {
            $fetch_error = 'Key Data: ' . $key_result['error'];
        } elseif ($key_result['data'] && $key_result['time_key'] && $key_result['value_key']) {
            $y_axis_label = $key_result['y_axis_label'] ?: $y_axis_label; // Use label from key data if available
            foreach ($key_result['data'] as $item) {
                if (isset($item[$key_result['time_key']]) && isset($item[$key_result['value_key']]) && is_numeric($item[$key_result['value_key']])) {
                    $timestamp_s = strtotime($item[$key_result['time_key']]);
                    if ($timestamp_s !== false) {
                        $key_series_data[] = [$timestamp_s * 1000, floatval($item[$key_result['value_key']])];
                    }
                }
            }
            if (!empty($key_series_data)) {
                 usort($key_series_data, function($a, $b) { return $a[0] - $b[0]; });
            } elseif (!$fetch_error) {
                 $fetch_error = __('Could not process Key Data for chart.', 'data-community');
            }
        } else {
            $fetch_error = __('Could not process Key Data (missing keys or data).', 'data-community');
        }
    } else {
         $fetch_error = __('Key Data JSON URL is not configured.', 'data-community');
    }

    // Fetch Baseline Data (only if key data fetch was somewhat successful)
    if ($baseline_json_url && !$fetch_error) { // Don't fetch baseline if key failed badly
        $baseline_result = dc_fetch_and_parse_json($baseline_json_url, $post_id);
        if ($baseline_result['error']) {
            // Append baseline error, don't overwrite key error if it exists
            $fetch_error = ($fetch_error ? $fetch_error . '<br>' : '') . 'Baseline Data: ' . $baseline_result['error'];
        } elseif ($baseline_result['data'] && $baseline_result['time_key'] && $baseline_result['value_key']) {
            foreach ($baseline_result['data'] as $item) {
                 if (isset($item[$baseline_result['time_key']]) && isset($item[$baseline_result['value_key']]) && is_numeric($item[$baseline_result['value_key']])) {
                    $timestamp_s = strtotime($item[$baseline_result['time_key']]);
                    if ($timestamp_s !== false) {
                        $baseline_series_data[] = [$timestamp_s * 1000, floatval($item[$baseline_result['value_key']])];
                    }
                }
            }
             if (!empty($baseline_series_data)) {
                 usort($baseline_series_data, function($a, $b) { return $a[0] - $b[0]; });
             } // Don't add another error if only baseline fails processing, chart might still show key data
        }
         // Silently ignore baseline processing errors if keys/data missing, chart will show key data
    } elseif (!$baseline_json_url && !$fetch_error) {
        // Baseline URL missing, not necessarily an error for the chart itself
        // We might still show the key data chart.
    }


    // --- Prepare Data & Config for JavaScript ---
    $chart_id = 'dc-chart-container-' . $post_id;
    $chart_series = [];
    if (!empty($key_series_data)) {
        $chart_series[] = [
            'name' => __('Key Data', 'data-community'),
            'data' => $key_series_data,
            'zIndex' => 2, // Ensure Key data is potentially drawn on top
            'color' => '#7cb5ec' // Default Highcharts blue
        ];
    }
    if (!empty($baseline_series_data)) {
        $chart_series[] = [
            'name' => __('Baseline Data', 'data-community'),
            'data' => $baseline_series_data,
            'zIndex' => 1,
             'color' => '#f7a35c', // Default Highcharts orange
             'dashStyle' => 'shortdot' // Differentiate baseline visually
        ];
    }

    $chart_config = [
        'chartId'    => $chart_id,
        'title'      => get_the_title($post_id),
        'yAxisTitle' => $y_axis_label, // Use label determined from key data
        'series'     => $chart_series, // Array of series objects
    ];

    // Enqueue scripts and pass data if we have at least one series
    if (!empty($chart_series)) {
        wp_enqueue_script('highcharts');
        wp_enqueue_script('dc-frontend-script');

        $script_data = sprintf(
            'window.dcChartConfigs = window.dcChartConfigs || {}; window.dcChartConfigs["%s"] = %s;',
            esc_js($chart_id),
            wp_json_encode($chart_config)
        );

        if ( ! wp_script_is('dc-chart-init-trigger', 'added') ) {
            $script_data .= ' document.addEventListener("DOMContentLoaded", function() { if(typeof initDcAllCharts === "function") { initDcAllCharts(); } });';
            wp_add_inline_script('dc-frontend-script', $script_data, 'after');
            wp_scripts()->add_data('dc-chart-init-trigger', 'added', true);
        } else {
            wp_add_inline_script('dc-frontend-script', $script_data, 'after');
        }
    }


    // --- Build HTML Output ---
    $output = '<div class="data-community-viewer" id="dc-item-' . esc_attr( $post_id ) . '">';

    // 1. Title
    $output .= '<h3>' . esc_html( get_the_title( $post_id ) ) . '</h3>';

    // 2. Content
    if ( ! empty( $post_content_html ) ) {
        $output .= '<div class="dc-item-content">' . $post_content_html . '</div>';
    }

    // 3. Chart Area
    $output .= '<div class="dc-chart-area">';
    $output .= '<h4>' . esc_html__( 'Data Visualization', 'data-community' ) . '</h4>';
    if ( ! empty( $fetch_error ) ) {
         // Display fetch/processing error ONLY IF no chart data could be prepared at all
        if (empty($chart_series)) {
             $output .= '<p class="dc-error">' . $fetch_error . '</p>'; // Already escaped html potentially
        } else {
            // Show chart but maybe show a warning above/below? For now, just show chart.
             $output .= '<div id="' . esc_attr( $chart_id ) . '" class="dc-chart-container" style="width:100%; min-height:300px;">';
             $output .= '<p style="text-align:center;">' . esc_html__('Loading chart...', 'data-community') . '</p>';
             $output .= '</div>';
             $output .= '<p class="dc-warning">' . sprintf(__('Note: %s', 'data-community'), $fetch_error) . '</p>'; // Show error as note if chart is partial
        }

    } elseif (empty($chart_series)) {
         $output .= '<p class="dc-error">' . esc_html__('No data available to display chart. Check JSON URLs and data format.', 'data-community') . '</p>';
    } else {
        // Data is ready, output the container
        $output .= '<div id="' . esc_attr( $chart_id ) . '" class="dc-chart-container" style="width:100%; min-height:300px;">';
        $output .= '<p style="text-align:center;">' . esc_html__('Loading chart...', 'data-community') . '</p>';
        $output .= '</div>';
    }
    $output .= '</div>'; // .dc-chart-area

    // 4. Display Calculated Criteria
    $output .= '<br><h4>' . esc_html__( 'Benefits', 'data-community' ) . '</h4>';
    $output .= '<div class="dc-criteria">';
    $has_criteria = false;
    $criteria_display = ['social', 'economic', 'environmental'];
    $crit_labels = [
        'social' => __('Social:', 'data-community'),
        'economic' => __('Economic:', 'data-community'),
        'environmental' => __('Environmental:', 'data-community')
    ];

    // Check if cumulative difference calculation was successful
    $diff_available = is_numeric($cumulative_diff);
    $diff_value = $diff_available ? floatval($cumulative_diff) : 0;

    foreach ($criteria_display as $crit) {
        $source_type = get_post_meta( $post_id, '_dc_' . $crit . '_source_type', true ) ?: 'static';
        $static_value = get_post_meta( $post_id, '_dc_' . $crit . '_static_value', true );
        $factor_value = get_post_meta( $post_id, '_dc_' . $crit . '_factor_value', true );
        $unit = get_post_meta( $post_id, '_dc_' . $crit . '_criteria_unit', true );
        $comment = get_post_meta( $post_id, '_dc_' . $crit . '_criteria_comment', true );

        $display_value = __('N/A', 'data-community'); // Default if calculation not possible

        switch ($source_type) {
            case 'static':
                $display_value = $static_value;
                break;
            case 'diff':
                if ($diff_available) {
                    $display_value = number_format_i18n($diff_value, 2); // Format difference
                } else {
                     $display_value = '<em>' . __('Diff N/A', 'data-community') . '</em>';
                }
                break;
            case 'factor':
                if ($diff_available && is_numeric($factor_value)) {
                     $calculated = floatval($factor_value) * $diff_value;
                     $display_value = number_format_i18n($calculated, 2); // Format result
                } else {
                    $display_value = '<em>' . __('Calc N/A', 'data-community') . '</em>';
                }
                 break;
        }

        // Only display if there's a value to show (even if N/A message)
        if ($display_value !== __('N/A', 'data-community') && $display_value !== '') { // Check for empty static value too
            $has_criteria = true;
            $output .= '<div class="' . esc_attr($crit) . '">';
            $output .= '<strong>' . esc_html( $crit_labels[$crit] ) . '</strong><br>';
            $output .= $display_value; // Value is already formatted or contains safe HTML '<em>'
            if ($unit) { $output .= ' <span class="dc-unit">' . esc_html( $unit ) . '</span>'; }
            if ($comment) { $output .= '<br><span class="dc-comment">' . nl2br( esc_html( $comment ) ) . '</span>'; }
            $output .= '</div>';
        }
    }

    if ( ! $has_criteria ) {
        $output .= '<p><em>' . esc_html__( 'No benefit criteria values configured or calculated.', 'data-community' ) . '</em></p>';
    }
    $output .= '</div>'; // .dc-criteria


    $output .= '</div>'; // .data-community-viewer

    return $output;
}
add_shortcode( 'data_community', 'dc_display_community_item_shortcode' );


/**
 * Enqueue frontend styles and scripts.
 */
function dc_enqueue_styles_and_scripts() {
    // Enqueue Stylesheet
    wp_enqueue_style(
        'dc-frontend-style',
        DC_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        DC_VERSION
    );

    // Register Highcharts library from CDN (core only needed)
    wp_register_script(
        'highcharts',
        'https://code.highcharts.com/highcharts.js',
        array(),
        null,
        true
    );
    // Optional: Register modules like exporting if needed later
    // wp_register_script('highcharts-exporting', 'https://code.highcharts.com/modules/exporting.js', array('highcharts'), null, true);
    // wp_register_script('highcharts-export-data', 'https://code.highcharts.com/modules/export-data.js', array('highcharts'), null, true);


    // Register our custom chart script
    wp_register_script(
        'dc-frontend-script',
        DC_PLUGIN_URL . 'assets/js/data-community-chart.js',
        array('highcharts'), // Depends on Highcharts core
        DC_VERSION,
        true
    );

    // Scripts are enqueued conditionally within the shortcode function.
}
add_action( 'wp_enqueue_scripts', 'dc_enqueue_styles_and_scripts' );

?>