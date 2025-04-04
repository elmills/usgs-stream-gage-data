<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and public-facing functionality,
 * including the shortcode implementation.
 *
 * @package    USGS_Stream_Gage
 * @since      1.0.0
 */

class USGS_Stream_Gage_Public {

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
     * API instance for getting USGS data
     *
     * @since    1.0.0
     * @access   private
     * @var      USGS_Stream_Gage_API    $api    API handler instance.
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new USGS_Stream_Gage_API();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, USGS_STREAM_GAGE_PLUGIN_URL . 'public/css/usgs-stream-gage-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, USGS_STREAM_GAGE_PLUGIN_URL . 'public/js/usgs-stream-gage-public.js', array( 'jquery' ), $this->version, false );
    }
}