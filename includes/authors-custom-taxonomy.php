<?php
/***
 * Creating the custom taxonomy "rb_authors" for research briefings
 */

add_action( 'init', 'author_custom_taxonomy' );

function author_custom_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Authors', 'taxonomy general name', 'author_custom_taxonomy'),
        'singular_name'              => _x( 'Author', 'taxonomy singular name', 'author_custom_taxonomy'),
        'search_items'               => __( 'Search Authors', 'author_custom_taxonomy'),
        'popular_items'              => __( 'Popular Authors', 'author_custom_taxonomy'),
        'all_items'                  => __( 'All Authors', 'author_custom_taxonomy'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Author', 'author_custom_taxonomy'),
        'update_item'                => __( 'Update Author', 'author_custom_taxonomy'),
        'add_new_item'               => __( 'Add New Author', 'author_custom_taxonomy'),
        'new_item_name'              => __( 'New Author Name', 'author_custom_taxonomy'),
        'separate_items_with_commas' => __( 'Separate authors with commas', 'author_custom_taxonomy'),
        'add_or_remove_items'        => __( 'Add or remove authors', 'author_custom_taxonomy'),
        'choose_from_most_used'      => __( 'Choose from the most used authors', 'author_custom_taxonomy'),
        'not_found'                  => __( 'No authors found.', 'author_custom_taxonomy'),
        'menu_name'                  => __( 'Authors', 'author_custom_taxonomy'),
    );

    $args = array(
        'hierarchical'          => false,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'author' ),
    );

    register_taxonomy( 'rb_authors', array('post', 'research-briefing'), $args );
}
