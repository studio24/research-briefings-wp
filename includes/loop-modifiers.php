<?php
add_action('pre_get_posts', 'hoc_pre_get_posts');

function hoc_pre_get_posts($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (is_category() || is_tag() || is_home() || is_feed()) {
        $query->set('post_type', array( 'post' , 'research-briefing' ));
    }
}
