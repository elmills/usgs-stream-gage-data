/**
 * USGS Stream Gage - Admin JavaScript
 * Handles the admin interface functionality for managing stream gage sites
 */

(function($) {
    'use strict';

    // DOM ready
    $(function() {
        // Cache selectors
        var $addMethod = $('input[name="usgs-add-method"]'),
            $addByNumber = $('.usgs-add-by-number'),
            $searchSites = $('.usgs-search-sites'),
            $siteNumber = $('#usgs-site-number'),
            $validateSiteBtn = $('#usgs-validate-site'),
            $siteSearch = $('#usgs-site-search'),
            $searchSitesBtn = $('#usgs-search-sites'),
            $searchResults = $('#usgs-search-results'),
            $messageContainer = $('#usgs-message'),
            $sitesList = $('#usgs-sites-list');

        // Toggle between "Add by Site Number" and "Search for Site"
        $addMethod.on('change', function() {
            var method = $(this).val();
            
            if (method === 'number') {
                $addByNumber.show();
                $searchSites.hide();
            } else if (method === 'search') {
                $addByNumber.hide();
                $searchSites.show();
            }
        });
        
        // Validate site number via AJAX
        $validateSiteBtn.on('click', function() {
            var siteNumber = $siteNumber.val().trim();
            
            if (!siteNumber) {
                showMessage('error', 'Please enter a site number.');
                return;
            }
            
            // Start loading indicator
            var $spinner = $(this).siblings('.spinner');
            $spinner.addClass('is-active');
            $validateSiteBtn.prop('disabled', true);
            
            // Clear previous messages
            $messageContainer.empty().hide();
            
            // Send AJAX request
            $.ajax({
                url: usgs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'usgs_validate_site',
                    nonce: usgs_ajax.nonce,
                    site_number: siteNumber
                },
                success: function(response) {
                    if (response.success) {
                        // Add site to the list
                        addSiteToList(response.data.site);
                        
                        // Show success message
                        showMessage('success', response.data.message);
                        
                        // Clear input
                        $siteNumber.val('');
                    } else {
                        // Show error message
                        showMessage('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('error', 'An error occurred: ' + error);
                },
                complete: function() {
                    // Stop loading indicator
                    $spinner.removeClass('is-active');
                    $validateSiteBtn.prop('disabled', false);
                }
            });
        });
        
        // Search for sites via AJAX
        $searchSitesBtn.on('click', function() {
            var searchTerm = $siteSearch.val().trim();
            
            if (!searchTerm) {
                showMessage('error', 'Please enter a search term.');
                return;
            }
            
            // Start loading indicator
            var $spinner = $(this).siblings('.spinner');
            $spinner.addClass('is-active');
            $searchSitesBtn.prop('disabled', true);
            
            // Clear previous messages and results
            $messageContainer.empty().hide();
            $searchResults.empty();
            
            // Send AJAX request
            $.ajax({
                url: usgs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'usgs_search_sites',
                    nonce: usgs_ajax.nonce,
                    search_term: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        // Display search results
                        displaySearchResults(response.data.sites);
                        
                        // Show success message
                        showMessage('success', response.data.message);
                    } else {
                        // Show error message
                        showMessage('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('error', 'An error occurred: ' + error);
                },
                complete: function() {
                    // Stop loading indicator
                    $spinner.removeClass('is-active');
                    $searchSitesBtn.prop('disabled', false);
                }
            });
        });
        
        // Handle click on search result
        $searchResults.on('click', '.usgs-search-result', function() {
            var siteData = $(this).data('site');
            
            // Add site to the list
            addSiteToList(siteData);
            
            // Show success message
            showMessage('success', 'Site added successfully.');
            
            // Clear search input and results
            $siteSearch.val('');
            $searchResults.empty();
        });
        
        // Handle site removal
        $sitesList.on('click', '.remove-site', function() {
            $(this).closest('tr').remove();
            
            // Show message if no sites remain
            if ($sitesList.find('tr').length === 0) {
                $sitesList.html('<tr class="no-sites"><td colspan="4">No sites have been added yet.</td></tr>');
            }
        });
        
        // Copy shortcode to clipboard
        $sitesList.on('click', '.copy-shortcode', function() {
            var $code = $(this).prev('code'),
                shortcode = $code.text(),
                tempInput = $('<input>');
            
            // Create temporary input element to copy from
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Provide visual feedback
            var $button = $(this);
            $button.text('Copied!');
            
            setTimeout(function() {
                $button.text('Copy');
            }, 1500);
        });
        
        /**
         * Display search results in a list
         * 
         * @param {Array} sites Array of site objects
         */
        function displaySearchResults(sites) {
            var html = '';
            
            $.each(sites, function(index, site) {
                html += '<div class="usgs-search-result" data-site=\'' + JSON.stringify(site) + '\'>';
                html += '<div class="usgs-search-result-name">' + site.site_name + '</div>';
                html += '<div class="usgs-search-result-number">USGS ' + site.site_number + '</div>';
                html += '</div>';
            });
            
            $searchResults.html(html);
        }
        
        /**
         * Add site to the list of sites
         * 
         * @param {Object|string} site Site data object or JSON string
         */
        function addSiteToList(site) {
            // Ensure site is an object
            if (typeof site === 'string') {
                try {
                    site = JSON.parse(site);
                } catch (e) {
                    console.error('Failed to parse site data:', e);
                    showMessage('error', 'Error processing site data. Please try again.');
                    return;
                }
            }
            
            // Ensure site is an object before continuing
            if (typeof site !== 'object' || site === null) {
                console.error('Invalid site data:', site);
                showMessage('error', 'Invalid site data format. Please try again.');
                return;
            }
            
            // Generate unique ID if not present
            if (!site.id) {
                site.id = 'usgs_' + Math.random().toString(36).substr(2, 9);
            }
            
            // Set validation flag
            site.is_validated = true;
            
            // Remove "no sites" message if present
            if ($sitesList.find('.no-sites').length) {
                $sitesList.empty();
            }
            
            // Get current site count for input name
            var siteCount = $sitesList.find('tr').length;
            
            // Create HTML for the new site row
            var html = '<tr data-site-id="' + site.id + '">';
            html += '<td>' + site.site_name + '</td>';
            html += '<td>' + site.site_number + '</td>';
            html += '<td>';
            html += '<code>[usgs_stream_gage id="' + site.id + '"]</code>';
            html += '<button type="button" class="button button-small copy-shortcode">Copy</button>';
            html += '</td>';
            html += '<td>';
            html += '<button type="button" class="button button-small remove-site">Remove</button>';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][id]" value="' + site.id + '">';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][site_number]" value="' + site.site_number + '">';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][site_name]" value="' + site.site_name + '">';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][latitude]" value="' + site.latitude + '">';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][longitude]" value="' + site.longitude + '">';
            html += '<input type="hidden" name="usgs_stream_gage_sites[' + siteCount + '][is_validated]" value="1">';
            html += '</td>';
            html += '</tr>';
            
            // Append to list
            $sitesList.append(html);
        }
        
        /**
         * Show message in the message container
         * 
         * @param {string} type Message type ('error' or 'success')
         * @param {string} message Message text
         */
        function showMessage(type, message) {
            $messageContainer
                .removeClass('notice-error notice-success')
                .addClass(type === 'error' ? 'notice-error' : 'notice-success')
                .html('<p>' + message + '</p>')
                .show();
        }
    });

})(jQuery);