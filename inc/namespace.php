<?php
/**
 * Query filter main file.
 *
 * @package query-loop-filter
 */
namespace up\query_loop_filter;

use WP_HTML_Tag_Processor;
use WP_Query;

/**
 *
 * @return void
 */
function bootstrap() : void {
	// General hooks.
	add_action( 'init', __NAMESPACE__ . '\load_textdomain' );
	add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\filter_query_loop_block_query_vars', 10, 3 );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\pre_get_posts_transpose_query_vars' );
	add_filter( 'block_type_metadata', __NAMESPACE__ . '\filter_block_type_metadata', 10 );
	add_action( 'init', __NAMESPACE__ . '\register_blocks' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\action_wp_enqueue_scripts' );

	// Search.
	add_filter( 'render_block_core/search', __NAMESPACE__ . '\\render_block_search', 10, 3 );

	// Query.
	add_filter( 'render_block_core/query', __NAMESPACE__ . '\\render_block_query', 10, 3 );

	// REST API.
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_routes' );
}

/**
 * Load translations.
 */
function load_textdomain() : void {
    load_plugin_textdomain(
        'up-gutenberg-query-filter',
        false,
        dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages'
    );
}

/**
 * Fires when scripts and styles are enqueued.
 *
 * @TODO work out why this doesn't work but building interactivity via the blocks does.
 */
function action_wp_enqueue_scripts() : void {
	$asset = include ROOT_DIR . '/build/taxonomy/index.asset.php';
	wp_register_style(
		'query-filter-view',
		plugins_url( '/build/taxonomy/index.css', PLUGIN_FILE ),
		[],
		$asset['version']
	);
}
/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 */
function register_blocks() : void {
    register_block_type( ROOT_DIR . '/build/taxonomy' );
    register_block_type( ROOT_DIR . '/build/post-type' );
    register_block_type( ROOT_DIR . '/build/active-filters' );
}

/**
 * Filters the arguments which will be passed to `WP_Query` for the Query Loop Block.
 *
{{ ... }}
 * @param array     $query Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 * @param \WP_Block $block Block instance.
 * @param int       $page  Current query's page.
 * @return array Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 */
function filter_query_loop_block_query_vars( array $query, \WP_Block $block, int $page ) : array {
    if ( isset( $block->context['queryId'] ) ) {
        $query['query_id'] = $block->context['queryId'];
    }

    return $query;
}

/**
 * Fires after the query variable object is created, but before the actual query is run.
 *
 * @param  WP_Query $query The WP_Query instance (passed by reference).
 */
function pre_get_posts_transpose_query_vars( WP_Query $query ) : void {
    $query_id = $query->get( 'query_id', null );
    if ( ! $query->is_main_query() && is_null( $query_id ) ) {
        return;
    }

    $prefix = $query->is_main_query() ? 'query-' : "query-{$query_id}-";
    $tax_query = [];
    $tax_operators = [];
    $valid_keys = [
        'post_type' => $query->is_search() ? 'any' : 'post',
        's' => '',
    ];

    // Preserve valid params for later retrieval.
    foreach ( $valid_keys as $key => $default ) {
        $query->set(
            "query-filter-$key",
            $query->get( $key, $default )
        );
    }
    foreach ( $_GET as $key => $value ) {
        if ( strpos( $key, $prefix ) !== 0 ) {
            continue;
        }

        $key = str_replace( $prefix, '', $key );
        $value = sanitize_text_field( urldecode( wp_unslash( $value ) ) );

        // Handle taxonomies specifically: capture explicit operator params in the form <taxonomy>-op.
        if ( substr( $key, -3 ) === '-op' ) {
            $tax_key = substr( $key, 0, -3 );
            if ( get_taxonomy( $tax_key ) ) {
                $op = strtoupper( $value );
                $tax_operators[ $tax_key ] = in_array( $op, [ 'IN', 'AND' ], true ) ? $op : 'IN';
            }
            continue;
        }

        if ( get_taxonomy( $key ) ) {
            $tax_query['relation'] = 'AND';
            $slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', (string) $value ) ) ) );
            if ( ! empty( $slugs ) ) {
                $tax_query[] = [
                    'taxonomy' => $key,
                    'terms'    => $slugs,
                    'field'    => 'slug',
                    'operator' => $tax_operators[ $key ] ?? 'IN',
                ];
            }
        } else {
            // Other options should map directly to query vars.
            if ( ! in_array( $key, array_keys( $valid_keys ), true ) ) {
                continue;
            }
            $query->set( $key, $value );
        }
    }

    // Default to current term on any taxonomy archive when no explicit filter for that taxonomy is present.
    if ( is_category() || is_tax() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) && isset( $term->taxonomy, $term->term_id ) ) {
            $taxonomy = $term->taxonomy;
            $has_filter_for_tax = false;
            if ( ! empty( $tax_query ) ) {
                foreach ( $tax_query as $clause ) {
                    if ( is_array( $clause ) && ( $clause['taxonomy'] ?? '' ) === $taxonomy ) {
                        $has_filter_for_tax = true;
                        break;
                    }
                }
            }
            if ( ! $has_filter_for_tax ) {
                $tax_query['relation'] = 'AND';
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'terms'    => [ (int) $term->term_id ],
                    'field'    => 'term_id',
                    'operator' => $tax_operators[ $taxonomy ] ?? 'IN',
                ];
            }
        }
    }

	if ( ! empty( $tax_query ) ) {
		$existing_query = $query->get( 'tax_query', [] );

		// Normalize existing tax_query into a flat list of clauses.
		$existing_clauses = [];
		if ( is_array( $existing_query ) && ! empty( $existing_query ) ) {
			if ( isset( $existing_query['relation'] ) ) {
				foreach ( $existing_query as $k => $v ) {
					if ( is_int( $k ) && is_array( $v ) ) {
						$existing_clauses[] = $v;
					}
				}
			} else {
				$existing_clauses = $existing_query;
			}
		}

		// Normalize new tax_query into a flat list of clauses.
		$new_clauses = [];
		if ( isset( $tax_query['relation'] ) ) {
			foreach ( $tax_query as $k => $v ) {
				if ( is_int( $k ) && is_array( $v ) ) {
					$new_clauses[] = $v;
				}
			}
		} else {
			$new_clauses = $tax_query;
		}

		$final_tax_query = [ 'relation' => 'AND' ];
		foreach ( array_merge( $existing_clauses, $new_clauses ) as $clause ) {
			$final_tax_query[] = $clause;
		}

		$query->set( 'tax_query', $final_tax_query );
	}
}

/**
 * Filters the settings determined from the block type metadata.
 *
 * @param array $metadata Metadata provided for registering a block type.
 * @return array Array of metadata for registering a block type.
 */
function filter_block_type_metadata( array $metadata ) : array {
	// Add query context to search block.
	if ( $metadata['name'] === 'core/search' ) {
		$metadata['usesContext'] = array_merge( $metadata['usesContext'] ?? [], [ 'queryId', 'query' ] );
	}

	return $metadata;
}

/**
 * Filters the content of a single block.
 *
 * @param string    $block_content The block content.
 * @param array     $block         The full block, including name and attributes.
 * @param \WP_Block $instance      The block instance.
 * @return string The block content.
 */
function render_block_search( string $block_content, array $block, \WP_Block $instance ) : string {
	if ( empty( $instance->context['query'] ) ) {
		return $block_content;
	}

	wp_enqueue_script_module( 'query-filter-taxonomy-view-script-module' );

	$query_var = empty( $instance->context['query']['inherit'] )
		? sprintf( 'query-%d-s', $instance->context['queryId'] ?? 0 )
		: 's';

	$action = str_replace( '/page/'. get_query_var( 'paged', 1 ), '', add_query_arg( [ $query_var => '' ] ) );

	// Note sanitize_text_field trims whitespace from start/end of string causing unexpected behaviour.
	$value = wp_unslash( $_GET[ $query_var ] ?? '' );
	$value = urldecode( $value );
	$value = wp_check_invalid_utf8( $value );
	$value = wp_pre_kses_less_than( $value );
	$value = strip_tags( $value );

	wp_interactivity_state( 'query-filter', [
		'searchValue' => $value,
	] );

	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag( [ 'tag_name' => 'form' ] );
	$block_content->set_attribute( 'action', $action );
	$block_content->set_attribute( 'data-wp-interactive', 'query-filter' );
	$block_content->set_attribute( 'data-wp-on--submit', 'actions.search' );
	$block_content->set_attribute( 'data-wp-context', '{searchValue:""}' );
	$block_content->next_tag( [ 'tag_name' => 'input', 'class_name' => 'wp-block-search__input' ] );
	$block_content->set_attribute( 'name', $query_var );
	$block_content->set_attribute( 'inputmode', 'search' );
	$block_content->set_attribute( 'value', $value );
	$block_content->set_attribute( 'data-wp-bind--value', 'state.searchValue' );
	$block_content->set_attribute( 'data-wp-on--input', 'actions.search' );

	return (string) $block_content;
}

/**
 * Add data attributes to the query block to describe the block query.
 *
 * @param string    $block_content Default query content.
 * @param array     $block         Parsed block.
 * @return string
 */
function render_block_query( $block_content, $block ) {
	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag();

	// Always allow region updates on interactivity, use standard core region naming.
	$block_content->set_attribute( 'data-wp-router-region', 'query-' . ( $block['attrs']['queryId'] ?? 0 ) );

	return (string) $block_content;
}

/**
 * Register REST API routes.
 *
 * @return void
 */
function register_rest_routes() : void {
	register_rest_route( 'query-filter/v1', '/available-terms', [
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\\get_available_terms',
		'permission_callback' => '__return_true',
		'args' => [
			'taxonomy' => [
				'required' => true,
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'query_id' => [
				'required' => false,
				'type' => 'integer',
				'default' => 0,
			],
			'post_type' => [
				'required' => false,
				'type' => 'string',
				'default' => 'post',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'use_archive' => [
				'required' => false,
				'type' => 'boolean',
				'default' => false,
			],
			'archive_term_id' => [
				'required' => false,
				'type' => 'integer',
				'default' => 0,
			],
			'archive_taxonomy' => [
				'required' => false,
				'type' => 'string',
				'default' => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'filters' => [
				'required' => false,
				'type' => 'string',
				'default' => '',
			],
		],
	] );
}

/**
 * Get available terms for a taxonomy based on current filters.
 *
 * @param \WP_REST_Request $request REST request.
 * @return \WP_REST_Response|\WP_Error
 */
function get_available_terms( \WP_REST_Request $request ) {
	$taxonomy = $request->get_param( 'taxonomy' );
	$query_id = $request->get_param( 'query_id' );
	$post_type = $request->get_param( 'post_type' );
	$filters_param = $request->get_param( 'filters' );
	$use_archive = (bool) $request->get_param( 'use_archive' );
	$archive_term_id = (int) $request->get_param( 'archive_term_id' );
	$archive_taxonomy = $request->get_param( 'archive_taxonomy' );

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new \WP_Error( 'invalid_taxonomy', 'Invalid taxonomy', [ 'status' => 400 ] );
	}

	// Parse filters from JSON string
	$filters = [];
	if ( ! empty( $filters_param ) ) {
		$filters = json_decode( $filters_param, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$filters = [];
		}
	}

	// Build tax_query from filters, excluding the current taxonomy
	$tax_query = [ 'relation' => 'AND' ];
	foreach ( $filters as $filter_taxonomy => $filter_data ) {
		if ( $filter_taxonomy === $taxonomy || empty( $filter_data['values'] ) ) {
			continue;
		}
		
		$slugs = array_filter( array_map( 'sanitize_title', (array) $filter_data['values'] ) );
		if ( ! empty( $slugs ) ) {
			$tax_query[] = [
				'taxonomy' => $filter_taxonomy,
				'terms'    => $slugs,
				'field'    => 'slug',
				'operator' => $filter_data['operator'] ?? 'IN',
			];
		}
	}

	// If requested and archive term info provided, scope to the archive term
	if ( $use_archive && $archive_term_id > 0 && ! empty( $archive_taxonomy ) && taxonomy_exists( $archive_taxonomy ) ) {
		// Only add if not already filtered by this taxonomy
		$has_filter_for_tax = false;
		foreach ( $tax_query as $clause ) {
			if ( is_array( $clause ) && ( $clause['taxonomy'] ?? '' ) === $archive_taxonomy ) {
				$has_filter_for_tax = true;
				break;
			}
		}
		if ( ! $has_filter_for_tax ) {
			$tax_query[] = [
				'taxonomy' => $archive_taxonomy,
				'terms'    => [ $archive_term_id ],
				'field'    => 'term_id',
				'operator' => 'IN',
			];
		}
	}

	// Query posts with current filters
	$query_args = [
		'post_type' => $post_type,
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => true,
	];

	if ( count( $tax_query ) > 1 ) {
		$query_args['tax_query'] = $tax_query;
	}

	$posts = get_posts( $query_args );

	// Get all terms for the requested taxonomy
	$all_terms = get_terms( [
		'taxonomy' => $taxonomy,
		'hide_empty' => false,
	] );

	if ( is_wp_error( $all_terms ) ) {
		return new \WP_Error( 'terms_error', 'Error retrieving terms', [ 'status' => 500 ] );
	}

	// Count posts for each term
	$available_terms = [];
	foreach ( $all_terms as $term ) {
		$term_posts = array_filter( $posts, function( $post_id ) use ( $term, $taxonomy ) {
			return has_term( $term->term_id, $taxonomy, $post_id );
		} );
		
		$available_terms[] = [
			'term_id' => $term->term_id,
			'slug' => $term->slug,
			'name' => $term->name,
			'count' => count( $term_posts ),
		];
	}

	return rest_ensure_response( [
		'taxonomy' => $taxonomy,
		'terms' => $available_terms,
	] );
}
