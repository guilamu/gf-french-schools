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
        if (empty($statut) || empty($departement) || strlen($query) < 2) {
            return array();
        }

        $cache_key = 'gf_ecoles_villes_' . md5($statut . $departement . $query . ($hide_ecoles ? '1' : '0') . ($hide_colleges_lycees ? '1' : '0'));
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $where = sprintf(
            'statut_public_prive="%s" and libelle_departement="%s" and suggest(nom_commune,"%s")',
            $this->escape_api_string($statut),
            $this->escape_api_string($departement),
            $this->escape_api_string($query)
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
                'select' => 'nom_commune',
                'where' => $where,
                'group_by' => 'nom_commune',
                'limit' => 20,
            ),
            self::API_BASE . '/records'
        );

        $response = $this->make_request($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $results = array();
        if (!empty($response['results'])) {
            foreach ($response['results'] as $item) {
                $results[] = array(
                    'value' => $item['nom_commune'],
                    'label' => $item['nom_commune'],
                );
            }
        }

        set_transient($cache_key, $results, self::CACHE_EXPIRATION);

        return $results;
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
    public function get_ecoles($statut, $departement, $ville, $query, $hide_ecoles = false, $hide_colleges_lycees = false)
    {
        if (empty($statut) || empty($departement) || empty($ville) || strlen($query) < 2) {
            return array();
        }

        $cache_key = 'gf_ecoles_ecoles_' . md5($statut . $departement . $ville . $query . ($hide_ecoles ? '1' : '0') . ($hide_colleges_lycees ? '1' : '0'));
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

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
        );

        $where = sprintf(
            'statut_public_prive="%s" and libelle_departement="%s" and nom_commune="%s" and search(nom_etablissement,"%s")',
            $this->escape_api_string($statut),
            $this->escape_api_string($departement),
            $this->escape_api_string($ville),
            $this->escape_api_string($query)
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
                'select' => implode(',', $select_fields),
                'where' => $where,
                'limit' => 20,
            ),
            self::API_BASE . '/records'
        );

        $response = $this->make_request($url);

        if (is_wp_error($response)) {
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
                );
            }
        }

        set_transient($cache_key, $results, self::CACHE_EXPIRATION);

        return $results;
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
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
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
        // Escape double quotes for OpenDataSoft API
        return str_replace('"', '\\"', $string);
    }
}
