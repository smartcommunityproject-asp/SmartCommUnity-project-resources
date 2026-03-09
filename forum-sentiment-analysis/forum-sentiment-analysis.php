<?php
/*
Plugin Name: Forum Post Sentiment Analysis & Content Analytics
Description: Combines sentiment analysis for forum posts with content performance analytics.
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Simple sentiment analysis function
function simple_sentiment_analysis($text) {
    // Basic keyword-based sentiment analysis
    $positive_words = array('good', 'great', 'awesome', 'excellent', 'happy', 'love');
    $negative_words = array('bad', 'poor', 'terrible', 'sad', 'hate', 'angry');

    $score = 0;

    // Convert text to lowercase for comparison
    $text = strtolower($text);

    // Check for positive words
    foreach ($positive_words as $word) {
        $score += substr_count($text, $word);
    }

    // Check for negative words
    foreach ($negative_words as $word) {
        $score -= substr_count($text, $word);
    }

    // Return sentiment
    if ($score > 0) {
        return 'Positive';
    } elseif ($score < 0) {
        return 'Negative';
    } else {
        return 'Neutral';
    }
}

// Shortcode to analyze and group forum replies by sentiment
function display_forum_posts_sentiment_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(
        array(
            'post_type' => 'reply', // Default to replies
        ),
        $atts,
        'display_forum_posts_sentiment'
    );

    // Arguments for WP_Query
    $args = array(
        'post_type'      => sanitize_text_field($atts['post_type']),
        'posts_per_page' => -1, // Get all replies
        'post_status'    => 'publish', // Only published posts
    );

    $query = new WP_Query($args);

    // Sentiment groups
    $sentiments = array(
        'Positive' => array(),
        'Negative' => array(),
        'Neutral'  => array(),
    );

    // Process posts
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $content = get_the_content();
            $sentiment = simple_sentiment_analysis($content);

            // Group posts by sentiment
            $sentiments[$sentiment][] = array(
                'title'   => get_the_title(),
                'content' => $content,
            );
        }
    }

    // Reset post data
    wp_reset_postdata();

    // Output grouped posts
    $output = '<div class="forum-posts-sentiment">';
    foreach ($sentiments as $sentiment => $posts) {
        $output .= '<h2>' . esc_html($sentiment) . '</h2>';
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $output .= '<div class="forum-post">';
                $output .= '<h3>' . esc_html($post['title']) . '</h3>';
                $output .= '<p>' . esc_html($post['content']) . '</p>';
                $output .= '</div>';
            }
        } else {
            $output .= '<p>No ' . esc_html(strtolower($sentiment)) . ' posts found.</p>';
        }
    }
    $output .= '</div>';

    return $output;
}

// Register the shortcode
add_shortcode('display_forum_posts_sentiment', 'display_forum_posts_sentiment_shortcode');

// Track post views
function cad_track_post_views() {
    if (is_single()) {
        global $post;
        $views = get_post_meta($post->ID, 'cad_post_views', true);
        $views = $views ? $views + 1 : 1;
        update_post_meta($post->ID, 'cad_post_views', $views);
    }
}
add_action('wp_head', 'cad_track_post_views');

// Add custom column to display views in the admin posts table
function cad_add_views_column($columns) {
    $columns['cad_post_views'] = 'Views';
    return $columns;
}
add_filter('manage_posts_columns', 'cad_add_views_column');

function cad_display_views_column($column, $post_id) {
    if ($column === 'cad_post_views') {
        echo get_post_meta($post_id, 'cad_post_views', true) ?: '0';
    }
}
add_action('manage_posts_custom_column', 'cad_display_views_column', 10, 2);

// Create the dashboard widget
function cad_dashboard_widget() {
    $posts = new WP_Query([
        'posts_per_page' => 5,
        'orderby' => 'meta_value_num',
        'meta_key' => 'cad_post_views',
        'order' => 'DESC'
    ]);

    echo '<ul>';
    if ($posts->have_posts()) {
        while ($posts->have_posts()) {
            $posts->the_post();
            $views = get_post_meta(get_the_ID(), 'cad_post_views', true) ?: '0';
            echo '<li>' . get_the_title() . ' - ' . $views . ' Views, ' . get_comments_number() . ' Comments</li>';
        }
    } else {
        echo '<li>No posts found.</li>';
    }
    echo '</ul>';
    wp_reset_postdata();
}

function cad_add_dashboard_widget() {
    wp_add_dashboard_widget('cad_dashboard_widget', 'Top Performing Posts', 'cad_dashboard_widget');
}
add_action('wp_dashboard_setup', 'cad_add_dashboard_widget');



