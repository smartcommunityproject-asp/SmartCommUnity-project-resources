// Shortcode for Top Active Users
function ccd_top_active_users_shortcode() {
    global $wpdb;

    $top_users = $wpdb->get_results("
        SELECT user_id, COUNT(comment_ID) as comment_count
        FROM {$wpdb->comments}
        WHERE comment_approved = 1
        GROUP BY user_id
        ORDER BY comment_count DESC
        LIMIT 5
    ");

    $output = '<h3>Top Active Users</h3>';
    if ($top_users) {
        $output .= '<ul>';
        foreach ($top_users as $user) {
            $user_info = get_userdata($user->user_id);
            $username = $user_info ? $user_info->display_name : 'Guest';
            $output .= '<li>' . esc_html($username) . ' - ' . intval($user->comment_count) . ' Comments</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No active users found.</p>';
    }

    return $output;
}
add_shortcode('ccd_top_active_users', 'ccd_top_active_users_shortcode');

// Shortcode for Latest Forum Topics
function ccd_latest_forum_topics_shortcode() {
    $latest_topics = new WP_Query([
        'post_type' => 'topic',
        'posts_per_page' => 5,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    $output = '<h3>Latest Forum Topics</h3>';
    if ($latest_topics->have_posts()) {
        $output .= '<ul>';
        while ($latest_topics->have_posts()) {
            $latest_topics->the_post();
            $output .= '<li><a href="' . get_the_permalink() . '">' . get_the_title() . '</a> - ' . get_the_date() . '</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>No forum topics found.</p>';
    }

    wp_reset_postdata();

    return $output;
}
add_shortcode('ccd_latest_forum_topics', 'ccd_latest_forum_topics_shortcode');

// Shortcode for Sentiment Analysis
function ccd_sentiment_analysis_shortcode() {
    global $wpdb;

    $comments = $wpdb->get_results("
        SELECT comment_content FROM {$wpdb->comments} WHERE comment_approved = 1
    ");

    $sentiments = ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0];
    foreach ($comments as $comment) {
        $sentiment = ccd_simple_sentiment_analysis($comment->comment_content);
        $sentiments[$sentiment]++;
    }

    $output = '<h3>Sentiment Analysis</h3>';
    $output .= '<ul>';
    foreach ($sentiments as $sentiment => $count) {
        $output .= '<li>' . esc_html($sentiment) . ': ' . intval($count) . ' comments</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('ccd_sentiment_analysis', 'ccd_sentiment_analysis_shortcode');

