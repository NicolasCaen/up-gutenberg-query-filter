<?php
global $wp_query;

$id = 'query-filter-' . wp_generate_uuid4();

if ( $block->context['query']['inherit'] ) {
    // Long-form and short-form keys
    $query_var_long = 'query-post_type';
    $query_var      = 'q-post_type';
    $page_var       = 'page';
    $base_url = str_replace(
        '/page/' . get_query_var( 'paged' ),
        '',
        remove_query_arg( [ $query_var_long, $query_var, $page_var ] )
    );
} else {
    $query_id       = $block->context['queryId'] ?? 0;
    $query_var_long = sprintf( 'query-%d-post_type', $query_id );
    $query_var      = sprintf( 'q%d-post_type', $query_id );
    $page_var       = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
    $base_url       = remove_query_arg( [ $query_var_long, $query_var, $page_var ] );
}

// Determine control type
$control_type = $attributes['controlType'] ?? 'select';

$post_types = array_map( 'trim', explode( ',', $block->context['query']['postType'] ?? 'post' ) );

// Support for enhanced query block.
if ( isset( $block->context['query']['multiple_posts'] ) && is_array( $block->context['query']['multiple_posts'] ) ) {
    $post_types = array_merge( $post_types, $block->context['query']['multiple_posts'] );
}

// Fill in inherited query types.
if ( $block->context['query']['inherit'] ) {
    $inherited_post_types = $wp_query->get( 'query-filter-post_type' ) === 'any'
        ? get_post_types( [ 'public' => true, 'exclude_from_search' => false ] )
        : (array) $wp_query->get( 'query-filter-post_type' );

    $post_types = array_merge( $post_types, $inherited_post_types );
    if ( ! get_option( 'wp_attachment_pages_enabled' ) ) {
        $post_types = array_diff( $post_types, [ 'attachment' ] );
    }
}

$post_types = array_unique( $post_types );
$post_types = array_map( 'get_post_type_object', $post_types );

if ( empty( $post_types ) ) {
    return;
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="{}"
    data-base-url="<?php echo esc_attr( $base_url ); ?>"
    data-query-var="<?php echo esc_attr( $query_var ); ?>"
    data-page-var="<?php echo esc_attr( $page_var ); ?>"
>
    <label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
        <?php echo esc_html( $attributes['label'] ?? __( 'Content Type', 'query-filter' ) ); ?>
    </label>
        <?php
    $raw = $_GET[ $query_var ] ?? ( $_GET[ $query_var_long ] ?? '' );
    $current = $raw !== '' ? array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', wp_unslash( $raw ) ) ) ) ) : [];
    ?>

    <?php if ( $control_type === 'select' ) : ?>
        <select class="wp-block-query-filter-post-type__select wp-block-query-filter__select" id="<?php echo esc_attr( $id ); ?>" data-wp-on--change="actions.navigate">
            <option value="<?php echo esc_attr( $base_url ) ?>"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?></option>
            <?php foreach ( $post_types as $post_type ) : ?>
                <option value="<?php echo esc_attr( add_query_arg( [ $query_var => $post_type->name, $page_var => false ], $base_url ) ) ?>" <?php selected( in_array( $post_type->name, $current, true ) ); ?>><?php echo esc_html( $post_type->label ); ?></option>
            <?php endforeach; ?>
        </select>
    <?php elseif ( $control_type === 'radio' ) : ?>
        <div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
            <button type="button" class="wp-block-query-filter__reset" value="<?php echo esc_attr( $base_url ); ?>" data-wp-on--click="actions.navigate">
                <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
            </button>
            <?php foreach ( $post_types as $post_type ) :
                $input_id = $id . '-' . $post_type->name;
                $url = add_query_arg( [ $query_var => $post_type->name, $page_var => false ], $base_url );
                $checked = in_array( $post_type->name, $current, true );
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
                    <label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $post_type->label ); ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : /* checkbox */ ?>
        <div class="wp-block-query-filter__terms" id="<?php echo esc_attr( $id ); ?>">
            <button type="button" class="wp-block-query-filter__reset" data-wp-on--click="actions.clearTerms">
                <?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?>
            </button>
            <?php foreach ( $post_types as $post_type ) :
                $input_id = $id . '-' . $post_type->name;
                $checked = in_array( $post_type->name, $current, true );
            ?>
                <div class="wp-block-query-filter__term">
                    <input 
                        type="checkbox"
                        class="wp-block-query-filter__checkbox"
                        id="<?php echo esc_attr( $input_id ); ?>"
                        value="<?php echo esc_attr( $post_type->name ); ?>"
                        data-wp-on--change="actions.toggleTerm"
                        <?php checked( $checked ); ?>
                    />
                    <label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $post_type->label ); ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
