<?php
if ( empty( $attributes['taxonomy'] ) ) {
	return;
}
$id = 'query-filter-' . wp_generate_uuid4();

$taxonomy = get_taxonomy( $attributes['taxonomy'] );

if ( empty( $block->context['query']['inherit'] ) ) {
    $query_id = $block->context['queryId'] ?? 0;
    $query_var = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
    $page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
    $base_url = remove_query_arg( [ $query_var, $page_var ] );
} else {
    $query_var = sprintf( 'query-%s', $attributes['taxonomy'] );
    $page_var = 'page';
    $base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
}

// Determine if we should scope to current archive term on initial render
$use_archive_term = ! empty( $block->context['query']['inherit'] ) && ( is_category() || is_tax() );
$archive_term_id = 0;
$archive_taxonomy = '';
$object_ids = [];

if ( $use_archive_term ) {
    $queried = get_queried_object();
    if ( $queried && ! is_wp_error( $queried ) && isset( $queried->taxonomy, $queried->term_id ) ) {
        $archive_term_id = $queried->term_id;
        $archive_taxonomy = $queried->taxonomy;
        $object_ids = get_posts( [
            'post_type'              => $block->context['query']['postType'] ?? 'post',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [
                [
                    'taxonomy' => $queried->taxonomy,
                    'terms'    => [ (int) $queried->term_id ],
                    'field'    => 'term_id',
                    'operator' => 'IN',
                ],
            ],
        ] );
    }
} else {
    // Custom query (non-inherited): scope terms to the query's post type
    $post_type = $block->context['query']['postType'] ?? 'post';
    $object_ids = get_posts( [
        'post_type'              => $post_type,
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ] );
}

// Always scope terms to object_ids to avoid loading irrelevant terms
$terms = get_terms( [
    'taxonomy'   => $attributes['taxonomy'],
    'hide_empty' => false,
    'number'     => 100,
    'object_ids' => $object_ids,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}
// New options with defaults.
$show_reset_button = $attributes['showResetButton'] ?? true;
$reset_position    = $attributes['resetPosition'] ?? 'before'; // 'before' or 'after'
$hide_zero_terms       = $attributes['hideZeroCountTerms'] ?? true;
$mark_zero_inactive    = $attributes['markZeroCountInactive'] ?? false;
$show_counts           = $attributes['showCounts'] ?? false;

// Calculate initial counts if showCounts is enabled and we have object_ids
$term_counts = [];
if ( $show_counts && ! empty( $object_ids ) ) {
	foreach ( $terms as $term ) {
		$count = 0;
		foreach ( $object_ids as $post_id ) {
			if ( has_term( $term->term_id, $attributes['taxonomy'], $post_id ) ) {
				$count++;
			}
		}
		$term_counts[ $term->term_id ] = $count;
	}
}
?>

<div 
    <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="{}"
    data-base-url="<?php echo esc_url( $base_url ); ?>"
    data-query-var="<?php echo esc_attr( $query_var ); ?>"
    data-page-var="<?php echo esc_attr( $page_var ); ?>"
    data-operator="<?php echo esc_attr( $attributes['operator'] ?? 'IN' ); ?>"
    data-taxonomy="<?php echo esc_attr( $attributes['taxonomy'] ); ?>"
    data-query-id="<?php echo esc_attr( $block->context['queryId'] ?? 0 ); ?>"
    data-post-type="<?php echo esc_attr( $block->context['query']['postType'] ?? 'post' ); ?>"
    data-hide-zero-terms="<?php echo $hide_zero_terms ? 'true' : 'false'; ?>"
    data-mark-zero-inactive="<?php echo $mark_zero_inactive ? 'true' : 'false'; ?>"
    data-show-counts="<?php echo $show_counts ? 'true' : 'false'; ?>"
    data-use-archive-term="<?php echo $use_archive_term ? 'true' : 'false'; ?>"
    data-archive-term-id="<?php echo esc_attr( $archive_term_id ); ?>"
    data-archive-taxonomy="<?php echo esc_attr( $archive_taxonomy ); ?>"
>
	<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
	</label>

	<?php
		$current = isset( $_GET[ $query_var ] )
			? array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', wp_unslash( $_GET[ $query_var ] ) ) ) ) )
			: [];
	?>

	<?php if ( $show_reset_button && $reset_position === 'before' ) : ?>
		<button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
			<?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'up-gutenberg-query-filter' ) ); ?>
		</button>
	<?php endif; ?>

	<div class="wp-block-query-filter__terms">
	<?php
	$__seen_terms = [];
	foreach ( $terms as $term ) :
		if ( isset( $__seen_terms[ $term->term_id ] ) ) {
			continue;
		}
		$__seen_terms[ $term->term_id ] = true;
		$input_id = $id . '-' . $term->term_id;
		$checked = in_array( $term->slug, $current, true );
		$initial_count = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : '';
		$inactive_class = ( $initial_count === 0 && ! $checked ) ? ' inactive' : '';
	?>
		<div class="wp-block-query-filter__term<?php echo $inactive_class; ?>" data-term-slug="<?php echo esc_attr( $term->slug ); ?>"<?php if ( $initial_count !== '' ) echo ' data-count="' . esc_attr( $initial_count ) . '"'; ?>>
			<input
				type="checkbox"
				class="wp-block-query-filter__checkbox"
				id="<?php echo esc_attr( $input_id ); ?>"
				value="<?php echo esc_attr( $term->slug ); ?>"
				data-wp-on--change="actions.toggleTerm"
				<?php checked( $checked ); ?>
			/>
			<label for="<?php echo esc_attr( $input_id ); ?>">
				<span class="term-name"><?php echo esc_html( $term->name ); ?></span><span class="term-count"><?php 
					// Always output count if available, JS will update it dynamically
					if ( isset( $term_counts[ $term->term_id ] ) ) {
						echo $show_counts ? ' (' . $term_counts[ $term->term_id ] . ')' : '';
					}
				?></span>
			</label>
		</div>
	<?php endforeach; ?>
	</div>

	<?php if ( $show_reset_button && $reset_position === 'after' ) : ?>
		<button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
			<?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'up-gutenberg-query-filter' ) ); ?>
		</button>
	<?php endif; ?>

</div>
