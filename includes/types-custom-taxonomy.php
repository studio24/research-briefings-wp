<?php
/***
 * Creating the custom taxonomy "type" for research briefings
 */

add_action( 'init', 'pds_custom_taxonomy' );

function pds_custom_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Types', 'taxonomy general name', 'apollo'),
        'singular_name'              => _x( 'Type', 'taxonomy singular name', 'apollo'),
        'search_items'               => __( 'Search Types', 'apollo'),
        'popular_items'              => __( 'Popular Types', 'apollo'),
        'all_items'                  => __( 'All Types', 'apollo'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Type', 'apollo'),
        'update_item'                => __( 'Update Type', 'apollo'),
        'add_new_item'               => __( 'Add New Type', 'apollo'),
        'new_item_name'              => __( 'New Type Name', 'apollo'),
        'separate_items_with_commas' => __( 'Separate types with commas', 'apollo'),
        'add_or_remove_items'        => __( 'Add or remove types', 'apollo'),
        'choose_from_most_used'      => __( 'Choose from the most used types', 'apollo'),
        'not_found'                  => __( 'No types found.', 'apollo'),
        'menu_name'                  => __( 'Types', 'apollo'),
    );

    $args = array(
        'hierarchical'          => false,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'briefing-type' ),
    );

    register_taxonomy( 'rb_types', array('post', 'research-briefing'), $args );


}
