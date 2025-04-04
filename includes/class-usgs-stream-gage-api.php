<?php
/**
 * Handle all API calls to USGS water services.
 *
 * Interacts with USGS water services API to validate sites,
 * fetch current and historical data for stream gages.
 *
 * @since      1.0.0
 * @package    USGS_Stream_Gage
 */

class USGS_Stream_Gage_API {

    /**
     * Base URL for USGS Instantaneous Values service
     */
    const USGS_IV_SERVICE_URL = 'https://waterservices.usgs.gov/nwis/iv/';

    /**
     * Base URL for USGS Site service
     */
    const USGS_SITE_SERVICE_URL = 'https://waterservices.usgs.gov/nwis/site/';

    /**
     * Cache expiration times in seconds
     * 
     * @since    1.1.0
     * @access   private
     * @var      array    $cache_expiration    Expiration times for different types of cached data.
     */
    private $cache_expiration;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Set up transient expiration times (in seconds)
        $this->cache_expiration = [
            'site_validation' => 86400, // 24 hours
            'current_data'    => 900,   // 15 minutes
            '24h_data'        => 1800,  // 30 minutes
            '7d_data'         => 3600,  // 1 hour
            '30d_data'        => 7200,  // 2 hours
            '1y_data'         => 14400, // 4 hours
        ];
        
        // Make sure the logger class is loaded
        require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-logger.php';
    }

    /**
     * Validate a USGS site by site number.
     *
     * @since    1.0.0
     * @param    string    $site_number    The USGS site number to validate.
     * @return   bool|array                False if invalid, site data array if valid.
     */
    public function validate_site( $site_number ) {
        // Log validation attempt
        USGS_Stream_Gage_Logger::log(
            'Validating USGS site',
            array(
                'site_number' => $site_number,
                'method' => 'validate_site'
            )
        );
        
        // Check transient cache first
        $cache_key = 'usgs_site_validation_' . sanitize_key( $site_number );
        $cached_data = get_transient( $cache_key );
        
        if ( false !== $cached_data ) {
            // Log cache hit
            USGS_Stream_Gage_Logger::log(
                'Using cached validation data',
                array(
                    'site_number' => $site_number,
                    'cache_key' => $cache_key,
                    'has_data' => ( $cached_data !== false )
                )
            );
            
            return $cached_data;
        }
        
        // Build the API URL
        $args = [
            'format' => 'json',
            'sites' => $site_number,
            'siteStatus' => 'active'
        ];
        
        // Log the API request
        $url = USGS_Stream_Gage_Logger::log_request( self::USGS_SITE_SERVICE_URL, $args );
        
        // Make the API request
        $response = wp_remote_get( $url );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            // Log the error
            USGS_Stream_Gage_Logger::log_error(
                'API Error validating site',
                $response
            );
            
            return false;
        }
        
        // Log the response
        USGS_Stream_Gage_Logger::log_response( self::USGS_SITE_SERVICE_URL, $args, $response );
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        // Validate the response
        if ( empty( $data['value']['timeSeries'] ) ) {
            // Site not found or not active
            USGS_Stream_Gage_Logger::log(
                'Invalid USGS site', 
                array(
                    'site_number' => $site_number,
                    'response' => 'No time series data found'
                ),
                USGS_Stream_Gage_Logger::LOG_LEVEL_WARNING
            );
            
            set_transient( $cache_key, false, $this->cache_expiration['site_validation'] );
            return false;
        }
        
        // Site is valid, extract and cache basic site info
        $site_data = [
            'site_number' => $site_number,
            'site_name' => $data['value']['timeSeries'][0]['sourceInfo']['siteName'],
            'latitude' => $data['value']['timeSeries'][0]['sourceInfo']['geoLocation']['geogLocation']['latitude'],
            'longitude' => $data['value']['timeSeries'][0]['sourceInfo']['geoLocation']['geogLocation']['longitude'],
        ];
        
        // Log successful validation
        USGS_Stream_Gage_Logger::log(
            'USGS site validated successfully',
            array(
                'site_number' => $site_number,
                'site_name' => $site_data['site_name']
            )
        );
        
        set_transient( $cache_key, $site_data, $this->cache_expiration['site_validation'] );
        
        return $site_data;
    }

    /**
     * Search for a USGS site by name or partial name.
     *
     * @since    1.0.0
     * @param    string    $site_name    The site name to search for.
     * @return   array                   Array of matching sites.
     */
    public function search_sites_by_name( $site_name ) {
        // Log search attempt
        USGS_Stream_Gage_Logger::log(
            'Searching for USGS sites',
            array(
                'search_term' => $site_name,
                'method' => 'search_sites_by_name'
            )
        );
        
        // Build the API URL - with less restrictive parameters
        $args = [
            'format' => 'json',
            'siteNameLike' => urlencode( $site_name ),
            'siteStatus' => 'active',
            // Make parameter filtering less restrictive - allow ANY site with water data
            'siteType' => 'ST', // Stream sites
            'hasDataTypeCd' => 'dv' // Daily values (more common than instantaneous)
        ];
        
        // Log the API request
        $url = USGS_Stream_Gage_Logger::log_request( self::USGS_SITE_SERVICE_URL, $args );
        
        // Make the API request
        $response = wp_remote_get( $url );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            // Log the error
            USGS_Stream_Gage_Logger::log_error(
                'API Error searching sites',
                $response
            );
            
            return [];
        }
        
        // Log the full response for debugging
        USGS_Stream_Gage_Logger::log(
            'API Search Response (Raw)',
            array(
                'url' => $url,
                'response_code' => wp_remote_retrieve_response_code( $response ),
                'response_body_preview' => substr( wp_remote_retrieve_body( $response ), 0, 500 )
            ),
            USGS_Stream_Gage_Logger::LOG_LEVEL_DEBUG
        );
        
        // Log the response
        USGS_Stream_Gage_Logger::log_response( self::USGS_SITE_SERVICE_URL, $args, $response );
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        $sites = [];
        
        // Extract site information with better error handling
        if ( !empty( $data['value']['sites'] ) ) {
            // Log the number of sites found
            USGS_Stream_Gage_Logger::log(
                'USGS API returned sites',
                array(
                    'count' => count( $data['value']['sites'] ),
                    'search_term' => $site_name
                ),
                USGS_Stream_Gage_Logger::LOG_LEVEL_DEBUG
            );
            
            foreach ( $data['value']['sites'] as $site ) {
                // More robust field checking
                if ( !isset( $site['siteCode'][0]['value'] ) || !isset( $site['siteName'] ) ) {
                    continue;
                }
                
                $site_data = [
                    'site_number' => $site['siteCode'][0]['value'],
                    'site_name' => $site['siteName'],
                ];
                
                // Optional fields with fallbacks
                if ( isset( $site['geoLocation']['geogLocation']['latitude'] ) ) {
                    $site_data['latitude'] = $site['geoLocation']['geogLocation']['latitude'];
                } else {
                    $site_data['latitude'] = null;
                }
                
                if ( isset( $site['geoLocation']['geogLocation']['longitude'] ) ) {
                    $site_data['longitude'] = $site['geoLocation']['geogLocation']['longitude'];
                } else {
                    $site_data['longitude'] = null;
                }
                
                $sites[] = $site_data;
            }
            
            // Log successful search
            USGS_Stream_Gage_Logger::log(
                'USGS sites found',
                array(
                    'search_term' => $site_name,
                    'sites_found' => count( $sites ),
                    'first_site' => !empty($sites) ? $sites[0]['site_name'] : 'None'
                )
            );
        } else {
            // More detailed error logging
            $error_details = array(
                'search_term' => $site_name,
                'response_code' => wp_remote_retrieve_response_code( $response )
            );
            
            // Check if we can determine why no sites were found
            if ( isset( $data['value'] ) ) {
                $error_details['has_value_object'] = true;
                $error_details['value_keys'] = is_array( $data['value'] ) ? array_keys( $data['value'] ) : 'not an array';
            } else {
                $error_details['has_value_object'] = false;
            }
            
            if ( isset( $data['error'] ) ) {
                $error_details['api_error'] = $data['error'];
            }
            
            // Log no sites found with detailed information
            USGS_Stream_Gage_Logger::log(
                'No USGS sites found', 
                $error_details,
                USGS_Stream_Gage_Logger::LOG_LEVEL_WARNING
            );
        }
        
        return $sites;
    }

    /**
     * Get current discharge and gage height for a site.
     *
     * @since    1.0.0
     * @param    string    $site_number    The USGS site number.
     * @return   array                     Current discharge and gage height data.
     */
    public function get_current_data( $site_number ) {
        // Check transient cache first
        $cache_key = 'usgs_current_data_' . sanitize_key( $site_number );
        $cached_data = get_transient( $cache_key );
        
        if ( false !== $cached_data ) {
            return $cached_data;
        }
        
        // Build the API URL
        $args = [
            'format' => 'json',
            'sites' => $site_number,
            'parameterCd' => '00060,00065', // Discharge and gage height
            'siteStatus' => 'active'
        ];
        
        // Log the API request
        $url = USGS_Stream_Gage_Logger::log_request( self::USGS_IV_SERVICE_URL, $args );
        
        // Make the API request
        $response = wp_remote_get( $url );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            // Log the error
            USGS_Stream_Gage_Logger::log_error(
                'API Error getting current data',
                $response
            );
            
            return [
                'error' => true,
                'message' => $response->get_error_message()
            ];
        }
        
        // Log the response
        USGS_Stream_Gage_Logger::log_response( self::USGS_IV_SERVICE_URL, $args, $response );
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        $result = [
            'error' => false,
            'site_number' => $site_number,
            'timestamp' => current_time( 'timestamp' ),
            'discharge' => null,
            'discharge_unit' => null,
            'gage_height' => null,
            'gage_height_unit' => null
        ];
        
        // Process the response
        if ( !empty( $data['value']['timeSeries'] ) ) {
            foreach ( $data['value']['timeSeries'] as $series ) {
                // Check for discharge data
                if ( $series['variable']['variableCode'][0]['value'] === '00060' ) {
                    $result['discharge'] = $series['values'][0]['value'][0]['value'];
                    $result['discharge_unit'] = $series['variable']['unit']['unitCode'];
                }
                
                // Check for gage height data
                if ( $series['variable']['variableCode'][0]['value'] === '00065' ) {
                    $result['gage_height'] = $series['values'][0]['value'][0]['value'];
                    $result['gage_height_unit'] = $series['variable']['unit']['unitCode'];
                }
            }
        }
        
        // Cache the result
        set_transient( $cache_key, $result, $this->cache_expiration['current_data'] );
        
        return $result;
    }

    /**
     * Get historical high/low data for a specific time period.
     *
     * @since    1.0.0
     * @param    string    $site_number    The USGS site number.
     * @param    string    $period         Time period ('24h', '7d', '30d', '1y')
     * @return   array                     Historical high/low data.
     */
    public function get_historical_data( $site_number, $period ) {
        // Validate period
        $valid_periods = ['24h', '7d', '30d', '1y'];
        if ( !in_array( $period, $valid_periods ) ) {
            return [
                'error' => true,
                'message' => 'Invalid time period specified.'
            ];
        }
        
        // Check transient cache first
        $cache_key = 'usgs_' . $period . '_data_' . sanitize_key( $site_number );
        $cached_data = get_transient( $cache_key );
        
        if ( false !== $cached_data ) {
            return $cached_data;
        }
        
        // Calculate period start time
        $end_time = current_time( 'timestamp' );
        $start_time = $end_time;
        
        switch ( $period ) {
            case '24h':
                $start_time = strtotime( '-24 hours', $end_time );
                break;
            case '7d':
                $start_time = strtotime( '-7 days', $end_time );
                break;
            case '30d':
                $start_time = strtotime( '-30 days', $end_time );
                break;
            case '1y':
                $start_time = strtotime( '-1 year', $end_time );
                break;
        }
        
        // Format dates for API
        $start_date = date( 'Y-m-d', $start_time );
        $end_date = date( 'Y-m-d', $end_time );
        
        // Build the API URL
        $args = [
            'format' => 'json',
            'sites' => $site_number,
            'startDT' => $start_date,
            'endDT' => $end_date,
            'parameterCd' => '00060,00065', // Discharge and gage height
            'siteStatus' => 'active'
        ];
        
        // Log the API request
        $url = USGS_Stream_Gage_Logger::log_request( self::USGS_IV_SERVICE_URL, $args );
        
        // Make the API request
        $response = wp_remote_get( $url );
        
        // Check for errors
        if ( is_wp_error( $response ) ) {
            // Log the error
            USGS_Stream_Gage_Logger::log_error(
                'API Error getting historical data',
                $response
            );
            
            return [
                'error' => true,
                'message' => $response->get_error_message()
            ];
        }
        
        // Log the response
        USGS_Stream_Gage_Logger::log_response( self::USGS_IV_SERVICE_URL, $args, $response );
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        $result = [
            'error' => false,
            'site_number' => $site_number,
            'period' => $period,
            'timestamp' => current_time( 'timestamp' ),
            'discharge' => [
                'high' => null,
                'high_datetime' => null,
                'low' => null,
                'low_datetime' => null,
                'unit' => null
            ],
            'gage_height' => [
                'high' => null,
                'high_datetime' => null,
                'low' => null,
                'low_datetime' => null,
                'unit' => null
            ]
        ];
        
        // Process the response
        if ( !empty( $data['value']['timeSeries'] ) ) {
            foreach ( $data['value']['timeSeries'] as $series ) {
                $values = $series['values'][0]['value'];
                
                // Skip if no values
                if ( empty( $values ) ) {
                    continue;
                }
                
                // Process discharge data
                if ( $series['variable']['variableCode'][0]['value'] === '00060' ) {
                    $discharge_values = array_filter( array_map( function( $item ) {
                        return $item['value'] !== '' ? floatval( $item['value'] ) : null;
                    }, $values ) );
                    
                    if ( !empty( $discharge_values ) ) {
                        $high_discharge = max( $discharge_values );
                        $low_discharge = min( $discharge_values );
                        
                        // Find datetime for high and low values
                        $high_datetime = null;
                        $low_datetime = null;
                        
                        foreach ( $values as $value ) {
                            if ( $value['value'] == $high_discharge ) {
                                $high_datetime = $value['dateTime'];
                            }
                            if ( $value['value'] == $low_discharge ) {
                                $low_datetime = $value['dateTime'];
                            }
                        }
                        
                        $result['discharge']['high'] = $high_discharge;
                        $result['discharge']['high_datetime'] = $high_datetime;
                        $result['discharge']['low'] = $low_discharge;
                        $result['discharge']['low_datetime'] = $low_datetime;
                        $result['discharge']['unit'] = $series['variable']['unit']['unitCode'];
                    }
                }
                
                // Process gage height data
                if ( $series['variable']['variableCode'][0]['value'] === '00065' ) {
                    $height_values = array_filter( array_map( function( $item ) {
                        return $item['value'] !== '' ? floatval( $item['value'] ) : null;
                    }, $values ) );
                    
                    if ( !empty( $height_values ) ) {
                        $high_height = max( $height_values );
                        $low_height = min( $height_values );
                        
                        // Find datetime for high and low values
                        $high_datetime = null;
                        $low_datetime = null;
                        
                        foreach ( $values as $value ) {
                            if ( $value['value'] == $high_height ) {
                                $high_datetime = $value['dateTime'];
                            }
                            if ( $value['value'] == $low_height ) {
                                $low_datetime = $value['dateTime'];
                            }
                        }
                        
                        $result['gage_height']['high'] = $high_height;
                        $result['gage_height']['high_datetime'] = $high_datetime;
                        $result['gage_height']['low'] = $low_height;
                        $result['gage_height']['low_datetime'] = $low_datetime;
                        $result['gage_height']['unit'] = $series['variable']['unit']['unitCode'];
                    }
                }
            }
        }
        
        // Cache the result
        set_transient( $cache_key, $result, $this->cache_expiration[$period . '_data'] );
        
        return $result;
    }

    /**
     * Format a date in human-readable format.
     *
     * @since    1.0.0
     * @param    string    $date_string    ISO date string from USGS API.
     * @return   string                    Formatted date.
     */
    public function format_date( $date_string ) {
        if ( empty( $date_string ) ) {
            return '';
        }
        
        $timestamp = strtotime( $date_string );
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }
}