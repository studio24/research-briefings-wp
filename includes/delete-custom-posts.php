<?php
/***
 * Deleting all the custom research briefings posts from the posts table
 */

add_action( 'init', 'pds_delete_custom_research_briefings_posts' );

function pds_delete_custom_research_briefings_posts() {

    $args = array(
        'post_type' => 'research-briefing',
        'posts_per_page' => -1,
    );

    $rb_posts = get_posts( $args );

    foreach($rb_posts as $post) {
        wp_delete_post( $post->ID, true);
    }
}
