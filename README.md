# Query Loop Filter (Upcoder)

Plugin WordPress ajoutant des blocs de filtres pour le bloc « Boucle de requête » (Query Loop), avec l'API d'interactivité de Gutenberg.

- Auteur: Upcoder
- Version: 1.3.0
- Text Domain: `up-gutenberg-query-filter`
- Namespace PHP: `up\\query_loop_filter`

## Fonctionnalités
- Bloc Taxonomie: filtre les résultats par termes d'une taxonomie (ex: catégories, étiquettes, taxos personnalisées).
- Bloc Type de contenu: filtre les résultats par type de contenu (`post`, `page`, CPT…).
- Nouveaux types de contrôle: `checkbox` (multi), `radio` (mono) ou `select` (mono).
- Intégration avec les contextes `queryId`/`query` du bloc Query Loop.
- Mise à jour d'URL et rendu côté serveur pour préserver la pagination et le référencement.

## Installation
1. Copier le dossier du plugin dans `wp-content/plugins/up-gutenberg-query-filter/`.
2. Activer le plugin dans l'admin WordPress.
3. (Optionnel) Construire les assets si vous modifiez le code source (voir « Développement »).

## Utilisation
1. Insérer un bloc « Boucle de requête » (Query Loop) dans une page/modèle.
2. À l'intérieur de la boucle, insérer l'un des blocs fournis par ce plugin:
   - Taxonomy Filter (`query-filter/taxonomy`)
   - Post Type Filter (`query-filter/post-type`)
3. Configurer les options du bloc via la barre latérale (taxonomie ciblée, libellés, opérateur…).
4. Publier et tester les filtres depuis le front.

## Bloc et options
### 1) Taxonomy Filter
- Attributs principaux:
  - `taxonomy` (string): slug de la taxonomie ciblée (ex: `category`).
  - `controlType` (string): `checkbox` | `radio` | `select` (défaut: `checkbox`).
  - `emptyLabel` (string): libellé pour l'option « Tous ».
  - `label` (string): libellé affiché au-dessus du sélecteur.
  - `showLabel` (bool): afficher/masquer le libellé.
  - `operator` (string): `IN` (OU) ou `AND` (ET) pour la combinaison de termes.

- Paramètres d'URL courts générés:
  - Valeurs: `?q<id>-<taxonomy>=slug1,slug2` (dans une Boucle avec ID) ou `?q-<taxonomy>=...` (héritée).
  - Opérateur (uniquement `checkbox`): `?op<id>-<taxonomy>=IN|AND` ou `?op-<taxonomy>=...` (héritée).
  - Compatibilité: les anciens paramètres `query-...` et `...-op` sont toujours lus côté serveur.

### 2) Post Type Filter
- Attributs principaux:
  - `controlType` (string): `select` | `radio` | `checkbox` (défaut: `select`).
  - `label`, `emptyLabel`, `showLabel`.

- Paramètres d'URL courts générés:
  - Valeurs: `?q<id>-post_type=post` (mono: select/radio) ou `?q<id>-post_type=post,page` (multi: checkbox).
  - Modèle hérité: `?q-post_type=...`.
  - Compatibilité: l'ancien `query-<id>-post_type=...` est toujours lu.

## Champs contextuels ajoutés
Le plugin enrichit certains blocs (ex: `core/search`) avec les contextes `queryId` et `query` pour synchroniser les filtres et la recherche.

## Développement
- Dépendances JS: `@wordpress/scripts`.
- Commandes:
  - `npm install`
  - `npm run start` (développement)
  - `npm run build` (production)

Les sources sont dans `src/` et sont copiées/minifiées dans `build/` lors du build. Après modification des fichiers `src/*`, exécuter `npm run build` pour régénérer `build/*`.

## Compatibilité
- WordPress ≥ 6.6
- PHP ≥ 8.0

## Journal des modifications
### 1.3.0 — 2025-10-01
- **Nouveau**: Bloc `Active Filters` affichant les filtres actifs en chips avec croix et lien « Effacer tout » optionnel.
- **Compatibilité**: prise en charge des paramètres courts `q...`/`op...` et des clés legacy côté bloc Active Filters.
- **UX**: attribut `data-activenumber` sur le wrapper (cache « Effacer tout » si 0).
- **Stabilisation**: enregistrement du bloc côté PHP et build des assets.

### 1.2.0 — 2025-10-01
- **Nouveau**: option de rendu `controlType` pour les blocs Taxonomie et Type de contenu (`checkbox`/`radio`/`select`).
- **Nouveau**: support des paramètres d'URL courts `q...` et `op...` (lecture rétrocompatible des `query-...`).
- **Amélioration**: navigation mono-sélection (radio/select) via URLs préconstruites; multi-sélection (checkbox) via Interactivity API.
- **Amélioration**: `post_type` accepte plusieurs valeurs (CSV) et `any`.

### 1.1.0 — 2025-09-30
- **Correctif**: fusion correcte des clauses `tax_query` (structure plate, plus de tableaux imbriqués) afin que les filtres s'appliquent de manière fiable.
- **Amélioration**: application par défaut du terme courant sur toute archive de taxonomie lorsqu’aucun filtre explicite pour cette taxonomie n’est passé.
  - Fonctionne pour `is_category()` et toute archive `is_tax()` (taxonomies personnalisées).
- **Compatibilité**: alignement des paramètres GET côté front avec les modes de la Boucle de requête:
  - Modèle hérité: `query-<taxonomy>` et `query-<taxonomy>-op`.
  - Boucle avec ID: `query-<id>-<taxonomy>` et `query-<id>-<taxonomy>-op`.
- **Maintenance**: mise à jour des versions vers `1.1.0` (entête du plugin, composer.json, metadata des blocs `src/*/block.json` et `build/*/block.json`).

### 1.0.0
- Première version stable.
- Harmonisation du namespace `up\query_loop_filter`, du text domain `up-gutenberg-query-filter` et des métadonnées (auteur: Upcoder).
- Bloc Taxonomy Filter et Post Type Filter.
