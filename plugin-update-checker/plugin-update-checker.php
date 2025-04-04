<?php
/**
 * Plugin Update Checker Library for WordPress plugins.
 * 
 * This is a simplified version of the Plugin Update Checker by YahnisElsts.
 * Original library: https://github.com/YahnisElsts/plugin-update-checker
 * 
 * @package Plugin Update Checker
 * @version 1.0.0
 */

if (!class_exists('Puc_v4_Factory')):

class Puc_v4_Factory {
    /**
     * Create a GitHub-based update checker.
     *
     * @param string $repositoryUrl GitHub repository URL in this format: username/repository-name
     * @param string $pluginFile Absolute path to the main plugin file.
     * @param string $branch GitHub branch to use for updates. Defaults to "master".
     * @return Puc_v4p10_Plugin_UpdateChecker
     */
    public static function buildGitHubUpdater($repositoryUrl, $pluginFile, $branch = 'master') {
        require_once dirname(__FILE__) . '/Puc_v4_GitHub_UpdateChecker.php';
        return new Puc_v4_GitHub_UpdateChecker($repositoryUrl, $pluginFile, $branch);
    }
}

/**
 * Base class for plugin update checker
 */
abstract class Puc_v4_UpdateChecker {
    protected $pluginFile; // Plugin basename, e.g. plugin_directory/plugin_file.php.
    protected $checkPeriod = 12; // How often to check for updates (in hours).
    protected $optionName = '';
    
    /**
     * Class constructor.
     *
     * @param string $pluginFile Absolute path to the main plugin file.
     */
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;
        $this->optionName = 'external_updates-' . basename(dirname($this->pluginFile));
        
        $this->installHooks();
    }
    
    /**
     * Install hooks needed for the update checker to function.
     */
    protected function installHooks() {
        // Check for updates when WordPress does its update checks.
        add_filter('pre_set_site_transient_update_plugins', array($this, 'checkForUpdates'));
        
        // Add our plugin info to the WordPress Details window
        add_filter('plugins_api', array($this, 'injectInfo'), 10, 3);
    }
    
    /**
     * Check for updates and inject them into the update list maintained by WordPress.
     *
     * @param object $updates Update list.
     * @return object Modified update list.
     */
    public function checkForUpdates($updates) {
        if (!is_object($updates)) {
            $updates = new stdClass();
        }
        
        $info = $this->requestUpdate();
        if ($info && is_object($info)) {
            $updates->response[$this->pluginFile] = $info;
        }
        
        return $updates;
    }
    
    /**
     * Request plugin info from the external source.
     * 
     * @return stdClass|null Plugin info or null if no info is available.
     */
    abstract protected function requestUpdate();
    
    /**
     * Insert the plugin information into the WordPress plugins API response.
     *
     * @param false|object $result
     * @param string $action
     * @param object $args
     * @return object
     */
    public function injectInfo($result, $action, $args) {
        if ($action != 'plugin_information' || !isset($args->slug)) {
            return $result;
        }
        
        $pluginSlug = basename(dirname($this->pluginFile));
        
        if ($args->slug != $pluginSlug) {
            return $result;
        }
        
        $info = $this->requestInfo();
        if ($info) {
            return $info;
        }
        
        return $result;
    }
    
    /**
     * Request plugin information for display in the WordPress Details window.
     * 
     * @return stdClass|null Plugin info or null if no info is available.
     */
    abstract protected function requestInfo();
}

/**
 * A base class for plugin update checkers.
 */
abstract class Puc_v4p10_Plugin_UpdateChecker extends Puc_v4_UpdateChecker {
    /**
     * Set the slug that will be used for updates. Normally it's the directory name of the plugin.
     *
     * @param string $slug
     * @return $this
     */
    public function setSlug($slug) {
        $this->slug = $slug;
        return $this;
    }
    
    /**
     * Set the update check interval in hours.
     *
     * @param int $hours
     * @return $this
     */
    public function setCheckPeriod($hours) {
        $this->checkPeriod = $hours;
        return $this;
    }
}

endif;