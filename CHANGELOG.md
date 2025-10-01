# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2025-10-01

- New: Added `controlType` option to both Taxonomy and Post Type blocks to choose the UI: `checkbox` (multi), `radio` (single), or `select` (single).
- New: Introduced short URL parameters for filters: `q...` for values and `op...` for combining terms (checkbox only). Backward compatible with legacy `query-...` and `...-op`.
- Improved: Single-select controls (radio/select) navigate via prebuilt URLs for a snappy UX; multi-select (checkbox) continues to use Interactivity API state updates.
- Improved: `post_type` now accepts comma-separated lists and `any`.

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
