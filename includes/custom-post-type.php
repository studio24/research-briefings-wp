<?php
/***
 * Creating the custom post type for research briefings
 */

add_action( 'init', 'pds_custom_post_types' );

function pds_custom_post_types() {
    $labels = array(
        'name'               => _x( 'Research Briefings', 'post type general name', 'pds-custom-post-type' ),
        'singular_name'      => _x( 'Research Briefing', 'post type singular name', 'pds-custom-post-type' ),
        'menu_name'          => _x( 'Research Briefings', 'admin menu', 'pds-custom-post-type' ),
        'name_admin_bar'     => _x( 'Research Briefing', 'add new on admin bar', 'pds-custom-post-type' ),
        'add_new'            => _x( 'Add New', 'book', 'pds-custom-post-type' ),
        'add_new_item'       => __( 'Add New Research Briefing', 'pds-custom-post-type' ),
        'new_item'           => __( 'New Research Briefing', 'pds-custom-post-type' ),
        'edit_item'          => __( 'Edit Research Briefing', 'pds-custom-post-type' ),
        'view_item'          => __( 'View Research Briefing', 'pds-custom-post-type' ),
        'all_items'          => __( 'All Research Briefings', 'pds-custom-post-type' ),
        'search_items'       => __( 'Search Research Briefings', 'pds-custom-post-type' ),
        'parent_item_colon'  => __( 'Parent Research Briefings:', 'pds-custom-post-type' ),
        'not_found'          => __( 'No research briefings found.', 'pds-custom-post-type' ),
        'not_found_in_trash' => __( 'No research briefings found in Trash.', 'pds-custom-post-type' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Research briefings', 'pds-custom-post-type' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'research-briefings' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 10,
        'supports'           => array( 'title', 'editor', 'author', 'excerpt', 'thumbnail', 'custom-fields' ),
        'taxonomies'         => array( 'category', 'post_tag', 'type' )
    );

    register_post_type( 'research-briefing', $args );


}
