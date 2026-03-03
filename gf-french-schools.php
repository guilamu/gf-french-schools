<?php

/**
 * Plugin Name: Gravity Forms - French Schools
 * Plugin URI: https://github.com/guilamu/gf-french-schools
 * Description: Ajoute un champ "Écoles françaises" à Gravity Forms permettant de rechercher et sélectionner un établissement scolaire français via l'API du Ministère de l'Éducation Nationale.
 * Version: 1.8.6
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * Text Domain: gf-french-schools
 * Domain Path: /languages
 * Update URI: https://github.com/guilamu/gf-french-schools/
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: AGPL-3.0
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GF_FRENCH_SCHOOLS_VERSION', '1.8.6');
define('GF_FRENCH_SCHOOLS_PLUGIN_FILE', __FILE__);
define('GF_FRENCH_SCHOOLS_PATH', plugin_dir_path(__FILE__));
define('GF_FRENCH_SCHOOLS_URL', plugin_dir_url(__FILE__));

/**
 * Load translations using absolute path to handle edge cases where plugin_basename()
 * doesn't work correctly early in the loading process, or when GF loads very early.
 *
 * @param bool $force Force reload even if already loaded (useful when locale changes).
 */
function gf_french_schools_load_textdomain($force = false)
{
    static $loaded_locale = '';

    // Get locale - use determine_locale() if available (WP 5.0+), fallback to get_locale()
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

    // Skip if already loaded for this locale (unless forced)
    if (!$force && $loaded_locale === $locale) {
        return;
    }

    // Try loading with absolute path first (most reliable)
    $mofile = GF_FRENCH_SCHOOLS_PATH . 'languages/gf-french-schools-' . $locale . '.mo';

    if (file_exists($mofile)) {
        // Unload any existing textdomain and reload with correct file
        unload_textdomain('gf-french-schools');
        if (load_textdomain('gf-french-schools', $mofile)) {
            $loaded_locale = $locale;
            return;
        }
    }

    // Try base locale (e.g., 'fr' instead of 'fr_FR')
    $base_locale = explode('_', $locale)[0];
    $mofile_base = GF_FRENCH_SCHOOLS_PATH . 'languages/gf-french-schools-' . $base_locale . '.mo';

    if ($base_locale !== $locale && file_exists($mofile_base)) {
        unload_textdomain('gf-french-schools');
        if (load_textdomain('gf-french-schools', $mofile_base)) {
            $loaded_locale = $locale;
            return;
        }
    }

    // Fallback: try standard load_plugin_textdomain
    load_plugin_textdomain(
        'gf-french-schools',
        false,
        dirname(plugin_basename(GF_FRENCH_SCHOOLS_PLUGIN_FILE)) . '/languages'
    );
    $loaded_locale = $locale;
}

// Load on init, as recommended by WP 6.7+ to avoid early translation loading warning
add_action('init', 'gf_french_schools_load_textdomain', 1);

// Final safety net: force reload translations right before GF renders any form
// This handles edge cases where locale was not correctly set during early hooks
add_filter('gform_pre_render', function ($form) {
    // Force reload to ensure correct locale is used (may differ from early hooks)
    gf_french_schools_load_textdomain(true);
    return $form;
}, 1);

// Include the GitHub auto-updater
require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-github-updater.php';

// Include the local database fallback.
require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-ecoles-local-db.php';

/**
 * Initialize the plugin after Gravity Forms is loaded.
 */
add_action('gform_loaded', 'gf_french_schools_init', 5);

function gf_french_schools_init()
{
    if (!method_exists('GFForms', 'include_addon_framework')) {
        return;
    }

    // Ensure translations are loaded before registering fields
    gf_french_schools_load_textdomain();

    // Enforce minimum Gravity Forms version for compatibility.
    $min_version = '2.5';
    if (empty(GFForms::$version) || version_compare(GFForms::$version, $min_version, '<')) {
        add_action('admin_notices', static function () use ($min_version) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    esc_html__('Gravity Forms - French Schools requires Gravity Forms %s or higher.', 'gf-french-schools'),
                    esc_html($min_version)
                )
            );
        });
        return;
    }

    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-ecoles-api-service.php';
    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-gf-field-ecoles-fr.php';
    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-gf-french-schools-addon.php';

    GF_Fields::register(new GF_Field_Ecoles_FR());
    GFAddOn::register('GF_French_Schools_AddOn');
}

// ------------------------------------------------------------------
// Activation / Deactivation hooks for local DB fallback.
// ------------------------------------------------------------------

register_activation_hook(__FILE__, 'gf_french_schools_activate');

/**
 * Plugin activation: create the local DB table and schedule monthly sync.
 */
function gf_french_schools_activate()
{
    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-ecoles-local-db.php';

    GF_Ecoles_Local_DB::create_table();
    GF_Ecoles_Local_DB::schedule_sync();

    // Schedule a one-time event to run the initial sync shortly after activation.
    if (!wp_next_scheduled('gf_ecoles_fr_initial_sync')) {
        wp_schedule_single_event(time() + 30, 'gf_ecoles_fr_initial_sync');
    }
}

register_deactivation_hook(__FILE__, 'gf_french_schools_deactivate');

/**
 * Plugin deactivation: unschedule cron events (table is preserved).
 */
function gf_french_schools_deactivate()
{
    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-ecoles-local-db.php';

    GF_Ecoles_Local_DB::unschedule_sync();
    $timestamp = wp_next_scheduled('gf_ecoles_fr_initial_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gf_ecoles_fr_initial_sync');
    }
}

/**
 * Register a custom "monthly" cron interval (30 days).
 */
add_filter('cron_schedules', 'gf_french_schools_cron_schedules');

function gf_french_schools_cron_schedules($schedules)
{
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => __('Once Monthly', 'gf-french-schools'),
        );
    }
    return $schedules;
}

/**
 * Handle the monthly cron sync event.
 */
add_action(GF_Ecoles_Local_DB::CRON_HOOK, 'gf_french_schools_run_sync');
add_action('gf_ecoles_fr_initial_sync', 'gf_french_schools_run_sync');

function gf_french_schools_run_sync()
{
    GF_Ecoles_Local_DB::sync();
}



/**
 * Collect CSS rules for preselected fields during form rendering.
 * This is more performant than loading all forms on every page.
 */
add_filter('gform_pre_render', 'gf_french_schools_collect_hide_css', 10, 1);

/**
 * Collect CSS rules from forms being rendered.
 *
 * @param array $form The form object.
 * @return array The form object.
 */
function gf_french_schools_collect_hide_css($form)
{
    global $gf_french_schools_css_rules;

    if (!isset($gf_french_schools_css_rules)) {
        $gf_french_schools_css_rules = array();
    }

    if (!isset($form['fields']) || !is_array($form['fields'])) {
        return $form;
    }

    foreach ($form['fields'] as $field) {
        if ('ecoles_fr' !== $field->type) {
            continue;
        }

        $form_id = absint($form['id']);
        $field_id = absint($field->id);

        // Hide Status field if preselected
        if (!empty($field->preselectedStatut)) {
            $gf_french_schools_css_rules[] = sprintf(
                '#input_%d_%d_container .gf-ecoles-fr-statut-field { display: none !important; }',
                $form_id,
                $field_id
            );
        }

        // Hide Department field if preselected
        if (!empty($field->preselectedDepartement)) {
            $gf_french_schools_css_rules[] = sprintf(
                '#input_%d_%d_container .gf-ecoles-fr-departement-field { display: none !important; }',
                $form_id,
                $field_id
            );
        }
    }

    return $form;
}

/**
 * Output collected CSS rules in footer.
 */
add_action('wp_footer', 'gf_french_schools_output_hide_preselected_css', 20);

function gf_french_schools_output_hide_preselected_css()
{
    global $gf_french_schools_css_rules;

    if (empty($gf_french_schools_css_rules)) {
        return;
    }

    $css = implode(' ', array_unique($gf_french_schools_css_rules));
    printf('<style type="text/css">%s</style>', wp_strip_all_tags($css));
}

/**
 * Enqueue admin scripts for form editor.
 */
add_action('gform_editor_js', 'gf_french_schools_editor_js');

function gf_french_schools_editor_js()
{
    wp_enqueue_style(
        'gf-ecoles-fr-admin-css',
        GF_FRENCH_SCHOOLS_URL . 'assets/css/ecoles-fr-admin.css',
        array(),
        GF_FRENCH_SCHOOLS_VERSION
    );

    wp_enqueue_script(
        'gf-ecoles-fr-admin',
        GF_FRENCH_SCHOOLS_URL . 'assets/js/ecoles-fr-admin.js',
        array('jquery', 'gform_gravityforms'),
        GF_FRENCH_SCHOOLS_VERSION,
        true
    );

    // Pass departements list to admin JS
    wp_localize_script('gf-ecoles-fr-admin', 'gfEcolesFRAdmin', array(
        'departements' => GF_Field_Ecoles_FR::get_departements(),
        'i18n' => array(
            'preselectionTitle' => __('Preselection Settings', 'gf-french-schools'),
            'preselectedStatut' => __('Preselected Status', 'gf-french-schools'),
            'preselectedDepartement' => __('Preselected Department', 'gf-french-schools'),
            'none' => __('-- None --', 'gf-french-schools'),
            'public' => __('Public', 'gf-french-schools'),
            'private' => __('Private', 'gf-french-schools'),
            'preselectionHint' => __('Preselected fields will be hidden from users on the frontend.', 'gf-french-schools'),
        ),
    ));
}

/**
 * Output custom field settings in the form editor.
 */
add_action('gform_field_advanced_settings', 'gf_french_schools_field_settings', 10, 2);

function gf_french_schools_field_settings($position, $form_id)
{
    // Add settings at position 50 (after label settings)
    if ($position == 50) {
        ?>
        <li class="ecoles_fr_preselection_setting field_setting">
            <label class="section_label">
                <?php esc_html_e('Preselection Settings', 'gf-french-schools'); ?>
                <?php gform_tooltip('ecoles_fr_preselection'); ?>
            </label>

            <div style="margin-bottom: 10px;">
                <label for="ecoles_fr_preselected_statut" style="display: block; margin-bottom: 5px;">
                    <?php esc_html_e('Preselected Status', 'gf-french-schools'); ?>
                </label>
                <select id="ecoles_fr_preselected_statut" class="ecoles-fr-setting" data-setting="preselectedStatut"
                    style="width: 100%;">
                    <option value=""><?php esc_html_e('-- None --', 'gf-french-schools'); ?></option>
                    <option value="Public"><?php esc_html_e('Public', 'gf-french-schools'); ?></option>
                    <option value="Privé"><?php esc_html_e('Private', 'gf-french-schools'); ?></option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label for="ecoles_fr_preselected_departement" style="display: block; margin-bottom: 5px;">
                    <?php esc_html_e('Preselected Department', 'gf-french-schools'); ?>
                </label>
                <select id="ecoles_fr_preselected_departement" class="ecoles-fr-setting" data-setting="preselectedDepartement"
                    style="width: 100%;">
                    <option value=""><?php esc_html_e('-- None --', 'gf-french-schools'); ?></option>
                    <?php foreach (GF_Field_Ecoles_FR::get_departements() as $dept): ?>
                        <option value="<?php echo esc_attr($dept); ?>"><?php echo esc_html($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label class="section_label" style="margin-top: 15px;">
                <?php esc_html_e('School Type Filters', 'gf-french-schools'); ?>
            </label>

            <div style="margin-bottom: 10px;">
                <input type="checkbox" id="ecoles_fr_hide_ecoles" class="ecoles-fr-setting" data-setting="hideEcoles" />
                <label for="ecoles_fr_hide_ecoles" style="display: inline;">
                    <?php esc_html_e('Hide primary schools (Ecoles)', 'gf-french-schools'); ?>
                </label>
            </div>

            <div style="margin-bottom: 10px;">
                <input type="checkbox" id="ecoles_fr_hide_colleges_lycees" class="ecoles-fr-setting"
                    data-setting="hideCollegesLycees" />
                <label for="ecoles_fr_hide_colleges_lycees" style="display: inline;">
                    <?php esc_html_e('Hide middle and high schools (Collèges and Lycées)', 'gf-french-schools'); ?>
                </label>
            </div>

            <label class="section_label" style="margin-top: 15px;">
                <?php esc_html_e('Result Display', 'gf-french-schools'); ?>
            </label>

            <div style="margin-bottom: 10px;">
                <input type="checkbox" id="ecoles_fr_hide_result" class="ecoles-fr-setting" data-setting="hideResult" />
                <label for="ecoles_fr_hide_result" style="display: inline;">
                    <?php esc_html_e('Hide the selected school summary block', 'gf-french-schools'); ?>
                </label>
            </div>
        </li>
        <?php
    }
}

/**
 * Add tooltip for preselection settings.
 */
add_filter('gform_tooltips', 'gf_french_schools_tooltips');

function gf_french_schools_tooltips($tooltips)
{
    $tooltips['ecoles_fr_preselection'] = sprintf(
        '<h6>%s</h6>%s',
        __('Preselection Settings', 'gf-french-schools'),
        __('Set default values for Status and Department. When a value is preselected, the corresponding field will be hidden from users on the form.', 'gf-french-schools')
    );
    return $tooltips;
}

/**
 * Enqueue frontend scripts and styles.
 */
add_action('gform_enqueue_scripts', 'gf_french_schools_enqueue_scripts', 10, 2);

function gf_french_schools_enqueue_scripts($form, $is_ajax)
{
    // Check if form has our field type
    $has_ecoles_field = false;
    foreach ($form['fields'] as $field) {
        if ($field->type === 'ecoles_fr') {
            $has_ecoles_field = true;
            break;
        }
    }

    if (!$has_ecoles_field) {
        return;
    }

    wp_enqueue_style(
        'gf-ecoles-fr',
        GF_FRENCH_SCHOOLS_URL . 'assets/css/ecoles-fr.css',
        array(),
        GF_FRENCH_SCHOOLS_VERSION
    );

    wp_enqueue_script(
        'gf-ecoles-fr-frontend',
        GF_FRENCH_SCHOOLS_URL . 'assets/js/ecoles-fr-frontend.js',
        array('jquery'),
        GF_FRENCH_SCHOOLS_VERSION,
        true
    );

    $timings = apply_filters(
        'gf_french_schools_timings',
        array(
            'debounce' => 300,
            'ajaxTimeout' => 15000,
            'retryLimit' => 2,
            'retryDelay' => 700,
        )
    );

    wp_localize_script('gf-ecoles-fr-frontend', 'gfEcolesFR', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gf_ecoles_fr_nonce'),
        'timings' => $timings,
        'i18n' => array(
            'selectStatut' => __('-- Select status first --', 'gf-french-schools'),
            'selectDepartement' => __('-- Select department first --', 'gf-french-schools'),
            'selectVille' => __('-- Select city first --', 'gf-french-schools'),
            'noResults' => __('No results found', 'gf-french-schools'),
            'searching' => __('Searching...', 'gf-french-schools'),
            'minChars' => __('Type at least 2 characters', 'gf-french-schools'),
            'errorLoading' => __('Error loading results. Please try again.', 'gf-french-schools'),
            'noValue' => __('No', 'gf-french-schools'),
            'otherSchool' => __('Other: Enter school name manually', 'gf-french-schools'),
            'manualEntry' => __('Manual Entry', 'gf-french-schools'),
        ),
    ));
}

/**
 * Resolve client IP.
 *
 * Proxy-forwarding headers (X-Forwarded-For, CF-Connecting-IP, etc.) can be
 * freely spoofed by any client, which would allow bypassing the transient-based
 * rate limiter. These headers are only trusted when REMOTE_ADDR is listed in
 * the `gf_french_schools_trusted_proxies` filter (e.g. known Cloudflare CIDRs
 * or a local load-balancer IP). By default the filter returns an empty list, so
 * REMOTE_ADDR is always used as-is.
 *
 * @return string
 */
function gf_french_schools_get_client_ip()
{
    $remote_addr = sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));

    /**
     * Allowlist of trusted reverse-proxy IP addresses.
     * When REMOTE_ADDR matches one of these, forwarding headers are trusted.
     *
     * @param string[] $trusted_proxies Array of trusted proxy IPs.
     */
    $trusted_proxies = (array) apply_filters('gf_french_schools_trusted_proxies', array());

    if (!empty($trusted_proxies) && in_array($remote_addr, $trusted_proxies, true)) {
        $forward_headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP');
        foreach ($forward_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field((string) wp_unslash($_SERVER[$header]));
                $ip = trim(explode(',', $ip)[0]); // take left-most (client) IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }

    return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : 'unknown';
}

/**
 * Build a rate-limit key based on user/session and IP.
 *
 * @return string
 */
function gf_french_schools_get_rate_key()
{
    $identifier = is_user_logged_in() ? 'user_' . get_current_user_id() : 'visitor_' . gf_french_schools_get_client_ip();

    return 'gf_ecoles_rate_' . md5($identifier);
}

/**
 * AJAX handler for API searches.
 */
add_action('wp_ajax_gf_ecoles_fr_search', 'gf_french_schools_ajax_search');
add_action('wp_ajax_nopriv_gf_ecoles_fr_search', 'gf_french_schools_ajax_search');

function gf_french_schools_ajax_search()
{
    if (!check_ajax_referer('gf_ecoles_fr_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Security check failed.', 'gf-french-schools')));
        return;
    }

    // Require a valid form context for all requests.
    $form_id = isset($_POST['form_id']) ? absint(wp_unslash($_POST['form_id'])) : 0;
    $form = $form_id ? GFAPI::get_form($form_id) : false;
    if (!$form) {
        wp_send_json_error(array('message' => __('Unauthorized access.', 'gf-french-schools')));
        return;
    }

    // Verify the form actually contains an ecoles_fr field.
    // This prevents scripted use of the endpoint with arbitrary valid form IDs.
    $has_ecoles_field = false;
    if (!empty($form['fields']) && is_array($form['fields'])) {
        foreach ($form['fields'] as $_field) {
            if ($_field->type === 'ecoles_fr') {
                $has_ecoles_field = true;
                break;
            }
        }
    }
    if (!$has_ecoles_field) {
        wp_send_json_error(array('message' => __('Unauthorized access.', 'gf-french-schools')));
        return;
    }

    // Simple rate limiting (filterable).
    $rate_limit = (int) apply_filters('gf_french_schools_rate_limit', 30);
    $rate_window = (int) apply_filters('gf_french_schools_rate_window', MINUTE_IN_SECONDS);
    $rate_key = gf_french_schools_get_rate_key();
    $rate_count = (int) get_transient($rate_key);
    if ($rate_count >= $rate_limit) {
        wp_send_json_error(array('message' => __('Too many requests. Please wait a moment.', 'gf-french-schools')));
        return;
    }
    set_transient($rate_key, $rate_count + 1, $rate_window);

    // Sanitize and validate input.
    $allowed_types = array('villes', 'ecoles');
    $search_type = sanitize_key(wp_unslash($_POST['search_type'] ?? ''));
    if (!in_array($search_type, $allowed_types, true)) {
        wp_send_json_error(array('message' => __('Invalid search type', 'gf-french-schools')));
        return;
    }

    $statut = sanitize_text_field(wp_unslash($_POST['statut'] ?? ''));
    $valid_statuses = array('Public', 'Privé');
    if (!in_array($statut, $valid_statuses, true)) {
        wp_send_json_error(array('message' => __('Invalid status.', 'gf-french-schools')));
        return;
    }

    $departement = sanitize_text_field(wp_unslash($_POST['departement'] ?? ''));
    if (!in_array($departement, GF_Field_Ecoles_FR::get_departements(), true)) {
        wp_send_json_error(array('message' => __('Invalid department.', 'gf-french-schools')));
        return;
    }

    $ville = sanitize_text_field(wp_unslash($_POST['ville'] ?? ''));
    $query = sanitize_text_field(wp_unslash($_POST['query'] ?? ''));

    // Get school type filter settings
    $hide_ecoles = filter_var(wp_unslash($_POST['hide_ecoles'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $hide_colleges_lycees = filter_var(wp_unslash($_POST['hide_colleges_lycees'] ?? false), FILTER_VALIDATE_BOOLEAN);

    $api_service = new GF_Ecoles_API_Service();
    $results = array();

    if (strlen($query) < 2) {
        wp_send_json_error(array('message' => __('Type at least 2 characters', 'gf-french-schools')));
        return;
    }

    switch ($search_type) {
        case 'villes':
            $results = $api_service->search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
            break;
        case 'ecoles':
            if (empty($ville)) {
                wp_send_json_error(array('message' => __('City is required.', 'gf-french-schools')));
                return;
            }
            $results = $api_service->search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees);
            break;
    }

    if (is_wp_error($results)) {
        wp_send_json_error(array('message' => $results->get_error_message()));
    } else {
        wp_send_json_success($results);
    }
}



/**
 * AJAX handler for manual sync trigger (admin only).
 */
add_action('wp_ajax_gf_ecoles_fr_manual_sync', 'gf_french_schools_ajax_manual_sync');

function gf_french_schools_ajax_manual_sync()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized.', 'gf-french-schools')));
        return;
    }

    if (!check_ajax_referer('gf_ecoles_fr_sync_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Security check failed.', 'gf-french-schools')));
        return;
    }

    $result = GF_Ecoles_Local_DB::sync();
    $status = GF_Ecoles_Local_DB::get_status();

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
            'status' => $status,
        ));
    } else {
        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %s: number of records */
                __('%s records imported successfully.', 'gf-french-schools'),
                number_format_i18n($status['record_count'])
            ),
            'status' => $status,
        ));
    }
}

/**
 * Register with Guilamu Bug Reporter
 */
add_action('plugins_loaded', function () {
    if (class_exists('Guilamu_Bug_Reporter')) {
        Guilamu_Bug_Reporter::register(array(
            'slug' => 'gf-french-schools',
            'name' => 'Gravity Forms - French Schools',
            'version' => GF_FRENCH_SCHOOLS_VERSION,
            'github_repo' => 'guilamu/gf-french-schools',
        ));
    }
}, 20);

/**
 * Add 'Report a Bug' link to plugin row meta.
 *
 * @param array  $links Plugin row meta links.
 * @param string $file  Plugin file path.
 * @return array Modified links.
 */
function gf_french_schools_plugin_row_meta($links, $file)
{
    if (plugin_basename(GF_FRENCH_SCHOOLS_PLUGIN_FILE) !== $file) {
        return $links;
    }

    if (class_exists('Guilamu_Bug_Reporter')) {
        $links[] = sprintf(
            '<a href="#" class="guilamu-bug-report-btn" data-plugin-slug="gf-french-schools" data-plugin-name="%s">%s</a>',
            esc_attr__('Gravity Forms - French Schools', 'gf-french-schools'),
            esc_html__('🐛 Report a Bug', 'gf-french-schools')
        );
    } else {
        $links[] = '<a href="https://github.com/guilamu/guilamu-bug-reporter/releases" target="_blank">🐛 Report a Bug (install Bug Reporter)</a>';
    }

    return $links;
}
add_filter('plugin_row_meta', 'gf_french_schools_plugin_row_meta', 10, 2);
