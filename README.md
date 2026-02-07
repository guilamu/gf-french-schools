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

## Merge Tags

Accédez aux données de l'établissement dans les notifications et confirmations :

| Merge Tag | Description |
|-----------|-------------|
| `{Libellé:ID}` | Nom de l'établissement (par défaut) |
| `{Libellé:ID:id}` | Identifiant UAI |
| `{Libellé:ID:nom}` | Nom de l'établissement |
| `{Libellé:ID:type}` | Type d'établissement |
| `{Libellé:ID:nature}` | Catégorie |
| `{Libellé:ID:adresse}` | Adresse |
| `{Libellé:ID:code_postal}` | Code postal |
| `{Libellé:ID:commune}` | Ville |
| `{Libellé:ID:telephone}` | Téléphone |
| `{Libellé:ID:mail}` | Email |
| `{Libellé:ID:education_prioritaire}` | Statut éducation prioritaire |
| `{Libellé:ID:nom_circonscription}` | Nom de la circonscription |
| `{Libellé:ID:code_circonscription}` | Email Circonscription |
| `{Libellé:ID:all}` | Toutes les informations |

Remplacez `Libellé` par le libellé de votre champ et `ID` par le numéro d'identifiant du champ.

## Mises à jour automatiques

Le plugin supporte les mises à jour automatiques depuis GitHub. Lorsqu'une nouvelle version est publiée, WordPress vous proposera la mise à jour dans la page Extensions.

## Traduction

Le plugin est entièrement traduisible et inclut une traduction française complète.

## Structure du projet

```
.
├── .github
│   └── workflows
│       └── release.yml                # GitHub Actions release workflow
├── gf-french-schools.php
├── README.md
├── assets
│   ├── css
│   │   ├── ecoles-fr-admin.css        # styles éditeur GF
│   │   └── ecoles-fr.css              # styles frontend + bloc résultat
│   └── js
│       ├── ecoles-fr-admin.js         # réglages custom dans l’éditeur GF
│       └── ecoles-fr-frontend.js      # logique cascade, autocomplétion, accessibilité
├── includes
│   ├── class-ecoles-api-service.php   # client OpenDataSoft + cache + fallback local
│   ├── class-ecoles-local-db.php      # base de données locale, import CSV, sync cron
│   ├── class-gf-field-ecoles-fr.php   # définition du champ GF, rendu, validation
│   └── class-github-updater.php       # mise à jour GitHub
└── languages
	├── gf-french-schools-fr_FR.mo     # binaire FR
	├── gf-french-schools-fr_FR.po     # sources FR
	└── gf-french-schools.pot          # modèle de traduction
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

```
Copyright (C) 2024 Guilamu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```

## Auteur

**Guilamu** - [GitHub](https://github.com/guilamu)

## Remerciements

- [Gravity Forms](https://www.gravityforms.com/) pour leur excellent framework de formulaires
- [data.education.gouv.fr](https://data.education.gouv.fr/) pour l'API de l'annuaire des établissements scolaires

## Change Log

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
