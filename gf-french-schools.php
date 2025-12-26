<?php
/**
 * Plugin Name: Gravity Forms - French Schools
 * Plugin URI: https://github.com/guilamu/gf-french-schools
 * Description: Adds a "French Schools" field type to Gravity Forms allowing users to search and select French educational institutions via the Education Ministry API.
 * Version: 1.0.3
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

define('GF_FRENCH_SCHOOLS_VERSION', '1.0.0');
define('GF_FRENCH_SCHOOLS_PATH', plugin_dir_path(__FILE__));
define('GF_FRENCH_SCHOOLS_URL', plugin_dir_url(__FILE__));

// Include the GitHub auto-updater
require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-github-updater.php';

/**
 * Initialize the plugin after Gravity Forms is loaded.
 */
add_action('gform_loaded', 'gf_french_schools_init', 5);

function gf_french_schools_init()
{
    if (!method_exists('GFForms', 'include_addon_framework')) {
        return;
    }

    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-ecoles-api-service.php';
    require_once GF_FRENCH_SCHOOLS_PATH . 'includes/class-gf-field-ecoles-fr.php';

    GF_Fields::register(new GF_Field_Ecoles_FR());
}

/**
 * Load plugin text domain for translations.
 */
add_action('init', 'gf_french_schools_load_textdomain');

function gf_french_schools_load_textdomain()
{
    load_plugin_textdomain(
        'gf-french-schools',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

/**
 * Output dynamic CSS to hide preselected fields.
 * This follows the same pattern as gf-chained-select-enhancer.
 */
add_action('wp_head', 'gf_french_schools_output_hide_preselected_css');

function gf_french_schools_output_hide_preselected_css()
{
    if (!class_exists('GFAPI')) {
        return;
    }

    $forms = GFAPI::get_forms();
    $css_rules = array();

    foreach ($forms as $form) {
        if (!isset($form['fields']) || !is_array($form['fields'])) {
            continue;
        }

        foreach ($form['fields'] as $field) {
            if ($field->type !== 'ecoles_fr') {
                continue;
            }

            $form_id = $form['id'];
            $field_id = $field->id;

            // Hide Status field if preselected
            if (!empty($field->preselectedStatut)) {
                $css_rules[] = sprintf(
                    '#input_%d_%d_container .gf-ecoles-fr-statut-field { display: none !important; }',
                    $form_id,
                    $field_id
                );
            }

            // Hide Department field if preselected
            if (!empty($field->preselectedDepartement)) {
                $css_rules[] = sprintf(
                    '#input_%d_%d_container .gf-ecoles-fr-departement-field { display: none !important; }',
                    $form_id,
                    $field_id
                );
            }
        }
    }

    if (!empty($css_rules)) {
        echo '<style type="text/css">' . implode(' ', $css_rules) . '</style>';
    }
}

/**
 * Enqueue admin scripts for form editor.
 */
add_action('gform_editor_js', 'gf_french_schools_editor_js');

function gf_french_schools_editor_js()
{
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

    // Add admin CSS to hide field labels and add spacing in the form editor
    echo '<style type="text/css">
        .gf-ecoles-fr-wrapper .gf-ecoles-fr-field label {
            display: none !important;
        }
        .gf-ecoles-fr-wrapper > span.gf-ecoles-fr-field {
            display: block !important;
            margin-bottom: 10px !important;
        }
        .gf-ecoles-fr-wrapper select,
        .gf-ecoles-fr-wrapper input[type="text"] {
            margin-bottom: 10px !important;
            color: #6b7280 !important;
        }
        .gf-ecoles-fr-wrapper input[type="text"]::placeholder {
            color: #6b7280 !important;
            opacity: 1;
        }
    </style>';
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
                <select id="ecoles_fr_preselected_statut" onchange="SetFieldProperty('preselectedStatut', this.value);"
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
                <select id="ecoles_fr_preselected_departement"
                    onchange="SetFieldProperty('preselectedDepartement', this.value);" style="width: 100%;">
                    <option value=""><?php esc_html_e('-- None --', 'gf-french-schools'); ?></option>
                    <?php foreach (GF_Field_Ecoles_FR::get_departements() as $dept): ?>
                        <option value="<?php echo esc_attr($dept); ?>"><?php echo esc_html($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p class="description" style="margin-top: 10px; margin-bottom: 15px; color: #666;">
                <?php esc_html_e('Preselected fields will be hidden from users on the frontend.', 'gf-french-schools'); ?>
            </p>

            <label class="section_label" style="margin-top: 15px;">
                <?php esc_html_e('School Type Filters', 'gf-french-schools'); ?>
            </label>

            <div style="margin-bottom: 10px;">
                <input type="checkbox" id="ecoles_fr_hide_ecoles" onclick="SetFieldProperty('hideEcoles', this.checked);" />
                <label for="ecoles_fr_hide_ecoles" style="display: inline;">
                    <?php esc_html_e('Hide primary schools (Ecoles)', 'gf-french-schools'); ?>
                </label>
            </div>

            <div style="margin-bottom: 10px;">
                <input type="checkbox" id="ecoles_fr_hide_colleges_lycees"
                    onclick="SetFieldProperty('hideCollegesLycees', this.checked);" />
                <label for="ecoles_fr_hide_colleges_lycees" style="display: inline;">
                    <?php esc_html_e('Hide middle and high schools (Collèges and Lycées)', 'gf-french-schools'); ?>
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

    wp_localize_script('gf-ecoles-fr-frontend', 'gfEcolesFR', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gf_ecoles_fr_nonce'),
        'i18n' => array(
            'selectStatut' => __('-- Select status first --', 'gf-french-schools'),
            'selectDepartement' => __('-- Select department first --', 'gf-french-schools'),
            'selectVille' => __('-- Select city first --', 'gf-french-schools'),
            'noResults' => __('No results found', 'gf-french-schools'),
            'searching' => __('Searching...', 'gf-french-schools'),
            'minChars' => __('Type at least 2 characters', 'gf-french-schools'),
        ),
    ));
}

/**
 * AJAX handler for API searches.
 */
add_action('wp_ajax_gf_ecoles_fr_search', 'gf_french_schools_ajax_search');
add_action('wp_ajax_nopriv_gf_ecoles_fr_search', 'gf_french_schools_ajax_search');

function gf_french_schools_ajax_search()
{
    check_ajax_referer('gf_ecoles_fr_nonce', 'nonce');

    $search_type = sanitize_text_field(wp_unslash($_POST['search_type'] ?? ''));
    $statut = sanitize_text_field(wp_unslash($_POST['statut'] ?? ''));
    $departement = sanitize_text_field(wp_unslash($_POST['departement'] ?? ''));
    $ville = sanitize_text_field(wp_unslash($_POST['ville'] ?? ''));
    $query = sanitize_text_field(wp_unslash($_POST['query'] ?? ''));

    // Get school type filter settings
    $hide_ecoles = isset($_POST['hide_ecoles']) && $_POST['hide_ecoles'] === 'true';
    $hide_colleges_lycees = isset($_POST['hide_colleges_lycees']) && $_POST['hide_colleges_lycees'] === 'true';

    $api_service = new GF_Ecoles_API_Service();
    $results = array();

    switch ($search_type) {
        case 'villes':
            $results = $api_service->get_villes($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
            break;
        case 'ecoles':
            $results = $api_service->get_ecoles($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees);
            break;
        default:
            wp_send_json_error(array('message' => __('Invalid search type', 'gf-french-schools')));
            return;
    }

    if (is_wp_error($results)) {
        wp_send_json_error(array('message' => $results->get_error_message()));
    } else {
        wp_send_json_success($results);
    }
}



