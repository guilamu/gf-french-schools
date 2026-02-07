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

        // Load result visibility
        $('#ecoles_fr_hide_result').prop('checked', field.hideResult === true);
    });

    // Persist setting changes without inline handlers.
    $(document).on('change', '.ecoles-fr-setting', function () {
        var setting = $(this).data('setting');
        if (!setting) {
            return;
        }

        var value;
        if ($(this).is(':checkbox')) {
            value = $(this).is(':checked');
        } else {
            value = $(this).val();
        }

        SetFieldProperty(setting, value);
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

    // ------------------------------------------------------------------
    // Sync settings page (Settings > French Schools)
    // Uses event delegation so it works regardless of DOM timing.
    // ------------------------------------------------------------------

    $(document).on('click', '#gf-ecoles-sync-btn', function () {
        if (typeof gfEcolesFRSync === 'undefined') {
            return;
        }

        var $btn = $(this);
        var $spinner = $('#gf-ecoles-sync-spinner');
        var $msg = $('#gf-ecoles-sync-message');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $msg.text(gfEcolesFRSync.i18n.syncing).removeClass('gf-ecoles-msg-error gf-ecoles-msg-success');

        $.ajax({
            url: gfEcolesFRSync.ajaxUrl,
            type: 'POST',
            timeout: 600000, // 10 minutes
            data: {
                action: 'gf_ecoles_fr_manual_sync',
                nonce: gfEcolesFRSync.nonce
            },
            success: function (response) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);

                if (response.success) {
                    $msg.text(response.data.message).addClass('gf-ecoles-msg-success');
                    updateStatusDisplay(response.data.status);
                } else {
                    $msg.text(response.data.message || gfEcolesFRSync.i18n.error).addClass('gf-ecoles-msg-error');
                    if (response.data && response.data.status) {
                        updateStatusDisplay(response.data.status);
                    }
                }
            },
            error: function () {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                $msg.text(gfEcolesFRSync.i18n.error).addClass('gf-ecoles-msg-error');
            }
        });
    });

    /**
     * Update the status fields on the page after a sync.
     */
    function updateStatusDisplay(status) {
        if (!status) return;

        var $badge = $('#gf-ecoles-sync-status');
        $badge.removeClass('gf-ecoles-status-idle gf-ecoles-status-running gf-ecoles-status-success gf-ecoles-status-error');
        $badge.addClass('gf-ecoles-status-' + status.status);

        var statusLabels = {
            'idle': gfEcolesFRSync.i18n.never,
            'running': gfEcolesFRSync.i18n.statusRunning,
            'success': gfEcolesFRSync.i18n.statusOk,
            'error': gfEcolesFRSync.i18n.statusError
        };
        $badge.text(statusLabels[status.status] || status.status);

        if (status.record_count !== undefined) {
            $('#gf-ecoles-record-count').text(Number(status.record_count).toLocaleString());
        }

        if (status.last_sync) {
            var d = new Date(status.last_sync * 1000);
            $('#gf-ecoles-last-sync').text(d.toLocaleString());
        }
    }

})(jQuery);
