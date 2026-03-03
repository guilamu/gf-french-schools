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
     * Department name → official department number mapping.
     */
    var departementNumbers = {
        'Ain': '01', 'Aisne': '02', 'Allier': '03', 'Alpes-de-Haute-Provence': '04',
        'Hautes-Alpes': '05', 'Alpes-Maritimes': '06', 'Ard\u00e8che': '07', 'Ardennes': '08',
        'Ari\u00e8ge': '09', 'Aube': '10', 'Aude': '11', 'Aveyron': '12',
        'Bouches-du-Rh\u00f4ne': '13', 'Calvados': '14', 'Cantal': '15', 'Charente': '16',
        'Charente-Maritime': '17', 'Cher': '18', 'Corr\u00e8ze': '19', 'Corse-du-Sud': '2A',
        "C\u00f4te-d'Or": '21', "C\u00f4tes-d'Armor": '22', 'Creuse': '23', 'Dordogne': '24',
        'Doubs': '25', 'Dr\u00f4me': '26', 'Eure': '27', 'Eure-et-Loir': '28',
        'Finist\u00e8re': '29', 'Gard': '30', 'Haute-Garonne': '31', 'Gers': '32',
        'Gironde': '33', 'H\u00e9rault': '34', 'Ille-et-Vilaine': '35', 'Indre': '36',
        'Indre-et-Loire': '37', 'Is\u00e8re': '38', 'Jura': '39', 'Landes': '40',
        'Loir-et-Cher': '41', 'Loire': '42', 'Haute-Loire': '43', 'Loire-Atlantique': '44',
        'Loiret': '45', 'Lot': '46', 'Lot-et-Garonne': '47', 'Loz\u00e8re': '48',
        'Maine-et-Loire': '49', 'Manche': '50', 'Marne': '51', 'Haute-Marne': '52',
        'Mayenne': '53', 'Meurthe-et-Moselle': '54', 'Meuse': '55', 'Morbihan': '56',
        'Moselle': '57', 'Ni\u00e8vre': '58', 'Nord': '59', 'Oise': '60',
        'Orne': '61', 'Pas-de-Calais': '62', 'Puy-de-D\u00f4me': '63',
        'Pyr\u00e9n\u00e9es-Atlantiques': '64', 'Hautes-Pyr\u00e9n\u00e9es': '65',
        'Pyr\u00e9n\u00e9es-Orientales': '66', 'Bas-Rhin': '67', 'Haut-Rhin': '68',
        'Rh\u00f4ne': '69', 'Haute-Sa\u00f4ne': '70', 'Sa\u00f4ne-et-Loire': '71', 'Sarthe': '72',
        'Savoie': '73', 'Haute-Savoie': '74', 'Paris': '75', 'Seine-Maritime': '76',
        'Seine-et-Marne': '77', 'Yvelines': '78', 'Deux-S\u00e8vres': '79', 'Somme': '80',
        'Tarn': '81', 'Tarn-et-Garonne': '82', 'Var': '83', 'Vaucluse': '84',
        'Vend\u00e9e': '85', 'Vienne': '86', 'Haute-Vienne': '87', 'Vosges': '88',
        'Yonne': '89', 'Territoire de Belfort': '90', 'Essonne': '91',
        'Hauts-de-Seine': '92', 'Seine-Saint-Denis': '93', 'Val-de-Marne': '94',
        "Val-d'Oise": '95', 'Guadeloupe': '971', 'Martinique': '972', 'Guyane': '973',
        'La R\u00e9union': '974', 'St-Pierre-et-Miquelon': '975', 'Mayotte': '976',
        'Saint-Barth\u00e9l\u00e9my': '977', 'Saint-Martin': '978',
        'Wallis et Futuna': '986', 'Polyn\u00e9sie Fran\u00e7aise': '987', 'Nouvelle Cal\u00e9donie': '988'
    };

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
     *
     * Removes school-type prefixes, status words, contract types,
     * structure names, and common typos/abbreviations so that only
     * the actual school name remains.
     *
     * NOTE: We use whitespace / string-boundary assertions instead of \b
     * because JavaScript \b only recognises ASCII word characters [a-zA-Z0-9_].
     * Accented letters (é, è, ô …) are treated as non-word chars, so \b
     * silently fails for words like élémentaire, École, Collège, etc.
     */
    function cleanDisplayValue(value) {
        if (!value) return '';
        var cleaned = value;

        // Helper: escape regex special characters in a literal string
        function escapeRe(s) {
            return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // --- 1. Remove multi-word phrases first (order matters: longer first) ---
        var phrasesToRemove = [
            // SEGPA full name
            "Section d'enseignement général et professionnel adapté",
            // Section d'enseignement variants
            "Section d'enseignement professionnel",
            "Section d'enseignement général",
            "Section d'Enseignement Professionnel",
            "Section d'Enseignement Général",
            // Structure prefixes
            'Groupe scolaire',
            'Gpe Scolaire',
            'Centre scolaire',
            'Ensemble scolaire',
            // Contract types
            'hors contrat',
            'hors-contrat',
            'sous contrat',
            // Paris abbreviated school types (longest first)
            'E.P.A.PU',  // École Primaire d'Application Publique
            'E.M.A.PU',  // École Maternelle d'Application Publique
            'E.P.S.PR',  // École Primaire Spécialisée Privée
            'E.M.PU',    // École Maternelle Publique
            'E.E.PU',    // École Élémentaire Publique
            'E.P.PU',    // École Primaire Publique
            'E.M.PR',    // École Maternelle Privée
            'E.E.PR',    // École Élémentaire Privée
            'E.P.PR',    // École Primaire Privée
            // Other abbreviations / typos
            'ECOL EPRIMAIRE'
        ];
        phrasesToRemove.forEach(function (phrase) {
            cleaned = cleaned.replace(new RegExp(escapeRe(phrase), 'gi'), '');
        });

        // --- 1b. Remove Paris-style addresses (number + street type + text at end) ---
        // Matches: "3 rue Ferdinand Flocon", "22 bis avenue Victor Hugo", etc.
        var addressPattern = /\s+\d+(?:\s*(?:bis|ter))?\s+(?:rue|avenue|av\.|boulevard|bd|impasse|passage|place|allée|chemin|voie|square|quai|cours|cité)[^,]*/gi;
        cleaned = cleaned.replace(addressPattern, '');

        // --- 2. Remove individual words (word-boundary match) ---
        var wordsToRemove = [
            // Core school types
            'Collège', 'Lycée', 'Ecole', 'École', 'Ecolé',
            // School level
            'Primaire', 'Maternelle',
            // Élémentaire and all its variants / typos
            'Élémentaire', 'Elémentaire', 'Elementaire', 'élémentaire',
            'éléméntaire', 'elmentaire', 'elementaitre', 'élém',
            // Primaire typo
            'pimaire',
            // Status words
            'publique', 'public', 'privée', 'privé', 'privee', 'prive',
            // Lycée type qualifiers
            'professionnel', 'professionnelle',
            'polyvalent', 'polyvalente',
            'général', 'générale', 'general',
            'technologique',
            // Other structure / qualifier words
            'Institut', 'Campus', 'Pôle', 'Pole',
            'scolaire', 'intercommunale', 'intercommunal',
            'spéciale', 'spécialisée', 'Bilingue',
            // Abbreviation
            'RPI',
            // d'application (special: starts with d')
            "d'application"
        ];
        wordsToRemove.forEach(function (word) {
            // (?:^|\s) = start of string OR whitespace  (Unicode-safe "before")
            // (?=\s|$) = followed by whitespace or end   (Unicode-safe "after")
            var regex = new RegExp('(?:^|\\s)' + escapeRe(word) + '(?=\\s|$)', 'gi');
            cleaned = cleaned.replace(regex, ' ');
        });

        // --- 3. Clean up extra whitespace and trim ---
        return cleaned.replace(/\s+/g, ' ').trim();
    }

    /**
     * Clean category value.
     *
     * Transforms raw API "libelle_nature" into a user-friendly label:
     *   - "ECOLE MATERNELLE"            → "Maternelle"
     *   - "ECOLE DE NIVEAU ELEMENTAIRE" → "Élémentaire"
     *     … but when the school name contains "primaire" → "Primaire"
     *
     * @param {string} value     The raw libelle_nature from the API.
     * @param {string} [nomEtab] The raw nom_etablissement (used for primaire detection).
     * @returns {string}
     */
    function cleanCategoryValue(value, nomEtab) {
        if (!value) return '';

        var upper = value.toUpperCase().trim();

        // Explicit mapping for known API values.
        if (upper === 'ECOLE MATERNELLE') {
            return 'Maternelle';
        }

        if (upper === 'ECOLE DE NIVEAU ELEMENTAIRE') {
            // If the school name contains "primaire", label it "Primaire".
            if (nomEtab && /primaire/i.test(nomEtab)) {
                return 'Primaire';
            }
            return 'Élémentaire';
        }

        // Fallback for any other value: strip "ecole/école", sentence-case.
        var cleaned = value.replace(/\b(ecole|école)\b/gi, '');
        cleaned = cleaned.replace(/\s+/g, ' ').trim();
        if (cleaned.length > 0) {
            cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
        }
        return cleaned;
    }

    /**
     * Clean circonscription name by removing standard prefixes.
     * Removes:
     * - "Circonscription d'inspection du 1er degré de/du/d'"
     * - "Circonscription d'inspection du 1r degré de/du/d'"
     * - "Circonscription" alone
     */
    function cleanNomCirconscription(value) {
        if (!value) return '';
        var cleaned = value;

        // Patterns to remove (order matters - longer patterns first)
        var patterns = [
            /^Circonscription d'inspection du 1er degré de\s+/i,
            /^Circonscription d'inspection du 1er degré du\s+/i,
            /^Circonscription d'inspection du 1er degré d'/i,
            /^Circonscription d'inspection du 1er degré\s+/i,
            /^Circonscription d'inspection du 1r degré de\s+/i,
            /^Circonscription d'inspection du 1r degré du\s+/i,
            /^Circonscription d'inspection du 1r degré d'/i,
            /^Circonscription d'inspection du 1r degré\s+/i,
            /^Circonscription\s+/i
        ];

        patterns.forEach(function (pattern) {
            cleaned = cleaned.replace(pattern, '');
        });

        return cleaned.trim();
    }

    /**
     * Generate circonscription email from code and school mail domain.
     * 
     * @param {string} codeCirconscription - The circonscription code (e.g., "0931052N")
     * @param {string} schoolMail - The school email to extract domain from (e.g., "ce.xxx@ac-creteil.fr")
     * @returns {string} The circonscription email (e.g., "ce.0931052N@ac-creteil.fr") or empty string
     */
    function getCirconscriptionMail(codeCirconscription, schoolMail) {
        if (!codeCirconscription) return '';
        if (!schoolMail) return codeCirconscription;

        // Extract domain from school mail (everything after @)
        var atIndex = schoolMail.indexOf('@');
        if (atIndex === -1) return codeCirconscription;

        var domain = schoolMail.substring(atIndex + 1);
        if (!domain) return codeCirconscription;

        return 'ce.' + codeCirconscription + '@' + domain;
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
            // Use longer delay and check if mouse is over results
            $ville.on('blur', function () {
                setTimeout(function () {
                    if (!$villeResults.is(':hover')) {
                        $villeResults.hide();
                    }
                }, 350);
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
            // Use longer delay and check if mouse is over results
            $ecole.on('blur', function () {
                setTimeout(function () {
                    if (!$ecoleResults.is(':hover')) {
                        $ecoleResults.hide();
                    }
                }, 350);
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
                var cleanNature = cleanCategoryValue(ecole.nature, ecole.nom);
                var cleanCirco = cleanNomCirconscription(ecole.nom_circonscription);
                var circoMail = getCirconscriptionMail(ecole.code_circonscription, ecole.mail);

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
                    $result.find('[data-field="nom_circonscription"]').text(cleanCirco || '');
                    $result.find('[data-field="code_circonscription"]').text(circoMail || '');
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
                    numero_departement: departementNumbers[$departement.val()] || '',
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
                    education_prioritaire: ecole.education_prioritaire,
                    nom_circonscription: cleanCirco,
                    code_circonscription: circoMail
                };

                $dataInput.val(JSON.stringify(data)).trigger('change');
                updateSubInputs(data);
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
                        numero_departement: departementNumbers[$departement.val()] || '',
                        ville: selectedVille
                    };
                    $dataInput.val(JSON.stringify(currentData));
                    clearSubInputs();
                }
            }

            /**
             * Sub-input ID → data-key mapping for conditional logic.
             */
            var subInputMap = {
                1: 'identifiant',
                2: 'nom',
                3: 'autres_nom',
                4: 'type',
                5: 'nature',
                6: 'adresse',
                7: 'code_postal',
                8: 'commune',
                9: 'telephone',
                11: 'mail',
                12: 'education_prioritaire',
                13: 'nom_circonscription',
                14: 'code_circonscription',
                15: 'statut',
                16: 'departement',
                17: 'ville',
                18: 'numero_departement'
            };

            /**
             * Populate hidden sub-inputs and trigger conditional logic re-evaluation.
             * Also fires jQuery change events so that gwcopycat (GP Copy Cat) can
             * detect value changes on sub-inputs and copy them to target fields.
             */
            function updateSubInputs(data) {
                $.each(subInputMap, function (subId, dataKey) {
                    var el = document.getElementById('input_' + formId + '_' + fieldId + '_' + subId);
                    if (el) {
                        el.value = data[dataKey] || '';
                        // Fire a jQuery change event so gwcopycat picks up the new value.
                        $(el).trigger('change');
                    }
                });
                // Trigger Gravity Forms conditional logic re-evaluation.
                var firstEl = document.getElementById('input_' + formId + '_' + fieldId + '_1');
                if (firstEl && typeof gf_input_change === 'function') {
                    gf_input_change(firstEl, formId, fieldId);
                }
            }

            /**
             * Clear all hidden sub-inputs and trigger conditional logic.
             */
            function clearSubInputs() {
                updateSubInputs({});
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
                    numero_departement: departementNumbers[$departement.val()] || '',
                    ville: selectedVille,
                    autres_nom: autresNom
                };

                $dataInput.val(JSON.stringify(data)).trigger('change');
                updateSubInputs(data);

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
