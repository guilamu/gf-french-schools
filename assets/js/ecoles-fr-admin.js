/**
 * Gravity Forms French Schools - Admin JavaScript
 *
 * Handles field settings in the form editor.
 *
 * @package GF_French_Schools
 */

(function ($) {
    'use strict';

    // Bind to the form editor when field settings are loaded
    $(document).on('gform_load_field_settings', function (event, field, form) {
        if (field.type !== 'ecoles_fr') {
            return;
        }

        // Load preselected status value
        var preselectedStatut = field.preselectedStatut || '';
        $('#ecoles_fr_preselected_statut').val(preselectedStatut);

        // Load preselected department value
        var preselectedDepartement = field.preselectedDepartement || '';
        $('#ecoles_fr_preselected_departement').val(preselectedDepartement);

        // Load school type filter values
        $('#ecoles_fr_hide_ecoles').prop('checked', field.hideEcoles === true);
        $('#ecoles_fr_hide_colleges_lycees').prop('checked', field.hideCollegesLycees === true);
    });

    // Custom field title in the editor
    if (typeof gform !== 'undefined' && gform.addFilter) {
        gform.addFilter('gform_form_editor_can_field_be_added', function (canBeAdded, type) {
            if (type === 'ecoles_fr') {
                return true;
            }
            return canBeAdded;
        });
    }

})(jQuery);
