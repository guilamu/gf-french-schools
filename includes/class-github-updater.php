<?php
/**
 * GitHub Auto-Updater for Gravity Forms French Schools
 *
 * Enables automatic updates from GitHub releases.
 *
 * @package GF_French_Schools
 * @author Guilamu
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GF_French_Schools_GitHub_Updater
 *
 * Handles automatic updates from GitHub releases.
 */
class GF_French_Schools_GitHub_Updater
{

    /**
     * GitHub username.
     *
     * @var string
     */
    private const GITHUB_USER = 'guilamu';

    /**
     * GitHub repository name.
     *
     * @var string
     */
    private const GITHUB_REPO = 'gf-french-schools';

    /**
     * Plugin file path relative to plugins directory.
     *
     * @var string
     */
    private const PLUGIN_FILE = 'gf-french-schools/gf-french-schools.php';

    /**
     * Cache key for GitHub release data.
     *
     * @var string
     */
    private const CACHE_KEY = 'gf_french_schools_github_release';

    /**
     * Cache expiration in seconds (12 hours).
     *
     * @var int
     */
    private const CACHE_EXPIRATION = 43200;

    /**
     * Initialize the updater.
     *
     * @return void
     */
    public static function init(): void
    {
        add_filter('update_plugins_github.com', array(self::class, 'check_for_update'), 10, 4);
        add_filter('plugins_api', array(self::class, 'plugin_info'), 20, 3);
        add_filter('upgrader_source_selection', array(self::class, 'fix_folder_name'), 10, 4);
    }

    /**
     * Get release data from GitHub with caching.
     *
     * @return array|null Release data or null on failure.
     */
    private static function get_release_data(): ?array
    {
        $release_data = get_transient(self::CACHE_KEY);

        if (false !== $release_data && is_array($release_data)) {
            return $release_data;
        }

        $response = wp_remote_get(
            sprintf('https://api.github.com/repos/%s/%s/releases/latest', self::GITHUB_USER, self::GITHUB_REPO),
            array(
                'user-agent' => 'WordPress/GF-French-Schools',
                'timeout' => 15,
            )
        );

        // Handle request errors
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GF French Schools Update Error: ' . $response->get_error_message());
            }
            return null;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== $response_code) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("GF French Schools Update Error: HTTP {$response_code}");
            }
            return null;
        }

        // Parse JSON response
        $release_data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($release_data['tag_name'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GF French Schools Update Error: No tag_name in release');
            }
            return null;
        }

        // Cache the release data
        set_transient(self::CACHE_KEY, $release_data, self::CACHE_EXPIRATION);

        return $release_data;
    }

    /**
     * Check for plugin updates from GitHub.
     *
     * @param array|false $update      The plugin update data.
     * @param array       $plugin_data Plugin headers.
     * @param string      $plugin_file Plugin file path.
     * @param array       $locales     Installed locales.
     * @return array|false Updated plugin data or false.
     */
    public static function check_for_update($update, array $plugin_data, string $plugin_file, $locales)
    {
        // Verify this is our plugin
        if (self::PLUGIN_FILE !== $plugin_file) {
            return $update;
        }

        $release_data = self::get_release_data();
        if (null === $release_data) {
            return $update;
        }

        // Clean version (remove 'v' prefix: v1.0.0 -> 1.0.0)
        $new_version = ltrim($release_data['tag_name'], 'v');

        // Compare versions - only return update if newer version exists
        if (version_compare($plugin_data['Version'], $new_version, '>=')) {
            return $update;
        }

        // Build update object
        return array(
            'version' => $new_version,
            'package' => $release_data['zipball_url'],
            'url' => $release_data['html_url'],
            'tested' => '6.7',
            'requires_php' => '7.4',
            'compatibility' => new stdClass(),
            'icons' => array(),
            'banners' => array(),
        );
    }

    /**
     * Provide plugin information for the WordPress plugin details popup.
     *
     * @param false|object|array $res    The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object Plugin information or false.
     */
    public static function plugin_info($res, $action, $args)
    {
        // Only handle plugin_information requests
        if ('plugin_information' !== $action) {
            return $res;
        }

        // Check this is our plugin
        if (!isset($args->slug) || 'gf-french-schools' !== $args->slug) {
            return $res;
        }

        $release_data = self::get_release_data();
        if (null === $release_data) {
            return $res;
        }

        $new_version = ltrim($release_data['tag_name'], 'v');

        // Build response object
        $res = new stdClass();
        $res->name = 'Gravity Forms - French Schools';
        $res->slug = 'gf-french-schools';
        $res->version = $new_version;
        $res->author = '<a href="https://github.com/guilamu">Guilamu</a>';
        $res->homepage = sprintf('https://github.com/%s/%s', self::GITHUB_USER, self::GITHUB_REPO);
        $res->download_link = $release_data['zipball_url'];
        $res->requires = '5.8';
        $res->tested = '6.7';
        $res->requires_php = '7.4';
        $res->last_updated = $release_data['published_at'] ?? '';
        $res->sections = array(
            'description' => 'Adds a "French Schools" field type to Gravity Forms allowing users to search and select French educational institutions via the official Education Ministry API.',
            'changelog' => !empty($release_data['body'])
                ? nl2br(esc_html($release_data['body']))
                : 'See <a href="https://github.com/guilamu/gf-french-schools/releases" target="_blank">GitHub releases</a> for changelog.',
        );

        return $res;
    }

    /**
     * Rename the extracted folder to match the expected plugin folder name.
     *
     * GitHub zipball contains a folder named "username-repo-hash" which breaks
     * WordPress plugin updates. This filter renames it to the correct folder name.
     *
     * @param string      $source        File source location.
     * @param string      $remote_source Remote file source location.
     * @param WP_Upgrader $upgrader      WP_Upgrader instance.
     * @param array       $hook_extra    Extra arguments passed to hooked filters.
     * @return string|WP_Error The corrected source path or WP_Error on failure.
     */
    public static function fix_folder_name($source, $remote_source, $upgrader, $hook_extra)
    {
        global $wp_filesystem;

        // Only process plugin updates
        if (!isset($hook_extra['plugin'])) {
            return $source;
        }

        // Check if this is our plugin
        if (self::PLUGIN_FILE !== $hook_extra['plugin']) {
            return $source;
        }

        // Expected folder name
        $correct_folder = 'gf-french-schools';

        // Get the current folder name from source path
        $source_folder = basename(untrailingslashit($source));

        // If already correct, no action needed
        if ($source_folder === $correct_folder) {
            return $source;
        }

        // Build new source path with correct folder name
        $new_source = trailingslashit($remote_source) . $correct_folder . '/';

        // Rename the folder
        if ($wp_filesystem->move($source, $new_source)) {
            return $new_source;
        }

        // If rename failed, return error
        return new WP_Error(
            'rename_failed',
            __('Unable to rename the update folder.', 'gf-french-schools')
        );
    }
}

// Initialize the updater
GF_French_Schools_GitHub_Updater::init();
