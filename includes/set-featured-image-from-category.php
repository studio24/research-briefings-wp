<?php
/***
 * Iterate through
 */

add_action( 'init', 'pds_set_featured_images_from_category' );

function pds_set_featured_images_from_category() {

    $args = array(
        'post_type' => 'research-briefing',
        'posts_per_page' => 1000,
        'numberposts' => 1000
        
    );

    $query = new WP_Query( $args );

    $omitted = $updated = $noimage = 0;

    if ($query->have_posts()) {

        while($query->have_posts()){

            $query->the_post();

            $post = get_post(get_the_ID());

            if (has_post_thumbnail($post)) { 
                $omitted++;
                echo "Omitting {$post->ID} ({$post->post_title})<br>\n";
                continue;
            }

            $actualRbImage = null;

            $categories = wp_get_post_categories($post->ID);

            foreach ($categories as $category) {
                $imageId = get_term_meta($category, 'rb_image', true);
                if($imageId) { $actualRbImage = $imageId; }
            }

            if($actualRbImage) {
                
                echo "Updating {$post->ID} ({$post->post_title}) image to $actualRbImage<br>\n";
                $updated++;

                 set_post_thumbnail((int) $post->ID, (int) $actualRbImage);
            } else {
                $noimage++;
                echo "...<br>";
            }

        }
    }

    echo "<p>$updated posts updated<br>$omitted posts skipped<br>$noimage posts unable to find image</p>";

}
