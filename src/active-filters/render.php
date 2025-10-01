<?php
// Server-rendered Active Filters block.

$show_clear = $attributes['showClearAll'] ?? true;
$clear_label = $attributes['clearAllLabel'] ?? 'Effacer tout';

// Determine query context vars similar to other blocks.
if ( empty( $block->context['query']['inherit'] ) ) {
	$query_id = $block->context['queryId'] ?? 0;
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
	$prefix_long   = 'query-' . ( $query_id ?: 0 ) . '-';
	$prefix_short  = 'q' . ( $query_id ?: 0 ) . '-';
	$base_url = remove_query_arg( [ $page_var ] );
} else {
	$query_id = null;
	$page_var = 'page';
	$prefix_long   = 'query-';
	$prefix_short  = 'q-';
	$base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $page_var ] ) );
}

// Collect active filters from $_GET based on our prefixes (short and long).
$chips = [];
$clear_params = [];
foreach ( $_GET as $key => $value ) {
    $is_long = strpos( $key, $prefix_long ) === 0;
    $is_short = strpos( $key, $prefix_short ) === 0;
    if ( ! $is_long && ! $is_short ) {
        continue;
    }
    // Derive taxonomy key and type.
    if ( $is_long ) {
        $tax_key = str_replace( $prefix_long, '', $key );
        $op_key  = $tax_key . '-op';
        // Ignore long-form operator keys here.
        if ( substr( $tax_key, -3 ) === '-op' ) {
            continue;
        }
    } else { // short form
        $tax_key = str_replace( $prefix_short, '', $key ); // e.g. category
        // Ignore short-form operator keys that start with 'op'
        // (operators are provided as a separate param, not prefixed with q)
        if ( strpos( $key, 'op' ) === 0 ) {
            continue;
        }
        // Compute matching operator key for clear-all removal.
        $suffix = $tax_key; // already without prefix
        $op_key = 'op' . substr( $prefix_short, 1 ) . $suffix; // e.g. op3-category or op-category
    }

    $raw = sanitize_text_field( urldecode( wp_unslash( $value ) ) );
    if ( $raw === '' ) {
        continue;
    }

    // Taxonomy chips.
    if ( get_taxonomy( $tax_key ) ) {
        $slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) ) );
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, $tax_key );
            $label = $term && ! is_wp_error( $term ) ? $term->name : $slug;

            // Build URL removing just this slug from the current key.
            $new_slugs = array_values( array_diff( $slugs, [ $slug ] ) );
            $args = [];
            if ( ! empty( $new_slugs ) ) {
                $args[ $key ] = implode( ',', $new_slugs );
            } else {
                $args[ $key ] = null; // remove the value param entirely
                $args[ $op_key ] = null; // also drop its operator if present
            }
            $args[ $page_var ] = null; // reset pagination
            $href = add_query_arg( array_filter( $args, function( $v ) { return ! is_null( $v ); } ), remove_query_arg( array_keys( array_filter( $args, function( $v ) { return is_null( $v ); } ) ) ) );

            $chips[] = [
                'type' => 'taxonomy',
                'taxonomy' => $tax_key,
                'value' => $slug,
                'label' => $label,
                'href' => esc_url( $href ),
            ];
        }

        // For clear-all we will remove this param entirely (and its operator counterpart).
        $clear_params[] = $key;
        $clear_params[] = $op_key;
        continue;
    }

    // Support search 's' and post_type.
    $short_key = $is_long ? str_replace( $prefix_long, '', $key ) : str_replace( $prefix_short, '', $key );
    if ( in_array( $short_key, [ 's', 'post_type' ], true ) ) {
        $label = $short_key === 's' ? sprintf( __( 'Recherche: %s', 'query-filter' ), esc_html( $raw ) ) : sprintf( __( 'Type: %s', 'query-filter' ), esc_html( $raw ) );
        $args = [ $key => null, $page_var => null ];
        $href = remove_query_arg( array_keys( $args ) );
        $chips[] = [
            'type' => $short_key,
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
