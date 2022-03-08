<?php
/***
 * Deleting the old research briefings posts from the posts (insights) table
 */

add_action( 'init', 'pds_delete_old_research_briefings_posts' );

function pds_delete_old_research_briefings_posts() {

    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => 'briefing-paper',
            ),
        ),
    );

    $query = new WP_Query( $args );

    if ($query->have_posts()) {
        while($query->have_posts()){

            $query->the_post();
            $id = get_the_ID();

            wp_delete_post( $id, true);
        }
    }

}
