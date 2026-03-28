<?php
/**
 * Local database fallback for French Education Ministry data.
 *
 * Downloads the full dataset CSV monthly and stores it in a custom table
 * so searches still work when the remote API is unavailable.
 *
 * @package GF_French_Schools
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GF_Ecoles_Local_DB
 *
 * Manages a local copy of the French schools directory for offline search.
 */
class GF_Ecoles_Local_DB
{

    /**
     * Base table name (without prefix).
     */
    const TABLE_NAME = 'gf_ecoles_fr';

    /**
     * Option key for last successful sync timestamp.
     */
    const OPTION_LAST_SYNC = 'gf_ecoles_fr_last_sync';

    /**
     * Option key for sync status (idle | running | success | error).
     */
    const OPTION_SYNC_STATUS = 'gf_ecoles_fr_sync_status';

    /**
     * Option key for record count after last sync.
     */
    const OPTION_RECORD_COUNT = 'gf_ecoles_fr_record_count';

    /**
     * Option key for last sync error message.
     */
    const OPTION_SYNC_ERROR = 'gf_ecoles_fr_sync_error';

    /**
     * WP-Cron hook name.
     */
    const CRON_HOOK = 'gf_ecoles_fr_monthly_sync';

    /**
     * CSV export URL selecting only the columns we need.
     */
    const EXPORT_URL = 'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/exports/csv?select=identifiant_de_l_etablissement%2Cnom_etablissement%2Ctype_etablissement%2Clibelle_nature%2Cstatut_public_prive%2Cadresse_1%2Ccode_postal%2Cnom_commune%2Ccode_commune%2Clibelle_departement%2Ctelephone%2Cmail%2Cappartenance_education_prioritaire%2Cnom_circonscription%2Ccode_circonscription&delimiter=%3B';

    /**
     * Minimum accepted record count for a sync to be considered valid.
     * Protects against importing an empty/truncated export.
     */
    const MIN_VALID_RECORDS = 50000;

    /**
     * Batch size for database inserts.
     */
    const BATCH_SIZE = 500;

    // ------------------------------------------------------------------
    // Table management
    // ------------------------------------------------------------------

    /**
     * Get the full table name including the WordPress prefix.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create the database table (runs on plugin activation).
     *
     * @return void
     */
    public static function create_table()
    {
        global $wpdb;

        $table = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            identifiant VARCHAR(20) NOT NULL DEFAULT '',
            nom_etablissement VARCHAR(255) NOT NULL DEFAULT '',
            type_etablissement VARCHAR(100) NOT NULL DEFAULT '',
            libelle_nature VARCHAR(255) NOT NULL DEFAULT '',
            statut_public_prive VARCHAR(10) NOT NULL DEFAULT '',
            adresse VARCHAR(255) NOT NULL DEFAULT '',
            code_postal VARCHAR(10) NOT NULL DEFAULT '',
            nom_commune VARCHAR(100) NOT NULL DEFAULT '',
            code_commune VARCHAR(10) NOT NULL DEFAULT '',
            libelle_departement VARCHAR(100) NOT NULL DEFAULT '',,
            telephone VARCHAR(30) NOT NULL DEFAULT '',
            mail VARCHAR(255) NOT NULL DEFAULT '',
            education_prioritaire VARCHAR(50) NOT NULL DEFAULT '',
            nom_circonscription VARCHAR(255) NOT NULL DEFAULT '',
            code_circonscription VARCHAR(20) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY identifiant (identifiant),
            KEY idx_search_ville (statut_public_prive, libelle_departement, nom_commune),
            KEY idx_search_ecole (statut_public_prive, libelle_departement, nom_commune, nom_etablissement(50)),
            KEY idx_search_code_commune (statut_public_prive, libelle_departement, code_commune, nom_etablissement(50)),
            KEY idx_type (type_etablissement)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop the database table (runs on plugin uninstall).
     *
     * @return void
     */
    public static function drop_table()
    {
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        delete_option(self::OPTION_LAST_SYNC);
        delete_option(self::OPTION_SYNC_STATUS);
        delete_option(self::OPTION_RECORD_COUNT);
        delete_option(self::OPTION_SYNC_ERROR);
    }

    /**
     * Check whether the local table exists and has data.
     *
     * @return bool
     */
    public static function has_data()
    {
        global $wpdb;
        $table = self::get_table_name();
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return !empty($count) && (int) $count > 0;
    }

    // ------------------------------------------------------------------
    // Sync / Import
    // ------------------------------------------------------------------

    /**
     * Download the CSV export and import it into the local table.
     *
     * Uses a staging table so the live data is never lost on failure.
     *
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public static function sync()
    {
        // Prevent concurrent syncs.
        if (get_option(self::OPTION_SYNC_STATUS) === 'running') {
            return new WP_Error('sync_running', __('A sync is already in progress.', 'gf-french-schools'));
        }

        update_option(self::OPTION_SYNC_STATUS, 'running');
        update_option(self::OPTION_SYNC_ERROR, '');

        // Increase limits for large import.
        if (function_exists('set_time_limit')) {
            @set_time_limit(600); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        wp_raise_memory_limit('admin');

        // 1. Download CSV to a temporary file.
        $tmp_file = download_url(self::EXPORT_URL, 300);
        if (is_wp_error($tmp_file)) {
            self::sync_failed($tmp_file->get_error_message());
            return $tmp_file;
        }

        // 2. Parse and import using a staging table.
        $result = self::import_csv($tmp_file);

        // Clean up temp file.
        @unlink($tmp_file); // phpcs:ignore WordPress.PHP.NoSilencedErrors

        if (is_wp_error($result)) {
            self::sync_failed($result->get_error_message());
            return $result;
        }

        // 3. Record success.
        update_option(self::OPTION_LAST_SYNC, time());
        update_option(self::OPTION_SYNC_STATUS, 'success');
        update_option(self::OPTION_RECORD_COUNT, $result);
        update_option(self::OPTION_SYNC_ERROR, '');

        self::log('Sync completed successfully. Records imported: ' . $result);

        return true;
    }

    /**
     * Import a CSV file into the staging table, then swap with production.
     *
     * @param string $file_path Path to the CSV file.
     * @return int|WP_Error Number of imported records or error.
     */
    private static function import_csv($file_path)
    {
        global $wpdb;

        $handle = fopen($file_path, 'r'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        if (!$handle) {
            return new WP_Error('csv_open_error', __('Failed to open downloaded CSV file.', 'gf-french-schools'));
        }

        // Read header row.
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            return new WP_Error('csv_header_error', __('CSV file has no header row.', 'gf-french-schools'));
        }

        // Normalise header (remove BOM, trim).
        $header = array_map(function ($col) {
            $col = preg_replace('/\x{FEFF}/u', '', $col);
            return strtolower(trim($col));
        }, $header);

        // Map CSV columns to DB columns.
        $column_map = self::map_columns($header);
        if (is_wp_error($column_map)) {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            return $column_map;
        }

        $table = self::get_table_name();
        $staging = $table . '_staging';

        // Create staging table as a copy of the main table structure.
        $wpdb->query("DROP TABLE IF EXISTS {$staging}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("CREATE TABLE {$staging} LIKE {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Parse rows and batch-insert.
        $total = 0;
        $batch = array();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $record = self::map_row($row, $column_map);
            if ($record) {
                $batch[] = $record;
                $total++;
            }

            if (count($batch) >= self::BATCH_SIZE) {
                self::insert_batch($staging, $batch);
                $batch = array();
            }
        }

        // Flush remaining batch.
        if (!empty($batch)) {
            self::insert_batch($staging, $batch);
        }

        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        // Validate import.
        if ($total < self::MIN_VALID_RECORDS) {
            $wpdb->query("DROP TABLE IF EXISTS {$staging}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return new WP_Error(
                'csv_too_few_records',
                sprintf(
                    /* translators: 1: Number of records imported, 2: minimum required */
                    __('Only %1$d records imported (minimum %2$d expected). Aborting to protect existing data.', 'gf-french-schools'),
                    $total,
                    self::MIN_VALID_RECORDS
                )
            );
        }

        // Swap staging → production.
        $old = $table . '_old';
        $wpdb->query("DROP TABLE IF EXISTS {$old}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("RENAME TABLE {$table} TO {$old}, {$staging} TO {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS {$old}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $total;
    }

    /**
     * Map CSV header columns to our database column names.
     *
     * Handles both raw field names and French-label export formats.
     *
     * @param array $header CSV header row (lowercased).
     * @return array|WP_Error Associative array mapping DB column → CSV index.
     */
    private static function map_columns($header)
    {
        // Map of DB column → possible CSV header names.
        $mappings = array(
            'identifiant'            => array('identifiant_de_l_etablissement', 'identifiant de l\'etablissement', 'identifiant de l\'établissement'),
            'nom_etablissement'      => array('nom_etablissement', 'nom etablissement', 'nom_etablissement'),
            'type_etablissement'     => array('type_etablissement', 'type etablissement', 'type_etablissement'),
            'libelle_nature'         => array('libelle_nature', 'libelle nature', 'libellé nature'),
            'statut_public_prive'    => array('statut_public_prive', 'statut public prive', 'statut_public_prive', 'statut public privé'),
            'adresse'                => array('adresse_1', 'adresse 1'),
            'code_postal'            => array('code_postal', 'code postal'),
            'nom_commune'            => array('nom_commune', 'nom commune'),
            'code_commune'           => array('code_commune', 'code commune'),
            'libelle_departement'    => array('libelle_departement', 'libelle departement', 'libellé département'),
            'telephone'              => array('telephone', 'téléphone'),
            'mail'                   => array('mail'),
            'education_prioritaire'  => array('appartenance_education_prioritaire', 'appartenance education prioritaire'),
            'nom_circonscription'    => array('nom_circonscription', 'nom circonscription'),
            'code_circonscription'   => array('code_circonscription', 'code circonscription'),
        );

        $column_map = array();
        $missing = array();

        foreach ($mappings as $db_col => $possible_names) {
            $found = false;
            foreach ($possible_names as $name) {
                $index = array_search($name, $header, true);
                if ($index !== false) {
                    $column_map[$db_col] = $index;
                    $found = true;
                    break;
                }
            }
            // identifiant is mandatory; others default to empty.
            if (!$found && $db_col === 'identifiant') {
                $missing[] = $db_col;
            } elseif (!$found) {
                $column_map[$db_col] = -1; // Will resolve to empty string.
            }
        }

        if (!empty($missing)) {
            return new WP_Error(
                'csv_missing_columns',
                sprintf(
                    /* translators: %s: Comma-separated list of missing columns */
                    __('CSV is missing required columns: %s', 'gf-french-schools'),
                    implode(', ', $missing)
                )
            );
        }

        return $column_map;
    }

    /**
     * Map a CSV row to a database record array.
     *
     * @param array $row        Raw CSV row.
     * @param array $column_map Column mapping from map_columns().
     * @return array|null Associative array for insertion, or null if invalid.
     */
    private static function map_row($row, $column_map)
    {
        $record = array();
        foreach ($column_map as $db_col => $csv_index) {
            if ($csv_index === -1 || !isset($row[$csv_index])) {
                $record[$db_col] = '';
            } else {
                $record[$db_col] = trim($row[$csv_index]);
            }
        }

        // Skip rows without an identifier.
        if (empty($record['identifiant'])) {
            return null;
        }

        return $record;
    }

    /**
     * Batch-insert records into the table.
     *
     * @param string $table   Table name.
     * @param array  $records Array of associative arrays.
     * @return void
     */
    private static function insert_batch($table, $records)
    {
        global $wpdb;

        if (empty($records)) {
            return;
        }

        $columns = array_keys($records[0]);
        $placeholders_row = '(' . implode(',', array_fill(0, count($columns), '%s')) . ')';
        $placeholders = array();
        $values = array();

        foreach ($records as $record) {
            $placeholders[] = $placeholders_row;
            foreach ($columns as $col) {
                $values[] = $record[$col] ?? '';
            }
        }

        $sql = "INSERT IGNORE INTO {$table} (`" . implode('`,`', $columns) . "`) VALUES " . implode(',', $placeholders); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $wpdb->query($wpdb->prepare($sql, $values)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Record a sync failure.
     *
     * @param string $message Error message.
     * @return void
     */
    private static function sync_failed($message)
    {
        update_option(self::OPTION_SYNC_STATUS, 'error');
        update_option(self::OPTION_SYNC_ERROR, $message);
        self::log('Sync failed: ' . $message);
    }

    // ------------------------------------------------------------------
    // Search (mirrors GF_Ecoles_API_Service interface)
    // ------------------------------------------------------------------

    /**
     * Search cities in the local database.
     *
     * @param string $statut              School status (Public/Privé).
     * @param string $departement         Department name.
     * @param string $query               Search query (min 2 chars).
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège" and "Lycée".
     * @return array List of cities.
     */
    public static function search_cities($statut, $departement, $query, $hide_ecoles = false, $hide_colleges_lycees = false)
    {
        global $wpdb;

        if (empty($statut) || empty($departement) || strlen($query) < 2) {
            return array();
        }

        $table = self::get_table_name();

        // Split the query into individual words so that "les pa" matches
        // "Les Pavillons Sous Bois" (each word is checked independently).
        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);

        // Base conditions (statut + departement).
        $base_where = $wpdb->prepare(
            'statut_public_prive = %s AND libelle_departement = %s',
            $statut,
            $departement
        );

        // One LIKE clause per word using CONCAT to avoid WP 6.x % escaping.
        $word_clauses = array();
        foreach ($words as $word) {
            $word_clauses[] = $wpdb->prepare(
                "nom_commune LIKE CONCAT('%%', %s, '%%')",
                $wpdb->esc_like($word)
            );
        }

        $where = $base_where . ' AND ' . implode(' AND ', $word_clauses);

        if ($hide_ecoles) {
            $where .= " AND type_etablissement != 'Ecole'";
        }
        if ($hide_colleges_lycees) {
            $where .= " AND type_etablissement != 'Collège' AND type_etablissement != 'Lycée'";
        }

        $sql = "SELECT DISTINCT nom_commune, code_commune FROM {$table} WHERE {$where} ORDER BY nom_commune LIMIT 20"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $rows = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $results = array();
        foreach ($rows as $row) {
            $results[] = array(
                'value' => $row['nom_commune'],
                'label' => $row['nom_commune'],
                'code_commune' => $row['code_commune'] ?? '',
            );
        }

        // Fuzzy fallback: if exact LIKE found nothing, try Levenshtein matching.
        if (empty($results) && mb_strlen($query) >= 3) {
            $results = self::fuzzy_search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees);
        }

        return $results;
    }

    /**
     * Fuzzy search for cities using Levenshtein distance.
     *
     * Fetches all distinct city names for the given department/status and
     * ranks them by edit distance against the query.
     *
     * @param string $statut              School status.
     * @param string $departement         Department name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège"/"Lycée".
     * @return array Matched cities.
     */
    private static function fuzzy_search_cities($statut, $departement, $query, $hide_ecoles, $hide_colleges_lycees)
    {
        global $wpdb;

        $table = self::get_table_name();

        $where = $wpdb->prepare(
            'statut_public_prive = %s AND libelle_departement = %s',
            $statut,
            $departement
        );

        if ($hide_ecoles) {
            $where .= " AND type_etablissement != 'Ecole'";
        }
        if ($hide_colleges_lycees) {
            $where .= " AND type_etablissement != 'Collège' AND type_etablissement != 'Lycée'";
        }

        $sql = "SELECT DISTINCT nom_commune FROM {$table} WHERE {$where} ORDER BY nom_commune"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $all_cities = $wpdb->get_col($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if (empty($all_cities)) {
            return array();
        }

        $query_normalized = self::normalize_for_fuzzy($query);
        $query_words = preg_split('/\s+/', $query_normalized, -1, PREG_SPLIT_NO_EMPTY);
        $query_len = mb_strlen($query_normalized);

        // Maximum allowed Levenshtein distance scales with query length.
        $max_distance = max(1, (int) floor($query_len / 3));
        // Cap at 3 to avoid overly broad matches.
        $max_distance = min($max_distance, 3);

        $scored = array();

        foreach ($all_cities as $city) {
            $city_normalized = self::normalize_for_fuzzy($city);

            // Strategy 1: Levenshtein on full normalized string (for short queries).
            $distance = levenshtein(
                mb_substr($query_normalized, 0, 255),
                mb_substr($city_normalized, 0, 255)
            );

            if ($distance <= $max_distance) {
                $scored[] = array('city' => $city, 'distance' => $distance);
                continue;
            }

            // Strategy 2: Match each query word against each city word with tolerance.
            // "mobtreuil" should match "Montreuil" even as part of a longer city name.
            $city_words = preg_split('/[\s\-]+/', $city_normalized, -1, PREG_SPLIT_NO_EMPTY);
            $all_words_matched = true;
            $total_word_distance = 0;

            foreach ($query_words as $qw) {
                $qw_len = mb_strlen($qw);
                $word_max = max(1, (int) floor($qw_len / 3));
                $word_max = min($word_max, 3);
                $best_word_dist = PHP_INT_MAX;

                foreach ($city_words as $cw) {
                    // Compare the query word against a prefix of the city word (for partial typing).
                    $cw_trimmed = mb_substr($cw, 0, mb_strlen($qw) + $word_max);
                    $d = levenshtein(
                        mb_substr($qw, 0, 255),
                        mb_substr($cw_trimmed, 0, 255)
                    );
                    if ($d < $best_word_dist) {
                        $best_word_dist = $d;
                    }
                }

                if ($best_word_dist > $word_max) {
                    $all_words_matched = false;
                    break;
                }
                $total_word_distance += $best_word_dist;
            }

            if ($all_words_matched) {
                $scored[] = array('city' => $city, 'distance' => $total_word_distance);
            }
        }

        // Sort by distance (best match first) and limit results.
        usort($scored, function ($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        $results = array();
        foreach (array_slice($scored, 0, 20) as $item) {
            $results[] = array(
                'value' => $item['city'],
                'label' => $item['city'],
            );
        }

        return $results;
    }

    /**
     * Normalize a string for fuzzy comparison.
     *
     * Lowercases, strips accents, and removes non-alphanumeric characters
     * except spaces and hyphens.
     *
     * @param string $str Input string.
     * @return string Normalized string.
     */
    private static function normalize_for_fuzzy($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        // Remove accents using transliteration.
        if (function_exists('remove_accents')) {
            $str = remove_accents($str);
        }
        // Keep only letters, numbers, spaces and hyphens.
        $str = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $str);
        $str = preg_replace('/\s+/', ' ', trim($str));
        return $str;
    }

    /**
     * Search schools in the local database.
     *
     * @param string $statut              School status (Public/Privé).
     * @param string $departement         Department name.
     * @param string $ville               City name.
     * @param string $query               Search query (min 2 chars).
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège" and "Lycée".
     * @return array List of schools.
     */
    public static function search_schools($statut, $departement, $ville, $query, $hide_ecoles = false, $hide_colleges_lycees = false, $code_commune = '')
    {
        global $wpdb;

        if (empty($statut) || empty($departement) || empty($ville) || strlen($query) < 2) {
            return array();
        }

        $table = self::get_table_name();
        $code_commune = preg_replace('/[^0-9A-Za-z]/', '', $code_commune);

        // Generate abbreviation variants for bidirectional matching.
        // e.g. "St Exupery" also matches "Saint Exupéry" and vice-versa.
        $variants = class_exists('GF_Ecoles_API_Service')
            ? GF_Ecoles_API_Service::get_query_variants($query)
            : array($query);

        $name_clauses = array();
        foreach ($variants as $variant) {
            $variant_escaped = $wpdb->esc_like($variant);
            $name_clauses[] = $wpdb->prepare(
                "nom_etablissement LIKE CONCAT('%%', %s, '%%')",
                $variant_escaped
            );
        }
        $name_condition = count($name_clauses) > 1
            ? '(' . implode(' OR ', $name_clauses) . ')'
            : $name_clauses[0];

        // Use code_commune when available for reliable matching.
        // Some schools have incorrect nom_commune in the national database.
        if (!empty($code_commune)) {
            $where = $wpdb->prepare(
                "statut_public_prive = %s AND libelle_departement = %s AND code_commune = %s AND ",
                $statut,
                $departement,
                $code_commune
            ) . $name_condition;
        } else {
            // Fallback to nom_commune matching
            // Build flexible city LIKE pattern: split by whitespace, escape each part, join with %
            // This handles inconsistent spacing in database (e.g., "Paris 18e  Arrondissement")
            $ville_parts = preg_split('/\s+/', trim($ville));
            $ville_like_parts = array_map(function($part) use ($wpdb) {
                return $wpdb->esc_like($part);
            }, $ville_parts);
            $ville_like = implode('%', $ville_like_parts);

            // Build WHERE clause using CONCAT for LIKE wildcards to avoid WordPress % escaping
            // WordPress 6.x escapes % in prepare() which breaks LIKE patterns
            $where = $wpdb->prepare(
                "statut_public_prive = %s AND libelle_departement = %s AND nom_commune LIKE CONCAT('%%', %s, '%%') AND ",
                $statut,
                $departement,
                $ville_like
            ) . $name_condition;
        }

        if ($hide_ecoles) {
            $where .= " AND type_etablissement != 'Ecole'";
        }
        if ($hide_colleges_lycees) {
            $where .= " AND type_etablissement != 'Collège' AND type_etablissement != 'Lycée'";
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} LIMIT 20"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $rows = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $results = array();
        foreach ($rows as $item) {
            $results[] = self::format_school_row($item);
        }

        // Fuzzy fallback: if exact LIKE found nothing, try Levenshtein matching on school names.
        if (empty($results) && mb_strlen($query) >= 3) {
            $results = self::fuzzy_search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune);
        }

        return $results;
    }

    /**
     * Format a database row into the standard school result array.
     *
     * @param array $item Database row.
     * @return array Formatted school data.
     */
    private static function format_school_row($item)
    {
        return array(
            'identifiant'          => $item['identifiant'] ?? '',
            'nom'                  => $item['nom_etablissement'] ?? '',
            'type'                 => $item['type_etablissement'] ?? '',
            'nature'               => $item['libelle_nature'] ?? '',
            'adresse'              => $item['adresse'] ?? '',
            'code_postal'          => $item['code_postal'] ?? '',
            'commune'              => $item['nom_commune'] ?? '',
            'telephone'            => $item['telephone'] ?? '',
            'mail'                 => $item['mail'] ?? '',
            'education_prioritaire' => $item['education_prioritaire'] ?? '',
            'nom_circonscription'  => $item['nom_circonscription'] ?? '',
            'code_circonscription' => $item['code_circonscription'] ?? '',
        );
    }

    /**
     * Fuzzy search for schools using Levenshtein distance on school names.
     *
     * Fetches all schools in the given city and ranks them by edit distance
     * against the query words.
     *
     * @param string $statut              School status.
     * @param string $departement         Department name.
     * @param string $ville               City name.
     * @param string $query               Search query.
     * @param bool   $hide_ecoles         Whether to hide "Ecole" type schools.
     * @param bool   $hide_colleges_lycees Whether to hide "Collège"/"Lycée".
     * @return array Matched schools.
     */
    private static function fuzzy_search_schools($statut, $departement, $ville, $query, $hide_ecoles, $hide_colleges_lycees, $code_commune = '')
    {
        global $wpdb;

        $table = self::get_table_name();
        $code_commune = preg_replace('/[^0-9A-Za-z]/', '', $code_commune);

        // Use code_commune when available for reliable matching.
        if (!empty($code_commune)) {
            $where = $wpdb->prepare(
                "statut_public_prive = %s AND libelle_departement = %s AND code_commune = %s",
                $statut,
                $departement,
                $code_commune
            );
        } else {
            $ville_parts = preg_split('/\s+/', trim($ville));
            $ville_like_parts = array_map(function ($part) use ($wpdb) {
                return $wpdb->esc_like($part);
            }, $ville_parts);
            $ville_like = implode('%', $ville_like_parts);

            $where = $wpdb->prepare(
                "statut_public_prive = %s AND libelle_departement = %s AND nom_commune LIKE CONCAT('%%', %s, '%%')",
                $statut,
                $departement,
                $ville_like
            );
        }

        if ($hide_ecoles) {
            $where .= " AND type_etablissement != 'Ecole'";
        }
        if ($hide_colleges_lycees) {
            $where .= " AND type_etablissement != 'Collège' AND type_etablissement != 'Lycée'";
        }

        $sql = "SELECT * FROM {$table} WHERE {$where}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $all_schools = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if (empty($all_schools)) {
            return array();
        }

        // Expand abbreviations in query before fuzzy matching so that
        // "st" is compared as "saint" against school names containing "saint".
        $expanded_query = $query;
        if (class_exists('GF_Ecoles_API_Service')) {
            $variants = GF_Ecoles_API_Service::get_query_variants($query);
            // Use the expanded variant (longest) for fuzzy comparison.
            foreach ($variants as $variant) {
                if (mb_strlen($variant) > mb_strlen($expanded_query)) {
                    $expanded_query = $variant;
                }
            }
        }

        $query_normalized = self::normalize_for_fuzzy($expanded_query);
        $query_words = preg_split('/\s+/', $query_normalized, -1, PREG_SPLIT_NO_EMPTY);

        $scored = array();

        foreach ($all_schools as $school) {
            $name_normalized = self::normalize_for_fuzzy($school['nom_etablissement'] ?? '');
            $name_words = preg_split('/[\s\-]+/', $name_normalized, -1, PREG_SPLIT_NO_EMPTY);

            $all_words_matched = true;
            $total_distance = 0;

            foreach ($query_words as $qw) {
                $qw_len = mb_strlen($qw);
                $word_max = max(1, (int) floor($qw_len / 3));
                $word_max = min($word_max, 3);
                $best_dist = PHP_INT_MAX;

                foreach ($name_words as $nw) {
                    $nw_trimmed = mb_substr($nw, 0, mb_strlen($qw) + $word_max);
                    $d = levenshtein(
                        mb_substr($qw, 0, 255),
                        mb_substr($nw_trimmed, 0, 255)
                    );
                    if ($d < $best_dist) {
                        $best_dist = $d;
                    }
                }

                if ($best_dist > $word_max) {
                    $all_words_matched = false;
                    break;
                }
                $total_distance += $best_dist;
            }

            if ($all_words_matched) {
                $scored[] = array('school' => $school, 'distance' => $total_distance);
            }
        }

        usort($scored, function ($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        $results = array();
        foreach (array_slice($scored, 0, 20) as $item) {
            $results[] = self::format_school_row($item['school']);
        }

        return $results;
    }

    // ------------------------------------------------------------------
    // Cron scheduling
    // ------------------------------------------------------------------

    /**
     * Schedule the monthly sync cron event.
     *
     * @return void
     */
    public static function schedule_sync()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'monthly', self::CRON_HOOK);
        }
    }

    /**
     * Unschedule the monthly sync cron event.
     *
     * @return void
     */
    public static function unschedule_sync()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    // ------------------------------------------------------------------
    // Status helpers
    // ------------------------------------------------------------------

    /**
     * Get human-readable sync status information.
     *
     * @return array{status: string, last_sync: int|false, record_count: int, error: string}
     */
    public static function get_status()
    {
        return array(
            'status'       => get_option(self::OPTION_SYNC_STATUS, 'idle'),
            'last_sync'    => get_option(self::OPTION_LAST_SYNC, false),
            'record_count' => (int) get_option(self::OPTION_RECORD_COUNT, 0),
            'error'        => get_option(self::OPTION_SYNC_ERROR, ''),
        );
    }

    // ------------------------------------------------------------------
    // Logging
    // ------------------------------------------------------------------

    /**
     * Log a message when WP_DEBUG is enabled.
     *
     * @param string $message Log message.
     * @return void
     */
    private static function log($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[GF French Schools - Local DB] ' . $message);
        }
    }
}
