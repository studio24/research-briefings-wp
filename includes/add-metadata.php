<?php

/**
 * Add meta tags and the canonical tag to the RB and Insight pages
 */
function pds_add_metadata() {

    global $post;

    if (is_singular('research-briefing') || is_single()) {

        $title = get_the_title($post->ID);
        $permalink = get_the_permalink($post->ID);
        $date = get_the_modified_date('d/m/Y H:i:s', $post->ID);
        $authors = wp_get_post_terms($post->ID, 'rb_authors', array('fields' => 'names'));
        $topics = json_decode(get_post_meta($post->ID, 'topics', true));
        $section = get_post_meta($post->ID, 'section', true);

        echo '<link rel="canonical" href="' . $permalink . '"/>
            <meta name="citation_title" content="' . $title . '" >';



        if (is_array($authors)) {
            foreach ($authors as $author) {
                echo '<meta name="citation_author" content="' . $author . '">';
            }
        }
        // if not Array, assume it's a string
        else {
	        echo '<meta name="citation_author" content="' . $authors . '">';
        }



        echo '<meta name="citation_online_date" content="' . $date . '">';



        if (is_array($topics)) {
            foreach ($topics as $topic) {
                echo '<meta name="citation_topic" content="' . $topic->prefLabel->_value . '">';
            }
        }
        // if not Array, assume it's a string
        else {
	        echo '<meta name="citation_author" content="' . $topics . '">';
        }

        $section_array = json_decode($section,true);

	    if (is_array($section_array)) {
		    foreach ($section_array as $section_item) {

			    echo '<meta name="citation_section" content="' . $section_item['prefLabel']['_value'] . '">';
		    }
	    }
	    // if not Array, assume it's a string
	    else {
		    echo '<meta name="citation_section" content="' . $section . '">';
	    }

    }
}

add_action( 'wp_head', 'pds_add_metadata' );

