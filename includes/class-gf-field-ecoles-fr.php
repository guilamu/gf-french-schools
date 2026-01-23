<?php
/**
 * Gravity Forms Field: Écoles FR
 *
 * @package GF_French_Schools
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GF_Field_Ecoles_FR
 *
 * Custom Gravity Forms field for selecting French schools.
 */
class GF_Field_Ecoles_FR extends GF_Field
{

    /**
     * Field type identifier.
     *
     * @var string
     */
    public $type = 'ecoles_fr';

    /**
     * List of French departments.
     *
     * @var array
     */
    private static $departements = array(
        'Ain',
        'Aisne',
        'Allier',
        'Alpes-Maritimes',
        'Alpes-de-Haute-Provence',
        'Ardèche',
        'Ardennes',
        'Ariège',
        'Aube',
        'Aude',
        'Aveyron',
        'Bas-Rhin',
        'Bouches-du-Rhône',
        'Calvados',
        'Cantal',
        'Charente',
        'Charente-Maritime',
        'Cher',
        'Corrèze',
        'Corse-du-Sud',
        "Côte-d'Or",
        "Côtes-d'Armor",
        'Creuse',
        'Deux-Sèvres',
        'Dordogne',
        'Doubs',
        'Drôme',
        'Essonne',
        'Eure',
        'Eure-et-Loir',
        'Finistère',
        'Gard',
        'Gers',
        'Gironde',
        'Guadeloupe',
        'Guyane',
        'Haut-Rhin',
        'Haute-Corse',
        'Haute-Garonne',
        'Haute-Loire',
        'Haute-Marne',
        'Haute-Saône',
        'Haute-Savoie',
        'Haute-Vienne',
        'Hautes-Alpes',
        'Hautes-Pyrénées',
        'Hauts-de-Seine',
        'Hérault',
        'Ille-et-Vilaine',
        'Indre',
        'Indre-et-Loire',
        'Isère',
        'Jura',
        'La Réunion',
        'Landes',
        'Loir-et-Cher',
        'Loire',
        'Loire-Atlantique',
        'Loiret',
        'Lot',
        'Lot-et-Garonne',
        'Lozère',
        'Maine-et-Loire',
        'Manche',
        'Marne',
        'Martinique',
        'Mayenne',
        'Mayotte',
        'Meurthe-et-Moselle',
        'Meuse',
        'Morbihan',
        'Moselle',
        'Nièvre',
        'Nord',
        'Nouvelle Calédonie',
        'Oise',
        'Orne',
        'Paris',
        'Pas-de-Calais',
        'Polynésie Française',
        'Puy-de-Dôme',
        'Pyrénées-Atlantiques',
        'Pyrénées-Orientales',
        'Rhône',
        'Saint-Barthélémy',
        'Saint-Martin',
        'Saône-et-Loire',
        'Sarthe',
        'Savoie',
        'Seine-Maritime',
        'Seine-Saint-Denis',
        'Seine-et-Marne',
        'Somme',
        'St-Pierre-et-Miquelon',
        'Tarn',
        'Tarn-et-Garonne',
        'Territoire de Belfort',
        "Val-d'Oise",
        'Val-de-Marne',
        'Var',
        'Vaucluse',
        'Vendée',
        'Vienne',
        'Vosges',
        'Wallis et Futuna',
        'Yonne',
        'Yvelines',
    );

    /**
     * Get form editor button configuration.
     *
     * @return array
     */
    public function get_form_editor_button()
    {
        return array(
            'group' => 'advanced_fields',
            'text' => $this->get_form_editor_field_title(),
        );
    }

    /**
     * Get form editor field title.
     *
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('French Schools', 'gf-french-schools');
    }

    /**
     * Get form editor field description.
     *
     * @return string
     */
    public function get_form_editor_field_description()
    {
        return esc_attr__('Allows users to search and select a French educational institution.', 'gf-french-schools');
    }

    /**
     * Get form editor field icon.
     *
     * @return string
     */
    public function get_form_editor_field_icon()
    {
        return 'gform-icon--place';
    }

    /**
     * Get form editor field settings.
     *
     * @return array
     */
    public function get_form_editor_field_settings()
    {
        return array(
            'label_setting',
            'description_setting',
            'rules_setting',
            'error_message_setting',
            'css_class_setting',
            'conditional_logic_field_setting',
            'label_placement_setting',
            'admin_label_setting',
            'visibility_setting',
            'ecoles_fr_preselection_setting',
        );
    }

    /**
     * Check if field is conditional logic supported.
     *
     * @return bool
     */
    public function is_conditional_logic_supported()
    {
        return true;
    }

    /**
     * Get the list of departments.
     *
     * @return array
     */
    public static function get_departements()
    {
        return self::$departements;
    }

    /**
     * Get field input HTML.
     *
     * @param array  $form  The form object.
     * @param string $value The field value.
     * @param array  $entry The entry object.
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null)
    {
        $form_id = absint($form['id']);
        $field_id = absint($this->id);
        $is_admin = $this->is_form_editor() || $this->is_entry_detail() || $this->is_entry_detail_edit();

        // Parse existing value
        $data = array();
        if (!empty($value)) {
            $data = json_decode($value, true);
            if (!is_array($data)) {
                $data = array();
            }
        }

        // Get preselected values from field settings
        $preselected_statut = !empty($this->preselectedStatut) ? $this->preselectedStatut : '';
        $preselected_departement = !empty($this->preselectedDepartement) ? $this->preselectedDepartement : '';

        // Get school type filter settings
        $hide_ecoles = !empty($this->hideEcoles) ? 'true' : 'false';
        $hide_colleges_lycees = !empty($this->hideCollegesLycees) ? 'true' : 'false';
        $hide_result = !empty($this->hideResult) ? 'true' : 'false';

        // Use preselected values if available, otherwise use saved data
        $statut_value = !empty($preselected_statut) ? $preselected_statut : ($data['statut'] ?? '');
        $departement_value = !empty($preselected_departement) ? $preselected_departement : ($data['departement'] ?? '');
        $ville_value = $data['ville'] ?? '';
        $ecole_value = $data['nom'] ?? '';
        $autres_nom_value = $data['autres_nom'] ?? '';

        // Determine which fields should be hidden
        // Only hide on frontend (not in form editor or entry detail)
        $is_form_editor = $this->is_form_editor();
        $hide_statut = !empty($preselected_statut) && !$is_form_editor;
        $hide_departement = !empty($preselected_departement) && !$is_form_editor;

        $input_id = "input_{$form_id}_{$field_id}";
        $disabled = $is_admin ? 'disabled="disabled"' : '';
        $tabindex = $this->get_tabindex();
        $aria_label = esc_attr($this->label);

        // Build the field HTML
        ob_start();
        ?>
        <div class="gf-ecoles-fr-wrapper ginput_complex ginput_container" id="<?php echo esc_attr($input_id); ?>_container"
            data-field-id="<?php echo esc_attr($field_id); ?>"
            data-form-id="<?php echo esc_attr($form_id); ?>"
            data-preselected-statut="<?php echo esc_attr($preselected_statut); ?>"
            data-preselected-departement="<?php echo esc_attr($preselected_departement); ?>"
            data-hide-ecoles="<?php echo esc_attr($hide_ecoles); ?>"
            data-hide-colleges-lycees="<?php echo esc_attr($hide_colleges_lycees); ?>"
            data-hide-result="<?php echo esc_attr($hide_result); ?>">

            <!-- Hidden field for storing complete data -->
            <input type="hidden" name="input_<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($input_id); ?>"
                value="<?php echo esc_attr($value); ?>" class="gf-ecoles-fr-data" />

            <!-- Statut -->
            <span class="gf-ecoles-fr-field gf-ecoles-fr-statut-field<?php echo $hide_statut ? ' gf-ecoles-fr-hidden' : ''; ?>">
                <label for="<?php echo esc_attr($input_id); ?>_statut">
                    <?php esc_html_e('Status', 'gf-french-schools'); ?>
                </label>
                <select id="<?php echo esc_attr($input_id); ?>_statut" class="gf-ecoles-fr-statut" <?php echo $disabled; ?>
                    aria-label="<?php esc_attr_e('School status', 'gf-french-schools'); ?>">
                    <option value=""><?php esc_html_e('-- Select --', 'gf-french-schools'); ?></option>
                    <option value="Public" <?php selected($statut_value, 'Public'); ?>>
                        <?php esc_html_e('Public', 'gf-french-schools'); ?>
                    </option>
                    <option value="Privé" <?php selected($statut_value, 'Privé'); ?>>
                        <?php esc_html_e('Private', 'gf-french-schools'); ?>
                    </option>
                </select>
            </span>

            <!-- Département -->
            <span
                class="gf-ecoles-fr-field gf-ecoles-fr-departement-field<?php echo $hide_departement ? ' gf-ecoles-fr-hidden' : ''; ?>">
                <label for="<?php echo esc_attr($input_id); ?>_departement">
                    <?php esc_html_e('Department', 'gf-french-schools'); ?>
                </label>
                <select id="<?php echo esc_attr($input_id); ?>_departement" class="gf-ecoles-fr-departement" <?php echo $is_admin ? 'disabled="disabled"' : ((empty($statut_value) || $hide_departement) ? 'disabled="disabled"' : ''); ?>
                    aria-label="<?php esc_attr_e('Department', 'gf-french-schools'); ?>">
                    <option value=""><?php esc_html_e('-- Select status first --', 'gf-french-schools'); ?></option>
                    <?php foreach (self::$departements as $dept): ?>
                        <option value="<?php echo esc_attr($dept); ?>" <?php selected($departement_value, $dept); ?>>
                            <?php echo esc_html($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </span>

            <!-- Ville -->
            <span class="gf-ecoles-fr-field gf-ecoles-fr-ville-field">
                <label for="<?php echo esc_attr($input_id); ?>_ville">
                    <?php esc_html_e('City', 'gf-french-schools'); ?>
                </label>
                <div class="gf-ecoles-fr-autocomplete-wrapper">
                    <input type="text" id="<?php echo esc_attr($input_id); ?>_ville" class="gf-ecoles-fr-ville"
                        value="<?php echo esc_attr($ville_value); ?>"
                        placeholder="<?php esc_attr_e('Start typing city name...', 'gf-french-schools'); ?>" autocomplete="off"
                        <?php echo $is_admin ? 'disabled="disabled"' : ((empty($departement_value) && !$hide_departement) ? 'disabled="disabled"' : ''); ?>
                        aria-label="<?php esc_attr_e('City', 'gf-french-schools'); ?>" />
                    <div class="gf-ecoles-fr-autocomplete-results" id="<?php echo esc_attr($input_id); ?>_ville_results">
                    </div>
                </div>
            </span>

            <!-- École -->
            <span class="gf-ecoles-fr-field gf-ecoles-fr-ecole-field">
                <label for="<?php echo esc_attr($input_id); ?>_ecole">
                    <?php esc_html_e('School', 'gf-french-schools'); ?>
                </label>
                <div class="gf-ecoles-fr-autocomplete-wrapper">
                    <input type="text" id="<?php echo esc_attr($input_id); ?>_ecole" class="gf-ecoles-fr-ecole"
                        value="<?php echo esc_attr($ecole_value); ?>"
                        placeholder="<?php esc_attr_e('Start typing school name...', 'gf-french-schools'); ?>"
                        autocomplete="off" <?php echo $is_admin ? 'disabled="disabled"' : ((empty($ville_value) || !empty($autres_nom_value)) ? 'disabled="disabled"' : ''); ?>
                        aria-label="<?php esc_attr_e('School', 'gf-french-schools'); ?>" />
                    <div class="gf-ecoles-fr-autocomplete-results" id="<?php echo esc_attr($input_id); ?>_ecole_results">
                    </div>
                </div>
            </span>

            <!-- Autres (Manual input when school not found) -->
            <span class="gf-ecoles-fr-field gf-ecoles-fr-autres-field gf-ecoles-fr-hidden">
                <label for="<?php echo esc_attr($input_id); ?>_autres">
                    <?php esc_html_e('Other (School not found)', 'gf-french-schools'); ?>
                </label>
                <div class="gf-ecoles-fr-autres-wrapper">
                    <input type="text" id="<?php echo esc_attr($input_id); ?>_autres" class="gf-ecoles-fr-autres"
                        value="<?php echo esc_attr($data['autres_nom'] ?? ''); ?>"
                        placeholder="<?php esc_attr_e('Enter school name manually...', 'gf-french-schools'); ?>"
                        autocomplete="off"
                        aria-label="<?php esc_attr_e('Other school name', 'gf-french-schools'); ?>" />
                    <button type="button" class="gf-ecoles-fr-autres-cancel" aria-label="<?php esc_attr_e('Cancel manual entry', 'gf-french-schools'); ?>">
                        <?php esc_html_e('Cancel', 'gf-french-schools'); ?>
                    </button>
                </div>
            </span>

            <?php if (empty($this->hideResult)) : ?>
                <!-- Result display -->
                <div class="gf-ecoles-fr-result" id="<?php echo esc_attr($input_id); ?>_result"
                    style="<?php echo empty($data['identifiant']) ? 'display:none;' : ''; ?>">
                    <div class="gf-ecoles-fr-result-header">
                        <?php esc_html_e('Selected School Information', 'gf-french-schools'); ?>
                    </div>
                    <div class="gf-ecoles-fr-result-grid">
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('ID', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="identifiant"><?php echo esc_html($data['identifiant'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Name', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="nom"><?php echo esc_html($data['nom'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Type', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="type"><?php echo esc_html($data['type'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Category', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="nature"><?php echo esc_html($data['nature'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Address', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="adresse"><?php echo esc_html($data['adresse'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Postal Code', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="code_postal"><?php echo esc_html($data['code_postal'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('City', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="commune"><?php echo esc_html($data['commune'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Phone', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="telephone"><?php echo esc_html($data['telephone'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span class="gf-ecoles-fr-result-label"><?php esc_html_e('Email', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="mail"><?php echo esc_html($data['mail'] ?? ''); ?></span>
                        </div>
                        <div class="gf-ecoles-fr-result-item">
                            <span
                                class="gf-ecoles-fr-result-label"><?php esc_html_e('Priority Education', 'gf-french-schools'); ?></span>
                            <span class="gf-ecoles-fr-result-value"
                                data-field="education_prioritaire"><?php echo esc_html(!empty($data['education_prioritaire']) ? $data['education_prioritaire'] : __('No', 'gf-french-schools')); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get field value for entry save.
     *
     * @param array  $value      The field value.
     * @param array  $form       The form object.
     * @param string $input_name The input name.
     * @param int    $lead_id    The entry ID.
     * @param array  $lead       The entry object.
     * @return string
     */
    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        // Value is already JSON from the hidden input
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * Get field value for entry detail display.
     *
     * @param string|array $value    The field value.
     * @param string       $currency The currency.
     * @param bool         $use_text Whether to use text mode.
     * @param string       $format   The format.
     * @param string       $media    The media type.
     * @return string
     */
    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen')
    {
        if (empty($value)) {
            return '';
        }

        $data = json_decode($value, true);
        if (!is_array($data)) {
            return esc_html($value);
        }

        if ($format === 'text') {
            $lines = array();
            // Check if this is a manual entry
            if (!empty($data['autres_nom'])) {
                $lines[] = sprintf('%s: %s', __('Name (Manual Entry)', 'gf-french-schools'), $data['autres_nom']);
                $lines[] = sprintf('%s: %s', __('City', 'gf-french-schools'), $data['ville'] ?? '');
                return implode("\n", $lines);
            }
            $lines[] = sprintf('%s: %s', __('ID', 'gf-french-schools'), $data['identifiant'] ?? '');
            $lines[] = sprintf('%s: %s', __('Name', 'gf-french-schools'), $data['nom'] ?? '');
            $lines[] = sprintf('%s: %s', __('Type', 'gf-french-schools'), $data['type'] ?? '');
            $lines[] = sprintf('%s: %s', __('Category', 'gf-french-schools'), $data['nature'] ?? '');
            $lines[] = sprintf('%s: %s', __('Address', 'gf-french-schools'), $data['adresse'] ?? '');
            $lines[] = sprintf('%s: %s', __('Postal Code', 'gf-french-schools'), $data['code_postal'] ?? '');
            $lines[] = sprintf('%s: %s', __('City', 'gf-french-schools'), $data['commune'] ?? '');
            $lines[] = sprintf('%s: %s', __('Phone', 'gf-french-schools'), $data['telephone'] ?? '');
            $lines[] = sprintf('%s: %s', __('Email', 'gf-french-schools'), $data['mail'] ?? '');
            $lines[] = sprintf('%s: %s', __('Priority Education', 'gf-french-schools'), $data['education_prioritaire'] ?? '');
            return implode("\n", $lines);
        }

        // HTML format
        $html = '<div class="gf-ecoles-fr-entry-detail">';
        $html .= '<table class="gf-ecoles-fr-entry-table">';
        // Check if this is a manual entry
        if (!empty($data['autres_nom'])) {
            $html .= '<tr><th>' . esc_html__('Name (Manual Entry)', 'gf-french-schools') . '</th><td>' . esc_html($data['autres_nom']) . '</td></tr>';
            $html .= '<tr><th>' . esc_html__('City', 'gf-french-schools') . '</th><td>' . esc_html($data['ville'] ?? '') . '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
            return $html;
        }
        $html .= '<tr><th>' . esc_html__('ID', 'gf-french-schools') . '</th><td>' . esc_html($data['identifiant'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Name', 'gf-french-schools') . '</th><td>' . esc_html($data['nom'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Type', 'gf-french-schools') . '</th><td>' . esc_html($data['type'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Category', 'gf-french-schools') . '</th><td>' . esc_html($data['nature'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Address', 'gf-french-schools') . '</th><td>' . esc_html($data['adresse'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Postal Code', 'gf-french-schools') . '</th><td>' . esc_html($data['code_postal'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('City', 'gf-french-schools') . '</th><td>' . esc_html($data['commune'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Phone', 'gf-french-schools') . '</th><td>' . esc_html($data['telephone'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Email', 'gf-french-schools') . '</th><td>' . esc_html($data['mail'] ?? '') . '</td></tr>';
        $html .= '<tr><th>' . esc_html__('Priority Education', 'gf-french-schools') . '</th><td>' . esc_html($data['education_prioritaire'] ?? '') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get field value for merge tag.
     *
     * @param string|array $value      The field value.
     * @param string       $input_id   The input ID.
     * @param array        $entry      The entry object.
     * @param array        $form       The form object.
     * @param string       $modifier   The merge tag modifier.
     * @param string|array $raw_value  The raw field value.
     * @param bool         $url_encode Whether to URL encode.
     * @param bool         $esc_html   Whether to escape HTML.
     * @param string       $format     The format.
     * @param bool         $nl2br      Whether to convert newlines to BR.
     * @return string
     */
    public function get_value_merge_tag($value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br)
    {
        if (empty($value)) {
            return '';
        }

        $data = json_decode($value, true);
        if (!is_array($data)) {
            return $value;
        }

        // If no modifier, return school name (or manual entry name)
        if (empty($modifier)) {
            return $data['nom'] ?? $data['autres_nom'] ?? '';
        }

        // Mapping of modifiers to data keys
        $modifier_map = array(
            'id' => 'identifiant',
            'identifiant' => 'identifiant',
            'nom' => 'nom',
            'name' => 'nom',
            'autres_nom' => 'autres_nom',
            'other_name' => 'autres_nom',
            'type' => 'type',
            'nature' => 'nature',
            'category' => 'nature',
            'adresse' => 'adresse',
            'address' => 'adresse',
            'code_postal' => 'code_postal',
            'postal_code' => 'code_postal',
            'commune' => 'commune',
            'city' => 'commune',
            'telephone' => 'telephone',
            'phone' => 'telephone',
            'mail' => 'mail',
            'email' => 'mail',
            'education_prioritaire' => 'education_prioritaire',
            'priority_education' => 'education_prioritaire',
            'all' => 'all',
        );

        $modifier_lower = strtolower($modifier);

        if ($modifier_lower === 'all') {
            // Return all data as formatted text
            $lines = array();
            foreach ($data as $key => $val) {
                if (!empty($val) && $key !== 'statut' && $key !== 'departement' && $key !== 'ville') {
                    $lines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $val;
                }
            }
            return implode("\n", $lines);
        }

        if (isset($modifier_map[$modifier_lower])) {
            $data_key = $modifier_map[$modifier_lower];
            return $data[$data_key] ?? '';
        }

        // Unknown modifier, return full value
        return $data['nom'] ?? '';
    }

    /**
     * Get merge tag modifiers for this field.
     *
     * @param array $modifiers Existing modifiers.
     * @return array
     */
    public function get_modifiers()
    {
        return array(
            'id',
            'nom',
            'type',
            'nature',
            'adresse',
            'code_postal',
            'commune',
            'telephone',
            'mail',
            'education_prioritaire',
            'all',
        );
    }

    /**
     * Validate the field value.
     *
     * @param string|array $value The field value.
     * @param array        $form  The form object.
     */
    public function validate($value, $form)
    {
        if ($this->isRequired) {
            if (empty($value)) {
                $this->failed_validation = true;
                $this->validation_message = empty($this->errorMessage)
                    ? __('This field is required. Please select a school.', 'gf-french-schools')
                    : $this->errorMessage;
                return;
            }

            $data = json_decode($value, true);
            // Accept either a school from the API (has identifiant) or a manual entry (has autres_nom)
            if (!is_array($data) || (empty($data['identifiant']) && empty($data['autres_nom']))) {
                $this->failed_validation = true;
                $this->validation_message = empty($this->errorMessage)
                    ? __('This field is required. Please select a school.', 'gf-french-schools')
                    : $this->errorMessage;
            }
        }
    }

    /**
     * Get value for export.
     *
     * @param array  $entry    The entry object.
     * @param string $input_id The input ID.
     * @param bool   $use_text Whether to use text mode.
     * @param bool   $is_csv   Whether exporting to CSV.
     * @return string
     */
    public function get_value_export($entry, $input_id = '', $use_text = false, $is_csv = false)
    {
        if (empty($this->id)) {
            return '';
        }

        $value = rgar($entry, $this->id);
        if (empty($value)) {
            return '';
        }

        $data = json_decode($value, true);
        if (!is_array($data)) {
            return $value;
        }

        // Check if this is a manual entry
        if (!empty($data['autres_nom'])) {
            return sprintf('%s (%s)', $data['autres_nom'], __('Manual Entry', 'gf-french-schools'));
        }

        // Return school name and ID for export
        return sprintf('%s (%s)', $data['nom'] ?? '', $data['identifiant'] ?? '');
    }
}
/**
 * Add custom merge tags to the merge tag dropdown.
 */
add_filter('gform_custom_merge_tags', 'gf_french_schools_custom_merge_tags', 10, 4);

function gf_french_schools_custom_merge_tags($merge_tags, $form_id, $fields, $element_id)
{
    foreach ($fields as $field) {
        if ($field->type === 'ecoles_fr') {
            $field_id = $field->id;
            $field_label = $field->label;

            $modifiers = array(
                'id' => __('School ID', 'gf-french-schools'),
                'nom' => __('School Name', 'gf-french-schools'),
                'type' => __('School Type', 'gf-french-schools'),
                'nature' => __('School Category', 'gf-french-schools'),
                'adresse' => __('Address', 'gf-french-schools'),
                'code_postal' => __('Postal Code', 'gf-french-schools'),
                'commune' => __('City', 'gf-french-schools'),
                'telephone' => __('Phone', 'gf-french-schools'),
                'mail' => __('Email', 'gf-french-schools'),
                'education_prioritaire' => __('Priority Education', 'gf-french-schools'),
                'all' => __('All Information', 'gf-french-schools'),
            );

            foreach ($modifiers as $mod => $label) {
                $merge_tags[] = array(
                    'label' => sprintf('%s - %s', $field_label, $label),
                    'tag' => sprintf('{%s:%d:%s}', $field_label, $field_id, $mod),
                );
            }
        }
    }

    return $merge_tags;
}
