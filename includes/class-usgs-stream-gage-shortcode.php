<?php
/**
 * The shortcode functionality of the plugin.
 *
 * Defines the shortcode for displaying USGS stream gage data.
 *
 * @package    USGS_Stream_Gage
 * @since      1.0.0
 */

class USGS_Stream_Gage_Shortcode {

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
     * Render the shortcode output.
     *
     * @since    1.0.0
     * @param    array    $atts    The shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function render_shortcode( $atts ) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'id' => '', // Site ID
                'show_discharge' => 'yes', // Show discharge data
                'show_gage_height' => 'yes', // Show gage height data
                'show_24h' => 'yes', // Show 24-hour historical data
                'show_7d' => 'yes', // Show 7-day historical data
                'show_30d' => 'yes', // Show 30-day historical data
                'show_1y' => 'yes', // Show 1-year historical data
            ),
            $atts,
            'usgs_stream_gage'
        );
        
        // If no ID provided, return error message
        if ( empty( $atts['id'] ) ) {
            return '<div class="usgs-error">Error: No stream gage ID provided. Please specify an ID attribute.</div>';
        }
        
        // Check if the site exists in settings
        $sites = get_option( 'usgs_stream_gage_sites', array() );
        $site = null;
        
        foreach ( $sites as $site_data ) {
            if ( $site_data['id'] === $atts['id'] ) {
                $site = $site_data;
                break;
            }
        }
        
        // If site not found, return error message
        if ( $site === null ) {
            return '<div class="usgs-error">Error: Stream gage with ID "' . esc_html( $atts['id'] ) . '" not found.</div>';
        }
        
        // Fetch current data
        $current_data = $this->api->get_current_data( $site['site_number'] );
        
        // Check for API error
        if ( !empty( $current_data['error'] ) && $current_data['error'] === true ) {
            return '<div class="usgs-error">Error fetching stream gage data: ' . esc_html( $current_data['message'] ) . '</div>';
        }
        
        // Determine which historical periods to display
        $periods = array();
        if ( $atts['show_24h'] === 'yes' ) $periods[] = '24h';
        if ( $atts['show_7d'] === 'yes' ) $periods[] = '7d';
        if ( $atts['show_30d'] === 'yes' ) $periods[] = '30d';
        if ( $atts['show_1y'] === 'yes' ) $periods[] = '1y';
        
        // Fetch historical data for each period
        $historical_data = array();
        foreach ( $periods as $period ) {
            $historical_data[$period] = $this->api->get_historical_data( $site['site_number'], $period );
        }
        
        // Start output buffer
        ob_start();
        
        // Output container
        echo '<div class="usgs-stream-gage-data" id="usgs-stream-gage-' . esc_attr( $site['id'] ) . '">';
        
        // Site header
        echo '<div class="usgs-site-header">';
        echo '<h3 class="usgs-site-name">' . esc_html( $site['site_name'] ) . '</h3>';
        echo '<div class="usgs-site-number">USGS ' . esc_html( $site['site_number'] ) . '</div>';
        echo '</div>';
        
        // Current data section
        echo '<div class="usgs-current-data">';
        echo '<h4>Current Conditions</h4>';
        echo '<div class="usgs-current-datetime">As of ' . esc_html( date( 'F j, Y g:i a', $current_data['timestamp'] ) ) . '</div>';
        
        echo '<table class="usgs-data-table">';
        echo '<thead><tr><th>Measurement</th><th>Current Value</th></tr></thead>';
        echo '<tbody>';
        
        // Discharge data if available and requested
        if ( $atts['show_discharge'] === 'yes' && !empty( $current_data['discharge'] ) ) {
            echo '<tr>';
            echo '<td>Discharge</td>';
            echo '<td>' . esc_html( $current_data['discharge'] ) . ' ' . esc_html( $current_data['discharge_unit'] ) . '</td>';
            echo '</tr>';
        }
        
        // Gage height data if available and requested
        if ( $atts['show_gage_height'] === 'yes' && !empty( $current_data['gage_height'] ) ) {
            echo '<tr>';
            echo '<td>Gage Height</td>';
            echo '<td>' . esc_html( $current_data['gage_height'] ) . ' ' . esc_html( $current_data['gage_height_unit'] ) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // End current data
        
        // Historical data section
        if ( !empty( $periods ) ) {
            echo '<div class="usgs-historical-data">';
            echo '<h4>Historical High/Low Values</h4>';
            
            // Tabs for different time periods
            echo '<div class="usgs-period-tabs">';
            foreach ( $periods as $index => $period ) {
                $period_label = $this->get_period_label( $period );
                $active_class = ( $index === 0 ) ? 'active' : '';
                echo '<button class="usgs-period-tab ' . $active_class . '" data-period="' . esc_attr( $period ) . '">' . esc_html( $period_label ) . '</button>';
            }
            echo '</div>';
            
            // Tab content
            echo '<div class="usgs-period-content">';
            foreach ( $periods as $index => $period ) {
                $display_style = ( $index === 0 ) ? 'block' : 'none';
                $period_data = $historical_data[$period];
                
                echo '<div class="usgs-period-data" data-period="' . esc_attr( $period ) . '" style="display: ' . $display_style . ';">';
                
                // Check for data errors
                if ( !empty( $period_data['error'] ) && $period_data['error'] === true ) {
                    echo '<div class="usgs-error">Error fetching historical data: ' . esc_html( $period_data['message'] ) . '</div>';
                } else {
                    echo '<table class="usgs-data-table">';
                    echo '<thead><tr><th>Measurement</th><th>High</th><th>Date/Time</th><th>Low</th><th>Date/Time</th></tr></thead>';
                    echo '<tbody>';
                    
                    // Discharge data if available and requested
                    if ( $atts['show_discharge'] === 'yes' && !empty( $period_data['discharge']['high'] ) ) {
                        echo '<tr>';
                        echo '<td>Discharge (' . esc_html( $period_data['discharge']['unit'] ) . ')</td>';
                        echo '<td class="usgs-high">' . esc_html( $period_data['discharge']['high'] ) . '</td>';
                        echo '<td>' . esc_html( $this->api->format_date( $period_data['discharge']['high_datetime'] ) ) . '</td>';
                        echo '<td class="usgs-low">' . esc_html( $period_data['discharge']['low'] ) . '</td>';
                        echo '<td>' . esc_html( $this->api->format_date( $period_data['discharge']['low_datetime'] ) ) . '</td>';
                        echo '</tr>';
                    }
                    
                    // Gage height data if available and requested
                    if ( $atts['show_gage_height'] === 'yes' && !empty( $period_data['gage_height']['high'] ) ) {
                        echo '<tr>';
                        echo '<td>Gage Height (' . esc_html( $period_data['gage_height']['unit'] ) . ')</td>';
                        echo '<td class="usgs-high">' . esc_html( $period_data['gage_height']['high'] ) . '</td>';
                        echo '<td>' . esc_html( $this->api->format_date( $period_data['gage_height']['high_datetime'] ) ) . '</td>';
                        echo '<td class="usgs-low">' . esc_html( $period_data['gage_height']['low'] ) . '</td>';
                        echo '<td>' . esc_html( $this->api->format_date( $period_data['gage_height']['low_datetime'] ) ) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                }
                
                echo '</div>'; // End period data
            }
            echo '</div>'; // End tab content
            echo '</div>'; // End historical data
            
            // Add script for tab functionality
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Get all tab buttons and tab content divs
                    var tabButtons = document.querySelectorAll("#usgs-stream-gage-' . esc_attr( $site['id'] ) . ' .usgs-period-tab");
                    var tabContents = document.querySelectorAll("#usgs-stream-gage-' . esc_attr( $site['id'] ) . ' .usgs-period-data");
                    
                    // Add click event listeners to tab buttons
                    tabButtons.forEach(function(button) {
                        button.addEventListener("click", function() {
                            var period = this.getAttribute("data-period");
                            
                            // Deactivate all tabs
                            tabButtons.forEach(function(btn) {
                                btn.classList.remove("active");
                            });
                            
                            // Hide all tab contents
                            tabContents.forEach(function(content) {
                                content.style.display = "none";
                            });
                            
                            // Activate selected tab
                            this.classList.add("active");
                            
                            // Show selected tab content
                            document.querySelector("#usgs-stream-gage-' . esc_attr( $site['id'] ) . ' .usgs-period-data[data-period=\'" + period + "\']").style.display = "block";
                        });
                    });
                });
            </script>';
        }
        
        // Footer with attribution
        echo '<div class="usgs-footer">';
        echo '<a href="https://waterdata.usgs.gov/nwis/uv?site_no=' . esc_attr( $site['site_number'] ) . '" target="_blank" rel="noopener noreferrer">View on USGS Water Data</a>';
        echo '</div>';
        
        echo '</div>'; // End container
        
        // Return the output
        return ob_get_clean();
    }
    
    /**
     * Get a human-readable label for a time period.
     *
     * @since    1.0.0
     * @param    string    $period    The period code ('24h', '7d', '30d', '1y').
     * @return   string               Human-readable label.
     */
    private function get_period_label( $period ) {
        switch ( $period ) {
            case '24h':
                return 'Last 24 Hours';
            case '7d':
                return 'Last 7 Days';
            case '30d':
                return 'Last 30 Days';
            case '1y':
                return 'Last Year';
            default:
                return ucfirst( $period );
        }
    }
}