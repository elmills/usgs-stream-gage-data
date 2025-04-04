<?php
/**
 * GitHub Update Checker
 * 
 * Checks for updates to the plugin using GitHub releases (tags)
 * 
 * @package Plugin Update Checker
 * @version 1.0.0
 */

class Puc_v4_GitHub_UpdateChecker extends Puc_v4p10_Plugin_UpdateChecker {
    protected $repositoryUrl = '';
    protected $branch = 'master';
    protected $username = '';
    protected $repository = '';
    
    /**
     * Class constructor.
     *
     * @param string $repositoryUrl GitHub repository URL (e.g. username/repository-name)
     * @param string $pluginFile Absolute path to the main plugin file.
     * @param string $branch GitHub branch to use for updates. Defaults to "master".
     */
    public function __construct($repositoryUrl, $pluginFile, $branch = 'master') {
        parent::__construct($pluginFile);
        
        // Parse the repository URL to extract username and repository name
        $this->repositoryUrl = $repositoryUrl;
        $this->branch = $branch;
        
        $this->parseRepositoryUrl($repositoryUrl);
    }
    
    /**
     * Parse the repository URL to extract username and repository name.
     *
     * @param string $repositoryUrl GitHub repository URL (e.g. username/repository-name)
     */
    protected function parseRepositoryUrl($repositoryUrl) {
        // Remove https://github.com/ if present
        $repositoryUrl = preg_replace('!^https?://github.com/!i', '', $repositoryUrl);
        
        // Split by slash to get username and repository name
        $parts = explode('/', $repositoryUrl);
        if (count($parts) >= 2) {
            $this->username = $parts[0];
            $this->repository = $parts[1];
            
            // Remove .git suffix if present
            $this->repository = preg_replace('/\.git$/', '', $this->repository);
        }
    }
    
    /**
     * Request plugin information for display in the WordPress Details window.
     * 
     * @return stdClass|null Plugin info or null if no info is available.
     */
    protected function requestInfo() {
        // Get the latest release info
        $releaseInfo = $this->getLatestRelease();
        
        if (!$releaseInfo) {
            return null;
        }
        
        // Convert to WordPress plugin info format
        $info = new stdClass();
        $info->name = basename(dirname($this->pluginFile));
        $info->slug = basename(dirname($this->pluginFile));
        $info->version = ltrim($releaseInfo->tag_name, 'v');
        $info->homepage = $releaseInfo->html_url;
        $info->download_url = $releaseInfo->zipball_url;
        $info->sections = array(
            'description' => isset($releaseInfo->body) ? $releaseInfo->body : 'GitHub release',
        );
        
        return $info;
    }
    
    /**
     * Request plugin update info.
     * 
     * @return stdClass|null Plugin info or null if no info is available.
     */
    protected function requestUpdate() {
        // Get the latest release info
        $releaseInfo = $this->getLatestRelease();
        
        if (!$releaseInfo) {
            return null;
        }
        
        // Check if the latest release is newer than the current version
        $currentVersion = $this->getInstalledVersion();
        $latestVersion = ltrim($releaseInfo->tag_name, 'v');
        
        if (version_compare($latestVersion, $currentVersion, '<=')) {
            return null;
        }
        
        // Convert to WordPress update format
        $update = new stdClass();
        $update->slug = basename(dirname($this->pluginFile));
        $update->plugin = plugin_basename($this->pluginFile);
        $update->new_version = $latestVersion;
        $update->url = $releaseInfo->html_url;
        $update->package = $releaseInfo->zipball_url;
        
        return $update;
    }
    
    /**
     * Get the installed version of the plugin.
     *
     * @return string Version number.
     */
    protected function getInstalledVersion() {
        $pluginHeader = get_file_data($this->pluginFile, array('Version' => 'Version'));
        return isset($pluginHeader['Version']) ? $pluginHeader['Version'] : '';
    }
    
    /**
     * Get the latest release information from GitHub.
     *
     * @return object|null Release info object or null on failure.
     */
    protected function getLatestRelease() {
        if (empty($this->username) || empty($this->repository)) {
            return null;
        }
        
        // Check if we have cached release info
        $cacheKey = 'puc_gh_release_' . md5($this->username . '/' . $this->repository);
        $releaseInfo = get_transient($cacheKey);
        
        if (false === $releaseInfo) {
            // API URL for the latest release
            $apiUrl = sprintf(
                'https://api.github.com/repos/%s/%s/releases/latest',
                $this->username,
                $this->repository
            );
            
            // Make the API request
            $response = wp_remote_get($apiUrl, array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
                ),
            ));
            
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return null;
            }
            
            $releaseInfo = json_decode(wp_remote_retrieve_body($response));
            
            // Cache for 6 hours
            set_transient($cacheKey, $releaseInfo, 6 * HOUR_IN_SECONDS);
        }
        
        return is_object($releaseInfo) ? $releaseInfo : null;
    }
}