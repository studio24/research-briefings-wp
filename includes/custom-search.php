<?php

use ResearchBriefing\Repository\SearchRepository;

/**
 * Creates a fake post array based on the briefing object and sets the correct properties
 * that will be used to display the proper content
 *
 * @param $briefing
 * @return array|bool|mixed
 */
function setPostProperties($briefing) {

   $postProperties = array(
       'ID'             => $briefing->getId(),
       'post_title'     => $briefing->getTitle(),
       'post_type'      => 'research-briefing',
       'post_excerpt'   => explode('|', $briefing->getContent())[1],
       'post_content'   => $briefing->getContent(),
       'post_name'      => $briefing->getUrl(),
       'post_parent'    => $briefing->getSite(),
       'guid'           => get_site_url($briefing->getSite()). '/'. $briefing->getUrl(),
       'post_status'    => 'publish',
       'post_date'      => $briefing->getDate(),
       'post_date_gmt'  => $briefing->getDate(),
       'comment_status' => 'closed',
       'thumbnail'      => $briefing->getThumbnailLink() ? get_site_url($briefing->getSite()). '/'. $briefing->getThumbnailLink() : '',
       'topics'         => json_decode($briefing->getCategories()),
       'authors'        => json_decode($briefing->getAuthors()),
       'site'           => $briefing->getSite(),
       'library'        => $briefing->getSite() === 1 ? 'House of Commons Library' :
                          ($briefing->getSite() === 2 ? 'House of Lords Library' : 'POST'),
       'filter'         => 'raw',
       'types'           => json_decode($briefing->getTypes())
);
    $urlParts = parse_url($postProperties['guid']);
    $postProperties['library_url'] = $urlParts['scheme'] . '://' . $urlParts['host'] . '/';

    return $postProperties;
}

/**
 * Return class name to help style library search results
 *
 * @param $siteId
 * @return string
 */
function parliament_library_classname($siteId)
{
    switch ($siteId) {
        case 1:
            // HOC
            return 'commonslibrary';
        case 2:
            // HOL
            return 'lordslibrary';
        case 3:
            // POST
            return 'post';
        default:
            return '';
    }
}

function add_custom_query_variables_to_wp_query( $qvars ) {
	$qvars[] = 'library';
	return $qvars;
}
add_filter( 'query_vars', 'add_custom_query_variables_to_wp_query' );

/**
 * Hook that overwrites the default WP search and
 * replaces the results with the ones from our custom search queries
 *
 */
add_action('template_redirect', 'pds_modify_posts_list');

function pds_modify_posts_list() {
    if (is_search()) {
        global $wp_query;

        if (isset($wp_query->query['s'])) {
            $searchKeyword = filter_var($wp_query->query['s'], FILTER_SANITIZE_STRING);
            $searchKeyword = trim($searchKeyword);
        }

        if (isset($wp_query->query_vars['posts_per_page'])) {
            $limit = (int) $wp_query->query_vars['posts_per_page'];
        }

        $limit = $limit ?? 20;

        if (isset($wp_query->query_vars['paged'])) {
            $page = (int)$wp_query->query_vars['paged'];
        }

        $page = $page ?? 0;

        // Get the site type to retrieve search results
        if (isset($_GET['library'])) {
            $site = filter_var($_GET['library'], FILTER_SANITIZE_STRING);
        }

        $site = $site ?? null;

        // Get the year to retrieve search results by year
        if (isset($_GET['year'])) {
            $year = filter_var($_GET['year'],FILTER_SANITIZE_STRING );
        }

        $year = $year ?? null;

        $searchRepository = new SearchRepository();

        if($searchKeyword) {

            $results = $searchRepository->performSearch($searchKeyword, $site, $year, $limit, $page);

            if ($results === false) {
                $results = [];
            }

            // Empty the existing wp_query to make sure no default post is returned
            $wp_query->posts = [];

            foreach ($results as $index => $result) {

                $postProperties = setPostProperties($result);
                $wp_post = new WP_Post((object)$postProperties);

                // There is a single post
                if ($index == 0) {
                    $wp_query->post = $wp_post;
                }

                $wp_query->posts[] = $wp_post;
            }

            // Total posts without any limit
            $wp_query->found_posts = $searchRepository->countSearchResults($searchKeyword, $site, $year);

            // Total number of pages
            $wp_query->max_num_pages = ceil($wp_query->found_posts / $wp_query->query_vars['posts_per_page']);

            $wp_query->post_count = count($results);
        }
    }
}

/**
 * Hook that empties the default WP search and sets a certain number of posts to be displayed
 *
 * Does not change queries within the admin screen
 * nor the main query for a page request (used by the primary post loop)
 */
add_action('pre_get_posts', 'pds_empty_default_search');

function pds_empty_default_search($query) {
    if (is_admin() && !$query->is_main_query()) {
        return;
    }

    if (is_search()) {
        $query->set('posts_per_page' , 20);
    }

}

/**
 * Return number of results string
 *
 * E.g.
 * 1 result, page 1 of 1.
 *
 * @return string
 */
function pds_number_of_results(): string
{
    global $wp_query;
    $content = $wp_query->found_posts . ' result';
    if ($wp_query->found_posts > 1) {
        $content .=  's';
    } else {
        $content .= '.';
        return $content;
    }

    $content .= ', page ';
    $content .= ($wp_query->query_vars['paged'] == 0) ? 1 : $wp_query->query_vars['paged'];
    $content .= ' of ' . $wp_query->max_num_pages . '.';
    return $content;
}
