<?php
/**
 * Plugin Name:     CCD Data Reporter
 * Plugin URI:      https://example.com/ccd-data-reporter
 * Description:     Self-contained data reporting plugin: picks dates, displays metrics, and downloads a print-ready HTML report—all in one file.
 * Version:         1.6.0
 * Author:          Your Name
 * Author URI:      https://example.com
 * Text Domain:     ccd-data-reporter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCD_Data_Reporter {
    const DATE_FORMAT   = 'Y-m-d';
    const TRANSIENT_TTL = 300;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_submenu' ) );
        add_action( 'wp_ajax_ccd_fetch_data', array( $this, 'ajax_fetch_data' ) );
        add_shortcode( 'ccd_data_report', array( $this, 'admin_shortcode' ) );
        add_shortcode( 'ccd_public_report', array( $this, 'public_shortcode' ) );
    }

    public function register_submenu() {
        add_submenu_page(
            'community-dashboard',
            __( 'Data Reports', 'ccd-data-reporter' ),
            __( 'Data Reports', 'ccd-data-reporter' ),
            'manage_options',
            'ccd-data-reports',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $today     = gmdate( self::DATE_FORMAT );
        $month_ago = gmdate( self::DATE_FORMAT, strtotime( '-30 days' ) );
        ?>
        <div class="wrap" style="font-family:sans-serif;">
            <h1><?php esc_html_e( 'Community Data Reports', 'ccd-data-reporter' ); ?></h1>
            <p><?php esc_html_e( 'Select a date range and download a polished, print-ready report.', 'ccd-data-reporter' ); ?></p>

            <form id="ccd-report-form" style="margin-bottom:1em;">
                <label><?php esc_html_e( 'From:', 'ccd-data-reporter' ); ?>
                    <input type="date" id="ccd-start" value="<?php echo esc_attr( $month_ago ); ?>" />
                </label>
                <label style="margin-left:1em;"><?php esc_html_e( 'To:', 'ccd-data-reporter' ); ?>
                    <input type="date" id="ccd-end" value="<?php echo esc_attr( $today ); ?>" />
                </label>
                <button type="button" id="ccd-generate" class="button button-primary" style="margin-left:1em;">
                    <?php esc_html_e( 'Generate', 'ccd-data-reporter' ); ?>
                </button>
                <button type="button" id="ccd-download" class="button" style="margin-left:0.5em;">
                    <?php esc_html_e( 'Download', 'ccd-data-reporter' ); ?>
                </button>
            </form>

            <div id="ccd-report-content"></div>
        </div>
        <style>
            #ccd-report-content h1,h2{color:#333;margin-top:1em;}
            #ccd-report-content table{width:100%;border-collapse:collapse;margin-bottom:1em;}
            #ccd-report-content th,#ccd-report-content td{border:1px solid #ccc;padding:0.5em;text-align:left;}
            #ccd-report-content ul{margin-bottom:1em;padding-left:1.2em;}
        </style>
        <script>
        (function(){
            var gen = document.getElementById('ccd-generate');
            var dl  = document.getElementById('ccd-download');
            var content = document.getElementById('ccd-report-content');
            var last;
            gen.addEventListener('click', function(){
                var s = document.getElementById('ccd-start').value;
                var e = document.getElementById('ccd-end').value;
                if(!s||!e){alert('<?php echo esc_js('Please select both dates.'); ?>');return;}
                content.innerHTML = '<p>Loading…</p>';
                var fd = new FormData();
                fd.append('action','ccd_fetch_data');
                fd.append('nonce','<?php echo wp_create_nonce('ccd_fetch_data'); ?>');
                fd.append('start_date',s);
                fd.append('end_date',e);
                fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
                    method:'POST',credentials:'same-origin',body:fd
                }).then(function(r){return r.json();})
                .then(function(j){
                    if(j.success){last=j.data;content.innerHTML=build(j.data);}else{content.innerHTML='<p>Error: '+(j.data||'Unknown')+'</p>';} 
                }).catch(function(){content.innerHTML='<p>Network error</p>';});
            });
            dl.addEventListener('click', function(){
                if(!last){alert('<?php echo esc_js('Please generate a report first.'); ?>');return;}
                var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Report</title>';
                html+='<style>'+
                    'body{font-family:sans-serif;padding:1em;}'+
                    'h1,h2{color:#333;}'+
                    'table{width:100%;border-collapse:collapse;margin-bottom:1em;}'+
                    'th,td{border:1px solid #ccc;padding:0.5em;text-align:left;}'+
                    'ul{margin-bottom:1em;padding-left:1.2em;}'+
                '</style></head><body>';
                html+=build(last);
                html+='</body></html>';
                var b=new Blob([html],{type:'text/html'});
                var u=URL.createObjectURL(b);
                var a=document.createElement('a');a.href=u;
                a.download='community-report-'+last.start+'-'+last.end+'.html';
                document.body.appendChild(a);a.click();document.body.removeChild(a);
                URL.revokeObjectURL(u);
            });
            function build(d){
                var s='<h1>Community Report: '+d.start+' to '+d.end+'</h1>';
                s+='<h2>Totals</h2><ul><li>Total Topics: '+d.topics+'</li>'+
                    '<li>Total Comments: '+d.comments+'</li></ul>';
                s+='<h2>Daily Topic Counts</h2><table><thead><tr><th>Date</th><th>Count</th></tr></thead><tbody>';
                d.daily.forEach(function(r){s+='<tr><td>'+r.day+'</td><td>'+r.count+'</td></tr>';});
                s+='</tbody></table>';
                s+='<h2>Daily Sentiment</h2><table><thead><tr><th>Date</th><th>Avg Sentiment</th></tr></thead><tbody>';
                d.sent.forEach(function(r){s+='<tr><td>'+r.day+'</td><td>'+parseFloat(r.avg_sentiment).toFixed(2)+'</td></tr>';});
                s+='</tbody></table>';
                s+='<h2>Top 5 Authors</h2><ul>';
                d.authors.forEach(function(a){s+='<li>'+a.name+': '+a.count+'</li>';});
                s+='</ul>';
                return s;
            }
        })();
        </script>
        <?php
    }

    public function ajax_fetch_data() {
        check_ajax_referer( 'ccd_fetch_data', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 401 );
        }
        $start = $this->validate_date( $_POST['start_date'] ?? '' );
        $end   = $this->validate_date( $_POST['end_date'] ?? '' );
        if ( ! $start || ! $end ) {
            wp_send_json_error( 'Invalid date', 400 );
        }
        $key  = 'ccd_report_' . md5( $start . $end );
        $data = get_transient( $key );
        if ( false === $data ) {
            $data = $this->get_report_data( $start, $end );
            set_transient( $key, $data, self::TRANSIENT_TTL );
        }
        wp_send_json_success( $data );
    }

    private function validate_date( $date ) {
        $d = DateTime::createFromFormat( self::DATE_FORMAT, sanitize_text_field( $date ) );
        return ( $d && $d->format( self::DATE_FORMAT ) === $date ) ? $date : false;
    }

    private function get_report_data( $start, $end ) {
        global $wpdb;
        $start_dt = $start . ' 00:00:00';
        $end_dt   = $end   . ' 23:59:59';
        $topics   = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s AND post_date BETWEEN %s AND %s", 'topic','publish',$start_dt,$end_dt) );
        $comments = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved=1 AND comment_date BETWEEN %s AND %s", $start_dt, $end_dt) );
        $daily    = $wpdb->get_results( $wpdb->prepare("SELECT DATE(post_date) day,COUNT(*) count FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s AND post_date BETWEEN %s AND %s GROUP BY day ORDER BY day", 'topic','publish',$start_dt,$end_dt), ARRAY_A );
        $sent     = $wpdb->get_results( $wpdb->prepare("SELECT DATE(post_date) day,AVG(((LENGTH(post_content)-LENGTH(REPLACE(LOWER(post_content),'good','')))/LENGTH('good'))-((LENGTH(post_content)-LENGTH(REPLACE(LOWER(post_content),'bad','')))/LENGTH('bad'))) avg_sentiment FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s AND post_date BETWEEN %s AND %s GROUP BY day ORDER BY day", 'topic','publish',$start_dt,$end_dt), ARRAY_A );
        $authors_raw = $wpdb->get_results( $wpdb->prepare("SELECT post_author author_id,COUNT(*) count FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s AND post_date BETWEEN %s AND %s GROUP BY author_id ORDER BY count DESC LIMIT 5", 'topic','publish',$start_dt,$end_dt) );
        $authors = array(); foreach($authors_raw as $r){ $authors[] = array('name'=>get_the_author_meta('display_name',$r->author_id),'count'=>(int)$r->count); }
        return array('topics'=>$topics,'comments'=>$comments,'daily'=>$daily,'sent'=>$sent,'authors'=>$authors,'start'=>$start,'end'=>$end);
    }

    public function admin_shortcode() {
        return '<p>' . esc_html__( 'Use the Data Reports menu in admin to generate and download reports.', 'ccd-data-reporter' ) . '</p>';
    }

    public function public_shortcode($atts) {
        $atts = shortcode_atts(array('start_date'=>gmdate(self::DATE_FORMAT,strtotime('-7 days')),'end_date'=>gmdate(self::DATE_FORMAT)), $atts,'ccd_public_report');
        if(!$atts['start_date']||!$atts['end_date']) return '<p>' . esc_html__( 'Invalid date range.', 'ccd-data-reporter' ) . '</p>';
        $data = $this->get_report_data($atts['start_date'],$atts['end_date']);
        return '<div class="ccd-public-report"><h2>'.sprintf(esc_html__('Report: %s to %s','ccd-data-reporter'),esc_html($atts['start_date']),esc_html($atts['end_date'])).'</h2><ul><li>'.esc_html__('Total Topics:','ccd-data-reporter').' '.esc_html($data['topics']).'</li><li>'.esc_html__('Total Comments:','ccd-data-reporter').' '.esc_html($data['comments']).'</li></ul></div>';
    }
}

new CCD_Data_Reporter();
