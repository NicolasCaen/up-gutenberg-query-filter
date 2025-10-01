# Changelog

All notable changes to this project will be documented in this file.

## [1.1.2] - 2025-10-01

- New block: `Active Filters` to display active filters as chips with remove (×) and optional "Clear all".
  - Attributes: `showClearAll` (bool), `clearAllLabel` (string).
  - Adds `data-activenumber` on wrapper to expose number of active filters; hides Clear all when 0.
  - SPA navigation for chip/clear links via Interactivity Router.

## [1.1.1] - 2025-10-01

- **New Feature**: Dynamic hiding of terms with 0 results when applying multiple filters.
  - Added REST API endpoint `/wp-json/query-filter/v1/available-terms` to retrieve available terms based on active filters.
  - Automatic update of term visibility when filters change.
  - Smooth CSS transitions for better UX.
  - Checked terms remain visible even if they have 0 results.
  - Implementation in `inc/namespace.php` (REST API) and `src/taxonomy/view.js` (frontend logic).
- **New Option**: Added `showResetButton` attribute to show/hide the "All" reset button in both admin and frontend.
  - Configurable via the block settings panel.
  - Default value is `true` (button visible).

## [1.1.0] - 2025-09-30

- **Fixed**: Correct merging of `tax_query` clauses to avoid nested arrays and ensure filters apply reliably.
  - Implementation in `inc/namespace.php` within `pre_get_posts_transpose_query_vars()`.
- **Improved**: Generalized default behavior on taxonomy archives to automatically apply the current term when no explicit filter for that taxonomy is present.
  - Works for `is_category()` and any `is_tax()` archive (custom taxonomies).
- **Compatibility**: Ensured front-end GET parameter handling aligns with Query Loop modes:
  - Inherited template: `query-<taxonomy>` and optional `query-<taxonomy>-op`.
  - Query by ID: `query-<id>-<taxonomy>` and `query-<id>-<taxonomy>-op`.
- **Maintenance**: Bumped versions to `1.1.0`.
  - Plugin header: `up-gutenberg-query-filter.php`.
  - Composer package: `composer.json`.
  - Block metadata: `src/*/block.json` and `build/*/block.json`.

## [1.0.0] - 2025-09-01

- Initial release with taxonomy and post type filter blocks powered by the Interactivity API.
