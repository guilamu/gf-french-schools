<?php
/**
 * Gravity Forms Add-On for French Schools settings.
 *
 * Registers the plugin settings page under Forms > Settings > French Schools
 * using the standard GFAddOn settings framework.
 *
 * @package GF_French_Schools
 */

if (!defined('ABSPATH')) {
    exit;
}

GFForms::include_addon_framework();

/**
 * Class GF_French_Schools_AddOn
 *
 * Integrates the French Schools settings into the Gravity Forms settings page.
 */
class GF_French_Schools_AddOn extends GFAddOn
{

    /**
     * Plugin version.
     *
     * @var string
     */
    protected $_version = GF_FRENCH_SCHOOLS_VERSION;

    /**
     * Minimum required Gravity Forms version.
     *
     * @var string
     */
    protected $_min_gravityforms_version = '2.5';

    /**
     * Add-on slug.
     *
     * @var string
     */
    protected $_slug = 'gf-french-schools';

    /**
     * Relative path to the main plugin file from the plugins folder.
     *
     * @var string
     */
    protected $_path = 'gf-french-schools/gf-french-schools.php';

    /**
     * Full path to this file.
     *
     * @var string
     */
    protected $_full_path = __FILE__;

    /**
     * Full title of the add-on.
     *
     * @var string
     */
    protected $_title = 'Gravity Forms - French Schools';

    /**
     * Short title displayed in the settings menu.
     *
     * @var string
     */
    protected $_short_title = 'French Schools';

    /**
     * Capabilities required for plugin settings.
     *
     * @var string|array
     */
    protected $_capabilities_settings_page = 'manage_options';

    /**
     * Settings tab icon (Dashicon class).
     *
     * @see https://developer.wordpress.org/resource/dashicons/#building
     *
     * @return string
     */
    public function get_menu_icon()
    {
        return 'dashicons-building';
    }

    /**
     * Singleton instance.
     *
     * @var GF_French_Schools_AddOn|null
     */
    private static $_instance = null;

    /**
     * Get singleton instance.
     *
     * @return GF_French_Schools_AddOn
     */
    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Register admin hooks.
     *
     * @return void
     */
    public function init_admin()
    {
        parent::init_admin();
        add_action('admin_enqueue_scripts', array($this, 'maybe_localize_sync_script'), 20);
    }

    /**
     * Enqueue admin scripts for the plugin settings page.
     *
     * @return array
     */
    public function scripts()
    {
        $scripts = array(
            array(
                'handle'  => 'gf-ecoles-fr-admin',
                'src'     => GF_FRENCH_SCHOOLS_URL . 'assets/js/ecoles-fr-admin.js',
                'version' => $this->_version,
                'deps'    => array('jquery'),
                'enqueue' => array(
                    array(
                        'admin_page' => array('plugin_settings'),
                        'tab'        => $this->_slug,
                    ),
                ),
            ),
        );

        return array_merge(parent::scripts(), $scripts);
    }

    /**
     * Enqueue admin styles for the plugin settings page.
     *
     * @return array
     */
    public function styles()
    {
        $styles = array(
            array(
                'handle'  => 'gf-ecoles-fr-admin-css',
                'src'     => GF_FRENCH_SCHOOLS_URL . 'assets/css/ecoles-fr-admin.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array(
                        'admin_page' => array('plugin_settings', 'form_editor', 'entry_view'),
                    ),
                ),
            ),
        );

        return array_merge(parent::styles(), $styles);
    }

    /**
     * Localize the sync script with AJAX data when on the plugin settings page.
     *
     * @return void
     */
    public function maybe_localize_sync_script()
    {
        if (!$this->is_plugin_settings($this->_slug)) {
            return;
        }

        wp_localize_script('gf-ecoles-fr-admin', 'gfEcolesFRSync', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('gf_ecoles_fr_sync_nonce'),
            'i18n'    => array(
                'syncing'       => __('Syncing — this may take a few minutes…', 'gf-french-schools'),
                'success'       => __('Sync completed successfully!', 'gf-french-schools'),
                'error'         => __('Sync failed. See error details below.', 'gf-french-schools'),
                'statusOk'      => __('OK', 'gf-french-schools'),
                'statusError'   => __('Error', 'gf-french-schools'),
                'statusRunning' => __('Sync in progress…', 'gf-french-schools'),
                'never'         => __('Never', 'gf-french-schools'),
            ),
        ));
    }

    /**
     * Define the plugin settings fields using the GFAddOn settings API.
     *
     * @return array
     */
    public function plugin_settings_fields()
    {
        return array(
            array(
                'title'       => esc_html__('Mode', 'gf-french-schools'),
                'description' => esc_html__('Configure how the plugin searches for schools.', 'gf-french-schools'),
                'fields'      => array(
                    array(
                        'name'          => 'local_only',
                        'type'          => 'toggle',
                        'label'         => esc_html__('Local Only', 'gf-french-schools'),
                        'description'   => esc_html__('Disable the remote API and use only the local database.', 'gf-french-schools'),
                        'tooltip'       => esc_html__('When enabled, all searches use the locally downloaded copy of the school directory. The remote API will never be called.', 'gf-french-schools'),
                        'default_value' => false,
                    ),
                    array(
                        'name'          => 'local_fallback_api',
                        'type'          => 'toggle',
                        'label'         => esc_html__('API Fallback', 'gf-french-schools'),
                        'description'   => esc_html__('When Local Only mode returns no results, silently query the remote API as a last resort.', 'gf-french-schools'),
                        'tooltip'       => esc_html__('If the local database returns no results for a search, the plugin will try the remote API before showing "no results". This helps when the local copy is incomplete or outdated.', 'gf-french-schools'),
                        'default_value' => false,
                        'dependency'    => array(
                            'live'   => true,
                            'fields' => array(
                                array(
                                    'field'  => 'local_only',
                                    'values' => array( '1' ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'title'       => esc_html__('Local Database Sync', 'gf-french-schools'),
                'description' => esc_html__('The plugin downloads a copy of the French Education Ministry directory monthly. This local copy is used automatically when the remote API is unavailable, or exclusively when Local Only mode is enabled.', 'gf-french-schools'),
                'fields'      => array(
                    array(
                        'name' => 'sync_dashboard',
                        'type' => 'sync_dashboard',
                    ),
                ),
            ),
        );
    }

    /**
     * Render the sync dashboard custom field type.
     *
     * @param array $field Field configuration.
     * @param bool  $echo  Whether to echo the output.
     * @return string
     */
    public function settings_sync_dashboard($field, $echo = true)
    {
        $status = GF_Ecoles_Local_DB::get_status();
        $next_scheduled = wp_next_scheduled(GF_Ecoles_Local_DB::CRON_HOOK);

        ob_start();
        ?>
        <div class="gf-ecoles-sync-dashboard">
            <table class="gf-ecoles-sync-table" style="margin: 0;">
                <tbody>
                    <tr>
                        <th style="padding-left: 0; white-space: nowrap;"><?php esc_html_e('Sync Status', 'gf-french-schools'); ?></th>
                        <td>
                            <span id="gf-ecoles-sync-status" class="gf-ecoles-status-badge gf-ecoles-status-<?php echo esc_attr($status['status']); ?>">
                                <?php echo esc_html(self::get_status_label($status['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding-left: 0;"><?php esc_html_e('Last Successful Sync', 'gf-french-schools'); ?></th>
                        <td id="gf-ecoles-last-sync">
                            <?php
                            if (!empty($status['last_sync'])) {
                                echo esc_html(
                                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $status['last_sync'])
                                );
                            } else {
                                esc_html_e('Never', 'gf-french-schools');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding-left: 0;"><?php esc_html_e('Records in Local DB', 'gf-french-schools'); ?></th>
                        <td id="gf-ecoles-record-count">
                            <?php echo esc_html(number_format_i18n($status['record_count'])); ?>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding-left: 0;"><?php esc_html_e('Next Scheduled Sync', 'gf-french-schools'); ?></th>
                        <td>
                            <?php
                            if ($next_scheduled) {
                                echo esc_html(
                                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled)
                                );
                            } else {
                                esc_html_e('Not scheduled', 'gf-french-schools');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if (!empty($status['error'])) : ?>
                    <tr>
                        <th style="padding-left: 0;"><?php esc_html_e('Last Error', 'gf-french-schools'); ?></th>
                        <td class="gf-ecoles-error-message">
                            <?php echo esc_html($status['error']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 12px;">
                <button type="button" id="gf-ecoles-sync-btn" class="button">
                    <?php esc_html_e('Sync Now', 'gf-french-schools'); ?>
                </button>
                <span id="gf-ecoles-sync-spinner" class="spinner" style="float:none;"></span>
                <span id="gf-ecoles-sync-message" class="gf-ecoles-sync-msg"></span>
            </p>
        </div>
        <?php
        $html = ob_get_clean();

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    /**
     * Load plugin settings, migrating legacy option on first access.
     *
     * @return array
     */
    public function get_plugin_settings()
    {
        $settings = parent::get_plugin_settings();

        // Migrate legacy option on first load.
        if (!is_array($settings)) {
            $settings = array();
            $legacy = get_option('gf_ecoles_fr_local_only', false);
            if ($legacy) {
                $settings['local_only'] = '1';
            }
            parent::update_plugin_settings($settings);
        }

        return $settings;
    }

    /**
     * Persist plugin settings and sync the legacy option.
     *
     * @param array $settings The settings to save.
     * @return void
     */
    public function update_plugin_settings($settings)
    {
        $local_only = !empty($settings['local_only']);

        // Disable local-only automatically when no data is available.
        if ($local_only && class_exists('GF_Ecoles_Local_DB') && !GF_Ecoles_Local_DB::has_data()) {
            $local_only = false;
            $settings['local_only'] = '';
        }

        // Sync fallback API option.
        $local_fallback_api = !empty($settings['local_fallback_api']) && $local_only;
        if (!$local_only) {
            $settings['local_fallback_api'] = '';
        }

        parent::update_plugin_settings($settings);
        update_option('gf_ecoles_fr_local_only', $local_only);
        update_option('gf_ecoles_fr_local_fallback_api', $local_fallback_api);
    }

    /**
     * Return a human-readable status label.
     *
     * @param string $status Status key.
     * @return string
     */
    private static function get_status_label($status)
    {
        $labels = array(
            'idle'    => __('Idle — no sync has run yet', 'gf-french-schools'),
            'running' => __('Sync in progress…', 'gf-french-schools'),
            'success' => __('OK', 'gf-french-schools'),
            'error'   => __('Error', 'gf-french-schools'),
        );
        return $labels[$status] ?? $status;
    }
}
