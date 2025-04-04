/**
 * USGS Stream Gage - Public JavaScript
 * Handles the public-facing functionality for displaying stream gage data
 */

(function($) {
    'use strict';

    // DOM ready
    $(function() {
        // Tab switching functionality
        $('.usgs-period-tab').on('click', function() {
            var $this = $(this),
                period = $this.data('period'),
                $container = $this.closest('.usgs-stream-gage-data');
            
            // Deactivate all tabs
            $container.find('.usgs-period-tab').removeClass('active');
            
            // Hide all tab contents
            $container.find('.usgs-period-data').hide();
            
            // Activate selected tab
            $this.addClass('active');
            
            // Show selected tab content
            $container.find('.usgs-period-data[data-period="' + period + '"]').show();
        });
    });

})(jQuery);