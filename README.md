# Gravity Forms - French Schools

Extension Gravity Forms permettant aux utilisateurs de rechercher et sélectionner un établissement scolaire français via l'API officielle du Ministère de l'Éducation Nationale.

![Plugin Screenshot](https://github.com/guilamu/gf-french-schools/blob/main/screenshot.jpg)

## Search & Select Schools

- Choose the school status (Public or Private) and a French department
- Search cities by autocomplete with fuzzy matching for typo tolerance
- Search schools by autocomplete with abbreviation expansion (St ↔ Saint, Dr ↔ Docteur, etc.)
- Match ~69 000 schools from the official [Annuaire de l'Éducation Nationale](https://data.education.gouv.fr/explore/dataset/fr-en-annuaire-education/)
- Enter a school name manually when no result is found

## Configure & Filter

- Set a preselected status and/or department to hide those fields from users
- Filter by school type: hide primary schools (Écoles) or middle/high schools (Collèges/Lycées)
- Enable **Local Only** mode via **Forms → Settings → French Schools** to use the local database exclusively
- Enable **API Fallback** to silently query the remote API when local returns no results
- Trigger manual sync or rely on the automatic monthly WP-Cron synchronization

## Collect & Use Data

- Collect 16 data points per school: ID, name, type, category, address, postal code, city, phone, email, priority education status, circonscription, and more
- Access all data via merge tags in notifications and confirmations (e.g., `{Label:ID:nom}`, `{Label:ID:all}`)
- Use sub-inputs for conditional logic on any data point (e.g., show a field only when type = "Collège")
- Compatible with GP Copy Cat for copying sub-input values to other fields

## Key Features

- **Multilingual:** Works with content in any language
- **Translation-Ready:** All strings are internationalized; French translation included
- **Secure:** AJAX endpoint validates nonce, form context, rate-limits requests, and restricts proxy header trust to an explicit allowlist
- **GitHub Updates:** Automatic updates from GitHub releases
- **Offline Resilient:** Local database fallback with ~69 000 schools, automatic monthly sync, and fuzzy search with Levenshtein distance

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- [Gravity Forms](https://www.gravityforms.com/) 2.5 or higher

## Installation

1. Download the plugin from [GitHub Releases](https://github.com/guilamu/gf-french-schools/releases)
2. Upload the `gf-french-schools` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. Go to **Forms → Settings → French Schools** and configure the operating mode (API, Local Only, or hybrid)
5. The new "French Schools" field type is now available in the Gravity Forms form editor

## Configuration

### Field Settings

In the form editor, the "French Schools" field offers these options:

#### Preselection
- **Preselected Status:** Set Public or Private as default (the field will be hidden from users)
- **Preselected Department:** Set a department as default (the field will be hidden from users)

#### School Type Filters
- **Hide primary schools:** Excludes Écoles maternelles and élémentaires from results
- **Hide middle and high schools:** Excludes Collèges and Lycées from results

### Settings Page

Accessible via **Forms → Settings → French Schools**:

#### Local Only Mode
- Enable/disable Local Only mode to use the local database exclusively without calling the remote API
- Useful for API downtime or performance optimization

#### Local Database Sync
- View sync status (status, last sync, record count, next scheduled sync)
- Trigger a manual sync via the "Sync Now" button
- The sync downloads the full directory (~69 000 schools) from the Open Data portal
- A WordPress cron schedules an automatic monthly sync

## Collected Data

For each selected school, the following data is stored:

| Field | Description |
|-------|-------------|
| Identifiant | School UAI code |
| Nom | School name |
| Type | Collège, Lycée, École, etc. |
| Catégorie | Maternelle, Élémentaire, etc. |
| Adresse | Postal address |
| Code postal | Postal code |
| Ville | City |
| Téléphone | Phone number |
| E-mail | Email address |
| Éducation prioritaire | REP, REP+, or Non |
| Circonscription | Circonscription name (cleaned of standard prefix) |
| Mail circo | Circonscription email (code + academic domain) |
| Statut | Public or Private |
| Département | Department name |
| N° Département | Official department number (e.g., 93, 2A, 974) |
| Ville recherchée | City entered during search |

## Merge Tags

Accédez aux données de l'établissement dans les notifications et confirmations :

| Merge Tag | Description |
|-----------|-------------|
| `{Libellé:ID}` | Nom de l'établissement (par défaut) |
| `{Libellé:ID:identifiant}` | Identifiant UAI |
| `{Libellé:ID:nom}` | Nom de l'établissement |
| `{Libellé:ID:autres_nom}` | Nom de l'école (saisie manuelle) |
| `{Libellé:ID:type}` | Type d'établissement |
| `{Libellé:ID:categorie}` | Catégorie |
| `{Libellé:ID:adresse}` | Adresse |
| `{Libellé:ID:code_postal}` | Code postal |
| `{Libellé:ID:ville}` | Ville |
| `{Libellé:ID:telephone}` | Téléphone |
| `{Libellé:ID:mail}` | Email |
| `{Libellé:ID:education_prioritaire}` | Statut éducation prioritaire |
| `{Libellé:ID:nom_circonscription}` | Nom de la circonscription |
| `{Libellé:ID:code_circonscription}` | Email Circonscription |
| `{Libellé:ID:statut}` | Statut (Public/Privé) |
| `{Libellé:ID:departement}` | Nom du département |
| `{Libellé:ID:numero_departement}` | Numéro du département (ex : 93, 2A, 974) |
| `{Libellé:ID:all}` | Toutes les informations |

Remplacez `Libellé` par le libellé de votre champ et `ID` par le numéro d'identifiant du champ.

### Sous-champs (Sub-inputs)

Chaque donnée est également accessible via son numéro de sous-champ `{Libellé:ID.N}` :

| Sous-champ | Description |
|------------|-------------|
| `{Libellé:ID.1}` | Identifiant UAI |
| `{Libellé:ID.2}` | Nom de l'établissement |
| `{Libellé:ID.3}` | Nom de l'école (saisie manuelle) |
| `{Libellé:ID.4}` | Type d'établissement |
| `{Libellé:ID.5}` | Catégorie |
| `{Libellé:ID.6}` | Adresse |
| `{Libellé:ID.7}` | Code postal |
| `{Libellé:ID.8}` | Commune |
| `{Libellé:ID.9}` | Téléphone |
| `{Libellé:ID.11}` | Email |
| `{Libellé:ID.12}` | Éducation prioritaire |
| `{Libellé:ID.13}` | Nom de la circonscription |
| `{Libellé:ID.14}` | Email Circonscription |
| `{Libellé:ID.15}` | Statut (Public/Privé) |
| `{Libellé:ID.16}` | Nom du département |
| `{Libellé:ID.17}` | Ville recherchée |
| `{Libellé:ID.18}` | Numéro du département |

> **Note :** le sous-champ `.10` n'existe pas (convention Gravity Forms).

## FAQ

### Which API is used?

The plugin queries the [French Education Ministry OpenDataSoft API](https://data.education.gouv.fr/explore/dataset/fr-en-annuaire-education/) (`v2.1`). No authentication or API key is required — the data is free and publicly accessible.

- **Endpoint:** `https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/records`
- **Documentation:** [OpenDataSoft API v2.1](https://help.opendatasoft.com/apis/ods-explore-v2/)

### What happens if the API is down?

The plugin automatically falls back to the local database (if synced). You can also enable **Local Only** mode in **Forms → Settings → French Schools** to never call the API at all.

### Can I customize the search debounce delay?

Yes, use the `gf_french_schools_timings` filter:

```php
add_filter( 'gf_french_schools_timings', function( $timings ) {
    $timings['debounce'] = 500; // milliseconds (default: 300)
    return $timings;
} );
```

### Can I change the rate limit?

Yes, use the `gf_french_schools_rate_limit` and `gf_french_schools_rate_window` filters:

```php
add_filter( 'gf_french_schools_rate_limit', function( $limit ) {
    return 50; // max requests per window (default: 30)
} );

add_filter( 'gf_french_schools_rate_window', function( $window ) {
    return 120; // window in seconds (default: 60)
} );
```

### Can I trust proxy headers for rate limiting?

By default, only `REMOTE_ADDR` is used. To trust forwarding headers (e.g., behind Cloudflare), use the `gf_french_schools_trusted_proxies` filter:

```php
add_filter( 'gf_french_schools_trusted_proxies', function( $proxies ) {
    $proxies[] = '172.16.0.1'; // your load balancer IP
    return $proxies;
} );
```

### The search feels slow in Local Only mode. What can I do?

Make sure you are running version 1.9.2 or later, which includes transient caching for local results, optimized `has_data()` checks, and cached form validation. After the first search, identical queries are served instantly from cache.

## Project Structure

```
.
├── gf-french-schools.php                # Main plugin file
├── uninstall.php                        # Cleanup on uninstall
├── assets
│   ├── css
│   │   ├── ecoles-fr-admin.css          # Form editor & settings styles
│   │   └── ecoles-fr.css               # Frontend styles & result block
│   └── js
│       ├── ecoles-fr-admin.js           # Form editor custom settings
│       └── ecoles-fr-frontend.js        # Cascade logic, autocomplete, accessibility
├── includes
│   ├── class-ecoles-api-service.php     # OpenDataSoft API client + cache + local fallback
│   ├── class-ecoles-local-db.php        # Local database, CSV import, cron sync
│   ├── class-gf-field-ecoles-fr.php     # GF field definition, rendering, validation
│   ├── class-gf-french-schools-addon.php # GFAddOn settings page (Local Only, Sync)
│   ├── class-github-updater.php         # GitHub auto-updates
│   └── Parsedown.php                   # Markdown parser for plugin details modal
└── languages
    ├── gf-french-schools-fr_FR.mo       # French translation (binary)
    ├── gf-french-schools-fr_FR.po       # French translation (source)
    └── gf-french-schools.pot            # Translation template
```

## Changelog

### 1.9.2 - 2026-05-21
- **Improved:** Local Only mode now caches city search results in transients — identical queries are served instantly instead of re-querying 68 000 rows
- **Improved:** `has_data()` check uses `SELECT 1 LIMIT 1` with static cache instead of `COUNT(*)` on every request
- **Improved:** AJAX form validation (form loading + field scan) is cached for 5 minutes instead of running on every keystroke
- **Improved:** Fuzzy search fallback queries limited to 500 cities / 200 schools to prevent memory/CPU spikes

### 1.9.1 - 2026-03-28
- **New:** "View details" link on the Plugins page opens a modal with Description, Installation, and Changelog tabs (parsed from local README.md via Parsedown)
- **Improved:** Fuzzy search detects typos in partially typed words (e.g., "aubertill" → "Aubervilliers"); remote API falls back to local DB when it returns no results

### 1.9.0 - 2026-03-28
- **New:** Automatic abbreviation recognition in school names — bidirectional search (e.g., "St Exupéry" finds "Saint-Exupéry" and vice-versa)
- **New:** Supported abbreviations: St/Saint, Ste/Sainte, Gén/Gal/Général, Cdt/Commandant, Lt/Lieutenant, Col/Colonel, Mal/Maréchal, Dr/Docteur, Pr/Professeur, Mgr/Monseigneur, Mme/Madame, Mlle/Mademoiselle, Pdt/Président, Cpt/Capitaine, Sgt/Sergent, Adj/Adjudant
- **Improved:** Works across all 3 search modes: remote API, local DB (LIKE), and fuzzy search (Levenshtein)

### 1.8.9 - 2026-03-27
- **Fixed:** Schools not found in some cities — school search now uses `code_commune` (INSEE code) instead of `nom_commune` for filtering, working around `nom_commune` errors in the national database (e.g., Pierrefitte-sur-Seine schools listed under "Saint-Denis")
- **New:** Added `code_commune` column to local database and synced CSV export
- **Improved:** Backward compatible — falls back to `nom_commune` filtering when `code_commune` is unavailable

### 1.8.8 - 2026-03-27
- **Fixed:** Empty school search results were cached for 1 hour, causing false "no results" for existing schools
- **New:** "API Fallback" option: when Local Only mode returns no results, silently queries the remote API as a last resort (disabled by default, visible only when Local Only is active)
- **New:** Fuzzy search on local database: tolerates typos in city and school names using Levenshtein distance (e.g., "mobtreuil" → "Montreuil"). Activates automatically when exact search returns no results (minimum 3 characters)
- **Changed:** Internal refactoring: remote API calls extracted into private methods `get_villes_from_api()` and `get_ecoles_from_api()`

### 1.8.7 - 2026-03-09
- **Fixed:** WordPress 6.7+ warning "Translation loading triggered too early" — removed `__()` call in `cron_schedules` filter
- **Fixed:** PHP warning "Undefined property: stdClass::$slug" on update-core.php — added required `id`, `slug`, `plugin`, and `new_version` fields to GitHub updater update object
- **Changed:** Centralized `TESTED_WP`, `REQUIRES_PHP`, and `PLUGIN_SLUG` constants in `GF_French_Schools_GitHub_Updater`

### 1.8.6 - 2026-03-03
- **New:** Department number as sub-input (`{Label:ID.18}`) and merge tag (`{Label:ID:numero_departement}`)
- **New:** Merge tags `{Label:ID:statut}` and `{Label:ID:departement}` for status and department name
- **Improved:** Documentation updated with all available merge tags and sub-inputs

### 1.8.5 - 2026-03-02
- **Fixed:** Circonscription name cleaning: added missing pattern "Circonscription d'inspection du 1er degré" (without article de/du/d')

### 1.8.4 - 2026-02-25
- **Security:** Fixed rate-limit bypass via proxy header spoofing (`X-Forwarded-For`, `CF-Connecting-IP`, etc.) — forwarding headers are now only trusted when `REMOTE_ADDR` is in the `gf_french_schools_trusted_proxies` allowlist
- **Security:** AJAX search endpoint now verifies the submitted form contains an `ecoles_fr` field

### 1.8.3 - 2026-02-23
- **Fixed:** WordPress 6.7 warning "`_load_textdomain_just_in_time` called incorrectly" — removed `__()` call in `get_default_inputs()` that triggered early translation loading
- **Changed:** Textdomain loading moved to `init` hook (instead of `plugins_loaded`) per WordPress 6.7+ recommendations

### 1.8.2 - 2026-02-20
- **Fixed:** "Email Circonscription" field was displayed on frontend but not saved in entries for forms created before the sub-input was added
- **Improved:** Field constructor now always synchronizes sub-inputs with the canonical definition

### 1.8.1 - 2026-02-18
- **Fixed:** City search with articles: "les pa" now finds "Les Pavillons Sous Bois", "les l" finds "Les Lilas", etc.
- **Improved:** Both remote API and local DB now use word-by-word matching for city names
- **New:** GP Copy Cat compatibility: hidden sub-inputs now fire jQuery `change` events on school selection

### 1.8.0 - 2026-02-16
- **New:** Full conditional logic support: every sub-input (ID, name, type, category, postal code, city, etc.) is available as a conditional logic parameter
- **Improved:** Category cleaning: "ECOLE DE NIVEAU ELEMENTAIRE" now displays "Élémentaire" instead of "De niveau elementaire"
- **Improved:** Automatic primary school detection: when the school name contains "primaire", category displays "Primaire" instead of "Élémentaire"
- **Improved:** Browser autocomplete blocked on all form fields

### 1.7.3 - 2026-02-15
- **Changed:** Merge tags renamed for consistency: `id` → `identifiant`, `nature` → `categorie`, `commune` → `ville`
- **Improved:** Old names remain functional for backward compatibility

### 1.7.2 - 2026-02-11
- **New:** Merge tag `autres_nom` for manually entered school names
- **Improved:** Merge tag now visible in Gravity Forms merge tag dropdown

### 1.7.1 - 2026-02-11
- **Fixed:** School search for Paris arrondissements (inconsistent spacing in database)
- **Improved:** Parisian school name display (removed E.M.PU, E.E.PU, E.P.PR, etc. prefixes and addresses)
- **Changed:** API search uses LIKE instead of search() for better partial matching
- **Fixed:** Cache now separates local and remote results

### 1.7.0 - 2026-02-11
- **New:** Full Gravity Forms Orbital theme support
- **Fixed:** Autocomplete dropdown hidden behind following fields (z-index)
- **Fixed:** Blur handler dismissed results too quickly
- **Fixed:** Department placeholder when status is preselected
- **Changed:** Field icon changed to match settings page (dashicons-building)
- **Improved:** Cancel button hidden in form editor

### 1.6.1 - 2026-02-10
- **Improved:** Major improvement to school name cleaning

### 1.6.0 - 2026-02-10
- **Improved:** Result display in Entry View
- **Fixed:** "Priority Education" field now shows "Non" instead of blank when not set

### 1.5.4 - 2026-02-09
- **Fixed:** Complete rewrite of translation loading system for compatibility
- **Improved:** Absolute path loading, multi-hook safety net, forced reload on locale change, base locale fallback

### 1.5.3 - 2026-02-08
- **Improved:** Toggle instead of checkbox for "Local Only" option (Gravity Forms style consistency)
- **Fixed:** "Sync Now" button styling to match Gravity Forms settings buttons

### 1.5.2 - 2026-02-07
- **New:** Dashicon "building" icon for the French Schools settings tab

### 1.5.1 - 2026-02-07
- **Changed:** Settings page integrated into Gravity Forms Settings tab (GFAddOn) instead of a separate submenu
- **Improved:** Uses GFAddOn framework for rendering and saving settings

### 1.5.0 - 2026-02-07
- **New:** Local database as safety net when the API is unavailable
- **New:** Automatic monthly download of the full directory (~69 000 schools) via WP-Cron
- **New:** Secure CSV import with staging table (existing data only replaced after validation)
- **New:** Automatic fallback to local DB on API error
- **New:** Settings page under **Forms → French Schools**
- **New:** Local Only mode to disable the remote API entirely
- **New:** Manual sync button with real-time status display

### 1.4.0 - 2026-01-27
- **New:** "Circonscription" and "Mail circo" fields for each school
- **Improved:** Circonscription name cleaned of standard prefix
- **Improved:** Circonscription email auto-generated from code + academic domain
- **New:** Merge tags `nom_circonscription` and `code_circonscription`

### 1.3.0 - 2026-01-23
- **New:** "Other" field for manual school name entry when search returns no results
- **Improved:** Manual entries correctly saved, displayed, and exported with "Manual Entry" label
- **Improved:** French translation files updated

### 1.2.0 - 2026-01-18
- **New:** Guilamu Bug Reporter integration
- **New:** "🐛 Report a Bug" link in the plugins list

### 1.1.3 - 2026-01-06
- **Improved:** Plugin description translated to French
- **New:** GitHub Actions workflow for automatic release creation
- **Improved:** Automatic ZIP generation with correct folder naming

### 1.1.1 - 2025-12-27
- **New:** Option to hide the summary block with accessible fallback directly in the field (Type Category Name)
- **New:** i18n string for "No" fallback in JS
- **Improved:** Consistent cleaning of stored and displayed values (name/category)

### 1.1.0 - 2025-12-27
- **Security:** AJAX form validation, status/department whitelists, filterable rate limiting
- **Improved:** Robust frontend requests: in-flight cancellation, deduplication, configurable timeouts, retries with backoff
- **Improved:** Minimum Gravity Forms version check with clear error messages
- **Improved:** More resilient GitHub updater (fallback copy/delete, debug logging)
- **Changed:** Admin CSS extracted to dedicated file (no more inline styles)

### 1.0.4 - 2025-12-26
- **Improved:** CSS for preselected fields no longer loaded on every page
- **New:** Rate limiting on AJAX endpoint
- **Improved:** GitHub updater refactored with API request caching
- **Removed:** Dead code (unused merge tag filter)
- **Improved:** Better contrast between enabled and disabled fields
- **Improved:** Translation files updated

### 1.0.3 - 2025-12-26
- Initial release

## Contributing

Contributions are welcome! Feel free to open an issue or a pull request on [GitHub](https://github.com/guilamu/gf-french-schools).

## License

This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.

## Author

**Guilamu** - [GitHub](https://github.com/guilamu)

## Acknowledgments

- [Gravity Forms](https://www.gravityforms.com/) for their excellent forms framework
- [data.education.gouv.fr](https://data.education.gouv.fr/) for the school directory API

---

<p align="center">
  Made with love for the WordPress community
</p>
