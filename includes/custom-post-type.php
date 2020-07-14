<?php
/***
 * Creating the custom post type for research briefings
 */

add_action( 'init', 'pds_custom_post_types' );

function pds_custom_post_types() {
    $labels = array(
        'name'               => _x( 'Research Briefings', 'post type general name', 'apollo' ),
        'singular_name'      => _x( 'Research Briefing', 'post type singular name', 'apollo' ),
        'menu_name'          => _x( 'Research Briefings', 'admin menu', 'apollo' ),
        'name_admin_bar'     => _x( 'Research Briefing', 'add new on admin bar', 'apollo' ),
        'add_new'            => _x( 'Add New', 'book', 'apollo' ),
        'add_new_item'       => __( 'Add New Research Briefing', 'apollo' ),
        'new_item'           => __( 'New Research Briefing', 'apollo' ),
        'edit_item'          => __( 'Edit Research Briefing', 'apollo' ),
        'view_item'          => __( 'View Research Briefing', 'apollo' ),
        'all_items'          => __( 'All Research Briefings', 'apollo' ),
        'search_items'       => __( 'Search Research Briefings', 'apollo' ),
        'parent_item_colon'  => __( 'Parent Research Briefings:', 'apollo' ),
        'not_found'          => __( 'No research briefings found.', 'apollo' ),
        'not_found_in_trash' => __( 'No research briefings found in Trash.', 'apollo' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Research briefings', 'apollo' ),
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
        'taxonomies'         => array( 'rb_topics', 'post_tag', 'rb_types' )
    );

    register_post_type( 'research-briefing', $args );


}
