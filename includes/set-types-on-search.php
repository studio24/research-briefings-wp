<?php

use ResearchBriefing\Repository\SearchRepository;


add_action( 'save_post_research-briefing', 'pds_set_types_on_search', 10, 2);

function pds_set_types_on_search($post_id, $post)
{
   if(!isset($_REQUEST['post_name'])) {

       return;
   }

    $searchRepository  = new SearchRepository();
    $search = $searchRepository->findOneById($post->post_name, 'briefing_id');

    // Type terms
    $typeTerms = get_the_terms($post_id, 'rb_types' );

    // Save the type terms whenever the post is updated
    $types = [];

    if (!empty($typeTerms) ) {
        foreach ($typeTerms as $term) {
            $types[] = trim(html_entity_decode(($term->name)));
        }
    }

    $search->setTypes(json_encode($types));
    $searchRepository->updateWhere('briefing_id', $post->post_name, 'types', $search->getTypes());

}
