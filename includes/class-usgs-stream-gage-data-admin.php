<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://blueboat.io
 * @since      1.0.0
 *
 * @package    Usgs_Stream_Gage_Data
 * @subpackage Usgs_Stream_Gage_Data/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Usgs_Stream_Gage_Data
 * @subpackage Usgs_Stream_Gage_Data/admin
 * @author     Blue Boat Partners LLC <support@blueboat.io>
 */
class Usgs_Stream_Gage_Data_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Usgs_Stream_Gage_Data_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Usgs_Stream_Gage_Data_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/usgs-stream-gage-data-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Usgs_Stream_Gage_Data_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Usgs_Stream_Gage_Data_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/usgs-stream-gage-data-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Register the admin menu
     */
    public function add_plugin_admin_menu() {
        // Add main menu item
        add_menu_page(
            __('USGS Stream Gage Data', 'usgs-stream-gage-data'),
            __('USGS Stream Gages', 'usgs-stream-gage-data'),
            'manage_options',
            'usgs-stream-gage-data',
            array($this, 'display_plugin_admin_page'),
            'dashicons-water', // Use WordPress built-in water dashicon as fallback
            26 // Menu position
        );

        // Enqueue Font Awesome in admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_font_awesome'));
        
        // Add custom admin menu icon via CSS
        add_action('admin_head', array($this, 'add_admin_menu_icon_styles'));
    }

    /**
     * Enqueue Font Awesome for admin
     */
    public function enqueue_admin_font_awesome() {
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
    }

    /**
     * Add custom admin menu icon styles
     */
    public function add_admin_menu_icon_styles() {
        ?>
        <style>
            #adminmenu .toplevel_page_usgs-stream-gage-data .wp-menu-image::before {
                font-family: "Font Awesome 6 Free" !important;
                content: "\f773"; /* Font Awesome water icon */
                font-weight: 900;
                font-size: 18px !important;
            }
            /* Hide default dashicon */
            #adminmenu .toplevel_page_usgs-stream-gage-data .dashicons-water {
                display: none !important;
            }
        </style>
        <?php
    }

}