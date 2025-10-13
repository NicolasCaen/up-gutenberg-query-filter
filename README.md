# Query Loop Filter (Upcoder)

Plugin WordPress ajoutant des blocs de filtres pour le bloc « Boucle de requête » (Query Loop), avec l'API d'interactivité de Gutenberg.

- Auteur: Upcoder
- Version: 1.1.4
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
  - `showResetButton` (bool): afficher/masquer le bouton de réinitialisation "Tous" (défaut: true).
  - `operator` (string): `IN` (OU) ou `AND` (ET) pour la combinaison de termes.
  - `resetPosition` (string): position du bouton de réinitialisation, `before` (avant) ou `after` (après) la liste des termes. Défaut: `before`.
  - `hideZeroCountTerms` (bool): masque les termes non cochés qui auraient 0 résultat selon les filtres actifs. Défaut: `true`.
  - `showCounts` (bool): affiche le nombre d'éléments correspondant entre parenthèses à côté de chaque terme. Défaut: `false`.

- Paramètres d'URL générés: `query-<queryId>-<taxonomy>` avec les slugs séparés par virgule. Exemple: `?query-3-category=actu,evenements`.
  - Opérateur optionnel: `query-<queryId>-<taxonomy>-op=AND|IN`.

- Comportement:
  - Au chargement de la page, les compteurs sont calculés et les termes 0 sont masqués selon l'option, sans interaction requise.
  - Un évènement personnalisé `query-filter:refresh` peut être déclenché pour recalculer à la demande (ex: depuis le bloc Active Filters).

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
### 1.1.4 — 2025-10-13
- Correctifs d'affichage des compteurs sur archive et premier clic:
  - Passage explicite du terme d'archive via REST (`archive_term_id`, `archive_taxonomy`) pour des compteurs cohérents.
  - Rendu serveur des compteurs initiaux dans `src/taxonomy/render.php` (évite la suppression par l'Interactivity API et les doublons au premier clic).
  - Mise à jour front `src/taxonomy/view.js`: mise à jour du `<span class="term-count">` dédié, plus de modifications directes du label.
- Robustesse: pas de doublons de compteurs, pas de disparition au premier clic, comportement stable avec `hideZeroCountTerms` et `markZeroCountInactive`.

### 1.1.3 — 2025-10-02
- Améliorations du bloc Taxonomy:
  - Nouveaux attributs: `resetPosition`, `hideZeroCountTerms`, `showCounts`.
  - Rendu serveur: positionne le bouton de réinitialisation selon `resetPosition`, expose `data-hide-zero-terms` et `data-show-counts`, conserve le libellé original des termes via `data-name`.
  - Front: prise en charge des nouvelles options, calcul initial au chargement, écoute de l’évènement `query-filter:refresh`.
- Bloc Active Filters:
  - "Effacer tout" supprime tous les paramètres de la requête (y compris `-op`) et la pagination.
  - Au clic sur `.is-clear-all`, décoche tous les filtres et déclenche le recalcul immédiat avant la navigation.
- Correctifs/robustesse: corrections JSX dans `src/taxonomy/edit.js`, parsing `showCounts`, accolades manquantes, application cohérente des compteurs/masquages au premier rendu.

### 1.1.2 — 2025-10-01
- **Nouveau bloc**: `Active Filters` pour afficher les filtres actifs sous forme de chips avec croix pour retirer un filtre.
  - Option `showClearAll` et libellé `clearAllLabel`.
  - Navigation SPA via Interactivity Router.
  - Ajout de `data-activenumber` sur le wrapper pour refléter le nombre de filtres actifs et masquer "Effacer tout" si 0.

### 1.1.1 — 2025-10-01
- **Nouvelle fonctionnalité**: Masquage dynamique des termes avec 0 résultats lors de l'application de filtres multiples.
  - Ajout d'un endpoint REST API `/wp-json/query-filter/v1/available-terms` pour récupérer les termes disponibles selon les filtres actifs.
  - Mise à jour automatique de la visibilité des termes lors du changement de filtres.
  - Amélioration de l'UX avec des transitions CSS fluides.
  - Les termes cochés restent toujours visibles même s'ils ont 0 résultats.
- **Nouvelle option**: Ajout de l'option `showResetButton` pour afficher/masquer le bouton "Tous" dans l'interface d'administration et le front-end.

### 1.1.0 — 2025-09-30
- **Correctif**: fusion correcte des clauses `tax_query` (structure plate, plus de tableaux imbriqués) afin que les filtres s'appliquent de manière fiable.
- **Amélioration**: application par défaut du terme courant sur toute archive de taxonomie lorsqu'aucun filtre explicite pour cette taxonomie n'est passé.
  - Fonctionne pour `is_category()` et toute archive `is_tax()` (taxonomies personnalisées).
- **Compatibilité**: alignement des paramètres GET côté front avec les modes de la Boucle de requête:
  - Modèle hérité: `query-<taxonomy>` et `query-<taxonomy>-op`.
  - Boucle avec ID: `query-<id>-<taxonomy>` et `query-<id>-<taxonomy>-op`.
- **Maintenance**: mise à jour des versions vers `1.1.0` (entête du plugin, composer.json, metadata des blocs `src/*/block.json` et `build/*/block.json`).
### 1.0.0
- Première version stable.
- Harmonisation du namespace `up\query_loop_filter`, du text domain `up-gutenberg-query-filter` et des métadonnées (auteur: Upcoder).
- Bloc Taxonomy Filter et Post Type Filter.
