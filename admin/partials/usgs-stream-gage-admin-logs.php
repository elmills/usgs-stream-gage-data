<?php
/**
 * Provide a admin area view for logs
 *
 * This file is used to display API logs for troubleshooting.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    USGS_Stream_Gage_Data
 * @subpackage USGS_Stream_Gage_Data/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get logs from logger
require_once USGS_STREAM_GAGE_PLUGIN_DIR . 'includes/class-usgs-stream-gage-logger.php';
$logs = USGS_Stream_Gage_Logger::get_logs();
$logs_stats = USGS_Stream_Gage_Logger::get_logs_stats();
?>

<div class="usgs-logs-container">
    <div class="usgs-logs-header">
        <h3><?php esc_html_e( 'API Logs', 'usgs-stream-gage-data' ); ?></h3>
        <p><?php esc_html_e( 'View logs of API requests, responses, and errors to help troubleshoot issues with USGS stream gage data.', 'usgs-stream-gage-data' ); ?></p>
    </div>
    
    <div class="usgs-logs-toolbar">
        <div class="usgs-logs-filters">
            <select id="usgs-log-level-filter">
                <option value=""><?php esc_html_e( 'All Levels', 'usgs-stream-gage-data' ); ?></option>
                <option value="info"><?php esc_html_e( 'Info', 'usgs-stream-gage-data' ); ?> (<?php echo esc_html( isset( $logs_stats['info'] ) ? $logs_stats['info'] : 0 ); ?>)</option>
                <option value="debug"><?php esc_html_e( 'Debug', 'usgs-stream-gage-data' ); ?> (<?php echo esc_html( isset( $logs_stats['debug'] ) ? $logs_stats['debug'] : 0 ); ?>)</option>
                <option value="warning"><?php esc_html_e( 'Warning', 'usgs-stream-gage-data' ); ?> (<?php echo esc_html( isset( $logs_stats['warning'] ) ? $logs_stats['warning'] : 0 ); ?>)</option>
                <option value="error"><?php esc_html_e( 'Error', 'usgs-stream-gage-data' ); ?> (<?php echo esc_html( isset( $logs_stats['error'] ) ? $logs_stats['error'] : 0 ); ?>)</option>
            </select>
            
            <button type="button" class="button" id="usgs-apply-log-filter"><?php esc_html_e( 'Apply Filter', 'usgs-stream-gage-data' ); ?></button>
        </div>
        
        <div class="usgs-logs-actions">
            <button type="button" class="button button-secondary" id="usgs-clear-logs"><?php esc_html_e( 'Clear Logs', 'usgs-stream-gage-data' ); ?></button>
        </div>
    </div>
    
    <div class="usgs-logs-table-wrapper">
        <table class="widefat usgs-logs-table">
            <thead>
                <tr>
                    <th class="log-time"><?php esc_html_e( 'Timestamp', 'usgs-stream-gage-data' ); ?></th>
                    <th class="log-level"><?php esc_html_e( 'Level', 'usgs-stream-gage-data' ); ?></th>
                    <th class="log-message"><?php esc_html_e( 'Message', 'usgs-stream-gage-data' ); ?></th>
                    <th class="log-data"><?php esc_html_e( 'Details', 'usgs-stream-gage-data' ); ?></th>
                </tr>
            </thead>
            <tbody id="usgs-logs-list">
                <?php if ( empty( $logs ) ) : ?>
                    <tr class="no-logs">
                        <td colspan="4"><?php esc_html_e( 'No logs available.', 'usgs-stream-gage-data' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <?php 
                        $level_class = '';
                        switch ( $log['level'] ) {
                            case 'error':
                                $level_class = 'log-level-error';
                                break;
                            case 'warning':
                                $level_class = 'log-level-warning';
                                break;
                            case 'debug':
                                $level_class = 'log-level-debug';
                                break;
                            default:
                                $level_class = 'log-level-info';
                        }
                        ?>
                        <tr class="<?php echo esc_attr( $level_class ); ?>" data-log-level="<?php echo esc_attr( $log['level'] ); ?>">
                            <td class="log-time"><?php echo esc_html( $log['timestamp'] ); ?></td>
                            <td class="log-level"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></td>
                            <td class="log-message"><?php echo esc_html( $log['message'] ); ?></td>
                            <td class="log-data">
                                <?php if ( !empty( $log['data'] ) ) : ?>
                                    <button type="button" class="button button-small toggle-log-details"><?php esc_html_e( 'Show Details', 'usgs-stream-gage-data' ); ?></button>
                                    <div class="log-details" style="display: none;">
                                        <pre><?php echo esc_html( json_encode( $log['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
                                    </div>
                                <?php else : ?>
                                    <span class="log-no-data"><?php esc_html_e( 'No data', 'usgs-stream-gage-data' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div id="usgs-logs-message" class="notice" style="display: none;"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle log details
    $('.toggle-log-details').on('click', function() {
        var $details = $(this).next('.log-details');
        if ($details.is(':visible')) {
            $details.hide();
            $(this).text('<?php echo esc_js( __( 'Show Details', 'usgs-stream-gage-data' ) ); ?>');
        } else {
            $details.show();
            $(this).text('<?php echo esc_js( __( 'Hide Details', 'usgs-stream-gage-data' ) ); ?>');
        }
    });
    
    // Apply log filter
    $('#usgs-apply-log-filter').on('click', function() {
        var level = $('#usgs-log-level-filter').val();
        if (level === '') {
            $('.usgs-logs-table tbody tr').show();
        } else {
            $('.usgs-logs-table tbody tr').hide();
            $('.usgs-logs-table tbody tr[data-log-level="' + level + '"]').show();
            
            // Show "no logs" message if no logs match the filter
            if ($('.usgs-logs-table tbody tr[data-log-level="' + level + '"]').length === 0) {
                if ($('.no-filtered-logs').length === 0) {
                    $('.usgs-logs-table tbody').append('<tr class="no-filtered-logs"><td colspan="4"><?php echo esc_js( __( 'No logs match the selected filter.', 'usgs-stream-gage-data' ) ); ?></td></tr>');
                }
            } else {
                $('.no-filtered-logs').remove();
            }
        }
    });
    
    // Clear logs
    $('#usgs-clear-logs').on('click', function() {
        if (confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs? This cannot be undone.', 'usgs-stream-gage-data' ) ); ?>')) {
            var data = {
                'action': 'usgs_clear_logs',
                'nonce': usgs_ajax.nonce
            };
            
            $.post(usgs_ajax.ajax_url, data, function(response) {
                if (response.success) {
                    $('#usgs-logs-list').html('<tr class="no-logs"><td colspan="4"><?php echo esc_js( __( 'No logs available.', 'usgs-stream-gage-data' ) ); ?></td></tr>');
                    $('#usgs-logs-message').removeClass('notice-error').addClass('notice-success').html('<p>' + response.data.message + '</p>').show();
                    
                    // Update log stats in filter dropdown
                    $('#usgs-log-level-filter option[value="info"]').text('<?php echo esc_js( __( 'Info', 'usgs-stream-gage-data' ) ); ?> (0)');
                    $('#usgs-log-level-filter option[value="debug"]').text('<?php echo esc_js( __( 'Debug', 'usgs-stream-gage-data' ) ); ?> (0)');
                    $('#usgs-log-level-filter option[value="warning"]').text('<?php echo esc_js( __( 'Warning', 'usgs-stream-gage-data' ) ); ?> (0)');
                    $('#usgs-log-level-filter option[value="error"]').text('<?php echo esc_js( __( 'Error', 'usgs-stream-gage-data' ) ); ?> (0)');
                } else {
                    $('#usgs-logs-message').removeClass('notice-success').addClass('notice-error').html('<p>' + response.data.message + '</p>').show();
                }
            });
        }
    });
});
</script>

<style type="text/css">
.usgs-logs-toolbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    align-items: center;
}

.usgs-logs-filters {
    display: flex;
    align-items: center;
    gap: 10px;
}

.usgs-logs-table-wrapper {
    margin-bottom: 20px;
    max-height: 600px;
    overflow-y: auto;
}

.usgs-logs-table .log-time {
    width: 15%;
    white-space: nowrap;
}

.usgs-logs-table .log-level {
    width: 10%;
    white-space: nowrap;
}

.usgs-logs-table .log-message {
    width: 35%;
}

.usgs-logs-table .log-data {
    width: 40%;
}

.usgs-logs-table .log-level-error {
    background-color: #ffebeb;
}

.usgs-logs-table .log-level-warning {
    background-color: #fff8e5;
}

.usgs-logs-table .log-level-debug {
    background-color: #f5f5f5;
}

.usgs-logs-table .log-details {
    margin-top: 10px;
    background-color: #f9f9f9;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    max-height: 300px;
    overflow: auto;
}

.usgs-logs-table .log-details pre {
    margin: 0;
    white-space: pre-wrap;
}
</style>