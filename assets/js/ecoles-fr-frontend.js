/**
 * Gravity Forms French Schools - Frontend JavaScript
 *
 * Handles the cascading field logic and autocomplete functionality.
 *
 * @package GF_French_Schools
 */

(function ($) {
    'use strict';

    // Timing configuration (overridable via localized data).
    var TIMINGS = $.extend({
        debounce: 300,
        ajaxTimeout: 15000,
        retryLimit: 2,
        retryDelay: 700,
    }, (window.gfEcolesFR && gfEcolesFR.timings) ? gfEcolesFR.timings : {});

    /**
     * Debounce function to limit API calls.
     * @param {Function} func
     * @param {number} wait
     * @returns {Function}
     */
    function debounce(func, wait) {
        var timeout;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Clean display value by removing school type words (for nom field).
     */
    function cleanDisplayValue(value) {
        if (!value) return '';
        // Remove specific phrases first
        var phrasesToRemove = ["Section d'enseignement général et professionnel adapté"];
        var cleaned = value;
        phrasesToRemove.forEach(function (phrase) {
            cleaned = cleaned.replace(new RegExp(phrase, 'gi'), '');
        });
        // Then remove individual words
        var wordsToRemove = ['Collège', 'Lycée', 'Ecole', 'École', 'Primaire', 'Maternelle', 'Elementaire', 'Élémentaire'];
        wordsToRemove.forEach(function (word) {
            var regex = new RegExp('\\b' + word + '\\b', 'gi');
            cleaned = cleaned.replace(regex, '');
        });
        // Clean up extra whitespace
        return cleaned.replace(/\s+/g, ' ').trim();
    }

    /**
     * Clean category value by removing only 'école/ecole' and converting to sentence case.
     */
    function cleanCategoryValue(value) {
        if (!value) return '';
        var cleaned = value.replace(/\b(ecole|école)\b/gi, '');
        cleaned = cleaned.replace(/\s+/g, ' ').trim();
        // Convert to sentence case (first letter uppercase, rest lowercase)
        if (cleaned.length > 0) {
            cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
        }
        return cleaned;
    }

    /**
     * Initialize the French Schools field.
     */
    function initEcolesFRField() {
        $('.gf-ecoles-fr-wrapper').each(function () {
            var $wrapper = $(this);
            var fieldId = $wrapper.data('field-id');
            var formId = $wrapper.data('form-id');

            if ($wrapper.data('initialized')) {
                return;
            }
            $wrapper.data('initialized', true);

            var $statut = $wrapper.find('.gf-ecoles-fr-statut');
            var $departement = $wrapper.find('.gf-ecoles-fr-departement');
            var $ville = $wrapper.find('.gf-ecoles-fr-ville');
            var $ecole = $wrapper.find('.gf-ecoles-fr-ecole');
            var $autres = $wrapper.find('.gf-ecoles-fr-autres');
            var $autresField = $wrapper.find('.gf-ecoles-fr-autres-field');
            var $autresCancel = $wrapper.find('.gf-ecoles-fr-autres-cancel');
            var $dataInput = $wrapper.find('.gf-ecoles-fr-data');
            var $result = $wrapper.find('.gf-ecoles-fr-result');
            var $villeResults = $wrapper.find('.gf-ecoles-fr-ville-field .gf-ecoles-fr-autocomplete-results');
            var $ecoleResults = $wrapper.find('.gf-ecoles-fr-ecole-field .gf-ecoles-fr-autocomplete-results');

            // Get preselected values from data attributes
            var preselectedStatut = $wrapper.data('preselected-statut') || '';
            var preselectedDepartement = $wrapper.data('preselected-departement') || '';

            // Get filter settings from data attributes
            var hideEcoles = $wrapper.data('hide-ecoles') === 'true' || $wrapper.data('hide-ecoles') === true;
            var hideCollegesLycees = $wrapper.data('hide-colleges-lycees') === 'true' || $wrapper.data('hide-colleges-lycees') === true;
            var hideResult = $wrapper.data('hide-result') === 'true' || $wrapper.data('hide-result') === true;

            if (hideResult) {
                $result = null;
            }

            var selectedVille = '';
            var schoolsData = [];
            var activeVilleRequest = null;
            var activeEcoleRequest = null;
            var lastVilleParams = '';
            var lastEcoleParams = '';

            // If both statut and departement are preselected, enable ville field
            if (preselectedStatut && preselectedDepartement) {
                $ville.prop('disabled', false).removeClass('disabled');
            } else if (preselectedStatut && !preselectedDepartement) {
                // Only statut is preselected, enable departement
                $departement.prop('disabled', false).removeClass('disabled');
            }

            // Hide preselected fields on frontend using class-based hiding
            // This matches the pattern from gf-chained-select-enhancer
            if (preselectedStatut) {
                $wrapper.find('.gf-ecoles-fr-statut-field').addClass('gf-ecoles-fr-hidden');
            }
            if (preselectedDepartement) {
                $wrapper.find('.gf-ecoles-fr-departement-field').addClass('gf-ecoles-fr-hidden');
            }

            // Initialize selected ville from current data
            try {
                var currentData = JSON.parse($dataInput.val() || '{}');
                if (currentData.ville) {
                    selectedVille = currentData.ville;
                }
                // Ensure proper state on page load based on existing data
                if (currentData.autres_nom) {
                    // Manual entry exists - show Autres field, disable école
                    $autresField.removeClass('gf-ecoles-fr-hidden');
                    $ecole.prop('disabled', true).addClass('disabled');
                } else {
                    // No manual entry - hide Autres field
                    $autresField.addClass('gf-ecoles-fr-hidden');
                    if (currentData.identifiant || $ecole.val()) {
                        // School is selected - enable école field
                        $ecole.prop('disabled', false).removeClass('disabled');
                    }
                }
            } catch (e) {
                // Invalid JSON, ensure Autres is hidden
                $autresField.addClass('gf-ecoles-fr-hidden');
            }

            // Statut change handler
            $statut.on('change', function () {
                var value = $(this).val();

                if (value) {
                    $departement.prop('disabled', false)
                        .removeClass('disabled')
                        .find('option:first').text(gfEcolesFR.i18n.selectDepartement || '-- Select --');
                } else {
                    resetFields(['departement', 'ville', 'ecole']);
                }

                updateDataInput();
            });

            // Département change handler
            $departement.on('change', function () {
                var value = $(this).val();

                if (value) {
                    $ville.prop('disabled', false)
                        .removeClass('disabled')
                        .val('');
                    selectedVille = '';
                    $ecole.prop('disabled', true)
                        .addClass('disabled')
                        .val('');
                    if ($result) {
                        $result.hide();
                    }
                } else {
                    resetFields(['ville', 'ecole']);
                }

                updateDataInput();
            });

            // Ville input handler with debounce
            $ville.on('input', debounce(function () {
                var query = $(this).val().trim();

                if (query.length < 2) {
                    $villeResults.empty().hide();
                    return;
                }

                searchVilles(query);
            }, TIMINGS.debounce));

            // Ville focus out - hide results after delay
            $ville.on('blur', function () {
                setTimeout(function () {
                    $villeResults.hide();
                }, 200);
            });

            // École input handler with debounce
            $ecole.on('input', debounce(function () {
                var query = $(this).val().trim();

                if (query.length < 2) {
                    $ecoleResults.empty().hide();
                    return;
                }

                searchEcoles(query);
            }, TIMINGS.debounce));

            // École focus out - hide results after delay
            $ecole.on('blur', function () {
                setTimeout(function () {
                    $ecoleResults.hide();
                }, 200);
            });

            /**
             * Search for cities via AJAX.
             */
            function searchVilles(query, retryCount) {
                retryCount = retryCount || 0;

                var params = {
                    action: 'gf_ecoles_fr_search',
                    nonce: gfEcolesFR.nonce,
                    form_id: formId,
                    search_type: 'villes',
                    statut: $statut.val(),
                    departement: $departement.val(),
                    query: query,
                    hide_ecoles: hideEcoles ? 'true' : 'false',
                    hide_colleges_lycees: hideCollegesLycees ? 'true' : 'false'
                };

                var paramsKey = JSON.stringify(params);
                if (paramsKey === lastVilleParams && $villeResults.is(':visible')) {
                    return;
                }
                lastVilleParams = paramsKey;

                if (activeVilleRequest) {
                    activeVilleRequest.abort();
                }

                $villeResults.html('<div class="gf-ecoles-fr-loading">' + (gfEcolesFR.i18n.searching || 'Searching...') + '</div>').show();

                activeVilleRequest = $.ajax({
                    url: gfEcolesFR.ajaxUrl,
                    type: 'POST',
                    timeout: TIMINGS.ajaxTimeout,
                    data: params,
                    success: function (response) {
                        activeVilleRequest = null;
                        if (response.success && response.data.length > 0) {
                            displayVilleResults(response.data);
                        } else {
                            $villeResults.html('<div class="gf-ecoles-fr-no-results">' + (gfEcolesFR.i18n.noResults || 'No results found') + '</div>');
                        }
                    },
                    error: function (jqXHR, textStatus) {
                        activeVilleRequest = null;

                        if (textStatus === 'abort') {
                            return;
                        }

                        if (retryCount < TIMINGS.retryLimit && (textStatus === 'timeout' || jqXHR.status >= 500)) {
                            setTimeout(function () {
                                searchVilles(query, retryCount + 1);
                            }, TIMINGS.retryDelay * (retryCount + 1));
                            return;
                        }

                        $villeResults.html('<div class="gf-ecoles-fr-error">' + (gfEcolesFR.i18n.errorLoading || 'Error loading results') + '</div>');
                    }
                });
            }

            /**
             * Display city autocomplete results.
             */
            function displayVilleResults(villes) {
                $villeResults.empty().show();

                villes.forEach(function (ville) {
                    var $item = $('<div class="gf-ecoles-fr-autocomplete-item"></div>')
                        .text(ville.label)
                        .on('mousedown', function (e) {
                            e.preventDefault(); // prevent blur before selection is applied

                            $ville.val(ville.value);
                            selectedVille = ville.value;
                            $villeResults.empty().hide();

                            // Enable school field and hide "Autres" field
                            $ecole.prop('disabled', false)
                                .removeClass('disabled')
                                .val('');
                            $autresField.addClass('gf-ecoles-fr-hidden');
                            $autres.val('');
                            if ($result) {
                                $result.hide();
                            }

                            updateDataInput();
                        });
                    $villeResults.append($item);
                });
            }

            /**
             * Search for schools via AJAX.
             */
            function searchEcoles(query, retryCount) {
                retryCount = retryCount || 0;

                var params = {
                    action: 'gf_ecoles_fr_search',
                    nonce: gfEcolesFR.nonce,
                    form_id: formId,
                    search_type: 'ecoles',
                    statut: $statut.val(),
                    departement: $departement.val(),
                    ville: selectedVille,
                    query: query,
                    hide_ecoles: hideEcoles ? 'true' : 'false',
                    hide_colleges_lycees: hideCollegesLycees ? 'true' : 'false'
                };

                var paramsKey = JSON.stringify(params);
                if (paramsKey === lastEcoleParams && $ecoleResults.is(':visible')) {
                    return;
                }
                lastEcoleParams = paramsKey;

                if (activeEcoleRequest) {
                    activeEcoleRequest.abort();
                }

                $ecoleResults.html('<div class="gf-ecoles-fr-loading">' + (gfEcolesFR.i18n.searching || 'Searching...') + '</div>').show();

                activeEcoleRequest = $.ajax({
                    url: gfEcolesFR.ajaxUrl,
                    type: 'POST',
                    timeout: TIMINGS.ajaxTimeout,
                    data: params,
                    success: function (response) {
                        activeEcoleRequest = null;
                        if (response.success && response.data.length > 0) {
                            schoolsData = response.data;
                            displayEcoleResults(response.data);
                        } else {
                            schoolsData = [];
                            // Show "Autres" option when no results found
                            displayNoResultsWithAutres();
                        }
                    },
                    error: function (jqXHR, textStatus) {
                        activeEcoleRequest = null;

                        if (textStatus === 'abort') {
                            return;
                        }

                        if (retryCount < TIMINGS.retryLimit && (textStatus === 'timeout' || jqXHR.status >= 500)) {
                            setTimeout(function () {
                                searchEcoles(query, retryCount + 1);
                            }, TIMINGS.retryDelay * (retryCount + 1));
                            return;
                        }

                        $ecoleResults.html('<div class="gf-ecoles-fr-error">' + (gfEcolesFR.i18n.errorLoading || 'Error loading results') + '</div>');
                    }
                });
            }

            /**
             * Display school autocomplete results.
             */
            function displayEcoleResults(ecoles) {
                $ecoleResults.empty().show();

                ecoles.forEach(function (ecole, index) {
                    var $item = $('<div class="gf-ecoles-fr-autocomplete-item gf-ecoles-fr-ecole-item"></div>')
                        .html('<strong>' + escapeHtml(ecole.nom) + '</strong><br><small>' + escapeHtml(ecole.adresse) + ', ' + escapeHtml(ecole.code_postal) + '</small>')
                        .data('index', index)
                        .on('mousedown', function (e) {
                            e.preventDefault(); // avoid blur swallowing selection
                            selectEcole(ecole);
                        });
                    $ecoleResults.append($item);
                });
            }

            /**
             * Select a school and display its information.
             */
            function selectEcole(ecole) {
                var cleanNom = cleanDisplayValue(ecole.nom);
                var cleanNature = cleanCategoryValue(ecole.nature);

                // Hide "Autres" field if it was shown and clear its value
                $autresField.addClass('gf-ecoles-fr-hidden');
                $autres.val('');

                // Re-enable the ecole field for potential re-search
                $ecole.prop('disabled', false).removeClass('disabled');

                $ecole.val(cleanNom);
                $ecoleResults.empty().hide();

                var fallbackNo = (gfEcolesFR && gfEcolesFR.i18n && gfEcolesFR.i18n.noValue) ? gfEcolesFR.i18n.noValue : 'No';

                if ($result) {
                    // Update result display (apply cleanDisplayValue to remove school type words from nom)
                    $result.find('[data-field="identifiant"]').text(ecole.identifiant || '');
                    $result.find('[data-field="nom"]').text(cleanNom || '');
                    $result.find('[data-field="type"]').text(ecole.type || '');
                    $result.find('[data-field="nature"]').text(cleanNature || '');
                    $result.find('[data-field="adresse"]').text(ecole.adresse || '');
                    $result.find('[data-field="code_postal"]').text(ecole.code_postal || '');
                    $result.find('[data-field="commune"]').text(ecole.commune || '');
                    $result.find('[data-field="telephone"]').text(ecole.telephone || '');
                    $result.find('[data-field="mail"]').text(ecole.mail || '');
                    $result.find('[data-field="education_prioritaire"]').text(ecole.education_prioritaire || fallbackNo);
                    $result.show();
                } else {
                    // Accessibility: when summary is hidden, show key info directly in the field (Type Catégorie Nom)
                    var summaryParts = [ecole.type, cleanNature, cleanNom].filter(Boolean);
                    $ecole.val(summaryParts.join(' '));
                }

                // Update hidden data input
                var data = {
                    statut: $statut.val(),
                    departement: $departement.val(),
                    ville: selectedVille,
                    identifiant: ecole.identifiant,
                    nom: cleanNom,
                    type: ecole.type,
                    nature: cleanNature,
                    adresse: ecole.adresse,
                    code_postal: ecole.code_postal,
                    commune: ecole.commune,
                    telephone: ecole.telephone,
                    mail: ecole.mail,
                    education_prioritaire: ecole.education_prioritaire
                };

                $dataInput.val(JSON.stringify(data)).trigger('change');
            }

            /**
             * Update the hidden data input with current selections.
             */
            function updateDataInput() {
                // Only store partial data if no school is selected yet
                var currentData = {};
                try {
                    currentData = JSON.parse($dataInput.val() || '{}');
                } catch (e) {
                    currentData = {};
                }

                // If we're changing filters, clear school data
                if (!$result || $result.is(':hidden')) {
                    currentData = {
                        statut: $statut.val(),
                        departement: $departement.val(),
                        ville: selectedVille
                    };
                    $dataInput.val(JSON.stringify(currentData));
                }
            }

            /**
             * Reset fields and their states.
             */
            function resetFields(fields) {
                fields.forEach(function (field) {
                    switch (field) {
                        case 'departement':
                            $departement.prop('disabled', true)
                                .addClass('disabled')
                                .val('')
                                .find('option:first').text(gfEcolesFR.i18n.selectStatut || '-- Select status first --');
                            break;
                        case 'ville':
                            $ville.prop('disabled', true)
                                .addClass('disabled')
                                .val('');
                            selectedVille = '';
                            $villeResults.empty().hide();
                            break;
                        case 'ecole':
                            $ecole.prop('disabled', true)
                                .addClass('disabled')
                                .val('');
                            $ecoleResults.empty().hide();
                            if ($result) {
                                $result.hide();
                            }
                            // Also hide autres field when resetting
                            hideAutresField();
                            break;
                    }
                });

                updateDataInput();
            }

            /**
             * Display no results message with "Autres" option.
             */
            function displayNoResultsWithAutres() {
                $ecoleResults.empty().show();

                var $noResults = $('<div class="gf-ecoles-fr-no-results"></div>')
                    .text(gfEcolesFR.i18n.noResults || 'No results found');
                $ecoleResults.append($noResults);

                var $autresOption = $('<div class="gf-ecoles-fr-autocomplete-item gf-ecoles-fr-autres-option"></div>')
                    .html('<strong>' + (gfEcolesFR.i18n.otherSchool || 'Other: Enter school name manually') + '</strong>')
                    .on('mousedown', function (e) {
                        e.preventDefault();
                        showAutresField();
                    });
                $ecoleResults.append($autresOption);
            }

            /**
             * Show the "Autres" manual input field.
             */
            function showAutresField() {
                $ecoleResults.empty().hide();
                $ecole.val('').prop('disabled', true).addClass('disabled');
                $autresField.removeClass('gf-ecoles-fr-hidden');
                $autres.focus();
                if ($result) {
                    $result.hide();
                }
            }

            /**
             * Hide the "Autres" manual input field.
             */
            function hideAutresField() {
                $autresField.addClass('gf-ecoles-fr-hidden');
                $autres.val('');
                $ecole.prop('disabled', false).removeClass('disabled').val('');
                // Clear the autres_nom from data
                var currentData = {};
                try {
                    currentData = JSON.parse($dataInput.val() || '{}');
                } catch (e) {
                    currentData = {};
                }
                delete currentData.autres_nom;
                $dataInput.val(JSON.stringify(currentData));
            }

            /**
             * Save the "Autres" manual entry.
             */
            function saveAutresEntry() {
                var autresNom = $autres.val().trim();
                if (!autresNom) {
                    return;
                }

                var data = {
                    statut: $statut.val(),
                    departement: $departement.val(),
                    ville: selectedVille,
                    autres_nom: autresNom
                };

                $dataInput.val(JSON.stringify(data)).trigger('change');

                // Show result if not hidden
                if ($result) {
                    $result.find('[data-field="identifiant"]').text('');
                    $result.find('[data-field="nom"]').text(autresNom + ' (' + (gfEcolesFR.i18n.manualEntry || 'Manual Entry') + ')');
                    $result.find('[data-field="type"]').text('');
                    $result.find('[data-field="nature"]').text('');
                    $result.find('[data-field="adresse"]').text('');
                    $result.find('[data-field="code_postal"]').text('');
                    $result.find('[data-field="commune"]').text(selectedVille);
                    $result.find('[data-field="telephone"]').text('');
                    $result.find('[data-field="mail"]').text('');
                    $result.find('[data-field="education_prioritaire"]').text('');
                    $result.show();
                }
            }

            // Cancel button for "Autres" field
            $autresCancel.on('click', function () {
                hideAutresField();
            });

            // Save "Autres" entry on blur or Enter key
            $autres.on('blur', function () {
                saveAutresEntry();
            });

            $autres.on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    saveAutresEntry();
                    $autres.blur();
                }
            });

            /**
             * Escape HTML entities.
             */
            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        initEcolesFRField();
    });

    // Re-initialize on AJAX form render (for multi-page forms)
    $(document).on('gform_post_render', function (event, formId) {
        initEcolesFRField();
    });

    // Close autocomplete dropdowns when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.gf-ecoles-fr-autocomplete-wrapper').length) {
            $('.gf-ecoles-fr-autocomplete-results').hide();
        }
    });

})(jQuery);
