/**
 * Gravity Forms French Schools - Frontend JavaScript
 *
 * Handles the cascading field logic and autocomplete functionality.
 *
 * @package GF_French_Schools
 */

(function ($) {
    'use strict';

    /**
     * Debounce function to limit API calls.
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

            if ($wrapper.data('initialized')) {
                return;
            }
            $wrapper.data('initialized', true);

            var $statut = $wrapper.find('.gf-ecoles-fr-statut');
            var $departement = $wrapper.find('.gf-ecoles-fr-departement');
            var $ville = $wrapper.find('.gf-ecoles-fr-ville');
            var $ecole = $wrapper.find('.gf-ecoles-fr-ecole');
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

            var selectedVille = '';
            var schoolsData = [];

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
                    $result.hide();
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
            }, 300));

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
            }, 300));

            // École focus out - hide results after delay
            $ecole.on('blur', function () {
                setTimeout(function () {
                    $ecoleResults.hide();
                }, 200);
            });

            /**
             * Search for cities via AJAX.
             */
            function searchVilles(query) {
                $villeResults.html('<div class="gf-ecoles-fr-loading">' + (gfEcolesFR.i18n.searching || 'Searching...') + '</div>').show();

                $.ajax({
                    url: gfEcolesFR.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'gf_ecoles_fr_search',
                        nonce: gfEcolesFR.nonce,
                        search_type: 'villes',
                        statut: $statut.val(),
                        departement: $departement.val(),
                        query: query,
                        hide_ecoles: hideEcoles ? 'true' : 'false',
                        hide_colleges_lycees: hideCollegesLycees ? 'true' : 'false'
                    },
                    success: function (response) {
                        if (response.success && response.data.length > 0) {
                            displayVilleResults(response.data);
                        } else {
                            $villeResults.html('<div class="gf-ecoles-fr-no-results">' + (gfEcolesFR.i18n.noResults || 'No results found') + '</div>');
                        }
                    },
                    error: function () {
                        $villeResults.html('<div class="gf-ecoles-fr-error">Error loading results</div>');
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
                        .on('click', function () {
                            $ville.val(ville.value);
                            selectedVille = ville.value;
                            $villeResults.empty().hide();

                            // Enable school field
                            $ecole.prop('disabled', false)
                                .removeClass('disabled')
                                .val('');
                            $result.hide();

                            updateDataInput();
                        });
                    $villeResults.append($item);
                });
            }

            /**
             * Search for schools via AJAX.
             */
            function searchEcoles(query) {
                $ecoleResults.html('<div class="gf-ecoles-fr-loading">' + (gfEcolesFR.i18n.searching || 'Searching...') + '</div>').show();

                $.ajax({
                    url: gfEcolesFR.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'gf_ecoles_fr_search',
                        nonce: gfEcolesFR.nonce,
                        search_type: 'ecoles',
                        statut: $statut.val(),
                        departement: $departement.val(),
                        ville: selectedVille,
                        query: query,
                        hide_ecoles: hideEcoles ? 'true' : 'false',
                        hide_colleges_lycees: hideCollegesLycees ? 'true' : 'false'
                    },
                    success: function (response) {
                        if (response.success && response.data.length > 0) {
                            schoolsData = response.data;
                            displayEcoleResults(response.data);
                        } else {
                            schoolsData = [];
                            $ecoleResults.html('<div class="gf-ecoles-fr-no-results">' + (gfEcolesFR.i18n.noResults || 'No results found') + '</div>');
                        }
                    },
                    error: function () {
                        $ecoleResults.html('<div class="gf-ecoles-fr-error">Error loading results</div>');
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
                        .on('click', function () {
                            selectEcole(ecole);
                        });
                    $ecoleResults.append($item);
                });
            }

            /**
             * Select a school and display its information.
             */
            function selectEcole(ecole) {
                $ecole.val(ecole.nom);
                $ecoleResults.empty().hide();

                // Update result display (apply cleanDisplayValue to remove school type words from nom)
                $result.find('[data-field="identifiant"]').text(ecole.identifiant || '');
                $result.find('[data-field="nom"]').text(cleanDisplayValue(ecole.nom) || '');
                $result.find('[data-field="type"]').text(ecole.type || '');
                $result.find('[data-field="nature"]').text(cleanCategoryValue(ecole.nature) || '');
                $result.find('[data-field="adresse"]').text(ecole.adresse || '');
                $result.find('[data-field="code_postal"]').text(ecole.code_postal || '');
                $result.find('[data-field="commune"]').text(ecole.commune || '');
                $result.find('[data-field="telephone"]').text(ecole.telephone || '');
                $result.find('[data-field="mail"]').text(ecole.mail || '');
                $result.find('[data-field="education_prioritaire"]').text(ecole.education_prioritaire || 'Non');
                $result.show();

                // Update hidden data input
                var data = {
                    statut: $statut.val(),
                    departement: $departement.val(),
                    ville: selectedVille,
                    identifiant: ecole.identifiant,
                    nom: ecole.nom,
                    type: ecole.type,
                    nature: ecole.nature,
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
                if ($result.is(':hidden')) {
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
                            $result.hide();
                            break;
                    }
                });

                updateDataInput();
            }

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
