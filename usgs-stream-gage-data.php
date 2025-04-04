<?php
/**
 * Plugin Name: USGS Stream Gage Data
 * Plugin URI: https://elmills.net/usgs-stream-gage-data
 * Description: A modern WordPress plugin that allows users to specify USGS stream gages and display their data using shortcodes.
 * Version: 1.2.3
 * Author: Everette Mills
 * Author URI: https://elmills.net
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
define( 'USGS_STREAM_GAGE_VERSION', '1.2.3' );
define( 'USGS_STREAM_GAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'USGS_STREAM_GAGE_FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css' );

// Include required files
require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage.php';

// Include the Plugin Update Checker library directly from its installed location
$pucPath = USGS_STREAM_GAGE_PLUGIN_DIR . 'plugin-update-checker-5.2/plugin-update-checker.php';
if (file_exists($pucPath)) {
    require_once $pucPath;
} else {
    // Log error if the file is missing
    if (function_exists('error_log')) {
        error_log('Plugin Update Checker library not found at path: ' . $pucPath);
    }
}

// Initialize the plugin
function usgs_stream_gage_init() {
    // Run the core plugin
    $plugin = new USGS_Stream_Gage();
    $plugin->run();
    
    // Set up GitHub updater with hardcoded repository URL
    $github_repo = 'elmills/usgs-stream-gage-data';
    
    // Initialize the updater with the GitHub repository using v5 factory
    // Only if the class exists
    if (class_exists('Puc_v5_Factory')) {
        $update_checker = Puc_v5_Factory::buildUpdateChecker(
            'https://github.com/' . $github_repo,
            __FILE__,
            'usgs-stream-gage-data'
        );
    }
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