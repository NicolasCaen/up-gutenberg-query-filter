# Changelog

All notable changes to this project will be documented in this file.

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
