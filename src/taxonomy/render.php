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

$terms = get_terms( [
	'hide_empty' => true,
	'taxonomy' => $attributes['taxonomy'],
	'number' => 100,
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

// New options with defaults.
$show_reset_button = $attributes['showResetButton'] ?? true;
$reset_position    = $attributes['resetPosition'] ?? 'before'; // 'before' or 'after'
$hide_zero_terms   = $attributes['hideZeroCountTerms'] ?? true;
$show_counts       = $attributes['showCounts'] ?? false;
?>

<div 
	<?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
	data-wp-interactive="query-filter"
	data-wp-context="{}"
	data-base-url="<?php echo esc_attr( $base_url ); ?>"
	data-query-var="<?php echo esc_attr( $query_var ); ?>"
	data-page-var="<?php echo esc_attr( $page_var ); ?>"
	data-operator="<?php echo esc_attr( $attributes['operator'] ?? 'IN' ); ?>"
	data-taxonomy="<?php echo esc_attr( $attributes['taxonomy'] ); ?>"
	data-query-id="<?php echo esc_attr( $block->context['queryId'] ?? 0 ); ?>"
	data-post-type="<?php echo esc_attr( $block->context['query']['postType'] ?? 'post' ); ?>"
	data-hide-zero-terms="<?php echo $hide_zero_terms ? 'true' : 'false'; ?>"
	data-show-counts="<?php echo $show_counts ? 'true' : 'false'; ?>"
>
	<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
	</label>

	<div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
		<?php
		$current = isset( $_GET[ $query_var ] ) ? array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', wp_unslash( $_GET[ $query_var ] ) ) ) ) ) : [];
		?>
		<?php if ( $show_reset_button && $reset_position === 'before' ) : ?>
			<button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
				<?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'up-gutenberg-query-filter' ) ); ?>
			</button>
		<?php endif; ?>
		<?php foreach ( $terms as $term ) :
			$input_id = $id . '-' . $term->term_id;
			$checked = in_array( $term->slug, $current, true );
			?>
			<div class="wp-block-query-filter__term">
				<input 
					type="checkbox"
					class="wp-block-query-filter__checkbox"
					id="<?php echo esc_attr( $input_id ); ?>"
					value="<?php echo esc_attr( $term->slug ); ?>"
					data-wp-on--change="actions.toggleTerm"
					<?php checked( $checked ); ?>
				/>
				<label for="<?php echo esc_attr( $input_id ); ?>" data-name="<?php echo esc_attr( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></label>
			</div>
		<?php endforeach; ?>
		<?php if ( $show_reset_button && $reset_position === 'after' ) : ?>
			<button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
				<?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'up-gutenberg-query-filter' ) ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>
