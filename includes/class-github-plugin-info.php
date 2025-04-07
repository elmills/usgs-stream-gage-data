<?php
/**
 * GitHub Plugin Updater
 *
 * @package   GitHubPluginUpdater
 * @author    Everette Mills
 * @license   GPL-2.0+
 * @link      https://blueboatsolutions.com
 *
 * This class handles WordPress plugin updates from GitHub repositories.
 * 
 * Requires: https://github.com/YahnisElsts/plugin-update-checker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('GitHub_Plugin_Updater')) {

    /**
     * GitHub Plugin Updater Class
     */
    class GitHub_Plugin_Updater {

        /**
         * The update checker instance.
         *
         * @var object
         */
        private $update_checker;

        /**
         * Main plugin file path.
         *
         * @var string
         */
        private $plugin_file;

        /**
         * Plugin slug.
         *
         * @var string
         */
        private $plugin_slug;

        /**
         * GitHub repository URL.
         *
         * @var string
         */
        private $repository_url;

        /**
         * GitHub branch name.
         *
         * @var string
         */
        private $branch;

        /**
         * GitHub access token (for private repositories).
         *
         * @var string|null
         */
        private $access_token;

        /**
         * Metadata for the readme.txt file.
         *
         * @var array
         */
        private $metadata;

        /**
         * Initialize the updater.
         *
         * @param string $plugin_file   Main plugin file path (__FILE__).
         * @param string $plugin_slug   Plugin slug (should match directory name).
         * @param string $repository    GitHub repository URL.
         * @param string $branch        GitHub branch name (default: 'main').
         * @param string $access_token  GitHub access token for private repos (optional).
         * @param array  $metadata      Additional metadata for readme.txt (optional).
         */
        public function __construct($plugin_file, $plugin_slug, $repository, $branch = 'main', $access_token = null, $metadata = []) {
            // Check if the update checker library exists
            if (!file_exists(dirname($plugin_file) . '/plugin-update-checker/plugin-update-checker.php')) {
                add_action('admin_notices', [$this, 'missing_library_notice']);
                return;
            }

            require_once dirname($plugin_file) . '/plugin-update-checker/plugin-update-checker.php';

            $this->plugin_file = $plugin_file;
            $this->plugin_slug = $plugin_slug;
            $this->repository_url = $repository;
            $this->branch = $branch;
            $this->access_token = $access_token;
            
            // Default metadata
            $default_metadata = [
                'contributors' => 'elmills',
                'donate_link' => 'https://example.com/donate',
                'tags' => 'plugin, wordpress',
                'requires_php' => '7.2',
                'license' => 'GPLv2 or later',
                'license_uri' => 'https://www.gnu.org/licenses/gpl-2.0.html'
            ];
            
            $this->metadata = wp_parse_args($metadata, $default_metadata);

            // Initialize the updater
            $this->init();
        }

        /**
         * Initialize the update checker and hooks.
         */
        private function init() {
            // Set up the update checker
            $this->setup_update_checker();
            
            // Set up hooks for plugin updates
            
            // Filter plugin info to ensure proper details
            add_filter("puc_request_info_result-{$this->plugin_slug}", [$this, 'ensure_plugin_info'], 10, 2);
            
            // Hook directly into WordPress plugin API for "View Details"
            add_filter('plugins_api', [$this, 'plugin_info_hook'], 10, 3);
        }

        /**
         * Set up the plugin update checker.
         */
        private function setup_update_checker() {
            $this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                $this->repository_url,
                $this->plugin_file,
                $this->plugin_slug
            );
            
            // Force checking GitHub releases instead of branch
            if (method_exists($this->update_checker, 'getVcsApi')) {
                // Enable release assets (for GitHub releases)
                $this->update_checker->getVcsApi()->enableReleaseAssets();

                // Force checking of releases
                // Use setReleaseVersionFilter with a regex that matches any version
                if (method_exists($this->update_checker->getVcsApi(), 'setReleaseVersionFilter')) {
                    $this->update_checker->getVcsApi()->setReleaseVersionFilter('/.*/', \YahnisElsts\PluginUpdateChecker\v5p5\Vcs\Api::RELEASE_FILTER_ALL);
                    add_filter('puc_request_info_query_args-' . $this->plugin_slug, function($args) {
                        $args['preferReleasedUpdates'] = true;
                        return $args;
                    });
                }
            }
            
            // Set the branch as fallback if no releases are found
            $this->update_checker->setBranch($this->branch);
            
            // Set authentication for private repositories
            if (!empty($this->access_token)) {
                $this->update_checker->setAuthentication($this->access_token);
            }
            
            // Clear update cache on initialization
            delete_site_transient('update_plugins');
            delete_site_transient('puc_check_count_' . $this->plugin_slug);
            delete_site_transient('puc_request_info_' . $this->plugin_slug);
        }

        /**
         * Display admin notice if the update checker library is missing.
         */
        public function missing_library_notice() {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php printf(
                        esc_html__('The %1$s plugin requires the Plugin Update Checker library. Please download it from %2$s and add it to your plugin directory.', 'github-plugin-updater'),
                        '<strong>' . esc_html(get_plugin_data($this->plugin_file)['Name']) . '</strong>',
                        '<a href="https://github.com/YahnisElsts/plugin-update-checker" target="_blank">GitHub</a>'
                    ); ?>
                </p>
            </div>
            <?php
        }

    /**
     * Get plugin information for update process.
     * 
     * @return array Plugin information for update process
     */
    public function get_plugin_info() {
        // Get plugin data from the plugin file header
        if (function_exists('get_plugin_data')) {
            $plugin_data = get_plugin_data($this->plugin_file);
        } else {
            // Fallback if function not available
            $plugin_data = [
                'Name' => 'Frost Date Lookup',
                'Version' => '1.0.32',
                'Author' => 'Everette Mills',
                'AuthorURI' => 'https://blueboatsolutions.com',
                'Description' => 'A plugin to retrieve average frost-free dates based on zip code using NOAA/NWS data.'
            ];
        }
        
        return array_merge($plugin_data, [
            'plugin_slug' => $this->plugin_slug,
            'repository_url' => $this->repository_url,
            'branch' => $this->branch
        ]);
    }

        /**
         * Ensure plugin info is properly set in the "View Details" dialog.
         *
         * @param object $info     Plugin info object.
         * @param mixed  $response Response from the update API.
         * @return object Modified plugin info object.
         */
        public function ensure_plugin_info($info, $response) {
            // Process plugin info
            return $info;
        }
        
        /**
         * Hook directly into the WordPress plugin information API for the "View Details" screen
         *
         * @param false|object|array $result The result object or array. Default false.
         * @param string $action The type of information being requested.
         * @param object $args Plugin API arguments.
         * @return false|object Plugin information or false if not our plugin.
         */
        public function plugin_info_hook($result, $action, $args) {
            // Only handle plugin information requests for our plugin
            if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->plugin_slug) {
                return $result;
            }
            
            // Parse readme.txt file if it exists
            
            // Get plugin data
            $plugin_data = get_plugin_data($this->plugin_file);
            
            // Read the readme.txt file
            $readme_txt_path = plugin_dir_path($this->plugin_file) . 'readme.txt';
            if (!file_exists($readme_txt_path)) {
                return $result; // If no readme.txt, let WordPress handle it
            }
            
            // Parse the readme.txt file content
            $readme_txt_content = file_get_contents($readme_txt_path);
            
            // Create object with plugin info
            $info = new stdClass();
            $info->name = $plugin_data['Name'];
            $info->slug = $this->plugin_slug;
            $info->version = $plugin_data['Version'];
            $info->author = $plugin_data['Author'];
            $info->author_profile = $plugin_data['AuthorURI'];
            $info->requires = '5.0'; // Default value
            $info->tested = get_bloginfo('version');
            $info->requires_php = '7.0'; // Default value
            $info->last_updated = date('Y-m-d');
            $info->sections = [];
            
            // Debug the readme content if enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Readme.txt content length: ' . strlen($readme_txt_content));
                error_log('First 200 chars: ' . substr($readme_txt_content, 0, 200));
            }
            
            // Parse sections using improved regular expressions that handle different format variations
            $sections = array(
                'description'  => array('pattern' => '/(?:^|\n)[ \t]*==[ \t]*Description[ \t]*==\s*(.*?)(?=\n[ \t]*==[ \t]*|$)/s'),
                'installation' => array('pattern' => '/(?:^|\n)[ \t]*==[ \t]*Installation[ \t]*==\s*(.*?)(?=\n[ \t]*==[ \t]*|$)/s'),
                'faq'          => array('pattern' => '/(?:^|\n)[ \t]*==[ \t]*(?:Frequently Asked Questions|FAQ)[ \t]*==\s*(.*?)(?=\n[ \t]*==[ \t]*|$)/s'),
                'changelog'    => array('pattern' => '/(?:^|\n)[ \t]*==[ \t]*Changelog[ \t]*==\s*(.*?)(?=\n[ \t]*==[ \t]*|$)/s'),
                'screenshots'  => array('pattern' => '/(?:^|\n)[ \t]*==[ \t]*Screenshots[ \t]*==\s*(.*?)(?=\n[ \t]*==[ \t]*|$)/s')
            );
            
            // Extract each section
            foreach ($sections as $section_key => $section_data) {
                preg_match($section_data['pattern'], $readme_txt_content, $matches);
                if (!empty($matches[1])) {
                    $info->sections[$section_key] = trim($matches[1]);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Found $section_key section with " . strlen($info->sections[$section_key]) . " chars");
                    }
                } else {
                    // Section not found in readme
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("$section_key section not found in readme.txt");
                    }
                }
            }
            
            // If description section is empty, use the plugin description as fallback
            if (empty($info->sections['description'])) {
                $info->sections['description'] = isset($plugin_data['Description']) ? $plugin_data['Description'] : '';
            }
            
            // If no sections were extracted, provide some defaults
            if (empty($info->sections)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("No sections found in readme.txt - using fallbacks");
                }
                $info->sections['description'] = isset($plugin_data['Description']) ? $plugin_data['Description'] : '';
                $info->sections['installation'] = 'Please see the plugin documentation for installation instructions.';
                $info->sections['changelog'] = "= {$info->version} =\n* Initial release.";
            }
            
            // Parse additional metadata
            preg_match('/=== (.*?) ===/', $readme_txt_content, $plugin_name_matches);
            preg_match('/Contributors: (.*?)\\n/', $readme_txt_content, $contributors_matches);
            preg_match('/Tags: (.*?)\\n/', $readme_txt_content, $tags_matches);
            preg_match('/Requires at least: (.*?)\\n/', $readme_txt_content, $requires_matches);
            preg_match('/Tested up to: (.*?)\\n/', $readme_txt_content, $tested_matches);
            preg_match('/Requires PHP: (.*?)\\n/', $readme_txt_content, $requires_php_matches);
            
            // Set additional metadata
            if (isset($plugin_name_matches[1])) $info->name = $plugin_name_matches[1];
            if (isset($contributors_matches[1])) {
                $contributors = array_map('trim', explode(',', $contributors_matches[1]));
                $info->contributors = is_array($contributors) ? $contributors : array($contributors_matches[1]);
            }
            if (isset($tags_matches[1])) {
                $tags = array_map('trim', explode(',', $tags_matches[1]));
                $info->tags = is_array($tags) ? $tags : array($tags_matches[1]);
            }
            if (isset($requires_matches[1])) $info->requires = $requires_matches[1];
            if (isset($tested_matches[1])) $info->tested = $tested_matches[1];
            if (isset($requires_php_matches[1])) $info->requires_php = $requires_php_matches[1];
            
            // Add homepage URL
            $info->homepage = $plugin_data['PluginURI'] ?: $this->repository_url;
            
            // Add download link pointing to GitHub repo
            $info->download_link = sprintf('%s/archive/refs/heads/%s.zip', $this->repository_url, $this->branch);
            
            // Set external flag to prevent "Attempt to assign property 'external' on null" error
            $info->external = true;
            
            return $info;
        }

        /**
         * Get the update checker instance.
         *
         * @return object Update checker instance.
         */
        public function get_update_checker() {
            return $this->update_checker;
        }
    }
}