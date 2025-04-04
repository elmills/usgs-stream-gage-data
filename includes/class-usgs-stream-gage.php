<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    USGS_Stream_Gage
 */

class USGS_Stream_Gage {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      USGS_Stream_Gage_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = USGS_STREAM_GAGE_VERSION;
        $this->plugin_name = 'usgs-stream-gage-data';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - USGS_Stream_Gage_Loader. Orchestrates the hooks of the plugin.
     * - USGS_Stream_Gage_i18n. Defines internationalization functionality.
     * - USGS_Stream_Gage_Admin. Defines all hooks for the admin area.
     * - USGS_Stream_Gage_Public. Defines all hooks for the public side of the site.
     * - USGS_Stream_Gage_API. Handles API calls to USGS services.
     * - USGS_Stream_Gage_Shortcode. Defines the shortcode functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-loader.php';

        // The class responsible for defining internationalization functionality of the plugin.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-i18n.php';

        // The class responsible for defining all actions that occur in the admin area.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'admin/class-usgs-stream-gage-admin.php';

        // The class responsible for defining all actions that occur in the public-facing side of the site.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'public/class-usgs-stream-gage-public.php';

        // The class responsible for handling API calls to USGS services.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-api.php';

        // The class responsible for defining shortcode functionality.
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-shortcode.php';

        $this->loader = new USGS_Stream_Gage_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the USGS_Stream_Gage_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new USGS_Stream_Gage_i18n();
        $plugin_i18n->set_domain( $this->get_plugin_name() );

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new USGS_Stream_Gage_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_options_page' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new USGS_Stream_Gage_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes() {
        $plugin_shortcode = new USGS_Stream_Gage_Shortcode( $this->get_plugin_name(), $this->get_version() );
        
        // Register shortcode
        add_shortcode( 'usgs_stream_gage', array( $plugin_shortcode, 'render_shortcode' ) );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    USGS_Stream_Gage_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}