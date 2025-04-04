<?php
/**
 * USGS Stream Gage Logger
 *
 * Handles logging API interactions for troubleshooting purposes.
 *
 * @since      1.1.0
 * @package    USGS_Stream_Gage
 */

class USGS_Stream_Gage_Logger {

    /**
     * Option name for storing logs
     */
    const LOG_OPTION_NAME = 'usgs_stream_gage_api_logs';

    /**
     * Maximum number of logs to keep
     */
    const MAX_LOGS = 100;

    /**
     * Log levels
     */
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_DEBUG = 'debug';

    /**
     * Add a log entry
     *
     * @param string $message Log message
     * @param array  $data    Additional data to log
     * @param string $level   Log level (info, error, warning, debug)
     * @return bool           Whether the log was saved
     */
    public static function log( $message, $data = array(), $level = self::LOG_LEVEL_INFO ) {
        // Create log entry
        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'message'   => $message,
            'data'      => $data,
            'level'     => $level,
        );

        // Get existing logs
        $logs = self::get_logs();

        // Add new log at the beginning
        array_unshift( $logs, $log_entry );

        // Trim logs if needed
        if ( count( $logs ) > self::MAX_LOGS ) {
            $logs = array_slice( $logs, 0, self::MAX_LOGS );
        }

        // Save logs
        return update_option( self::LOG_OPTION_NAME, $logs );
    }

    /**
     * Log an API request
     *
     * @param string $endpoint API endpoint
     * @param array  $args     Request arguments
     * @return void
     */
    public static function log_request( $endpoint, $args = array() ) {
        $url = add_query_arg( $args, $endpoint );
        
        self::log(
            'API Request',
            array(
                'endpoint' => $endpoint,
                'args'     => $args,
                'url'      => $url,
            ),
            self::LOG_LEVEL_DEBUG
        );
        
        return $url;
    }

    /**
     * Log an API response
     *
     * @param string $endpoint API endpoint
     * @param array  $args     Request arguments
     * @param mixed  $response API response
     * @return void
     */
    public static function log_response( $endpoint, $args, $response ) {
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        $log_data = array(
            'endpoint'    => $endpoint,
            'args'        => $args,
            'status_code' => $status_code,
        );
        
        // Log a condensed version of the response body to keep logs manageable
        $response_json = json_decode( $body, true );
        if ( $response_json ) {
            // For API responses, just log the basic structure and error info
            $log_data['response_summary'] = array(
                'has_data' => !empty( $response_json ),
            );
            
            if ( isset( $response_json['error'] ) ) {
                $log_data['response_summary']['error'] = $response_json['error'];
            }
            
            // For validation responses, check if site was found
            if ( isset( $response_json['value']['timeSeries'] ) ) {
                $log_data['response_summary']['time_series_count'] = count( $response_json['value']['timeSeries'] );
            }
            
            // For site search responses, check if sites were found
            if ( isset( $response_json['value']['sites'] ) ) {
                $log_data['response_summary']['sites_count'] = count( $response_json['value']['sites'] );
            }
        } else {
            // Not valid JSON, log a substring
            $log_data['response_preview'] = substr( $body, 0, 500 ) . (strlen( $body ) > 500 ? '...' : '');
        }
        
        $level = ($status_code >= 200 && $status_code < 300) ? self::LOG_LEVEL_DEBUG : self::LOG_LEVEL_ERROR;
        
        self::log(
            'API Response',
            $log_data,
            $level
        );
    }

    /**
     * Log an API error
     *
     * @param string    $message Error message
     * @param mixed     $error   Error object or details
     * @return void
     */
    public static function log_error( $message, $error ) {
        $error_data = is_wp_error( $error ) ? array(
            'code'    => $error->get_error_code(),
            'message' => $error->get_error_message(),
            'data'    => $error->get_error_data(),
        ) : $error;
        
        self::log(
            $message,
            $error_data,
            self::LOG_LEVEL_ERROR
        );
    }

    /**
     * Get all logs
     *
     * @param string $level Filter logs by level (optional)
     * @param int    $limit Maximum number of logs to return (optional)
     * @return array        Array of log entries
     */
    public static function get_logs( $level = null, $limit = null ) {
        $logs = get_option( self::LOG_OPTION_NAME, array() );
        
        // Filter by level if specified
        if ( $level ) {
            $logs = array_filter( $logs, function( $log ) use ( $level ) {
                return $log['level'] === $level;
            });
        }
        
        // Apply limit if specified
        if ( $limit && count( $logs ) > $limit ) {
            $logs = array_slice( $logs, 0, $limit );
        }
        
        return $logs;
    }

    /**
     * Clear all logs
     *
     * @return bool Whether the logs were cleared
     */
    public static function clear_logs() {
        return update_option( self::LOG_OPTION_NAME, array() );
    }

    /**
     * Get logs count
     *
     * @return int Number of logs
     */
    public static function get_logs_count() {
        $logs = get_option( self::LOG_OPTION_NAME, array() );
        return count( $logs );
    }

    /**
     * Get logs grouped by level
     *
     * @return array Counts of logs by level
     */
    public static function get_logs_stats() {
        $logs = get_option( self::LOG_OPTION_NAME, array() );
        
        $stats = array(
            'total'   => count( $logs ),
            'info'    => 0,
            'error'   => 0,
            'warning' => 0,
            'debug'   => 0,
        );
        
        foreach ( $logs as $log ) {
            if ( isset( $log['level'] ) && isset( $stats[$log['level']] ) ) {
                $stats[$log['level']]++;
            }
        }
        
        return $stats;
    }
}