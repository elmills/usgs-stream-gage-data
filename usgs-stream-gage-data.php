<?php
/**
 * Plugin Name: USGS Stream Gage Data
 * Plugin URI: https://example.com/usgs-stream-gage-data
 * Description: A modern WordPress plugin that allows users to specify USGS stream gages and display their data using shortcodes.
 * Version: 1.0.0
 * Author: WordPress User
 * Author URI: https://example.com
 * Text Domain: usgs-stream-gage-data
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'USGS_STREAM_GAGE_VERSION', '1.0.0' );
define( 'USGS_STREAM_GAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include required files
require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage.php';

// Initialize the plugin
function usgs_stream_gage_init() {
    $plugin = new USGS_Stream_Gage();
    $plugin->run();
}
add_action( 'plugins_loaded', 'usgs_stream_gage_init' );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'usgs_stream_gage_activate' );
register_deactivation_hook( __FILE__, 'usgs_stream_gage_deactivate' );

/**
 * The code that runs during plugin activation.
 */
function usgs_stream_gage_activate() {
    // Activation code here
}

/**
 * The code that runs during plugin deactivation.
 */
function usgs_stream_gage_deactivate() {
    // Deactivation code here
}