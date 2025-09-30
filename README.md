# Query Loop Filter (Upcoder)

Plugin WordPress ajoutant des blocs de filtres pour le bloc « Boucle de requête » (Query Loop), avec l'API d'interactivité de Gutenberg.

- Auteur: Upcoder
- Version: 1.0.0
- Text Domain: `up-gutenberg-query-filter`
- Namespace PHP: `up\query_loop_filter`

## Fonctionnalités
- Bloc Taxonomie: filtre les résultats par termes d'une taxonomie (ex: catégories, étiquettes, taxos personnalisées).
- Bloc Type de contenu: filtre les résultats par type de contenu (`post`, `page`, CPT…).
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
  - `emptyLabel` (string): libellé pour l'option « Tous ».
  - `label` (string): libellé affiché au-dessus du sélecteur.
  - `showLabel` (bool): afficher/masquer le libellé.
  - `operator` (string): `IN` (OU) ou `AND` (ET) pour la combinaison de termes.

- Paramètres d'URL générés: `query-<queryId>-<taxonomy>` avec les slugs séparés par virgule. Exemple: `?query-3-category=actu,evenements`.
  - Opérateur optionnel: `query-<queryId>-<taxonomy>-op=AND|IN`.

### 2) Post Type Filter
- Attributs principaux:
  - `label`, `emptyLabel`, `showLabel`.

- Paramètre d'URL généré: `query-<queryId>-post_type=post,page,portfolio`.

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
### 1.0.0
- Première version stable.
- Harmonisation du namespace `up\query_loop_filter`, du text domain `up-gutenberg-query-filter` et des métadonnées (auteur: Upcoder).
- Bloc Taxonomy Filter et Post Type Filter.
