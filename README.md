# Gravity Forms - French Schools

Extension Gravity Forms permettant aux utilisateurs de rechercher et sélectionner un établissement scolaire français via l'API officielle du Ministère de l'Éducation Nationale.

![Plugin Screenshot](https://github.com/guilamu/gf-french-schools/blob/main/screenshot.jpg)

## Description

Ce plugin ajoute un nouveau type de champ "Écoles françaises" à Gravity Forms. Il permet aux utilisateurs de :

- Sélectionner le statut de l'établissement (Public/Privé)
- Choisir un département français
- Rechercher une ville par auto-complétion
- Rechercher un établissement scolaire par auto-complétion
- Afficher les informations détaillées de l'établissement sélectionné

Les données proviennent de l'[Annuaire de l'Éducation Nationale](https://data.education.gouv.fr/explore/dataset/fr-en-annuaire-education/) via l'API OpenDataSoft.

## Prérequis

- WordPress 5.8 ou supérieur
- PHP 7.4 ou supérieur
- [Gravity Forms](https://www.gravityforms.com/) 2.5 ou supérieur

## Installation

1. Téléchargez le plugin depuis [GitHub Releases](https://github.com/guilamu/gf-french-schools/releases)
2. Uploadez le dossier `gf-french-schools` dans `/wp-content/plugins/`
3. Activez le plugin dans le menu "Extensions" de WordPress
4. Le nouveau type de champ "Écoles françaises" sera disponible dans l'éditeur de formulaires Gravity Forms

## Configuration

### Paramètres du champ

Dans l'éditeur de formulaire, le champ "Écoles françaises" propose les options suivantes :

#### Présélection
- **Statut présélectionné** : Définir Public ou Privé par défaut (le champ sera masqué)
- **Département présélectionné** : Définir un département par défaut (le champ sera masqué)

#### Filtres par type d'établissement
- **Masquer les écoles primaires** : Exclut les écoles maternelles et élémentaires des résultats
- **Masquer les collèges et lycées** : Exclut les établissements secondaires des résultats

### Page de réglages « Écoles françaises »

Accessible via **Formulaires → French Schools**, cette page permet de :

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

## Mises à jour automatiques

Le plugin supporte les mises à jour automatiques depuis GitHub. Lorsqu'une nouvelle version est publiée, WordPress vous proposera la mise à jour dans la page Extensions.

## Traduction

Le plugin est entièrement traduisible et inclut une traduction française complète.

## Structure du projet

```
.
├── .github
│   └── workflows
│       └── release.yml                  # GitHub Actions release workflow
├── gf-french-schools.php                # fichier principal du plugin
├── LICENSE                              # licence AGPL-3.0
├── README.md
├── uninstall.php                        # nettoyage lors de la désinstallation
├── assets
│   ├── css
│   │   ├── ecoles-fr-admin.css          # styles éditeur GF
│   │   └── ecoles-fr.css                # styles frontend + bloc résultat
│   └── js
│       ├── ecoles-fr-admin.js           # réglages custom dans l'éditeur GF
│       └── ecoles-fr-frontend.js        # logique cascade, autocomplétion, accessibilité
├── includes
│   ├── class-ecoles-api-service.php     # client OpenDataSoft + cache + fallback local
│   ├── class-ecoles-local-db.php        # base de données locale, import CSV, sync cron
│   ├── class-gf-field-ecoles-fr.php     # définition du champ GF, rendu, validation
│   ├── class-gf-french-schools-addon.php # page réglages GFAddOn (Local Only, Sync)
│   └── class-github-updater.php         # mise à jour automatique via GitHub
└── languages
    ├── gf-french-schools-fr_FR.mo       # binaire FR
    ├── gf-french-schools-fr_FR.po       # sources FR
    └── gf-french-schools.pot            # modèle de traduction
```

## API utilisée

- **Endpoint** : `https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-annuaire-education/records`
- **Documentation** : [OpenDataSoft API v2.1](https://help.opendatasoft.com/apis/ods-explore-v2/)
- Aucune authentification requise
- Gratuit et accessible publiquement

## Contribuer

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request sur [GitHub](https://github.com/guilamu/gf-french-schools).

## Licence

Ce projet est sous licence **GNU Affero General Public License v3.0 (AGPL-3.0)**.

Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Auteur

**Guilamu** - [GitHub](https://github.com/guilamu)

## Remerciements

- [Gravity Forms](https://www.gravityforms.com/) pour leur excellent framework de formulaires
- [data.education.gouv.fr](https://data.education.gouv.fr/) pour l'API de l'annuaire des établissements scolaires

## Change Log

### Version 1.9.0 - 2026-03-28
- Reconnaissance automatique des abréviations courantes dans les noms d'écoles : la recherche est désormais bidirectionnelle (ex : "St Exupéry" trouve "Saint-Exupéry" et inversement)
- Abréviations supportées : St/Saint, Ste/Sainte, Gén/Gal/Général, Cdt/Commandant, Lt/Lieutenant, Col/Colonel, Mal/Maréchal, Dr/Docteur, Pr/Professeur, Mgr/Monseigneur, Mme/Madame, Mlle/Mademoiselle, Pdt/Président, Cpt/Capitaine, Sgt/Sergent, Adj/Adjudant
- Fonctionne sur les 3 modes de recherche : API distante, base locale (LIKE) et recherche floue (Levenshtein)

### Version 1.8.9 - 2026-03-27
- Correction d'un problème d'écoles introuvables dans certaines communes : la recherche d'écoles utilise désormais le `code_commune` (code INSEE) au lieu du `nom_commune` pour filtrer les résultats, contournant les erreurs de `nom_commune` dans la base nationale (ex : écoles de Pierrefitte-sur-Seine listées sous « Saint-Denis »)
- Ajout de la colonne `code_commune` dans la base de données locale et l'export CSV synchronisé
- Rétro-compatibilité : si `code_commune` n'est pas disponible, le filtrage par `nom_commune` reste actif

### Version 1.8.8 - 2026-03-27
- Correction du bug de cache : les résultats vides de recherche d'écoles étaient mis en cache pendant 1 heure, provoquant des faux « aucun résultat » pour des écoles existantes
- Nouvelle option « Repli API » : lorsque le mode local seul est activé et qu'une recherche ne retourne aucun résultat, le plugin peut interroger silencieusement l'API distante en dernier recours (option désactivée par défaut, visible uniquement quand « Local Only » est actif)
- Recherche floue (fuzzy search) sur la base locale : tolère les fautes de frappe dans les noms de villes et d'écoles grâce à un algorithme de distance de Levenshtein (ex : « mobtreuil » → « Montreuil »). Activé automatiquement quand la recherche exacte ne retourne aucun résultat (minimum 3 caractères)
- Refactoring interne : extraction des appels API distants dans des méthodes privées `get_villes_from_api()` et `get_ecoles_from_api()`

### Version 1.8.7 - 2026-03-09
- Correction de l'avertissement WordPress 6.7+ « Translation loading triggered too early » : suppression de l'appel `__()` dans le filtre `cron_schedules` qui déclenchait le chargement des traductions avant `init`
- Correction de l'avertissement PHP « Undefined property: stdClass::$slug » sur la page update-core.php : ajout des champs obligatoires `id`, `slug`, `plugin` et `new_version` à l'objet de mise à jour retourné par le GitHub updater
- Centralisation des constantes `TESTED_WP`, `REQUIRES_PHP` et `PLUGIN_SLUG` dans la classe `GF_French_Schools_GitHub_Updater`

### Version 1.8.6 - 2026-03-03
- Ajout du numéro de département comme sous-champ (`{Libellé:ID.18}`) et merge tag (`{Libellé:ID:numero_departement}`), permettant de récupérer le code officiel du département (ex : « 93 » pour Seine-Saint-Denis, « 2A » pour Corse-du-Sud, « 974 » pour La Réunion)
- Ajout des merge tags `{Libellé:ID:statut}` et `{Libellé:ID:departement}` pour accéder au statut et au nom du département via modificateur
- Documentation complétée : tous les merge tags et sous-champs disponibles sont désormais listés dans le README

### Version 1.8.5 - 2026-03-02
- Correction du nettoyage des noms de circonscriptions : ajout du patron manquant « Circonscription d'inspection du 1er degré » (sans article de/du/d') qui laissait un résidu « d'inspection du 1er degré » dans le nom affiché

### Version 1.8.4 - 2026-02-25
- Sécurité : correction du contournement du rate-limiting par usurpation des en-têtes proxy (`X-Forwarded-For`, `CF-Connecting-IP`, etc.) — ces en-têtes ne sont désormais pris en compte que si `REMOTE_ADDR` figure dans la liste blanche `gf_french_schools_trusted_proxies` (filtre WordPress)
- Sécurité : l'endpoint AJAX de recherche vérifie maintenant que le formulaire soumis contient bien un champ `ecoles_fr`, empêchant l'utilisation scriptée de l'endpoint avec n'importe quel `form_id` valide

### Version 1.8.3 - 2026-02-23
- Correction de l'avertissement WordPress 6.7 « `_load_textdomain_just_in_time` called incorrectly » : suppression de l'appel `__()` dans `get_default_inputs()` qui déclenchait le chargement des traductions lors de la construction du champ (avant `init`), via `gform_loaded` → `plugins_loaded`
- Le chargement du textdomain est désormais effectué uniquement sur le hook `init` (et non plus `plugins_loaded`) conformément aux recommandations WordPress 6.7+

### Version 1.8.2 - 2026-02-20
- Correction de l'enregistrement du champ « Email Circonscription » : la valeur était affichée côté frontend mais n'était pas sauvegardée dans l'entrée pour les formulaires créés avant l'ajout de ce sous-champ
- Le constructeur du champ synchronise désormais systématiquement les sous-inputs avec la définition canonique, garantissant la prise en charge des sous-champs ajoutés dans les nouvelles versions

### Version 1.8.1 - 2026-02-18
- Correction de la recherche de villes avec article : "les pa" trouve désormais "Les Pavillons Sous Bois", "les l" trouve "Les Lilas", etc.
- La recherche distante (API) et la base locale effectuent désormais une correspondance mot par mot : chaque mot saisi doit apparaître dans le nom de la ville, au lieu d'un test de sous-chaîne continue qui échouait dès qu'un espace séparait deux mots.
- Compatibilité gwcopycat (GP Copy Cat) : les sous-champs cachés déclenchent désormais un événement jQuery `change` lors de la sélection d'un établissement, permettant leur copie vers d'autres champs.

### Version 1.8.0 - 2026-02-16
- Support complet de la logique conditionnelle : chaque sous-champ (identifiant, nom, type, catégorie, code postal, ville, etc.) est désormais disponible comme paramètre de logique conditionnelle pour les autres champs du formulaire
- Amélioration du nettoyage de la catégorie : "ECOLE DE NIVEAU ELEMENTAIRE" affiche désormais "Élémentaire" au lieu de "De niveau elementaire"
- Détection automatique des écoles primaires : lorsque le nom de l'établissement contient "primaire", la catégorie affiche "Primaire" au lieu de "Élémentaire"
- Blocage de l'auto-complétion navigateur sur tous les champs du formulaire

### Version 1.7.3 - 2026-02-15
- Renommage des merge tags pour plus de cohérence avec l'interface :
  - `id` -> `identifiant`
  - `nature` -> `categorie`
  - `commune` -> `ville`
- Les anciens noms restent fonctionnels pour la rétro-compatibilité.

### Version 1.7.2 - 2026-02-11
- Ajout du merge tag `autres_nom` pour accéder au nom d'école saisi manuellement ("School Name (Manual)" / "Nom de l'école (saisie manuelle)")
- Le merge tag est maintenant visible dans le dropdown des merge tags de Gravity Forms

### Version 1.7.1 - 2026-02-11
- Correction de la recherche d'écoles pour les arrondissements de Paris (espacement incohérent dans la base de données)
- Amélioration de l'affichage des noms d'écoles parisiennes (suppression des préfixes E.M.PU, E.E.PU, E.P.PR, etc. et des adresses)
- Modification de la recherche API pour utiliser LIKE au lieu de search() (meilleure correspondance partielle)
- Correction du cache pour séparer les résultats locaux et distants

### Version 1.7.0 - 2026-02-11
- Support complet du thème Gravity Forms Orbital
- Correction de l'affichage du dropdown autocomplete masqué par les champs suivants (z-index)
- Correction du gestionnaire blur qui masquait les résultats trop rapidement
- Correction du placeholder du département quand le statut est présélectionné
- Changement de l'icône du champ pour correspondre à la page de réglages (dashicons-building)
- Masquage du bouton Annuler dans l'éditeur de formulaire

### Version 1.6.1 - 2026-02-10
- Amélioration majeure du nettoyage des noms d'établissements

### Version 1.6.0 - 2026-02-10
- Amélioration de l'affichage des résultats dans la vue d'entrée (Entry View)
- Le champ "Éducation prioritaire" affiche maintenant "Non" au lieu d'un vide lorsqu'il n'est pas renseigné

### Version 1.5.4 - 2026-02-09
- Réécriture complète du système de chargement des traductions pour résoudre les problèmes de compatibilité :
  - Utilisation de chemins absolus avec `load_textdomain()` au lieu de `load_plugin_textdomain()`
  - Chargement sur plusieurs hooks (`plugins_loaded`, `init`, `gform_pre_render`) comme filets de sécurité
  - Support du rechargement forcé si la locale change entre les hooks
  - Fallback sur la locale de base (ex: 'fr' si 'fr_FR.mo' n'existe pas)

### Version 1.5.3 - 2026-02-08
- Utilisation d'un toggle au lieu d'une checkbox pour l'option "Local Only" (cohérence avec le style Gravity Forms)
- Style du bouton "Sync Now" corrigé pour correspondre aux autres boutons de réglages Gravity Forms

### Version 1.5.2 - 2026-02-07
- Ajout de l'icône Dashicon "building" pour l'onglet French Schools dans les réglages Gravity Forms

### Version 1.5.1 - 2026-02-07
- Intégration de la page de réglages dans l'onglet Gravity Forms Settings (GFAddOn) au lieu d'un sous-menu séparé
- Utilisation du framework GFAddOn pour le rendu et la sauvegarde des paramètres (bouton Save natif)

### Version 1.5.0 - 2026-02-07
- Ajout d'une base de données locale comme filet de sécurité en cas d'indisponibilité de l'API
- Téléchargement mensuel automatique de l'annuaire complet (~69 000 établissements) via WP-Cron
- Import CSV sécurisé avec table de staging (les données existantes ne sont remplacées qu'après validation)
- Basculement automatique vers la base locale en cas d'erreur API
- Nouvelle page de réglages sous Formulaires → French Schools
- Mode « Local Only » : possibilité de désactiver totalement l'API distante
- Bouton de synchronisation manuelle avec affichage du statut en temps réel

### Version 1.4.0 - 2026-01-27
- Ajout des champs "Circonscription" et "Mail circo" pour chaque établissement
- Le nom de la circonscription est nettoyé (suppression du préfixe "Circonscription d'inspection du 1er degré de/du/d'")
- Le mail de la circonscription est généré automatiquement (code + domaine académique de l'école)
- Nouveaux merge tags disponibles : `nom_circonscription` et `code_circonscription`

### Version 1.3.0 - 2026-01-23
- Ajout d'un champ "Autre" permettant la saisie manuelle du nom de l'école si la recherche ne retourne aucun résultat
- Le champ manuel apparaît uniquement après une recherche infructueuse
- Les entrées manuelles sont correctement enregistrées, affichées et exportées avec la mention "Saisie manuelle"
- Mise à jour des fichiers de traduction (français)

### Version 1.2.0 - 2026-01-18
- Intégration du support Guilamu Bug Reporter
- Ajout du lien "🐛 Report a Bug" dans la liste des extensions

### Version 1.1.3 - 2026-01-06
- Traduction de la description du plugin en français
- Ajout du workflow GitHub Actions pour la création automatique des releases
- Génération automatique du fichier ZIP avec le bon nommage de dossier

### Version 1.1.1 - 2025-12-27
- Option pour cacher le bloc de récapitulatif et fallback accessible directement dans le champ (Type Catégorie Nom)
- Chaîne i18n pour le fallback "No" côté JS
- Nettoyage cohérent des valeurs (nom/catégorie) stockées et affichées

### Version 1.1.0 - 2025-12-27
- Durcissement de la sécurité : validation du formulaire côté AJAX, whitelists statut/département, limite de requêtes filtrable
- Requêtes frontend plus robustes : annulation des appels en cours, déduplication, timeouts configurables et retries avec backoff
- Vérification de version minimale Gravity Forms et messages d'erreur plus clairs
- Updater GitHub plus résilient (fallback copy/delete, logs en debug)
- CSS admin extrait dans un fichier dédié (plus d'inline styles)

### Version 1.0.4 - 2025-12-26
- Amélioration des performances : le CSS pour les champs présélectionnés n'est plus chargé sur toutes les pages
- Ajout d'une limite de requêtes (rate limiting) sur l'endpoint AJAX
- Refactorisation du module de mise à jour GitHub avec mise en cache des requêtes API
- Suppression du code mort (filtre de merge tag inutilisé)
- Amélioration du contraste visuel entre les champs activés et désactivés
- Mise à jour des fichiers de traduction

### Version 1.0.3 - 2025-12-26
- Version initiale

