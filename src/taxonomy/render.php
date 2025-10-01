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

<?php $control_type = $attributes['controlType'] ?? 'checkbox'; ?>

<div 
    <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="{}"
    data-base-url="<?php echo esc_attr( $base_url ); ?>"
    data-query-var="<?php echo esc_attr( $query_var ); ?>"
    data-page-var="<?php echo esc_attr( $page_var ); ?>"
    data-operator="<?php echo esc_attr( $attributes['operator'] ?? 'IN' ); ?>"
    <?php if ( in_array( $control_type, [ 'checkbox', 'tag-buttons', 'search-multi' ], true ) ) : ?> data-op-var="<?php echo esc_attr( $op_var ); ?>"<?php endif; ?>
    data-hide-zero="<?php echo ! empty( $attributes['hideZeroResults'] ) ? '1' : '0'; ?>"
>
    <label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
        <?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
    </label>
    <?php
        // Read current selection from short var first, then fallback to long var for backward compatibility.
        $raw = $_GET[ $query_var ] ?? ( $_GET[ $query_var_long ] ?? '' );
        $current = $raw !== '' ? array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', wp_unslash( $raw ) ) ) ) ) : [];
    ?>

    <?php
    // If requested, compute visible terms based on other active filters only.
    $visible_terms = null;
    if ( ! empty( $attributes['hideZeroResults'] ) ) {
        // Build filters from $_GET excluding current taxonomy; support short and long param names.
        $tax_query = [];
        $tax_query['relation'] = 'AND';
        $tax_operators = [];
        foreach ( $_GET as $gk => $gv ) {
            $is_long = strpos( $gk, $query_id !== null ? ( 'query-' . (int) $query_id . '-' ) : 'query-' ) === 0;
            $is_short = strpos( $gk, $query_id !== null ? ( 'q' . (int) $query_id . '-' ) : 'q-' ) === 0;
            if ( ! $is_long && ! $is_short ) {
                continue;
            }
            // Determine taxonomy key for this param.
            $key_no_prefix = $is_long
                ? str_replace( $query_id !== null ? ( 'query-' . (int) $query_id . '-' ) : 'query-', '', $gk )
                : str_replace( $query_id !== null ? ( 'q' . (int) $query_id . '-' ) : 'q-', '', $gk );

            // Skip operators here; capture operators mapping separately.
            if ( substr( $key_no_prefix, -3 ) === '-op' ) {
                $tax = substr( $key_no_prefix, 0, -3 );
                if ( $tax && get_taxonomy( $tax ) ) {
                    $op = strtoupper( sanitize_text_field( wp_unslash( $gv ) ) );
                    $tax_operators[ $tax ] = in_array( $op, [ 'IN', 'AND' ], true ) ? $op : 'IN';
                }
                continue;
            }

            // Only process taxonomies; ignore current taxonomy.
            if ( $key_no_prefix && $key_no_prefix !== $attributes['taxonomy'] && get_taxonomy( $key_no_prefix ) ) {
                $raw = sanitize_text_field( urldecode( wp_unslash( $gv ) ) );
                if ( $raw === '' ) {
                    continue;
                }
                $slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', (string) $raw ) ) ) );
                if ( ! empty( $slugs ) ) {
                    $tax_query[] = [
                        'taxonomy' => $key_no_prefix,
                        'terms'    => $slugs,
                        'field'    => 'slug',
                        'operator' => $tax_operators[ $key_no_prefix ] ?? 'IN',
                    ];
                }
            }
        }

        // Determine search and post_type context.
        $post_type = $block->context['query']['postType'] ?? 'post';
        if ( is_string( $post_type ) && strpos( $post_type, ',' ) !== false ) {
            $post_type = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $post_type ) ) ) );
        }
        $search_param = empty( $block->context['query']['inherit'] ) ? ( $_GET[ sprintf( 'query-%d-s', $query_id ?? 0 ) ] ?? '' ) : ( $_GET['s'] ?? '' );
        $search_param = sanitize_text_field( wp_unslash( $search_param ) );

        // Query posts matching other filters.
        $args = [
            'post_type' => $post_type ?: 'post',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => true,
        ];
        if ( ! empty( $tax_query ) && count( $tax_query ) > 1 ) {
            $args['tax_query'] = $tax_query;
        }
        if ( $search_param !== '' ) {
            $args['s'] = $search_param;
        }
        $posts = get_posts( $args );

        // Compute visible terms: selected terms are always visible; otherwise check if any post has the term.
        $current_slugs = is_array( $current ) ? $current : [];
        $visible_terms = [];
        foreach ( $terms as $t ) {
            if ( in_array( $t->slug, $current_slugs, true ) ) {
                $visible_terms[ $t->slug ] = true;
                continue;
            }
            $has_any = false;
            if ( ! empty( $posts ) ) {
                foreach ( $posts as $pid ) {
                    if ( has_term( (int) $t->term_id, $attributes['taxonomy'], $pid ) ) {
                        $has_any = true;
                        break;
                    }
                }
            }
            if ( $has_any ) {
                $visible_terms[ $t->slug ] = true;
            }
        }
    }
    ?>

    <?php if ( $control_type === 'select' ) : ?>
        <select
            class="wp-block-query-filter-taxonomy__select wp-block-query-filter__select"
            id="<?php echo esc_attr( $id ); ?>"
            data-wp-on--change="actions.navigate"
        >
            <?php if ( ! empty( $attributes['showAllButton'] ) ) : ?>
                <option value="<?php echo esc_attr( $base_url ); ?>"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?></option>
            <?php endif; ?>
            <?php foreach ( $terms as $term ) :
                if ( is_array( $visible_terms ) && empty( $visible_terms[ $term->slug ] ) ) { continue; }
                $url = add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url );
            ?>
                <option value="<?php echo esc_attr( $url ); ?>" <?php selected( in_array( $term->slug, $current, true ) ); ?>>
                    <?php echo esc_html( $term->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php elseif ( $control_type === 'radio' ) : ?>
        <div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
            <?php if ( ! empty( $attributes['showAllButton'] ) ) : ?>
                <button type="button" class="wp-block-query-filter__reset" value="<?php echo esc_attr( $base_url ); ?>" data-wp-on--click="actions.navigate">
                    <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
                </button>
            <?php endif; ?>
            <?php foreach ( $terms as $term ) :
                if ( is_array( $visible_terms ) && empty( $visible_terms[ $term->slug ] ) ) { continue; }
                $input_id = $id . '-' . $term->term_id;
                $url = add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url );
                $checked = in_array( $term->slug, $current, true );
            ?>
                <div class="wp-block-query-filter__term">
                    <input
                        type="radio"
                        class="wp-block-query-filter__radio"
                        id="<?php echo esc_attr( $input_id ); ?>"
                        name="<?php echo esc_attr( $id ); ?>"
                        value="<?php echo esc_attr( $url ); ?>"
                        data-wp-on--change="actions.navigate"
                        <?php checked( $checked ); ?>
                    />
                    <label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $term->name ); ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ( $control_type === 'tag-buttons' ) : ?>
        <div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
            <?php if ( ! empty( $attributes['showAllButton'] ) ) : ?>
                <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
                    <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
                </button>
            <?php endif; ?>
            <?php foreach ( $terms as $term ) :
                if ( is_array( $visible_terms ) && empty( $visible_terms[ $term->slug ] ) ) { continue; }
                $is_active = in_array( $term->slug, $current, true );
                $bem_base = 'tag-btn__' . $attributes['taxonomy'];
                $classes = $bem_base . ' ' . $bem_base . '--' . $term->slug . ( $is_active ? ' is-active' : '' );
            ?>
                <button
                    type="button"
                    class="<?php echo esc_attr( $classes ); ?>"
                    data-term-value="<?php echo esc_attr( $term->slug ); ?>"
                    data-wp-on--click="actions.toggleTagButton"
                ><?php echo esc_html( $term->name ); ?></button>
            <?php endforeach; ?>
        </div>
    <?php elseif ( $control_type === 'search-multi' ) : ?>
        <?php
        $terms_payload = array_values( array_map( static function ( $t ) {
            return [ 'slug' => $t->slug, 'name' => $t->name ];
        }, array_filter( $terms, static function ( $t ) use ( $visible_terms ) { return ! is_array( $visible_terms ) || ! empty( $visible_terms[ $t->slug ] ); } ) ) );
        ?>
        <div class="wp-block-query-filter__typeahead" id="<?php echo esc_attr( $id ); ?>"
            data-terms='<?php echo wp_json_encode( $terms_payload ); ?>'>
            <div class="typeahead__tokens">
                <?php foreach ( $current as $slug ) :
                    $term = get_term_by( 'slug', $slug, $attributes['taxonomy'] );
                    if ( $term && ! is_wp_error( $term ) ) :
                        $bem_base = 'tag-btn__' . $attributes['taxonomy'];
                        $classes = 'token ' . $bem_base . ' ' . $bem_base . '--' . $term->slug;
                ?>
                    <span class="<?php echo esc_attr( $classes ); ?>" data-term-value="<?php echo esc_attr( $term->slug ); ?>">
                        <?php echo esc_html( $term->name ); ?>
                        <button type="button" class="token__remove" aria-label="<?php esc_attr_e( 'Remove', 'query-filter' ); ?>" data-wp-on--click="actions.removeToken">×</button>
                    </span>
                <?php endif; endforeach; ?>
            </div>
            <input class="typeahead__input" type="search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search…', 'query-filter' ); ?>" data-wp-on--input="actions.typeahead" />
            <ul class="wp-block-query-filter__suggestions" role="listbox"></ul>
            <?php if ( ! empty( $attributes['showAllButton'] ) ) : ?>
                <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
                    <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php else : /* checkbox (default) */ ?>
        <?php // Emit op var only for checkbox (multi-select)
            echo '<div class="wp-block-query-filter__terms" id="' . esc_attr( $id ) . '" data-op-var="' . esc_attr( $op_var ) . '">';
        ?>
            <?php if ( ! empty( $attributes['showAllButton'] ) ) : ?>
                <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
                    <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
                </button>
            <?php endif; ?>
            <?php foreach ( $terms as $term ) :
                if ( is_array( $visible_terms ) && empty( $visible_terms[ $term->slug ] ) ) { continue; }
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
    <?php endif; ?>
</div>
