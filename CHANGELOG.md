# Changelog

All notable changes to this project will be documented in this file.

## [1.1.5] - 2025-10-14

- Scope: cette version fonctionne uniquement pour les pages de catégorie (archives de catégorie).

## [1.1.4] - 2025-10-13

- Fix counts on taxonomy archives and first-click issues
  - Archive scoping for REST: pass `archive_term_id` and `archive_taxonomy` so counts reflect current archive term.
  - Server-render initial counts in `src/taxonomy/render.php` (prevents Interactivity API from removing them on first update).
  - Front-end updates only the dedicated `<span class="term-count">` to avoid label resets and duplicate counts.
  - No more disappearing counts on the first click, no `(n) (n)` duplicates.

## [1.1.3] - 2025-10-02

- Enhancements to Taxonomy Filter block
  - New attributes in `src/taxonomy/block.json`:
    - `resetPosition`: position the reset button "before" or "after" the terms (default: `before`).
    - `hideZeroCountTerms`: hide unchecked terms that would return 0 results under current filters (default: `true`).
    - `showCounts`: display post counts next to each term (default: `false`).
  - Server render updates in `src/taxonomy/render.php`:
    - Output reset button according to `resetPosition`.
    - Expose `data-hide-zero-terms` and `data-show-counts` flags on the container.
    - Keep original term label in `data-name` to toggle counts cleanly.
  - Front-end logic in `src/taxonomy/view.js`:
    - Honors `hideZeroCountTerms` and `showCounts` when updating each term.
    - Initializes counts/visibility on first page load (no click required).
    - Listens for a custom `query-filter:refresh` event to recompute counts/visibility on-demand.

- Active Filters block improvements
  - Clear-all behavior:
    - `src/active-filters/render.php`: clear-all link now removes ALL query parameters for the current query prefix, including operator params (`-op`) and pagination.
    - `src/active-filters/view.js`: when clicking the chip `.is-clear-all`, uncheck all taxonomy checkboxes and dispatch `query-filter:refresh` before navigation so counters/visibility reset immediately.

- Fixes & robustness
  - Resolved JSX syntax issues in `src/taxonomy/edit.js` and added Inspector controls for the new options.
  - Corrected `showCounts` flag parsing and closed a missing brace in `src/taxonomy/view.js`.
  - Ensured initial rendering applies counts/visibility consistently.

- Maintenance
  - Bumped versions across plugin header, `composer.json`, `package.json`, and block metadata in `src/*/block.json` and `build/*/block.json`.

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
