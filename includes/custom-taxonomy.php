<?php
/***
 * Creating the custom taxonomy "type" for research briefings
 */

add_action( 'init', 'pds_custom_taxonomy' );

function pds_custom_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Types', 'taxonomy general name', 'pds-custom-taxonomy'),
        'singular_name'              => _x( 'Type', 'taxonomy singular name', 'pds-custom-taxonomy'),
        'search_items'               => __( 'Search Types', 'pds-custom-taxonomy'),
        'popular_items'              => __( 'Popular Types', 'pds-custom-taxonomy'),
        'all_items'                  => __( 'All Types', 'pds-custom-taxonomy'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Type', 'pds-custom-taxonomy'),
        'update_item'                => __( 'Update Type', 'pds-custom-taxonomy'),
        'add_new_item'               => __( 'Add New Type', 'pds-custom-taxonomy'),
        'new_item_name'              => __( 'New Type Name', 'pds-custom-taxonomy'),
        'separate_items_with_commas' => __( 'Separate types with commas', 'pds-custom-taxonomy'),
        'add_or_remove_items'        => __( 'Add or remove types', 'pds-custom-taxonomy'),
        'choose_from_most_used'      => __( 'Choose from the most used types', 'pds-custom-taxonomy'),
        'not_found'                  => __( 'No types found.', 'pds-custom-taxonomy'),
        'menu_name'                  => __( 'Types', 'pds-custom-taxonomy'),
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

    register_taxonomy( 'type', 'research-briefing', $args );


}
