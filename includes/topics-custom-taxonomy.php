<?php
/***
 * Creating the custom taxonomy "rb_topics" for research briefings that will replace the "category" taxonomy
 */

add_action('init', 'topics_custom_taxonomy');

function topics_custom_taxonomy()
{
    $labels = array(
        'name'                       => _x('Topics', 'taxonomy general name', 'apollo'),
        'singular_name'              => _x('Topic', 'taxonomy singular name', 'apollo'),
        'search_items'               => __('Search Topics', 'apollo'),
        'popular_items'              => __('Popular Topics', 'apollo'),
        'all_items'                  => __('All Topics', 'apollo'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Topic', 'apollo'),
        'update_item'                => __('Update Topic', 'apollo'),
        'add_new_item'               => __('Add New Topic', 'apollo'),
        'new_item_name'              => __('New Topic Name', 'apollo'),
        'separate_items_with_commas' => __('Separate topics with commas', 'apollo'),
        'add_or_remove_items'        => __('Add or remove topics', 'apollo'),
        'choose_from_most_used'      => __('Choose from the most used topics', 'apollo'),
        'not_found'                  => __('No topics found.', 'apollo'),
        'menu_name'                  => __('Topics', 'apollo'),
    );

    $args = array(
        'hierarchical'          => true,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array(
            'slug' => 'topic',
            'hierarchical' => true
        ),
    );

    register_taxonomy('rb_topics', array('post', 'research-briefing'), $args);


}
