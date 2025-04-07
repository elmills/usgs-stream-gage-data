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
        
        // Make sure the logger class is loaded first
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-logger.php';
        
        // Initialize API after logger is loaded
        $this->api = new USGS_Stream_Gage_API();
        
        // Register AJAX handlers
        add_action( 'wp_ajax_usgs_validate_site', array( $this, 'ajax_validate_site' ) );
        add_action( 'wp_ajax_usgs_search_sites', array( $this, 'ajax_search_sites' ) );
        add_action( 'wp_ajax_usgs_clear_logs', array( $this, 'ajax_clear_logs' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Enqueue the main admin stylesheet
        wp_enqueue_style( $this->plugin_name, USGS_STREAM_GAGE_PLUGIN_URL . 'admin/css/usgs-stream-gage-admin.css', array(), $this->version, 'all' );
        
        // Enqueue Font Awesome for the admin icon
        wp_enqueue_style( $this->plugin_name . '-fontawesome', USGS_STREAM_GAGE_FONTAWESOME_URL, array(), '6.4.2', 'all' );
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
     * Display logs in the admin area.
     *
     * @since    1.1.0
     */
    public function display_logs() {
        // Get log stats
        $logs_stats = USGS_Stream_Gage_Logger::get_logs_stats();
        
        // Get all logs
        $logs = USGS_Stream_Gage_Logger::get_logs();
        
        // Pass logs data to template
        include_once USGS_STREAM_GAGE_PLUGIN_DIR . 'admin/partials/usgs-stream-gage-admin-logs.php';
    }
    
    /**
     * AJAX handler for clearing logs.
     *
     * @since    1.1.0
     */
    public function ajax_clear_logs() {
        // Check nonce for security
        check_ajax_referer( 'usgs-ajax-nonce', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Clear logs
        $cleared = USGS_Stream_Gage_Logger::clear_logs();
        
        if ( $cleared ) {
            wp_send_json_success( array(
                'message' => __( 'Logs cleared successfully.', 'usgs-stream-gage-data' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Failed to clear logs.', 'usgs-stream-gage-data' )
            ) );
        }
    }
    
    /**
     * Add a menu page to the admin menu.
     *
     * @since    1.0.0
     * @modified 1.1.0 Changed from add_options_page to add_menu_page for better visibility
     */
    public function add_menu_page() {
        // Create custom menu icon using Font Awesome water icon
        $icon_url = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="currentColor" d="M269.5 69.9c11.1-7.9 25.9-7.9 37 0C329 85.4 356.5 96 384 96c26.9 0 55.4-10.8 77.4-26.1l0 0c11.9-8.5 28.1-7.8 39.2 1.7c14.4 11.9 32.5 21 50.6 25.2c17.2 4 27.9 21.2 23.9 38.4s-21.2 27.9-38.4 23.9c-24.5-5.7-44.9-16.5-58.2-25C449.5 149.7 418 160 384 160c-31.9 0-60.6-9.9-80.4-18.9c-5.8-2.7-11.1-5.3-15.6-7.7c-4.5 2.4-9.7 5.1-15.6 7.7c-19.8 9-48.5 18.9-80.4 18.9c-33.5 0-65.3-10.3-94.3-25.4c-13.4 8.4-33.7 19.3-58.2 25c-17.2 4-34.4-6.7-38.4-23.9s6.7-34.4 23.9-38.4c18.1-4.2 36.2-13.3 50.6-25.2c11.1-9.4 27.3-10.1 39.2-1.7l0 0C136.7 85.2 165.1 96 192 96c27.5 0 55-10.6 77.5-26.1zm37 288C329 373.4 356.5 384 384 384c26.9 0 55.4-10.8 77.4-26.1l0 0c11.9-8.5 28.1-7.8 39.2 1.7c14.4 11.9 32.5 21 50.6 25.2c17.2 4 27.9 21.2 23.9 38.4s-21.2 27.9-38.4 23.9c-24.5-5.7-44.9-16.5-58.2-25C449.5 437.7 418 448 384 448c-31.9 0-60.6-9.9-80.4-18.9c-5.8-2.7-11.1-5.3-15.6-7.7c-4.5 2.4-9.7 5.1-15.6 7.7c-19.8 9-48.5 18.9-80.4 18.9c-33.5 0-65.3-10.3-94.3-25.4c-13.4 8.4-33.7 19.3-58.2 25c-17.2 4-34.4-6.7-38.4-23.9s6.7-34.4 23.9-38.4c18.1-4.2 36.2-13.3 50.6-25.2c11.1-9.4 27.3-10.1 39.2-1.7l0 0C136.7 373.2 165.1 384 192 384c27.5 0 55-10.6 77.5-26.1c11.1-7.9 25.9-7.9 37 0zM384 256c26.9 0 55.4-10.8 77.4-26.1l0 0c11.9-8.5 28.1-7.8 39.2 1.7c14.4 11.9 32.5 21 50.6 25.2c17.2 4 27.9 21.2 23.9 38.4s-21.2 27.9-38.4 23.9c-24.5-5.7-44.9-16.5-58.2-25C449.5 309.7 418 320 384 320c-31.9 0-60.6-9.9-80.4-18.9c-5.8-2.7-11.1-5.3-15.6-7.7c-4.5 2.4-9.7 5.1-15.6 7.7c-19.8 9-48.5 18.9-80.4 18.9c-33.5 0-65.3-10.3-94.3-25.4c-13.4 8.4-33.7 19.3-58.2 25c-17.2 4-34.4-6.7-38.4-23.9s6.7-34.4 23.9-38.4c18.1-4.2 36.2-13.3 50.6-25.2c11.1-9.4 27.3-10.1 39.2-1.7l0 0C136.7 245.2 165.1 256 192 256c27.5 0 55-10.6 77.5-26.1c11.1-7.9 25.9-7.9 37 0C329 245.4 356.5 256 384 256z"/></svg>');
        
        add_menu_page(
            __( 'USGS Stream Gage Settings', 'usgs-stream-gage-data' ),
            __( 'USGS Stream Gages', 'usgs-stream-gage-data' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_options_page' ),
            $icon_url,
            30
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
        
        // Log validation attempt for debugging
        USGS_Stream_Gage_Logger::log(
            'AJAX site validation attempt',
            array(
                'site_number' => $site_number,
                'request' => $_POST
            ),
            USGS_Stream_Gage_Logger::LOG_LEVEL_DEBUG
        );
        
        // Validate the site using API
        $validation = $this->api->validate_site( $site_number );
        
        // Improved error handling - specifically look for boolean false
        if ( $validation === false ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid or inactive USGS site number. Please verify and try again.', 'usgs-stream-gage-data' )
            ) );
        }
        
        // Ensure site data is properly structured
        if (!is_array($validation) || empty($validation['site_number']) || empty($validation['site_name'])) {
            // More detailed logging of the malformed response
            USGS_Stream_Gage_Logger::log(
                'Site validation returned malformed data',
                array(
                    'site_number' => $site_number,
                    'validation_result' => $validation,
                    'is_array' => is_array($validation),
                    'data_type' => gettype($validation)
                ),
                USGS_Stream_Gage_Logger::LOG_LEVEL_ERROR
            );
            
            // Clear the cache for this site to force fresh validation next time
            delete_transient('usgs_site_validation_' . sanitize_key($site_number));
            
            wp_send_json_error(array(
                'message' => __('The site was found but returned invalid data. Please try again.', 'usgs-stream-gage-data')
            ));
        }
        
        // Log successful validation
        USGS_Stream_Gage_Logger::log(
            'Site validated successfully via AJAX',
            array(
                'site_number' => $site_number,
                'site_name' => $validation['site_name']
            )
        );
        
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