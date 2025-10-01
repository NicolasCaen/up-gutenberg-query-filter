<?php
if ( empty( $attributes['taxonomy'] ) ) {
	return;
}
$id = 'query-filter-' . wp_generate_uuid4();

$taxonomy = get_taxonomy( $attributes['taxonomy'] );

if ( empty( $block->context['query']['inherit'] ) ) {
    $query_id = $block->context['queryId'] ?? 0;
    // Long-form (legacy) keys
    $query_var_long = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
    $op_var_long    = sprintf( 'query-%d-%s-op', $query_id, $attributes['taxonomy'] );
    $page_var       = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
    // Short keys (preferred): q<id>-<taxonomy> and op<id>-<taxonomy>
    $query_var = sprintf( 'q%d-%s', $query_id, $attributes['taxonomy'] );
    $op_var    = sprintf( 'op%d-%s', $query_id, $attributes['taxonomy'] );
    $base_url  = remove_query_arg( [ $query_var_long, $op_var_long, $query_var, $op_var, $page_var ] );
} else {
    // Long-form (legacy) keys
    $query_var_long = sprintf( 'query-%s', $attributes['taxonomy'] );
    $op_var_long    = sprintf( 'query-%s-op', $attributes['taxonomy'] );
    $page_var       = 'page';
    // Short keys (preferred): q-<taxonomy> and op-<taxonomy>
    $query_var = sprintf( 'q-%s', $attributes['taxonomy'] );
    $op_var    = sprintf( 'op-%s', $attributes['taxonomy'] );
    $base_url  = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var_long, $op_var_long, $query_var, $op_var, $page_var ] ) );
}

$terms = get_terms( [
    'hide_empty' => true,
    'taxonomy'   => $attributes['taxonomy'],
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
    return;
}
?>

<div 
    <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="{}"
    data-base-url="<?php echo esc_attr( $base_url ); ?>"
    data-query-var="<?php echo esc_attr( $query_var ); ?>"
    data-op-var="<?php echo esc_attr( $op_var ); ?>"
    data-page-var="<?php echo esc_attr( $page_var ); ?>"
    data-operator="<?php echo esc_attr( $attributes['operator'] ?? 'IN' ); ?>"
>
    <label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
        <?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
    </label>

    <div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
        <?php
        // Read current selection from short var first, then fallback to long var for backward compatibility.
        $raw = $_GET[ $query_var ] ?? ( $_GET[ $query_var_long ] ?? '' );
        $current = $raw !== '' ? array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', wp_unslash( $raw ) ) ) ) ) : [];
        ?>
        <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
            <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
        </button>
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
                <label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $term->name ); ?></label>
            </div>
        <?php endforeach; ?>
    </div>
</div>
