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
 * Hook into the update system for github.com hosted plugins.
 */
add_filter('update_plugins_github.com', 'gf_french_schools_auto_update', 10, 4);

/**
 * Check for plugin updates from GitHub.
 *
 * @param array|false $update      The plugin update data.
 * @param array       $plugin_data Plugin headers.
 * @param string      $plugin_file Plugin file path.
 * @param array       $locales     Installed locales.
 * @return array|false Updated plugin data or false.
 */
function gf_french_schools_auto_update($update, array $plugin_data, string $plugin_file, $locales)
{
    // Verify this is our plugin
    $our_plugin_file = 'gf-french-schools/gf-french-schools.php';

    if ($our_plugin_file !== $plugin_file) {
        return $update;
    }

    // GitHub configuration
    $github_user = 'guilamu';
    $github_repo = 'gf-french-schools';

    // Get the latest release from GitHub
    $response = wp_remote_get(
        "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest",
        array(
            'user-agent' => 'WordPress/GF-French-Schools',
            'timeout' => 15,
        )
    );

    // Handle request errors
    if (is_wp_error($response)) {
        error_log('GF French Schools Update Error: ' . $response->get_error_message());
        return $update;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log("GF French Schools Update Error: HTTP {$response_code}");
        return $update;
    }

    // Parse JSON response
    $release_data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($release_data['tag_name'])) {
        error_log('GF French Schools Update Error: No tag_name in release');
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
 * Add plugin information to the details popup.
 */
add_filter('plugins_api', 'gf_french_schools_plugin_info', 20, 3);

/**
 * Provide plugin information for the WordPress plugin details popup.
 *
 * @param false|object|array $res    The result object or array.
 * @param string             $action The type of information being requested.
 * @param object             $args   Plugin API arguments.
 * @return false|object Plugin information or false.
 */
function gf_french_schools_plugin_info($res, $action, $args)
{
    // Only handle plugin_information requests
    if ($action !== 'plugin_information') {
        return $res;
    }

    // Check this is our plugin
    if (!isset($args->slug) || $args->slug !== 'gf-french-schools') {
        return $res;
    }

    // GitHub configuration
    $github_user = 'guilamu';
    $github_repo = 'gf-french-schools';

    // Get info from GitHub
    $response = wp_remote_get(
        "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest",
        array(
            'user-agent' => 'WordPress/GF-French-Schools',
            'timeout' => 15,
        )
    );

    if (is_wp_error($response)) {
        return $res;
    }

    $release_data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($release_data)) {
        return $res;
    }

    $new_version = ltrim($release_data['tag_name'], 'v');

    // Build response object
    $res = new stdClass();
    $res->name = 'Gravity Forms - French Schools';
    $res->slug = 'gf-french-schools';
    $res->version = $new_version;
    $res->author = '<a href="https://github.com/guilamu">Guilamu</a>';
    $res->homepage = "https://github.com/{$github_user}/{$github_repo}";
    $res->download_link = $release_data['zipball_url'];
    $res->requires = '5.8';
    $res->tested = '6.7';
    $res->requires_php = '7.4';
    $res->last_updated = $release_data['published_at'] ?? '';
    $res->sections = array(
        'description' => 'Adds a "French Schools" field type to Gravity Forms allowing users to search and select French educational institutions via the official Education Ministry API.',
        'changelog' => !empty($release_data['body']) ? nl2br(esc_html($release_data['body'])) : 'See <a href="https://github.com/guilamu/gf-french-schools/releases" target="_blank">GitHub releases</a> for changelog.',
    );

    return $res;
}

/**
 * Fix the folder name after GitHub zip extraction.
 * 
 * GitHub zipball contains a folder named "username-repo-hash" which breaks
 * WordPress plugin updates. This filter renames it to the correct folder name.
 */
add_filter('upgrader_source_selection', 'gf_french_schools_fix_folder_name', 10, 4);

/**
 * Rename the extracted folder to match the expected plugin folder name.
 *
 * @param string      $source        File source location.
 * @param string      $remote_source Remote file source location.
 * @param WP_Upgrader $upgrader      WP_Upgrader instance.
 * @param array       $hook_extra    Extra arguments passed to hooked filters.
 * @return string|WP_Error The corrected source path or WP_Error on failure.
 */
function gf_french_schools_fix_folder_name($source, $remote_source, $upgrader, $hook_extra)
{
    global $wp_filesystem;

    // Only process plugin updates
    if (!isset($hook_extra['plugin'])) {
        return $source;
    }

    // Check if this is our plugin
    if ($hook_extra['plugin'] !== 'gf-french-schools/gf-french-schools.php') {
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
