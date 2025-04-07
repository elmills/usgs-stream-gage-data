<?php
/**
 * Plugin Name: USGS Stream Gage Data
 * Plugin URI: https://elmills.net/usgs-stream-gage-data
 * Description: A modern WordPress plugin that allows users to specify USGS stream gages and display their data using shortcodes.
 * Version: 1.2.7
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
define( 'USGS_STREAM_GAGE_VERSION', '1.2.7' );
define( 'USGS_STREAM_GAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'USGS_STREAM_GAGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'USGS_STREAM_GAGE_FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css' );


// Include required files
require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage.php';

// Initialize the plugin
function usgs_stream_gage_init() {
    // Run the core plugin
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

/**
 * Setup plugin updates from GitHub.
 */
if (file_exists(USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-github-plugin-info.php')) {
    require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-github-plugin-info.php';
    
    // Initialize the GitHub updater
    if (class_exists('GitHub_Plugin_Updater')) {
        // Define the GitHub repository URL for your plugin
        $github_repo_url = 'https://github.com/elmills/usgs-stream-gage-data';
        
        // Set the plugin metadata for the updater
        $plugin_metadata = [
            'contributors' => 'elmills',
            'donate_link' => 'https://blueboatsolutions.com/donate',
            'tags' => 'wordpress, plugin, template, base',
            'requires_php' => '8.0',
            'license' => 'GPLv2 or later',
            'license_uri' => 'https://www.gnu.org/licenses/gpl-2.0.html'
        ];
        
        // GitHub token for read-only access to updates
        // The hardcoded token will be used, but if it's removed (empty), it will default to null
        $token_value = '';
        $github_read_only_token = !empty($token_value) ? $token_value : null;
        
        // Initialize the updater
        // Parameters: plugin file, plugin slug, GitHub repo URL, branch name (default: main), access token, metadata
        new GitHub_Plugin_Updater(
            __FILE__,
            'usgs-stream-gage-data',
            $github_repo_url,
            'main',
            $github_read_only_token, // Will be null if no token is provided
            $plugin_metadata
        );
    }
}