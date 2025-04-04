<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin-area functionality.
 *
 * @package    USGS_Stream_Gage
 * @since      1.0.0
 */

class USGS_Stream_Gage_Admin {

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
     * API instance for validating USGS sites
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
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new USGS_Stream_Gage_API();
        
        // Register AJAX handlers
        add_action( 'wp_ajax_usgs_validate_site', array( $this, 'ajax_validate_site' ) );
        add_action( 'wp_ajax_usgs_search_sites', array( $this, 'ajax_search_sites' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, USGS_STREAM_GAGE_PLUGIN_URL . 'admin/css/usgs-stream-gage-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, USGS_STREAM_GAGE_PLUGIN_URL . 'admin/js/usgs-stream-gage-admin.js', array( 'jquery' ), $this->version, false );
        
        // Localize the script with data for AJAX
        wp_localize_script( $this->plugin_name, 'usgs_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'usgs-ajax-nonce' )
        ) );
    }

    /**
     * Add options page to the admin menu.
     *
     * @since    1.0.0
     */
    public function add_options_page() {
        add_options_page(
            __( 'USGS Stream Gage Settings', 'usgs-stream-gage-data' ),
            __( 'USGS Stream Gages', 'usgs-stream-gage-data' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_options_page' )
        );
    }

    /**
     * Render the options page.
     *
     * @since    1.0.0
     */
    public function display_options_page() {
        include_once USGS_STREAM_GAGE_PLUGIN_DIR . 'admin/partials/usgs-stream-gage-admin-display.php';
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register setting for storing gage sites
        register_setting(
            'usgs_stream_gage_options',
            'usgs_stream_gage_sites',
            array( $this, 'validate_sites_setting' )
        );
        
        // Register section for general settings
        add_settings_section(
            'usgs_stream_gage_general',
            __( 'Stream Gage Sites', 'usgs-stream-gage-data' ),
            array( $this, 'render_section_description' ),
            $this->plugin_name
        );
        
        // Register field for adding sites
        add_settings_field(
            'usgs_stream_gage_sites_field',
            __( 'Manage Sites', 'usgs-stream-gage-data' ),
            array( $this, 'render_sites_field' ),
            $this->plugin_name,
            'usgs_stream_gage_general'
        );
    }

    /**
     * Validate sites setting before saving.
     *
     * @since    1.0.0
     * @param    array    $input    The submitted sites array.
     * @return   array              The validated sites array.
     */
    public function validate_sites_setting( $input ) {
        $validated_sites = array();
        
        if ( !is_array( $input ) ) {
            return $validated_sites;
        }
        
        foreach ( $input as $site ) {
            // Ensure site number is provided
            if ( empty( $site['site_number'] ) ) {
                continue;
            }
            
            // Validate site with USGS API if not already validated
            if ( empty( $site['is_validated'] ) || $site['is_validated'] !== true ) {
                $validation = $this->api->validate_site( $site['site_number'] );
                
                if ( $validation === false ) {
                    // Skip invalid sites
                    continue;
                }
                
                // Update site data from API validation
                $site['site_name'] = $validation['site_name'];
                $site['latitude'] = $validation['latitude'];
                $site['longitude'] = $validation['longitude'];
                $site['is_validated'] = true;
            }
            
            // Generate a unique ID for the site if not already set
            if ( empty( $site['id'] ) ) {
                $site['id'] = uniqid( 'usgs_' );
            }
            
            $validated_sites[] = $site;
        }
        
        return $validated_sites;
    }

    /**
     * Render section description.
     *
     * @since    1.0.0
     */
    public function render_section_description() {
        echo '<p>' . esc_html__( 'Add and manage USGS stream gage sites. For each site you add, you can use a shortcode to display its data.', 'usgs-stream-gage-data' ) . '</p>';
    }

    /**
     * Render the sites management field.
     *
     * @since    1.0.0
     */
    public function render_sites_field() {
        $sites = get_option( 'usgs_stream_gage_sites', array() );
        ?>
        <div class="usgs-sites-container">
            <div class="usgs-sites-header">
                <h3><?php esc_html_e( 'Current Sites', 'usgs-stream-gage-data' ); ?></h3>
                <p><?php esc_html_e( 'These sites are currently configured and can be used with shortcodes.', 'usgs-stream-gage-data' ); ?></p>
            </div>
            
            <table class="widefat usgs-sites-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Site Name', 'usgs-stream-gage-data' ); ?></th>
                        <th><?php esc_html_e( 'Site Number', 'usgs-stream-gage-data' ); ?></th>
                        <th><?php esc_html_e( 'Shortcode', 'usgs-stream-gage-data' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'usgs-stream-gage-data' ); ?></th>
                    </tr>
                </thead>
                <tbody id="usgs-sites-list">
                    <?php if ( empty( $sites ) ) : ?>
                        <tr class="no-sites">
                            <td colspan="4"><?php esc_html_e( 'No sites have been added yet.', 'usgs-stream-gage-data' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $sites as $index => $site ) : ?>
                            <tr data-site-id="<?php echo esc_attr( $site['id'] ); ?>">
                                <td><?php echo esc_html( $site['site_name'] ); ?></td>
                                <td><?php echo esc_html( $site['site_number'] ); ?></td>
                                <td>
                                    <code>[usgs_stream_gage id="<?php echo esc_attr( $site['id'] ); ?>"]</code>
                                    <button type="button" class="button button-small copy-shortcode">
                                        <?php esc_html_e( 'Copy', 'usgs-stream-gage-data' ); ?>
                                    </button>
                                </td>
                                <td>
                                    <button type="button" class="button button-small remove-site">
                                        <?php esc_html_e( 'Remove', 'usgs-stream-gage-data' ); ?>
                                    </button>
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $site['id'] ); ?>">
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][site_number]" value="<?php echo esc_attr( $site['site_number'] ); ?>">
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][site_name]" value="<?php echo esc_attr( $site['site_name'] ); ?>">
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][latitude]" value="<?php echo esc_attr( $site['latitude'] ); ?>">
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][longitude]" value="<?php echo esc_attr( $site['longitude'] ); ?>">
                                    <input type="hidden" name="usgs_stream_gage_sites[<?php echo esc_attr( $index ); ?>][is_validated]" value="1">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="usgs-sites-actions">
                <h3><?php esc_html_e( 'Add New Site', 'usgs-stream-gage-data' ); ?></h3>
                
                <div class="usgs-add-site-container">
                    <div class="usgs-add-site-option">
                        <label>
                            <input type="radio" name="usgs-add-method" value="number" checked>
                            <?php esc_html_e( 'Add by Site Number', 'usgs-stream-gage-data' ); ?>
                        </label>
                        <div class="usgs-add-by-number">
                            <input type="text" id="usgs-site-number" placeholder="<?php esc_attr_e( 'Enter USGS site number', 'usgs-stream-gage-data' ); ?>">
                            <button type="button" class="button" id="usgs-validate-site">
                                <?php esc_html_e( 'Validate & Add', 'usgs-stream-gage-data' ); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>
                    </div>
                    
                    <div class="usgs-add-site-option">
                        <label>
                            <input type="radio" name="usgs-add-method" value="search">
                            <?php esc_html_e( 'Search for Site', 'usgs-stream-gage-data' ); ?>
                        </label>
                        <div class="usgs-search-sites" style="display: none;">
                            <input type="text" id="usgs-site-search" placeholder="<?php esc_attr_e( 'Enter site name to search', 'usgs-stream-gage-data' ); ?>">
                            <button type="button" class="button" id="usgs-search-sites">
                                <?php esc_html_e( 'Search', 'usgs-stream-gage-data' ); ?>
                            </button>
                            <span class="spinner"></span>
                            
                            <div id="usgs-search-results" class="usgs-search-results"></div>
                        </div>
                    </div>
                </div>
                
                <div id="usgs-message" class="notice" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for validating a site by number.
     *
     * @since    1.0.0
     */
    public function ajax_validate_site() {
        // Check nonce for security
        check_ajax_referer( 'usgs-ajax-nonce', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Get site number from request
        $site_number = isset( $_POST['site_number'] ) ? sanitize_text_field( $_POST['site_number'] ) : '';
        
        if ( empty( $site_number ) ) {
            wp_send_json_error( array(
                'message' => __( 'Site number is required.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Validate the site using API
        $validation = $this->api->validate_site( $site_number );
        
        if ( $validation === false ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid or inactive USGS site number. Please verify and try again.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Site is valid, return the site data
        wp_send_json_success( array(
            'message' => __( 'Site validated successfully.', 'usgs-stream-gage-data' ),
            'site' => $validation
        ) );
    }

    /**
     * AJAX handler for searching sites by name.
     *
     * @since    1.0.0
     */
    public function ajax_search_sites() {
        // Check nonce for security
        check_ajax_referer( 'usgs-ajax-nonce', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Get search term from request
        $search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
        
        if ( empty( $search_term ) ) {
            wp_send_json_error( array(
                'message' => __( 'Search term is required.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Search for sites using API
        $sites = $this->api->search_sites_by_name( $search_term );
        
        if ( empty( $sites ) ) {
            wp_send_json_error( array(
                'message' => __( 'No sites found matching your search term.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Return the search results
        wp_send_json_success( array(
            'message' => sprintf( 
                _n( 
                    '%d site found.', 
                    '%d sites found.', 
                    count( $sites ), 
                    'usgs-stream-gage-data' 
                ), 
                count( $sites ) 
            ),
            'sites' => $sites
        ) );
    }
}