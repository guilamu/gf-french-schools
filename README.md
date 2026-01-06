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
│   ├── class-ecoles-api-service.php   # client OpenDataSoft + cache
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

### Version 1.1.2 - 2026-01-06
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
