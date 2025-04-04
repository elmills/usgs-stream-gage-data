<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    USGS_Stream_Gage_Data
 * @subpackage USGS_Stream_Gage_Data/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get current active tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'sites';
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="usgs-stream-gage-admin">
        <div class="usgs-admin-header">
            <p><?php esc_html_e( 'This plugin allows you to display USGS stream gage data on your website using shortcodes. Add stream gage sites below, then use the provided shortcodes on any page or post.', 'usgs-stream-gage-data' ); ?></p>
        </div>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?php echo esc_attr( $this->plugin_name ); ?>&tab=sites" class="nav-tab <?php echo $active_tab === 'sites' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Manage Sites', 'usgs-stream-gage-data' ); ?>
            </a>
            <a href="?page=<?php echo esc_attr( $this->plugin_name ); ?>&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'API Logs', 'usgs-stream-gage-data' ); ?>
            </a>
        </h2>
        
        <?php if ( $active_tab === 'sites' ) : ?>
            <!-- Sites Tab Content -->
            <form method="post" action="options.php" class="usgs-admin-form">
                <?php
                // Output security fields
                settings_fields( 'usgs_stream_gage_options' );
                
                // Output setting sections and their fields
                do_settings_sections( $this->plugin_name );
                
                // Output save settings button
                submit_button( esc_html__( 'Save Sites', 'usgs-stream-gage-data' ) );
                ?>
            </form>
            
            <div class="usgs-admin-help">
                <h3><?php esc_html_e( 'Shortcode Usage', 'usgs-stream-gage-data' ); ?></h3>
                <p><?php esc_html_e( 'Use the following shortcode format to display stream gage data:', 'usgs-stream-gage-data' ); ?></p>
                <pre>[usgs_stream_gage id="site_id"]</pre>
                
                <h4><?php esc_html_e( 'Optional Parameters', 'usgs-stream-gage-data' ); ?></h4>
                <ul>
                    <li><code>show_discharge</code>: <?php esc_html_e( 'Show discharge data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                    <li><code>show_gage_height</code>: <?php esc_html_e( 'Show gage height data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                    <li><code>show_24h</code>: <?php esc_html_e( 'Show 24-hour historical data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                    <li><code>show_7d</code>: <?php esc_html_e( 'Show 7-day historical data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                    <li><code>show_30d</code>: <?php esc_html_e( 'Show 30-day historical data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                    <li><code>show_1y</code>: <?php esc_html_e( 'Show 1-year historical data (yes/no, default: yes)', 'usgs-stream-gage-data' ); ?></li>
                </ul>
                
                <h4><?php esc_html_e( 'Example', 'usgs-stream-gage-data' ); ?></h4>
                <pre>[usgs_stream_gage id="site_id" show_discharge="yes" show_gage_height="yes" show_24h="yes" show_7d="yes" show_30d="yes" show_1y="yes"]</pre>
                
                <p><?php esc_html_e( 'Replace "site_id" with the ID from the sites table above.', 'usgs-stream-gage-data' ); ?></p>
            </div>
        <?php elseif ( $active_tab === 'logs' ) : ?>
            <!-- Logs Tab Content -->
            <?php include_once USGS_STREAM_GAGE_PLUGIN_DIR . 'admin/partials/usgs-stream-gage-admin-logs.php'; ?>
        <?php endif; ?>
    </div>
</div>