# Gravity Forms - French Schools

Extension Gravity Forms permettant aux utilisateurs de rechercher et sélectionner un établissement scolaire français via l'API officielle du Ministère de l'Éducation Nationale.

![Plugin Screenshot](https://github.com/guilamu/gf-french-schools/blob/main/screenshot.jpg)

## Rechercher et sélectionner des établissements

- Choisir le statut de l'établissement (Public ou Privé) et un département français
- Rechercher une ville par auto-complétion avec tolérance aux fautes de frappe (recherche floue)
- Rechercher un établissement par auto-complétion avec expansion des abréviations (St ↔ Saint, Dr ↔ Docteur, etc.)
- Interroger ~69 000 établissements depuis l'[Annuaire de l'Éducation Nationale](https://data.education.gouv.fr/explore/dataset/fr-en-annuaire-education/)
- Saisir manuellement un nom d'école lorsqu'aucun résultat n'est trouvé

## Configurer et filtrer

- Présélectionner un statut et/ou un département pour masquer ces champs aux utilisateurs
- Filtrer par type d'établissement : masquer les écoles primaires (Écoles) ou les collèges/lycées
- Activer le mode **Local Only** via **Formulaires → Réglages → French Schools** pour utiliser exclusivement la base de données locale
- Activer le **Repli API** pour interroger silencieusement l'API distante quand la base locale ne retourne aucun résultat
- Lancer une synchronisation manuelle ou s'appuyer sur la synchronisation mensuelle automatique via WP-Cron

## Collecter et exploiter les données

- Collecter 16 données par établissement : identifiant, nom, type, catégorie, adresse, code postal, ville, téléphone, email, éducation prioritaire, circonscription, etc.
- Accéder à toutes les données via les merge tags dans les notifications et confirmations (ex : `{Libellé:ID:nom}`, `{Libellé:ID:all}`)
- Utiliser les sous-champs pour la logique conditionnelle sur n'importe quelle donnée (ex : afficher un champ uniquement quand type = « Collège »)
- Compatible avec GP Copy Cat pour copier les valeurs des sous-champs vers d'autres champs

## Fonctionnalités clés

- **Multilingue :** Fonctionne avec du contenu dans n'importe quelle langue
- **Prêt pour la traduction :** Toutes les chaînes sont internationalisées ; traduction française incluse
- **Sécurisé :** L'endpoint AJAX valide le nonce, le contexte du formulaire, limite les requêtes, et restreint la confiance aux en-têtes proxy via une liste blanche explicite
- **Mises à jour GitHub :** Mises à jour automatiques depuis les releases GitHub
- **Résilient hors-ligne :** Base de données locale de ~69 000 établissements, synchronisation mensuelle automatique et recherche floue par distance de Levenshtein

## Prérequis

- WordPress 5.8 ou supérieur
- PHP 7.4 ou supérieur
- [Gravity Forms](https://www.gravityforms.com/) 2.5 ou supérieur

## Installation

1. Téléchargez le plugin depuis [GitHub Releases](https://github.com/guilamu/gf-french-schools/releases)
2. Uploadez le dossier `gf-french-schools` dans `/wp-content/plugins/`
3. Activez le plugin dans le menu **Extensions** de WordPress
4. Accédez à **Formulaires → Réglages → French Schools** et configurez le mode de fonctionnement (API, Local Only, ou hybride)
5. Le nouveau type de champ « Écoles françaises » est disponible dans l'éditeur de formulaires Gravity Forms

## Configuration

### Paramètres du champ

Dans l'éditeur de formulaire, le champ « Écoles françaises » propose les options suivantes :

#### Présélection
- **Statut présélectionné :** Définir Public ou Privé par défaut (le champ sera masqué)
- **Département présélectionné :** Définir un département par défaut (le champ sera masqué)

#### Filtres par type d'établissement
- **Masquer les écoles primaires :** Exclut les écoles maternelles et élémentaires des résultats
- **Masquer les collèges et lycées :** Exclut les établissements secondaires des résultats

### Page de réglages

Accessible via **Formulaires → Réglages → French Schools** :

#### Mode Local uniquement
- Activer/désactiver le mode « Local Only » pour utiliser exclusivement la base de données locale sans appeler l'API distante
- Utile en cas d'indisponibilité prolongée de l'API ou pour des raisons de performance

#### Synchronisation de la base locale
- Visualiser l'état de la synchronisation (statut, dernière synchro, nombre d'enregistrements, prochaine synchro planifiée)
- Lancer une synchronisation manuelle via le bouton « Synchroniser maintenant »
- La synchronisation télécharge l'intégralité de l'annuaire (~69 000 établissements) depuis le portail Open Data du Ministère
- Un cron WordPress planifie automatiquement une synchronisation mensuelle

## Données collectées

Pour chaque établissement sélectionné, les informations suivantes sont enregistrées :

| Champ | Description |
|-------|-------------|
| Identifiant | Code UAI de l'établissement |
| Nom | Nom de l'établissement |
| Type | Collège, Lycée, École, etc. |
| Catégorie | Maternelle, Élémentaire, etc. |
| Adresse | Adresse postale |
| Code postal | Code postal |
| Ville | Commune |
| Téléphone | Numéro de téléphone |
| E-mail | Adresse email |
| Éducation prioritaire | REP, REP+, ou Non |
| Circonscription | Nom de la circonscription (nettoyé du préfixe standard) |
| Mail circo | Email Circonscription (code + domaine académique) |
| Statut | Public ou Privé |
| Département | Nom du département |
| N° Département | Numéro officiel du département (ex : 93, 2A, 974) |
| Ville recherchée | Ville saisie lors de la recherche |

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

### Quelle API est utilisée ?

Le plugin interroge l'[API OpenDataSoft du Ministère de l'Éducation Nationale](https://data.education.gouv.fr/explore/dataset/fr-en-annuaire-education/) (`v2.1`). Aucune authentification ou clé API n'est requise — les données sont gratuites et accessibles publiquement.

- **Endpoint :** `https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/records`
- **Documentation :** [OpenDataSoft API v2.1](https://help.opendatasoft.com/apis/ods-explore-v2/)

### Que se passe-t-il si l'API est indisponible ?

Le plugin bascule automatiquement vers la base de données locale (si synchronisée). Vous pouvez aussi activer le mode **Local Only** dans **Formulaires → Réglages → French Schools** pour ne jamais appeler l'API.

### Peut-on personnaliser le délai de debounce de la recherche ?

Oui, utilisez le filtre `gf_french_schools_timings` :

```php
add_filter( 'gf_french_schools_timings', function( $timings ) {
    $timings['debounce'] = 500; // millisecondes (défaut : 300)
    return $timings;
} );
```

### Peut-on modifier la limite de requêtes ?

Oui, utilisez les filtres `gf_french_schools_rate_limit` et `gf_french_schools_rate_window` :

```php
add_filter( 'gf_french_schools_rate_limit', function( $limit ) {
    return 50; // requêtes max par fenêtre (défaut : 30)
} );

add_filter( 'gf_french_schools_rate_window', function( $window ) {
    return 120; // fenêtre en secondes (défaut : 60)
} );
```

### Peut-on faire confiance aux en-têtes proxy pour le rate limiting ?

Par défaut, seule l'adresse `REMOTE_ADDR` est utilisée. Pour faire confiance aux en-têtes de forwarding (ex : derrière Cloudflare), utilisez le filtre `gf_french_schools_trusted_proxies` :

```php
add_filter( 'gf_french_schools_trusted_proxies', function( $proxies ) {
    $proxies[] = '172.16.0.1'; // IP de votre load balancer
    return $proxies;
} );
```

### La recherche est lente en mode Local Only. Que faire ?

Assurez-vous d'utiliser la version 1.9.2 ou ultérieure, qui inclut la mise en cache transient des résultats locaux, l'optimisation de la vérification `has_data()`, et la mise en cache de la validation du formulaire. Après la première recherche, les requêtes identiques sont servies instantanément depuis le cache.

## Structure du projet

```
.
├── gf-french-schools.php                # Fichier principal du plugin
├── uninstall.php                        # Nettoyage lors de la désinstallation
├── assets
│   ├── css
│   │   ├── ecoles-fr-admin.css          # Styles éditeur GF et réglages
│   │   └── ecoles-fr.css               # Styles frontend + bloc résultat
│   └── js
│       ├── ecoles-fr-admin.js           # Réglages custom dans l'éditeur GF
│       └── ecoles-fr-frontend.js        # Logique cascade, autocomplétion, accessibilité
├── includes
│   ├── class-ecoles-api-service.php     # Client OpenDataSoft + cache + fallback local
│   ├── class-ecoles-local-db.php        # Base de données locale, import CSV, sync cron
│   ├── class-gf-field-ecoles-fr.php     # Définition du champ GF, rendu, validation
│   ├── class-gf-french-schools-addon.php # Page réglages GFAddOn (Local Only, Sync)
│   ├── class-github-updater.php         # Mise à jour automatique via GitHub
│   └── Parsedown.php                   # Parseur Markdown pour la modale de détails
└── languages
    ├── gf-french-schools-fr_FR.mo       # Traduction FR (binaire)
    ├── gf-french-schools-fr_FR.po       # Traduction FR (source)
    └── gf-french-schools.pot            # Modèle de traduction
```

## Changelog

### 1.9.2 - 2026-05-21
- **Amélioration :** Le mode Local Only met désormais en cache les résultats de recherche de villes dans des transients — les requêtes identiques sont servies instantanément au lieu de re-interroger les 68 000 enregistrements
- **Amélioration :** La vérification `has_data()` utilise `SELECT 1 LIMIT 1` avec cache statique au lieu de `COUNT(*)` à chaque requête
- **Amélioration :** La validation du formulaire dans l'endpoint AJAX (chargement + scan des champs) est mise en cache pendant 5 minutes au lieu d'être exécutée à chaque frappe
- **Amélioration :** Les requêtes de recherche floue (fuzzy search) sont limitées à 500 villes / 200 écoles pour éviter les pics mémoire/CPU

### 1.9.1 - 2026-03-28
- **Nouveau :** Ajout du lien « View details » sur la page Extensions, ouvrant une popup modale avec les onglets Description, Installation et Changelog (parsés depuis le README.md local via Parsedown)
- **Amélioration :** La recherche floue (fuzzy search) détecte les fautes de frappe dans les mots partiellement saisis (ex : « aubertill » → « Aubervilliers ») ; l'API distante repasse sur la base locale quand elle ne retourne aucun résultat

### 1.9.0 - 2026-03-28
- **Nouveau :** Reconnaissance automatique des abréviations courantes dans les noms d'écoles — la recherche est bidirectionnelle (ex : « St Exupéry » trouve « Saint-Exupéry » et inversement)
- **Nouveau :** Abréviations supportées : St/Saint, Ste/Sainte, Gén/Gal/Général, Cdt/Commandant, Lt/Lieutenant, Col/Colonel, Mal/Maréchal, Dr/Docteur, Pr/Professeur, Mgr/Monseigneur, Mme/Madame, Mlle/Mademoiselle, Pdt/Président, Cpt/Capitaine, Sgt/Sergent, Adj/Adjudant
- **Amélioration :** Fonctionne sur les 3 modes de recherche : API distante, base locale (LIKE) et recherche floue (Levenshtein)

### 1.8.9 - 2026-03-27
- **Correction :** Problème d'écoles introuvables dans certaines communes — la recherche d'écoles utilise désormais le `code_commune` (code INSEE) au lieu du `nom_commune` pour filtrer les résultats, contournant les erreurs de `nom_commune` dans la base nationale (ex : écoles de Pierrefitte-sur-Seine listées sous « Saint-Denis »)
- **Nouveau :** Ajout de la colonne `code_commune` dans la base de données locale et l'export CSV synchronisé
- **Amélioration :** Rétro-compatibilité : si `code_commune` n'est pas disponible, le filtrage par `nom_commune` reste actif

### 1.8.8 - 2026-03-27
- **Correction :** Les résultats vides de recherche d'écoles étaient mis en cache pendant 1 heure, provoquant des faux « aucun résultat » pour des écoles existantes
- **Nouveau :** Option « Repli API » : lorsque le mode local seul est activé et qu'une recherche ne retourne aucun résultat, le plugin peut interroger silencieusement l'API distante en dernier recours (option désactivée par défaut, visible uniquement quand « Local Only » est actif)
- **Nouveau :** Recherche floue (fuzzy search) sur la base locale : tolère les fautes de frappe dans les noms de villes et d'écoles grâce à un algorithme de distance de Levenshtein (ex : « mobtreuil » → « Montreuil »). Activé automatiquement quand la recherche exacte ne retourne aucun résultat (minimum 3 caractères)
- **Modification :** Refactoring interne : extraction des appels API distants dans des méthodes privées `get_villes_from_api()` et `get_ecoles_from_api()`

### 1.8.7 - 2026-03-09
- **Correction :** Avertissement WordPress 6.7+ « Translation loading triggered too early » : suppression de l'appel `__()` dans le filtre `cron_schedules` qui déclenchait le chargement des traductions avant `init`
- **Correction :** Avertissement PHP « Undefined property: stdClass::$slug » sur la page update-core.php : ajout des champs obligatoires `id`, `slug`, `plugin` et `new_version` à l'objet de mise à jour retourné par le GitHub updater
- **Modification :** Centralisation des constantes `TESTED_WP`, `REQUIRES_PHP` et `PLUGIN_SLUG` dans la classe `GF_French_Schools_GitHub_Updater`

### 1.8.6 - 2026-03-03
- **Nouveau :** Numéro de département comme sous-champ (`{Libellé:ID.18}`) et merge tag (`{Libellé:ID:numero_departement}`), permettant de récupérer le code officiel du département (ex : « 93 » pour Seine-Saint-Denis, « 2A » pour Corse-du-Sud, « 974 » pour La Réunion)
- **Nouveau :** Merge tags `{Libellé:ID:statut}` et `{Libellé:ID:departement}` pour accéder au statut et au nom du département via modificateur
- **Amélioration :** Documentation complétée : tous les merge tags et sous-champs disponibles sont désormais listés dans le README

### 1.8.5 - 2026-03-02
- **Correction :** Nettoyage des noms de circonscriptions : ajout du patron manquant « Circonscription d'inspection du 1er degré » (sans article de/du/d') qui laissait un résidu « d'inspection du 1er degré » dans le nom affiché

### 1.8.4 - 2026-02-25
- **Sécurité :** Correction du contournement du rate-limiting par usurpation des en-têtes proxy (`X-Forwarded-For`, `CF-Connecting-IP`, etc.) — ces en-têtes ne sont désormais pris en compte que si `REMOTE_ADDR` figure dans la liste blanche `gf_french_schools_trusted_proxies` (filtre WordPress)
- **Sécurité :** L'endpoint AJAX de recherche vérifie maintenant que le formulaire soumis contient bien un champ `ecoles_fr`, empêchant l'utilisation scriptée de l'endpoint avec n'importe quel `form_id` valide

### 1.8.3 - 2026-02-23
- **Correction :** Avertissement WordPress 6.7 « `_load_textdomain_just_in_time` called incorrectly » : suppression de l'appel `__()` dans `get_default_inputs()` qui déclenchait le chargement des traductions lors de la construction du champ (avant `init`), via `gform_loaded` → `plugins_loaded`
- **Modification :** Le chargement du textdomain est désormais effectué uniquement sur le hook `init` (et non plus `plugins_loaded`) conformément aux recommandations WordPress 6.7+

### 1.8.2 - 2026-02-20
- **Correction :** L'enregistrement du champ « Email Circonscription » : la valeur était affichée côté frontend mais n'était pas sauvegardée dans l'entrée pour les formulaires créés avant l'ajout de ce sous-champ
- **Amélioration :** Le constructeur du champ synchronise désormais systématiquement les sous-inputs avec la définition canonique, garantissant la prise en charge des sous-champs ajoutés dans les nouvelles versions

### 1.8.1 - 2026-02-18
- **Correction :** Recherche de villes avec article : « les pa » trouve désormais « Les Pavillons Sous Bois », « les l » trouve « Les Lilas », etc.
- **Amélioration :** La recherche distante (API) et la base locale effectuent désormais une correspondance mot par mot : chaque mot saisi doit apparaître dans le nom de la ville
- **Nouveau :** Compatibilité gwcopycat (GP Copy Cat) : les sous-champs cachés déclenchent désormais un événement jQuery `change` lors de la sélection d'un établissement, permettant leur copie vers d'autres champs

### 1.8.0 - 2026-02-16
- **Nouveau :** Support complet de la logique conditionnelle : chaque sous-champ (identifiant, nom, type, catégorie, code postal, ville, etc.) est désormais disponible comme paramètre de logique conditionnelle pour les autres champs du formulaire
- **Amélioration :** Nettoyage de la catégorie : « ECOLE DE NIVEAU ELEMENTAIRE » affiche désormais « Élémentaire » au lieu de « De niveau elementaire »
- **Amélioration :** Détection automatique des écoles primaires : lorsque le nom de l'établissement contient « primaire », la catégorie affiche « Primaire » au lieu de « Élémentaire »
- **Amélioration :** Blocage de l'auto-complétion navigateur sur tous les champs du formulaire

### 1.7.3 - 2026-02-15
- **Modification :** Renommage des merge tags pour plus de cohérence avec l'interface : `id` → `identifiant`, `nature` → `categorie`, `commune` → `ville`
- **Amélioration :** Les anciens noms restent fonctionnels pour la rétro-compatibilité

### 1.7.2 - 2026-02-11
- **Nouveau :** Merge tag `autres_nom` pour accéder au nom d'école saisi manuellement
- **Amélioration :** Le merge tag est maintenant visible dans le dropdown des merge tags de Gravity Forms

### 1.7.1 - 2026-02-11
- **Correction :** Recherche d'écoles pour les arrondissements de Paris (espacement incohérent dans la base de données)
- **Amélioration :** Affichage des noms d'écoles parisiennes (suppression des préfixes E.M.PU, E.E.PU, E.P.PR, etc. et des adresses)
- **Modification :** Recherche API utilise LIKE au lieu de search() (meilleure correspondance partielle)
- **Correction :** Cache séparant les résultats locaux et distants

### 1.7.0 - 2026-02-11
- **Nouveau :** Support complet du thème Gravity Forms Orbital
- **Correction :** Affichage du dropdown autocomplete masqué par les champs suivants (z-index)
- **Correction :** Gestionnaire blur qui masquait les résultats trop rapidement
- **Correction :** Placeholder du département quand le statut est présélectionné
- **Modification :** Icône du champ changée pour correspondre à la page de réglages (dashicons-building)
- **Amélioration :** Masquage du bouton Annuler dans l'éditeur de formulaire

### 1.6.1 - 2026-02-10
- **Amélioration :** Amélioration majeure du nettoyage des noms d'établissements

### 1.6.0 - 2026-02-10
- **Amélioration :** Affichage des résultats dans la vue d'entrée (Entry View)
- **Correction :** Le champ « Éducation prioritaire » affiche maintenant « Non » au lieu d'un vide lorsqu'il n'est pas renseigné

### 1.5.4 - 2026-02-09
- **Correction :** Réécriture complète du système de chargement des traductions pour résoudre les problèmes de compatibilité
- **Amélioration :** Chargement par chemin absolu, filets de sécurité multi-hooks, rechargement forcé si la locale change, fallback sur la locale de base

### 1.5.3 - 2026-02-08
- **Amélioration :** Toggle au lieu d'une checkbox pour l'option « Local Only » (cohérence avec le style Gravity Forms)
- **Correction :** Style du bouton « Sync Now » corrigé pour correspondre aux autres boutons de réglages Gravity Forms

### 1.5.2 - 2026-02-07
- **Nouveau :** Icône Dashicon « building » pour l'onglet French Schools dans les réglages Gravity Forms

### 1.5.1 - 2026-02-07
- **Modification :** Page de réglages intégrée dans l'onglet Gravity Forms Settings (GFAddOn) au lieu d'un sous-menu séparé
- **Amélioration :** Utilisation du framework GFAddOn pour le rendu et la sauvegarde des paramètres (bouton Save natif)

### 1.5.0 - 2026-02-07
- **Nouveau :** Base de données locale comme filet de sécurité en cas d'indisponibilité de l'API
- **Nouveau :** Téléchargement mensuel automatique de l'annuaire complet (~69 000 établissements) via WP-Cron
- **Nouveau :** Import CSV sécurisé avec table de staging (les données existantes ne sont remplacées qu'après validation)
- **Nouveau :** Basculement automatique vers la base locale en cas d'erreur API
- **Nouveau :** Page de réglages sous **Formulaires → French Schools**
- **Nouveau :** Mode « Local Only » : possibilité de désactiver totalement l'API distante
- **Nouveau :** Bouton de synchronisation manuelle avec affichage du statut en temps réel

### 1.4.0 - 2026-01-27
- **Nouveau :** Champs « Circonscription » et « Mail circo » pour chaque établissement
- **Amélioration :** Nom de la circonscription nettoyé (suppression du préfixe « Circonscription d'inspection du 1er degré de/du/d' »)
- **Amélioration :** Mail de la circonscription généré automatiquement (code + domaine académique de l'école)
- **Nouveau :** Merge tags `nom_circonscription` et `code_circonscription`

### 1.3.0 - 2026-01-23
- **Nouveau :** Champ « Autre » permettant la saisie manuelle du nom de l'école si la recherche ne retourne aucun résultat
- **Amélioration :** Les entrées manuelles sont correctement enregistrées, affichées et exportées avec la mention « Saisie manuelle »
- **Amélioration :** Mise à jour des fichiers de traduction (français)

### 1.2.0 - 2026-01-18
- **Nouveau :** Intégration du support Guilamu Bug Reporter
- **Nouveau :** Lien « 🐛 Report a Bug » dans la liste des extensions

### 1.1.3 - 2026-01-06
- **Amélioration :** Traduction de la description du plugin en français
- **Nouveau :** Workflow GitHub Actions pour la création automatique des releases
- **Amélioration :** Génération automatique du fichier ZIP avec le bon nommage de dossier

### 1.1.1 - 2025-12-27
- **Nouveau :** Option pour cacher le bloc de récapitulatif et fallback accessible directement dans le champ (Type Catégorie Nom)
- **Nouveau :** Chaîne i18n pour le fallback « No » côté JS
- **Amélioration :** Nettoyage cohérent des valeurs (nom/catégorie) stockées et affichées

### 1.1.0 - 2025-12-27
- **Sécurité :** Validation du formulaire côté AJAX, whitelists statut/département, limite de requêtes filtrable
- **Amélioration :** Requêtes frontend plus robustes : annulation des appels en cours, déduplication, timeouts configurables et retries avec backoff
- **Amélioration :** Vérification de version minimale Gravity Forms et messages d'erreur plus clairs
- **Amélioration :** Updater GitHub plus résilient (fallback copy/delete, logs en debug)
- **Modification :** CSS admin extrait dans un fichier dédié (plus d'inline styles)

### 1.0.4 - 2025-12-26
- **Amélioration :** Le CSS pour les champs présélectionnés n'est plus chargé sur toutes les pages
- **Nouveau :** Limite de requêtes (rate limiting) sur l'endpoint AJAX
- **Amélioration :** Refactorisation du module de mise à jour GitHub avec mise en cache des requêtes API
- **Suppression :** Code mort (filtre de merge tag inutilisé)
- **Amélioration :** Contraste visuel entre les champs activés et désactivés
- **Amélioration :** Mise à jour des fichiers de traduction

### 1.0.3 - 2025-12-26
- Version initiale

## Contribuer

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request sur [GitHub](https://github.com/guilamu/gf-french-schools).

## Licence

Ce projet est sous licence **GNU Affero General Public License v3.0 (AGPL-3.0)** — voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Auteur

**Guilamu** - [GitHub](https://github.com/guilamu)

## Remerciements

- [Gravity Forms](https://www.gravityforms.com/) pour leur excellent framework de formulaires
- [data.education.gouv.fr](https://data.education.gouv.fr/) pour l'API de l'annuaire des établissements scolaires

---

<p align="center">
  Made with love for the WordPress community
</p>
