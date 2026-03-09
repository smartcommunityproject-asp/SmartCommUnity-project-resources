<?php
/*
Plugin Name: Comprehensive Community Dashboard Plus
Description: A full-featured WordPress plugin that provides community insights including static chart images (via QuickChart.io), forum topics, user activity, sentiment analysis, CSV export, server environment details, GamiPress stats, theme information, database overview, content performance, engagement metrics, and gamification insights.
Version: 3.6
Author: Your Name
Text Domain: ccd-dashboard
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/* =============================================================================
   Helper Functions
============================================================================= */

/**
 * Generate a QuickChart.io URL based on a Chart.js configuration array.
 *
 * @param array $chartConfig Chart configuration.
 * @return string URL to the rendered chart image.
 */
function ccd_generate_chart_url( $chartConfig ) {
    $jsonConfig = wp_json_encode( $chartConfig );
    return 'https://quickchart.io/chart?c=' . urlencode( $jsonConfig );
}

/**
 * Calculate a basic sentiment score for given text.
 *
 * @param string $text The text to analyze.
 * @return int Sentiment score.
 */
function ccd_calculate_sentiment( $text ) {
    $positive_words = array( 'good', 'great', 'excellent', 'happy', 'awesome', 'fantastic', 'amazing' );
    $negative_words = array( 'bad', 'terrible', 'awful', 'sad', 'horrible', 'poor', 'worst' );
    $text   = strtolower( $text );
    $score  = 0;

    foreach ( $positive_words as $word ) {
        $score += substr_count( $text, $word );
    }
    foreach ( $negative_words as $word ) {
        $score -= substr_count( $text, $word );
    }
    return $score;
}

/**
 * Export forum topics as CSV.
 */
function ccd_export_csv() {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Insufficient permissions to export CSV.', 'ccd-dashboard' ) );
    }

    // Verify nonce.
    if ( ! isset( $_GET['ccd_export_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['ccd_export_nonce'] ) ), 'ccd_export_csv' ) ) {
        wp_die( __( 'Nonce verification failed.', 'ccd-dashboard' ) );
    }

    // Prevent any pre-existing output.
    if ( ob_get_length() ) {
        ob_clean();
    }

    // Set headers.
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=forum_topics.csv' );

    $output = fopen( 'php://output', 'w' );

    // CSV header row.
    fputcsv( $output, array( 'Topic ID', 'Title', 'Author', 'Date', 'Sentiment Score' ) );

    // Fetch all published topics.
    $query = new WP_Query( array(
        'post_type'      => 'topic',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post ) {
            $sentiment = ccd_calculate_sentiment( $post->post_content );
            $author    = get_the_author_meta( 'display_name', $post->post_author );
            fputcsv( $output, array( $post->ID, $post->post_title, $author, $post->post_date, $sentiment ) );
        }
    } else {
        fputcsv( $output, array( __( 'No topics found.', 'ccd-dashboard' ) ) );
    }

    fclose( $output );
    exit;
}

/* =============================================================================
   CSV Export Action Hook
============================================================================= */
function ccd_check_for_csv_export() {
    if ( isset( $_GET['ccd_export'] ) && 'csv' === $_GET['ccd_export'] ) {
        ccd_export_csv();
    }
}
add_action( 'admin_init', 'ccd_check_for_csv_export' );

/* =============================================================================
   Plugin Activation/Deactivation and Admin Menu
============================================================================= */

/**
 * Register custom post type "topic".
 */
function ccd_register_topic_post_type() {
    register_post_type( 'topic', array(
        'label'        => __( 'Topics', 'ccd-dashboard' ),
        'public'       => true,
        'supports'     => array( 'title', 'editor' ),
        'has_archive'  => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-welcome-comments'
    ) );
}
add_action( 'init', 'ccd_register_topic_post_type' );

/**
 * Plugin activation hook.
 */
function ccd_plugin_activation() {
    ccd_register_topic_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ccd_plugin_activation' );

/**
 * Plugin deactivation hook.
 */
function ccd_plugin_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ccd_plugin_deactivation' );

/**
 * Add Community Dashboard page to the admin menu.
 */
function ccd_add_dashboard_menu() {
    add_menu_page(
        __( 'Community Dashboard', 'ccd-dashboard' ),
        __( 'Community Dashboard', 'ccd-dashboard' ),
        'manage_options',
        'community-dashboard',
        'ccd_render_dashboard_page',
        'dashicons-chart-bar',
        25
    );
}
add_action( 'admin_menu', 'ccd_add_dashboard_menu' );

/**
 * Track user login by updating the 'last_login' meta field.
 *
 * @param string   $user_login The username.
 * @param WP_User  $user       The WP_User object.
 */
function ccd_track_user_login( $user_login, $user ) {
    update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
}
add_action( 'wp_login', 'ccd_track_user_login', 10, 2 );

// Increment the 'likes_given' count for the current user.
function ccd_increment_likes_given( $user_id ) {
    $likes = (int) get_user_meta( $user_id, 'likes_given', true );
    update_user_meta( $user_id, 'likes_given', $likes + 1 );
}

// Increment the 'likes_received' count for the post author.
function ccd_increment_likes_received( $post_author_id ) {
    $likes = (int) get_user_meta( $post_author_id, 'likes_received', true );
    update_user_meta( $post_author_id, 'likes_received', $likes + 1 );
}


/* =============================================================================
   Dashboard Rendering Function
============================================================================= */

/**
 * Render the dashboard page.
 */
function ccd_render_dashboard_page() {
    global $wpdb;
    $export_nonce = wp_create_nonce( 'ccd_export_csv' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Community Dashboard Plus', 'ccd-dashboard' ); ?></h1>
        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=community-dashboard&ccd_export=csv&ccd_export_nonce=' . $export_nonce ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Export Forum Topics as CSV', 'ccd-dashboard' ); ?>
            </a>
        </p>

        <!-- Section: Site Overview -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Site Overview', 'ccd-dashboard' ); ?></h2>
            <?php
            $post_count    = wp_count_posts( 'post' )->publish;
            $page_count    = wp_count_posts( 'page' )->publish;
            $topic_count   = wp_count_posts( 'topic' )->publish;
            $comment_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 1" );
            $user_count    = count( get_users() );
            ?>
            <table class="ccd-table">
                <tr>
                    <th><?php esc_html_e( 'Posts', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Pages', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Topics', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Comments', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Users', 'ccd-dashboard' ); ?></th>
                </tr>
                <tr>
                    <td><?php echo esc_html( $post_count ); ?></td>
                    <td><?php echo esc_html( $page_count ); ?></td>
                    <td><?php echo esc_html( $topic_count ); ?></td>
                    <td><?php echo esc_html( $comment_count ); ?></td>
                    <td><?php echo esc_html( $user_count ); ?></td>
                </tr>
            </table>
        </div>

        <!-- Section: Word Cloud Chart (Top 20 Words in Comments) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Word Cloud Chart (Top 20 Words in Comments)', 'ccd-dashboard' ); ?></h2>
            <?php
            $comments = $wpdb->get_results( "SELECT comment_content FROM {$wpdb->comments} WHERE comment_approved = 1 LIMIT 500" );
            $word_frequencies = array();
            if ( $comments ) {
                foreach ( $comments as $comment ) {
                    $words = explode( ' ', strtolower( strip_tags( $comment->comment_content ) ) );
                    foreach ( $words as $word ) {
                        $word = preg_replace( '/[^a-z]/', '', $word );
                        if ( strlen( $word ) > 3 ) {
                            if ( ! isset( $word_frequencies[ $word ] ) ) {
                                $word_frequencies[ $word ] = 0;
                            }
                            $word_frequencies[ $word ]++;
                        }
                    }
                }
            }
            arsort( $word_frequencies );
            $top_words = array_slice( $word_frequencies, 0, 20, true );
            $chartConfig = array(
                'type'    => 'bar',
                'data'    => array(
                    'labels'   => array_keys( $top_words ),
                    'datasets' => array(
                        array(
                            'label'           => __( 'Word Frequency', 'ccd-dashboard' ),
                            'data'            => array_values( $top_words ),
                            'backgroundColor' => '#42A5F5'
                        )
                    )
                ),
                'options' => array(
                    'title'  => array( 'display' => true, 'text' => __( 'Top 20 Words', 'ccd-dashboard' ) ),
                    'legend' => array( 'display' => false )
                )
            );
            $chartUrl = ccd_generate_chart_url( $chartConfig );
            ?>
            <img src="<?php echo esc_url( $chartUrl ); ?>" alt="<?php esc_attr_e( 'Word Cloud Chart', 'ccd-dashboard' ); ?>" class="chart-image">
        </div>

        <!-- Section: Community Engagement Heatmap (Comments per Hour) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Community Engagement Heatmap (Comments per Hour)', 'ccd-dashboard' ); ?></h2>
            <?php
            $timestamps = $wpdb->get_results( "SELECT comment_date FROM {$wpdb->comments} WHERE comment_approved = 1" );
            $hours_count = array_fill( 0, 24, 0 );
            if ( $timestamps ) {
                foreach ( $timestamps as $timestamp ) {
                    $hour = (int) date( 'G', strtotime( $timestamp->comment_date ) );
                    $hours_count[ $hour ]++;
                }
            }
            $chartConfig = array(
                'type'    => 'line',
                'data'    => array(
                    'labels'   => range( 0, 23 ),
                    'datasets' => array(
                        array(
                            'label'       => __( 'Comments per Hour', 'ccd-dashboard' ),
                            'data'        => $hours_count,
                            'borderColor' => '#FF5722',
                            'fill'        => false
                        )
                    )
                ),
                'options' => array(
                    'title'  => array( 'display' => true, 'text' => __( 'Comments per Hour', 'ccd-dashboard' ) )
                )
            );
            $chartUrl = ccd_generate_chart_url( $chartConfig );
            ?>
            <img src="<?php echo esc_url( $chartUrl ); ?>" alt="<?php esc_attr_e( 'Engagement Heatmap', 'ccd-dashboard' ); ?>" class="chart-image">
        </div>

        <!-- Section: Topic Trends (Topics per Month) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Topic Trends (Topics per Month)', 'ccd-dashboard' ); ?></h2>
            <?php
            $topic_trends = $wpdb->get_results( "
                SELECT DATE_FORMAT(post_date, '%Y-%m') AS month, COUNT(*) AS count
                FROM {$wpdb->posts}
                WHERE post_type = 'topic'
                  AND post_status = 'publish'
                GROUP BY month
                ORDER BY month ASC
            " );
            $months = array();
            $topic_counts = array();
            if ( $topic_trends ) {
                foreach ( $topic_trends as $trend ) {
                    $months[] = $trend->month;
                    $topic_counts[] = (int) $trend->count;
                }
            }
            $chartConfig = array(
                'type'    => 'line',
                'data'    => array(
                    'labels'   => $months,
                    'datasets' => array(
                        array(
                            'label'       => __( 'Topics Created', 'ccd-dashboard' ),
                            'data'        => $topic_counts,
                            'borderColor' => '#4CAF50',
                            'fill'        => false
                        )
                    )
                ),
                'options' => array(
                    'title'  => array( 'display' => true, 'text' => __( 'Topics per Month', 'ccd-dashboard' ) )
                )
            );
            $chartUrl = ccd_generate_chart_url( $chartConfig );
            ?>
            <img src="<?php echo esc_url( $chartUrl ); ?>" alt="<?php esc_attr_e( 'Topic Trends', 'ccd-dashboard' ); ?>" class="chart-image">
        </div>

        <!-- Section: User Registration Trends (Registrations per Month) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'User Registration Trends (Registrations per Month)', 'ccd-dashboard' ); ?></h2>
            <?php
            $user_trends = $wpdb->get_results( "
                SELECT DATE_FORMAT(user_registered, '%Y-%m') AS month, COUNT(*) AS count
                FROM {$wpdb->users}
                GROUP BY month
                ORDER BY month ASC
            " );
            $user_months = array();
            $registration_counts = array();
            if ( $user_trends ) {
                foreach ( $user_trends as $trend ) {
                    $user_months[] = $trend->month;
                    $registration_counts[] = (int) $trend->count;
                }
            }
            $chartConfig = array(
                'type'    => 'line',
                'data'    => array(
                    'labels'   => $user_months,
                    'datasets' => array(
                        array(
                            'label'       => __( 'User Registrations', 'ccd-dashboard' ),
                            'data'        => $registration_counts,
                            'borderColor' => '#9C27B0',
                            'fill'        => false
                        )
                    )
                ),
                'options' => array(
                    'title'  => array( 'display' => true, 'text' => __( 'User Registrations per Month', 'ccd-dashboard' ) )
                )
            );
            $chartUrl = ccd_generate_chart_url( $chartConfig );
            ?>
            <img src="<?php echo esc_url( $chartUrl ); ?>" alt="<?php esc_attr_e( 'User Registration Trends', 'ccd-dashboard' ); ?>" class="chart-image">
        </div>
		
		
		<!-- Section: Category Insights -->
		<div class="ccd-card">
			<h2><?php esc_html_e( 'Category Insights', 'ccd-dashboard' ); ?></h2>
			<div class="toggle-section" style="display:block;">
				<?php
				$categories = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 5 ) );
				if ( ! empty( $categories ) ) {
					echo '<table class="ccd-table">
							<tr>
								<th>' . esc_html__( 'Category', 'ccd-dashboard' ) . '</th>
								<th>' . esc_html__( 'Post Count', 'ccd-dashboard' ) . '</th>
							</tr>';
					foreach ( $categories as $cat ) {
						echo '<tr>
								<td>' . esc_html( $cat->name ) . '</td>
								<td>' . esc_html( $cat->count ) . '</td>
							  </tr>';
					}
					echo '</table>';
				} else {
					echo '<p>' . esc_html__( 'No categories found.', 'ccd-dashboard' ) . '</p>';
				}
				?>
			</div>
		</div>

        <!-- Section: Top 5 Active Topics (By Comment Count) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Top 5 Active Topics (By Comment Count)', 'ccd-dashboard' ); ?></h2>
            <?php
            $top_topics = $wpdb->get_results( "
                SELECT comment_post_ID, COUNT(*) AS comment_count
                FROM {$wpdb->comments}
                WHERE comment_approved = 1
                GROUP BY comment_post_ID
                ORDER BY comment_count DESC
                LIMIT 5
            " );
            $topic_titles = array();
            $topic_comment_counts = array();
            if ( $top_topics ) {
                foreach ( $top_topics as $topic ) {
                    $post = get_post( $topic->comment_post_ID );
                    if ( $post && 'topic' === $post->post_type ) {
                        $topic_titles[] = get_the_title( $post );
                        $topic_comment_counts[] = (int) $topic->comment_count;
                    }
                }
            }
            $chartConfig = array(
                'type'    => 'bar',
                'data'    => array(
                    'labels'   => $topic_titles,
                    'datasets' => array(
                        array(
                            'label'           => __( 'Comment Count', 'ccd-dashboard' ),
                            'data'            => $topic_comment_counts,
                            'backgroundColor' => '#FFC107'
                        )
                    )
                ),
                'options' => array(
                    'title'  => array( 'display' => true, 'text' => __( 'Top 5 Active Topics', 'ccd-dashboard' ) ),
                    'legend' => array( 'display' => false )
                )
            );
            $chartUrl = ccd_generate_chart_url( $chartConfig );
            ?>
            <img src="<?php echo esc_url( $chartUrl ); ?>" alt="<?php esc_attr_e( 'Top 5 Active Topics', 'ccd-dashboard' ); ?>" class="chart-image">
        </div>

        <!-- Section: Active Users (Last 30 Days) -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Active Users (Last 30 Days)', 'ccd-dashboard' ); ?></h2>
            <?php
            $active_users = $wpdb->get_results( "
                SELECT post_author, COUNT(*) AS post_count
                FROM {$wpdb->posts}
                WHERE post_type = 'topic'
                  AND post_status = 'publish'
                  AND post_date > (NOW() - INTERVAL 30 DAY)
                GROUP BY post_author
                ORDER BY post_count DESC
                LIMIT 5
            " );
            if ( $active_users ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'User', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Topics Posted', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $active_users as $user ) {
                    $user_info = get_userdata( $user->post_author );
                    echo '<tr>
                            <td>' . esc_html( $user_info->display_name ) . '</td>
                            <td>' . esc_html( $user->post_count ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No active users found in the last 30 days.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>
		
        <!-- Section: Last 5 Forum Topics -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Last 5 Forum Topics', 'ccd-dashboard' ); ?></h2>
            <?php
            $last_topics = get_posts( array(
                'post_type'      => 'topic',
                'posts_per_page' => 5,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
            if ( $last_topics ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'Title', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Date', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Author', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $last_topics as $topic ) {
                    $author = get_the_author_meta( 'display_name', $topic->post_author );
                    echo '<tr>
                            <td>' . esc_html( $topic->post_title ) . '</td>
                            <td>' . esc_html( $topic->post_date ) . '</td>
                            <td>' . esc_html( $author ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No recent forum topics found.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>

        <!-- Section: Sentiment Analysis of Forum Topics -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Sentiment Analysis of Forum Topics', 'ccd-dashboard' ); ?></h2>
            <?php
            $forum_topics = get_posts( array(
                'post_type'      => 'topic',
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
            if ( $forum_topics ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'Title', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Date', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Sentiment Score', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $forum_topics as $topic ) {
                    $score = ccd_calculate_sentiment( $topic->post_content );
                    echo '<tr>
                            <td>' . esc_html( $topic->post_title ) . '</td>
                            <td>' . esc_html( $topic->post_date ) . '</td>
                            <td>' . esc_html( $score ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No forum topics available for sentiment analysis.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>

        <!-- Section: Feedback Sentiment Summary -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Feedback Sentiment Summary (Last 20 Topics)', 'ccd-dashboard' ); ?></h2>
            <?php
            $recent_topics = get_posts( array(
                'post_type'      => 'topic',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
            $positive_count = $negative_count = $neutral_count = 0;
            if ( $recent_topics ) {
                foreach ( $recent_topics as $topic ) {
                    $score = ccd_calculate_sentiment( $topic->post_content );
                    if ( $score > 0 ) {
                        $positive_count++;
                    } elseif ( $score < 0 ) {
                        $negative_count++;
                    } else {
                        $neutral_count++;
                    }
                }
            }
            $total = $positive_count + $negative_count + $neutral_count;
            ?>
            <table class="ccd-table">
                <tr>
                    <th><?php esc_html_e( 'Total Topics Analyzed', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Positive', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Negative', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Neutral', 'ccd-dashboard' ); ?></th>
                </tr>
                <tr>
                    <td><?php echo esc_html( $total ); ?></td>
                    <td><?php echo esc_html( $positive_count ); ?></td>
                    <td><?php echo esc_html( $negative_count ); ?></td>
                    <td><?php echo esc_html( $neutral_count ); ?></td>
                </tr>
            </table>
        </div>

        <!-- Section: Top Community Contributors -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Top Community Contributors', 'ccd-dashboard' ); ?></h2>
            <?php
            $contributors = $wpdb->get_results( "
                SELECT post_author, COUNT(*) AS topic_count
                FROM {$wpdb->posts}
                WHERE post_type = 'topic'
                  AND post_status = 'publish'
                GROUP BY post_author
                ORDER BY topic_count DESC
                LIMIT 5
            " );
            if ( $contributors ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'User', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Topics Created', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $contributors as $contributor ) {
                    $user_info = get_userdata( $contributor->post_author );
                    echo '<tr>
                            <td>' . esc_html( $user_info->display_name ) . '</td>
                            <td>' . esc_html( $contributor->topic_count ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No contributors found.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>
		
		<!-- Section: Engagement Leaderboard -->
		<div class="ccd-card">
			<h2><?php esc_html_e( 'Engagement Leaderboard', 'ccd-dashboard' ); ?></h2>
			<?php
			$users = get_users();
			$engagement_scores = array();
			foreach ( $users as $user ) {
				$topics = count( get_posts( array(
					'post_type'   => 'topic',
					'author'      => $user->ID,
					'post_status' => 'publish',
				) ) );
				$comments = get_comments( array(
					'user_id' => $user->ID,
					'count'   => true,
				) );
				$likes_received = (int) get_user_meta( $user->ID, 'likes_received', true );
				$score = ( $topics * 2 ) + $comments + $likes_received;
				$engagement_scores[] = array(
					'user'  => $user,
					'score' => $score,
				);
			}
			usort( $engagement_scores, function( $a, $b ) {
				return $b['score'] - $a['score'];
			} );
			$top_engaged = array_slice( $engagement_scores, 0, 5 );
			?>
			<table class="ccd-table">
				<tr>
					<th><?php esc_html_e( 'User', 'ccd-dashboard' ); ?></th>
					<th><?php esc_html_e( 'Engagement Score', 'ccd-dashboard' ); ?></th>
				</tr>
				<?php foreach ( $top_engaged as $entry ) : ?>
				<tr>
					<td><?php echo esc_html( $entry['user']->display_name ); ?></td>
					<td><?php echo esc_html( $entry['score'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>
		
        <!-- Section: Recent Comments -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Recent Comments', 'ccd-dashboard' ); ?></h2>
            <?php
            $recent_comments = get_comments( array(
                'number' => 5,
                'status' => 'approve'
            ) );
            if ( $recent_comments ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'Comment', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Author', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Date', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $recent_comments as $comment ) {
                    echo '<tr>
                            <td>' . esc_html( wp_trim_words( $comment->comment_content, 10, '...' ) ) . '</td>
                            <td>' . esc_html( $comment->comment_author ) . '</td>
                            <td>' . esc_html( $comment->comment_date ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No recent comments found.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>

        <!-- Section: Latest Registered Users -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Latest Registered Users', 'ccd-dashboard' ); ?></h2>
            <?php
            $latest_users = get_users( array(
                'number'  => 5,
                'orderby' => 'registered',
                'order'   => 'DESC'
            ) );
            if ( $latest_users ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'Username', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Email', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Registered Date', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $latest_users as $user ) {
                    echo '<tr>
                            <td>' . esc_html( $user->display_name ) . '</td>
                            <td>' . esc_html( $user->user_email ) . '</td>
                            <td>' . esc_html( $user->user_registered ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No new users registered recently.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>

        <!-- Section: Recent Blog Posts -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Recent Blog Posts', 'ccd-dashboard' ); ?></h2>
            <?php
            $recent_posts = get_posts( array(
                'post_type'      => 'post',
                'posts_per_page' => 5,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
            if ( $recent_posts ) {
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'Title', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Date', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html__( 'Author', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $recent_posts as $post ) {
                    $author = get_the_author_meta( 'display_name', $post->post_author );
                    echo '<tr>
                            <td>' . esc_html( $post->post_title ) . '</td>
                            <td>' . esc_html( $post->post_date ) . '</td>
                            <td>' . esc_html( $author ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'No recent blog posts found.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>


        <!-- Section: Gamification Leaderboard -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Gamification Leaderboard', 'ccd-dashboard' ); ?></h2>
            <?php
            if ( class_exists( 'GamiPress' ) && function_exists( 'gamipress_get_user_points' ) ) {
                $all_users   = get_users();
                $leaderboard = array();
                $points_type = 'points_default';

                foreach ( $all_users as $user ) {
                    $points = gamipress_get_user_points( $user->ID, $points_type );
                    $leaderboard[] = array(
                        'name'   => $user->display_name,
                        'points' => $points,
                    );
                }
                // Sort leaderboard by points descending.
                usort( $leaderboard, function( $a, $b ) {
                    return $b['points'] - $a['points'];
                } );
                $leaderboard = array_slice( $leaderboard, 0, 5 );
                echo '<table class="ccd-table">
                        <tr>
                            <th>' . esc_html__( 'User', 'ccd-dashboard' ) . '</th>
                            <th>' . esc_html( ucfirst( $points_type ) ) . ' ' . esc_html__( 'Points', 'ccd-dashboard' ) . '</th>
                        </tr>';
                foreach ( $leaderboard as $entry ) {
                    echo '<tr>
                            <td>' . esc_html( $entry['name'] ) . '</td>
                            <td>' . esc_html( $entry['points'] ) . '</td>
                          </tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__( 'GamiPress is not active or the points function is unavailable.', 'ccd-dashboard' ) . '</p>';
            }
            ?>
        </div>

		
		<!-- Section: Latest User Logins -->
		<div class="ccd-card">
			<h2><?php esc_html_e( 'Latest User Logins', 'ccd-dashboard' ); ?></h2>
			<div class="toggle-section" style="display:block;">
				<?php
				// Retrieve users that have a recorded last login.
				$users = get_users( array(
					'meta_key' => 'last_login',
					'orderby'  => 'meta_value',
					'order'    => 'DESC'
				) );

				if ( ! empty( $users ) ) {
					echo '<table class="ccd-table">
							<tr>
								<th>' . esc_html__( 'User', 'ccd-dashboard' ) . '</th>
								<th>' . esc_html__( 'Last Login', 'ccd-dashboard' ) . '</th>
							</tr>';
					foreach ( $users as $user ) {
						$last_login = get_user_meta( $user->ID, 'last_login', true );
						$formatted_date = $last_login ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_login ) ) : esc_html__( 'Never', 'ccd-dashboard' );
						echo '<tr>
								<td>' . esc_html( $user->display_name ) . '</td>
								<td>' . esc_html( $formatted_date ) . '</td>
							  </tr>';
					}
					echo '</table>';
				} else {
					echo '<p>' . esc_html__( 'No user login data available.', 'ccd-dashboard' ) . '</p>';
				}
				?>
			</div>
		</div>


        <!-- Section: Server Environment Information -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Server Environment Information', 'ccd-dashboard' ); ?></h2>
            <?php
            $wp_version    = get_bloginfo( 'version' );
            $php_version   = phpversion();
            $mysql_version = $wpdb->get_var( "SELECT VERSION()" );
            $current_theme = wp_get_theme();
            ?>
            <table class="ccd-table">
                <tr>
                    <th><?php esc_html_e( 'WordPress Version', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'PHP Version', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'MySQL Version', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Current Theme', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Memory Limit', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Max Execution Time', 'ccd-dashboard' ); ?></th>
                </tr>
                <tr>
                    <td><?php echo esc_html( $wp_version ); ?></td>
                    <td><?php echo esc_html( $php_version ); ?></td>
                    <td><?php echo esc_html( $mysql_version ); ?></td>
                    <td><?php echo esc_html( $current_theme->get( 'Name' ) ); ?></td>
                    <td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
                    <td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
                </tr>
            </table>
        </div>
		
		<!-- Section: Performance Metrics -->
		<div class="ccd-card">
			<h2><?php esc_html_e( 'Performance Metrics', 'ccd-dashboard' ); ?></h2>
			<div class="toggle-section" style="display:block;">
				<?php
				// Use a custom function to get average page load time if available.
				$average_load_time = function_exists( 'ccd_get_average_page_load_time' ) ? ccd_get_average_page_load_time() : __( 'Not available', 'ccd-dashboard' );
				$current_memory_usage = memory_get_usage();
				$peak_memory_usage    = memory_get_peak_usage();
				?>
				<table class="ccd-table">
					<tr>
						<th><?php esc_html_e( 'Average Page Load Time', 'ccd-dashboard' ); ?></th>
						<th><?php esc_html_e( 'Current Memory Usage', 'ccd-dashboard' ); ?></th>
						<th><?php esc_html_e( 'Peak Memory Usage', 'ccd-dashboard' ); ?></th>
					</tr>
					<tr>
						<td><?php echo esc_html( $average_load_time ); ?></td>
						<td><?php echo esc_html( size_format( $current_memory_usage ) ); ?></td>
						<td><?php echo esc_html( size_format( $peak_memory_usage ) ); ?></td>
					</tr>
				</table>
			</div>
		</div>

        <!-- Section: Cirkle Theme Information -->
        <div class="ccd-card">
            <h2><?php esc_html_e( 'Cirkle Theme Information', 'ccd-dashboard' ); ?></h2>
            <?php
            $cirkle_theme = wp_get_theme( 'cirkle' );
            ?>
            <table class="ccd-table">
                <tr>
                    <th><?php esc_html_e( 'Theme Name', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Version', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Author', 'ccd-dashboard' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'ccd-dashboard' ); ?></th>
                </tr>
                <?php if ( $cirkle_theme->exists() ) : ?>
                <tr>
                    <td><?php echo esc_html( $cirkle_theme->get( 'Name' ) ); ?></td>
                    <td><?php echo esc_html( $cirkle_theme->get( 'Version' ) ); ?></td>
                    <td><?php echo esc_html( $cirkle_theme->get( 'Author' ) ); ?></td>
                    <td><?php echo esc_html( $cirkle_theme->get( 'Description' ) ); ?></td>
                </tr>
                <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'Cirkle theme is not active.', 'ccd-dashboard' ); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
		
		<!-- Section: Disk Space Usage -->
		<div class="ccd-card">
			<h2><?php esc_html_e( 'Disk Space Usage', 'ccd-dashboard' ); ?></h2>
			<div class="toggle-section" style="display:block;">
				<?php
				$total_space = @disk_total_space( ABSPATH );
				$free_space  = @disk_free_space( ABSPATH );
				if ( $total_space && $free_space ) {
					$used_space = $total_space - $free_space;
					$usage_percentage = round( ( $used_space / $total_space ) * 100, 2 );
					?>
					<table class="ccd-table">
						<tr>
							<th><?php esc_html_e( 'Total Space', 'ccd-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Free Space', 'ccd-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Usage', 'ccd-dashboard' ); ?></th>
						</tr>
						<tr>
							<td><?php echo esc_html( size_format( $total_space ) ); ?></td>
							<td><?php echo esc_html( size_format( $free_space ) ); ?></td>
							<td><?php echo esc_html( $usage_percentage . '%' ); ?></td>
						</tr>
					</table>
					<?php
				} else {
					echo '<p>' . esc_html__( 'Unable to determine disk space usage.', 'ccd-dashboard' ) . '</p>';
				}
				?>
			</div>
		</div>

    </div>
    <?php
}

/* =============================================================================
   Shortcode Functionality
============================================================================= */

/**
 * Shortcode [ccd_dashboard] to display the dashboard on the front end.
 *
 * @return string
 */
function ccd_dashboard_shortcode() {
    ob_start();
    ccd_render_dashboard_page();
    return ob_get_clean();
}
add_shortcode( 'ccd_dashboard', 'ccd_dashboard_shortcode' );

