<?php
/**
 * API Service for French Education Ministry data.
 *
 * @package GF_French_Schools
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GF_Ecoles_API_Service
 *
 * Handles API requests to the French Education Ministry OpenDataSoft API.
 */
class GF_Ecoles_API_Service
{

    /**
     * API base URL.
     */
    const API_BASE = 'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education';

    /**
     * Cache expiration time in seconds (1 hour).
     */
    const CACHE_EXPIRATION = 3600;

    /**
     * Map of common abbreviations found in French school names.
     * Keys are lowercase, accent-stripped for matching.
     *
     * @var array<string, string>
     */
    private static $abbreviation_map = array(
        'st'    => 'Saint',
        'ste'   => 'Sainte',
        'sts'   => 'Saints',
        'gen'   => 'Général',
        'gal'   => 'Général',
        'cdt'   => 'Commandant',
        'cmdt'  => 'Commandant',
        'lt'    => 'Lieutenant',
        'col'   => 'Colonel',
        'mal'   => 'Maréchal',
        'mcl'   => 'Maréchal',
        'dr'    => 'Docteur',
        'pr'    => 'Professeur',
        'prof'  => 'Professeur',
        'mgr'   => 'Monseigneur',
        'mme'   => 'Madame',
        'mlle'  => 'Mademoiselle',
        'pdt'   => 'Président',
        'cpt'   => 'Capitaine',
        'sgt'   => 'Sergent',
        'adj'   => 'Adjudant',
    );

    /**
     * Reverse map: full forms (lowercase, accent-stripped) to their common abbreviation.
     *
     * @var array<string, string>
     */
    private static $reverse_abbreviation_map = array(
        'saint'        => 'St',
        'sainte'       => 'Ste',
        'saints'       => 'Sts',
        'general'      => 'Gén',
        'commandant'   => 'Cdt',
        'lieutenant'   => 'Lt',
        'colonel'      => 'Col',
        'marechal'     => 'Mal',
        'docteur'      => 'Dr',
        'professeur'   => 'Pr',
        'monseigneur'  => 'Mgr',
        'madame'       => 'Mme',
        'mademoiselle' => 'Mlle',
        'president'    => 'Pdt',
        'capitaine'    => 'Cpt',
        'sergent'      => 'Sgt',
        'adjudant'     => 'Adj',
    );

    /**
     * Generate alternative query strings by expanding/contracting abbreviations.
     *
     * Handles bidirectional matching: if the user types "St", also searches
     * for "Saint", and vice-versa. This covers common abbreviations found
     * in French school names (military titles, honorifics, etc.).
     *
     * @param string $query Original search query.
     * @return array Array of distinct query variants (always includes the original).
     */
    public static function get_query_variants($query)
    {
        $query = trim($query);
        if (empty($query)) {
            return array($query);
        }

        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        $expanded_words = array();
        $contracted_words = array();
        $has_expansion = false;
        $has_contraction = false;

        foreach ($words as $word) {
            // Normalize for lookup: lowercase, remove accents, strip trailing dot.
            $lower = mb_strtolower($word, 'UTF-8');
            $normalized = function_exists('remove_accents') ? remove_accents($lower) : $lower;
            $normalized_no_dot = rtrim($normalized, '.');

            if (isset(self::$abbreviation_map[$normalized_no_dot])) {
                // Word is an abbreviation → expand to full form.
                $expanded_words[] = self::$abbreviation_map[$normalized_no_dot];
                $contracted_words[] = $word;
                $has_expansion = true;
            } elseif (isset(self::$reverse_abbreviation_map[$normalized])) {
                // Word is a full form → contract to abbreviation.
                $expanded_words[] = $word;
                $contracted_words[] = self::$reverse_abbreviation_map[$normalized];
                $has_contraction = true;
            } else {
                $expanded_words[] = $word;
                $contracted_words[] = $word;
            }
        }

        $variants = array($query);
        if ($has_expansion) {
            $variants[] = implode(' ', $expanded_words);
        }
        if ($has_contraction) {
            $variants[] = implode(' ', $contracted_words);
        }

        return array_unique($variants);
    }

    /**
     * Get list of cities matching the query.
     *
     * @param string $statut              School status (Public/Privé).
     * @param string $departement         Department name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège" and "Lycée" type schools.
     * @return array|WP_Error List of cities or error.
     */
    public function get_villes($statut, $departement, $query, $hide_ecoles = false, $hide_colleges_lycees = false)
    {
        $statut = $this->validate_statut($statut);
        $departement = $this->validate_departement($departement);
        $query = $this->sanitize_query($query);

        if (empty($statut) || empty($departement) || strlen($query) < 2) {
            return array();
        }

        $cache_key = 'gf_ecoles_villes_' . md5(GF_FRENCH_SCHOOLS_VERSION . $statut . $departement . $query . ($hide_ecoles ? '1' : '0') . ($hide_colleges_lycees ? '1' : '0'));
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        // Local-only mode: skip remote API entirely.
        if (get_option('gf_ecoles_fr_local_only', false) && class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data()) {
            $results = GF_Ecoles_Local_DB::search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
            // Fallback to remote API if local returned nothing and fallback is enabled.
            if (empty($results) && get_option('gf_ecoles_fr_local_fallback_api', false)) {
                return $this->get_villes_from_api($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key);
            }
            return $results;
        }

        return $this->get_villes_from_api($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key);
    }

    /**
     * Fetch cities from the remote API.
     *
     * @param string $statut              School status.
     * @param string $departement         Department name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège"/"Lycée" type schools.
     * @param string $cache_key           Transient cache key.
     * @return array|WP_Error
     */
    private function get_villes_from_api($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key)
    {
        // Build per-word LIKE clauses so that "les pa" matches "Les Pavillons Sous Bois".
        // suggest() treats the whole query as a single token, failing on multi-word inputs
        // like "les pa". Splitting into words and requiring each to appear anywhere in
        // nom_commune gives the expected prefix-per-word behaviour.
        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        $word_clauses = array();
        foreach ($words as $word) {
            $word_clauses[] = sprintf('nom_commune like "*%s*"', $this->escape_api_string($word));
        }
        $where = sprintf(
            'statut_public_prive="%s" and libelle_departement="%s" and %s',
            $this->escape_api_string($statut),
            $this->escape_api_string($departement),
            implode(' and ', $word_clauses)
        );

        // Add school type filters
        if ($hide_ecoles) {
            $where .= ' and type_etablissement != "Ecole"';
        }
        if ($hide_colleges_lycees) {
            $where .= ' and type_etablissement != "Collège" and type_etablissement != "Lycée"';
        }

        $url = add_query_arg(
            array(
                'select' => 'nom_commune, code_commune',
                'where' => $where,
                'group_by' => 'nom_commune, code_commune',
                'limit' => 20,
            ),
            self::API_BASE . '/records'
        );

        $response = $this->make_request($url);

        if (is_wp_error($response)) {
            // Fallback to local database when API is unavailable.
            if (class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data()) {
                $this->log_error('Falling back to local database for city search', $url);
                return GF_Ecoles_Local_DB::search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
            }
            return $response;
        }

        $results = array();
        if (!empty($response['results'])) {
            foreach ($response['results'] as $item) {
                $results[] = array(
                    'value' => $item['nom_commune'],
                    'label' => $item['nom_commune'],
                    'code_commune' => $item['code_commune'] ?? '',
                );
            }
        }

        if (!empty($results)) {
            set_transient($cache_key, $results, self::CACHE_EXPIRATION);
        }

        // Fuzzy fallback: when API returns no results, try local DB fuzzy matching.
        if (empty($results) && class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data() && mb_strlen($query) >= 3) {
            $results = GF_Ecoles_Local_DB::search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
        }

        return $results;
    }

    /**
     * English alias for city search to ease future naming alignment.
     */
    public function search_cities($statut, $departement, $query, $hide_ecoles = false, $hide_colleges_lycees = false)
    {
        return $this->get_villes($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
    }

    /**
     * Get list of schools matching the query.
     *
     * @param string $statut              School status (Public/Privé).
     * @param string $departement         Department name.
     * @param string $ville               City name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège" and "Lycée" type schools.
     * @return array|WP_Error List of schools or error.
     */
    public function get_ecoles($statut, $departement, $ville, $query, $hide_ecoles = false, $hide_colleges_lycees = false, $code_commune = '')
    {
        $statut = $this->validate_statut($statut);
        $departement = $this->validate_departement($departement);
        $ville = $this->sanitize_query($ville);
        $query = $this->sanitize_query($query);
        $code_commune = preg_replace('/[^0-9A-Za-z]/', '', $code_commune);

        if (empty($statut) || empty($departement) || empty($ville) || strlen($query) < 2) {
            return array();
        }

        $local_only = get_option('gf_ecoles_fr_local_only', false);

        // Include local_only mode in cache key to separate cached results
        $cache_key = 'gf_ecoles_ecoles_' . md5(GF_FRENCH_SCHOOLS_VERSION . $statut . $departement . $ville . $code_commune . $query . ($hide_ecoles ? '1' : '0') . ($hide_colleges_lycees ? '1' : '0') . ($local_only ? 'L' : 'R'));
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        // Local-only mode: skip remote API entirely.
        if ($local_only && class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data()) {
            $results = GF_Ecoles_Local_DB::search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune);
            // Fallback to remote API if local returned nothing and fallback is enabled.
            if (empty($results) && get_option('gf_ecoles_fr_local_fallback_api', false)) {
                return $this->get_ecoles_from_api($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key, $code_commune);
            }
            if (!empty($results)) {
                set_transient($cache_key, $results, self::CACHE_EXPIRATION);
            }
            return $results;
        }

        return $this->get_ecoles_from_api($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key, $code_commune);
    }

    /**
     * Fetch schools from the remote API.
     *
     * @param string $statut              School status.
     * @param string $departement         Department name.
     * @param string $ville               City name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège"/"Lycée" type schools.
     * @param string $cache_key           Transient cache key.
     * @return array|WP_Error
     */
    private function get_ecoles_from_api($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $cache_key, $code_commune = '')
    {
        $select_fields = array(
            'identifiant_de_l_etablissement',
            'nom_etablissement',
            'type_etablissement',
            'libelle_nature',
            'adresse_1',
            'code_postal',
            'nom_commune',
            'telephone',
            'mail',
            'appartenance_education_prioritaire',
            'nom_circonscription',
            'code_circonscription',
        );

        // Build name matching condition with abbreviation variants.
        // e.g. "St Exupery" also searches for "Saint Exupery" and vice-versa.
        $variants = self::get_query_variants($query);
        $name_clauses = array();
        foreach ($variants as $variant) {
            $name_clauses[] = sprintf('nom_etablissement like "*%s*"', $this->escape_api_string($variant));
        }
        $name_condition = count($name_clauses) > 1
            ? '(' . implode(' or ', $name_clauses) . ')'
            : $name_clauses[0];

        // Use code_commune when available for reliable matching.
        // Some schools have incorrect nom_commune in the national database
        // (e.g. Pierrefitte-sur-Seine schools listed as "Saint-Denis")
        // but code_commune is always correct.
        if (!empty($code_commune)) {
            $where = sprintf(
                'statut_public_prive="%s" and libelle_departement="%s" and code_commune="%s" and %s',
                $this->escape_api_string($statut),
                $this->escape_api_string($departement),
                $this->escape_api_string($code_commune),
                $name_condition
            );
        } else {
            // Fallback to nom_commune matching when code_commune is not available
            // Normalize whitespace in city name (database has inconsistent spacing for Paris arrondissements)
            $ville_pattern = preg_replace('/\s+/', '*', trim($ville));

            // Use 'like' with wildcards for better partial matching (especially for Paris schools)
            // The 'search()' function does full-text tokenization which fails on names like "F. FLOCON"
            $where = sprintf(
                'statut_public_prive="%s" and libelle_departement="%s" and nom_commune like "%s" and %s',
                $this->escape_api_string($statut),
                $this->escape_api_string($departement),
                $this->escape_api_string($ville_pattern),
                $name_condition
            );
        }

        // Add school type filters
        if ($hide_ecoles) {
            $where .= ' and type_etablissement != "Ecole"';
        }
        if ($hide_colleges_lycees) {
            $where .= ' and type_etablissement != "Collège" and type_etablissement != "Lycée"';
        }

        $url = add_query_arg(
            array(
                'select' => implode(',', $select_fields),
                'where' => $where,
                'limit' => 20,
            ),
            self::API_BASE . '/records'
        );

        $response = $this->make_request($url);

        if (is_wp_error($response)) {
            // Fallback to local database when API is unavailable.
            if (class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data()) {
                $this->log_error('Falling back to local database for school search', $url);
                return GF_Ecoles_Local_DB::search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune);
            }
            return $response;
        }

        $results = array();
        if (!empty($response['results'])) {
            foreach ($response['results'] as $item) {
                $results[] = array(
                    'identifiant' => $item['identifiant_de_l_etablissement'] ?? '',
                    'nom' => $item['nom_etablissement'] ?? '',
                    'type' => $item['type_etablissement'] ?? '',
                    'nature' => $item['libelle_nature'] ?? '',
                    'adresse' => $item['adresse_1'] ?? '',
                    'code_postal' => $item['code_postal'] ?? '',
                    'commune' => $item['nom_commune'] ?? '',
                    'telephone' => $item['telephone'] ?? '',
                    'mail' => $item['mail'] ?? '',
                    'education_prioritaire' => $item['appartenance_education_prioritaire'] ?? '',
                    'nom_circonscription' => $item['nom_circonscription'] ?? '',
                    'code_circonscription' => $item['code_circonscription'] ?? '',
                );
            }
        }

        if (!empty($results)) {
            set_transient($cache_key, $results, self::CACHE_EXPIRATION);
        }

        // Fuzzy fallback: when API returns no results, try local DB fuzzy matching.
        if (empty($results) && class_exists('GF_Ecoles_Local_DB') && GF_Ecoles_Local_DB::has_data() && mb_strlen($query) >= 3) {
            $results = GF_Ecoles_Local_DB::search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune);
        }

        return $results;
    }

    /**
     * English alias for school search to ease future naming alignment.
     */
    public function search_schools($statut, $departement, $ville, $query, $hide_ecoles = false, $hide_colleges_lycees = false, $code_commune = '')
    {
        return $this->get_ecoles($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune);
    }

    /**
     * Make HTTP request to the API.
     *
     * @param string $url API URL.
     * @return array|WP_Error Response data or error.
     */
    private function make_request($url)
    {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if (is_wp_error($response)) {
            $this->log_error('API connection error: ' . $response->get_error_message(), $url);
            return new WP_Error('api_connection_error', __('Unable to connect to the school directory. Please try again later.', 'gf-french-schools'));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $this->log_error('API HTTP error: ' . $status_code, $url);
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP status code */
                    __('API request failed with status code %d', 'gf-french-schools'),
                    $status_code
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('JSON parse error: ' . json_last_error_msg(), $url);
            return new WP_Error('json_error', __('Failed to parse API response', 'gf-french-schools'));
        }

        return $data;
    }

    /**
     * Escape string for use in API query.
     *
     * @param string $string String to escape.
     * @return string Escaped string.
     */
    private function escape_api_string($string)
    {
        $string = (string) $string;
        // Remove control characters
        $string = preg_replace('/[\x00-\x1F\x7F]/', '', $string);
        // Escape backslashes first, then quotes and wildcards
        $string = str_replace(array('\\', '"', '*'), array('\\\\', '\\"', '\\*'), $string);

        return $string;
    }

    /**
     * Validate statut against allowed values.
     *
     * @param string $statut Statut value.
     * @return string
     */
    private function validate_statut($statut)
    {
        $allowed = array('Public', 'Privé');
        return in_array($statut, $allowed, true) ? $statut : '';
    }

    /**
     * Validate departement against allowed list.
     *
     * @param string $departement Departement value.
     * @return string
     */
    private function validate_departement($departement)
    {
        return in_array($departement, GF_Field_Ecoles_FR::get_departements(), true) ? $departement : '';
    }

    /**
     * Sanitize free-text query parameters.
     *
     * @param string $query Query value.
     * @return string
     */
    private function sanitize_query($query)
    {
        $query = is_string($query) ? $query : '';
        // Remove control chars and keep letters/numbers/basic punctuation
        $query = preg_replace('/[^\p{L}\p{N}\s\'\-]/u', '', $query);
        $query = trim($query);

        return mb_substr($query, 0, 100);
    }

    /**
     * Log API-related errors when debugging is enabled.
     *
     * @param string $message Message to log.
     * @param string $context Context (URL).
     * @return void
     */
    private function log_error($message, $context)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[GF French Schools] %s | Context: %s', $message, $context));
        }
    }
}
