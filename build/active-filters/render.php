<?php
// Server-rendered Active Filters block.

$show_clear = $attributes['showClearAll'] ?? true;
$clear_label = $attributes['clearAllLabel'] ?? 'Effacer tout';

// Determine query context vars similar to other blocks.
if ( empty( $block->context['query']['inherit'] ) ) {
	$query_id = $block->context['queryId'] ?? 0;
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
	$prefix   = 'query-' . ( $query_id ?: 0 ) . '-';
	$base_url = remove_query_arg( [ $page_var ] );
} else {
	$query_id = null;
	$page_var = 'page';
	$prefix   = 'query-';
	$base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $page_var ] ) );
}

// Collect active filters from $_GET based on our prefix.
$chips = [];
$clear_params = [];
foreach ( $_GET as $key => $value ) {
	if ( strpos( $key, $prefix ) !== 0 ) {
		continue;
	}
	$short = str_replace( $prefix, '', $key );

	// Taxonomy operator params are ignored here.
	if ( substr( $short, -3 ) === '-op' ) {
		continue;
	}

	$raw = sanitize_text_field( urldecode( wp_unslash( $value ) ) );
	if ( $raw === '' ) {
		continue;
	}

	// Taxonomy chips.
	if ( get_taxonomy( $short ) ) {
		$slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) ) );
		foreach ( $slugs as $slug ) {
			// Resolve name for display.
			$term = get_term_by( 'slug', $slug, $short );
			$label = $term && ! is_wp_error( $term ) ? $term->name : $slug;

			// Build URL with this single term removed from the list.
			$new_slugs = array_values( array_diff( $slugs, [ $slug ] ) );
			$args = [];
			if ( ! empty( $new_slugs ) ) {
				$args[ $key ] = implode( ',', $new_slugs );
			} else {
				// Removing the last slug removes the param entirely.
				$args[ $key ] = null;
			}
			$args[ $page_var ] = null; // reset pagination
			$href = add_query_arg( array_filter( $args, function( $v ) { return ! is_null( $v ); } ), remove_query_arg( array_keys( array_filter( $args, function( $v ) { return is_null( $v ); } ) ) ) );

			$chips[] = [
				'type' => 'taxonomy',
				'taxonomy' => $short,
				'value' => $slug,
				'label' => $label,
				'href' => esc_url( $href ),
			];
		}

		// For clear-all we will remove this param entirely.
		$clear_params[] = $key;
		continue;
	}

	// Support search 's' and post_type shortcuts for completeness.
	if ( in_array( $short, [ 's', 'post_type' ], true ) ) {
		$label = $short === 's' ? sprintf( __( 'Recherche: %s', 'query-filter' ), esc_html( $raw ) ) : sprintf( __( 'Type: %s', 'query-filter' ), esc_html( $raw ) );
		$args = [ $key => null, $page_var => null ];
		$href = remove_query_arg( array_keys( $args ) );
		$chips[] = [
			'type' => $short,
			'value' => $raw,
			'label' => $label,
			'href'  => esc_url( $href ),
		];
		$clear_params[] = $key;
	}
}

// Clear-all URL removes all recognized params and resets pagination.
$clear_href = remove_query_arg( array_merge( $clear_params, [ $page_var ] ) );
$active_count = count( $chips );

if ( empty( $chips ) && ! $show_clear ) {
	return; // Nothing to show.
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter-active-filters' ] ); ?> data-wp-interactive="query-filter" data-activenumber="<?php echo (int) $active_count; ?>">
	<div class="wp-block-query-filter-active-filters__chips">
		<?php foreach ( $chips as $chip ) : ?>
			<a class="wp-block-query-filter-chip" href="<?php echo $chip['href']; ?>" data-wp-on--click="actions.navigateHref">
				<span class="wp-block-query-filter-chip__text"><?php echo esc_html( $chip['label'] ); ?></span>
				<span class="wp-block-query-filter-chip__close" aria-hidden>×</span>
			</a>
		<?php endforeach; ?>
		<?php if ( $show_clear && $active_count > 0 ) : ?>
			<a class="wp-block-query-filter-chip is-clear-all" href="<?php echo esc_url( $clear_href ); ?>" data-wp-on--click="actions.navigateHref"><?php echo esc_html( $clear_label ); ?></a>
		<?php endif; ?>
	</div>
</div>
