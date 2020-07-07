<?php
/***
 * Creating the custom taxonomy "rb_topics" for research briefings that will replace the "category" taxonomy
 */

add_action('init', 'topics_custom_taxonomy');

function topics_custom_taxonomy()
{
    $labels = array(
        'name'                       => _x('Topics', 'taxonomy general name', 'topics-custom-taxonomy'),
        'singular_name'              => _x('Topic', 'taxonomy singular name', 'topics-custom-taxonomy'),
        'search_items'               => __('Search Topics', 'topics-custom-taxonomy'),
        'popular_items'              => __('Popular Topics', 'topics-custom-taxonomy'),
        'all_items'                  => __('All Topics', 'topics-custom-taxonomy'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Topic', 'topics-custom-taxonomy'),
        'update_item'                => __('Update Topic', 'topics-custom-taxonomy'),
        'add_new_item'               => __('Add New Topic', 'topics-custom-taxonomy'),
        'new_item_name'              => __('New Topic Name', 'topics-custom-taxonomy'),
        'separate_items_with_commas' => __('Separate topics with commas', 'topics-custom-taxonomy'),
        'add_or_remove_items'        => __('Add or remove topics', 'topics-custom-taxonomy'),
        'choose_from_most_used'      => __('Choose from the most used topics', 'topics-custom-taxonomy'),
        'not_found'                  => __('No topics found.', 'topics-custom-taxonomy'),
        'menu_name'                  => __('Topics', 'topics-custom-taxonomy'),
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
