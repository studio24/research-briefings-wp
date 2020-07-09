<?php

namespace ResearchBriefing\Wordpress;

use Exception;
use ResearchBriefing\Briefing;
use ResearchBriefing\Exception\NotFoundException;
use ResearchBriefing\Exception\TermNotSetException;
use ResearchBriefing\Exception\WordpressException;
use ResearchBriefing\Model\Insight;
use ResearchBriefing\Model\SearchInterface;
use WP_Error;
use WP_Query;

/**
 * Class Wordpress to save and update imported data in the CMS
 * @package ResearchBriefing\Wordpress
 */
class Wordpress
{

    /**
     * Method that translates topics to categories based on the topic url
     * Returns an array of the wp term ids which represents the category ids
     *
     * @param array $topicUrls Array of topic URLs from Research Briefing API
     * @return array Array of category IDs Array of matched WordPress category IDs
     * @throws Exception
     */
    public function getCategoriesByTopicUrl(array $topicUrls): array
    {
        $crosstagged = $this->getCrosstaggedCategories();
        $categories = [];

        foreach ($crosstagged as $categoryId => $topicsList) {
            foreach ($topicsList as $topicName => $topicUrl) {
                if (in_array($topicUrl, $topicUrls)) {
                    if (!in_array($categoryId, $categories)) {
                        $categories[] = (int) $categoryId;
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Get crosstagged categories from WP
     *
     * @return array
     * @throws NotFoundException
     */
    public function getCrosstaggedCategories(): array
    {
        static $categories;

        if (empty($categories)) {
            $categories = get_option('research_briefings_crosstagged');

            if ($categories === false) {
                throw new NotFoundException('Crosstagged categories not found!');
            }
        }

        return $categories;
    }

    /**
     * All imported data is assigned initially to the category 'Research Briefing'
     *
     * Method that checks if a category with this name already exists and if not, it creates one
     * Returns the term id of the category
     *
     * @param $categoryName 'Research Briefing'
     * @return int|WP_Error
     */
    public function getOrCreateCategory (string $categoryName): int
    {
        $categoryId = wp_create_category($categoryName);

        return (int) $categoryId;
    }

    /**
     * Return WP post based on the meta value "identifier"
     *
     * @param string $briefingId
     * @return int|\WP_Post
     * @see https://developer.wordpress.org/reference/functions/get_posts/
     * @see https://developer.wordpress.org/reference/classes/wp_query/parse_query/
     */
    public function getExistingBriefingById(string $briefingId)
    {
        $args = array(
            'posts_per_page' => 1,
            'post_type'      => 'research-briefing',
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => 'identifier',
                    'value'    => $briefingId,
                    'compare'    => '=',
                ),
            ),
        );

        return get_posts($args)[0];
    }

    /**
     * Method that maps the fields from the imported briefing and saves it into a new research briefing post
     *
     * @param Briefing $briefing
     * @return void
     * @throws WordpressException
     * @throws TermNotSetException
     */
    public function createBriefingPost (Briefing $briefing)
    {
        $now = new \DateTime();
        $meta = array(
            'identifier'    => $briefing->getId(),
            'topics'        => $briefing->getTopics() ? json_encode($briefing->getTopics()) : [],
            'related_link'  => $briefing->getRelatedLink() ? json_encode($briefing->getRelatedLink()) : [],
            'documents'     => $briefing->getDocuments() ? json_encode($briefing->getDocuments()) : [],
            'section'       => $briefing->getSection(),
            'created_date'  => ($briefing->getCreated())->format('Y-m-d H:i:s'),
            'import_last_update' => $now->format('r'),
        );

        // Create a new post
        $postId = wp_insert_post(array(
            'post_name'     => $briefing->getId(),
            'post_title'    => $briefing->getTitle(),
            'post_type'      => 'research-briefing',
            'post_excerpt'  => $briefing->getDescription() ? $briefing->getDescription() : "",
            'post_content' => $briefing->getHtmlSummary() ? $briefing->getHtmlSummary() : "",
            'post_status' => $briefing->isPublished() ? 'publish' : 'draft',
            'post_date' => ($briefing->getDate())->format('Y-m-d H:i:s'),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', ($briefing->getDate())->getTimestamp()),
            'meta_input' => $meta
        ));

        if (empty($postId)) {
            throw new WordpressException('Failed to insert briefing post');
        }

        $this->setTaxonomiesToPost($postId, $briefing) ;

        $this->setImage($this->getCategoriesToAttach($briefing), $postId);

        // Set extra properties to a briefing for search purposes
        $this->setExtraProperties($briefing, $postId);
    }

    /**
     * Method that updates the fields of an existing research briefing custom post type
     *
     * @param \WP_Post $existingBriefing
     * @param Briefing $briefing
     * @throws TermNotSetException
     * @throws WordpressException
     */
    public function updateBriefingPost (\WP_Post $existingBriefing, Briefing $briefing)
    {
        $existingBriefing->post_title = $briefing->getTitle();
        $existingBriefing->post_status = $briefing->isPublished() ? 'publish' : 'draft';
        $existingBriefing->post_date =  ($briefing->getDate())->format('Y-m-d H:i:s');
        $existingBriefing->post_date_gmt = gmdate('Y-m-d H:i:s', ($briefing->getDate())->getTimestamp());
        $existingBriefing->post_content = $briefing->getHtmlSummary();
        $existingBriefing->post_excerpt = $briefing->getDescription();

        $now = new \DateTime();
        $existingBriefing->meta_input = [
            'identifier'    => $briefing->getId(),
            'topics'        => $briefing->getTopics() ? json_encode($briefing->getTopics()) : [],
            'related_link'  => $briefing->getRelatedLink() ? json_encode($briefing->getRelatedLink()) : [],
            'documents'     => $briefing->getDocuments() ? json_encode($briefing->getDocuments()) : [],
            'section'       => $briefing->getSection(),
            'import_last_update' => $now->format('r'),
        ];

        $postId = wp_update_post($existingBriefing);

        if (empty($postId)) {
            throw new WordpressException('Failed to update briefing post');
        }

        $this->setTaxonomiesToPost($postId, $briefing);

        // Set extra properties to a briefing for search purposes
        $this->setExtraProperties($briefing, $postId);
    }

    /**
     * Unpublish a briefing post in WordPress
     *
     * @param \WP_Post $existingBriefing
     * @throws WordpressException
     */
    public function unpublishBriefingPost(\WP_Post $existingBriefing)
    {
        $existingBriefing->post_status = 'draft';

        $now = new \DateTime();
        $existingBriefing->meta_input = [
            'import_last_update' => $now->format('r'),
        ];

        $postId = wp_update_post($existingBriefing);

        if (empty($postId)) {
            throw new WordpressException('Failed to update briefing post');
        }
    }

    /**
     * Getting the research briefing categories array that contains the wp term_ids to attach to the WP posts
     *
     * @param $briefing
     * @return array Array of category IDs
     * @throws WordpressException
     * @throws Exception
     */
    public function getCategoriesToAttach (Briefing $briefing): array
    {
        $categories= [];

        // Get briefing topics (which come from the API)
        $topicUrls = [];
        $topics = $briefing->getTopics();

        // Match topics with WordPress category terms (via crosstagged categories)
        if (is_array($topics)) {
            foreach ($topics as $topic) {
                // Briefing topic URL, e.g. http://data.parliament.uk/terms/95554
                $topicUrls[] = $topic['_about'];
            }

            // Translate topics to categories
            $crosstaggedCategories = $this->getCategoriesByTopicUrl($topicUrls);

            $categories = array_merge($categories, $crosstaggedCategories);
        }

        return $categories;

    }

    /**
     * Getting the array that contains the author slug to attach to the WP posts
     *
     * @param $briefing
     * @return array Array of author slugs
     */
    public function getAuthorsToAttach (Briefing $briefing): array
    {
        $slug = [];

        $authors = $briefing->getAuthors();

        foreach ($authors as $author) {

            // Create the term if it doesn't exist
            if( !term_exists( $author, 'post_tag' ) ) {
                wp_insert_term(
                    $author,
                    'post_tag',
                    array(
                        'slug'=> sanitize_title($author)
                    )
                );
            }

            $slug[]= sanitize_title($author);
        }

        return $slug;

    }

    /**
     * Adding categories and authors as taxonomies to newly created posts
     *
     * @param int $postId
     * @param Briefing $briefing
     * @throws TermNotSetException
     * @throws WordpressException
     */
    public function setTaxonomiesToPost(int $postId, Briefing $briefing)
    {
        // Get the categories to attach to the WP post
        $categories = $this->getCategoriesToAttach($briefing);

        $categoriesTaxonomyIds =  wp_set_object_terms($postId, $categories, 'rb_topics');
        if (is_wp_error($categoriesTaxonomyIds)) {
            throw new TermNotSetException('Category terms not set');
        }

        // Get the author slugs to attach to the WP post
        $authors = $this->getAuthorsToAttach($briefing);

        if (!empty($authors)) {
            $authorsTaxonomyIds =  wp_set_object_terms($postId, $authors, 'post_tag');
        }

        if (is_wp_error($authorsTaxonomyIds)) {
            throw new TermNotSetException('Author terms not set');
        }

        // Get the type slug to attach to the WP post
        $type = $briefing->getType();

        if (!empty($type)) {
            $typeTaxonomyIds =  wp_set_object_terms($postId, $type, 'rb_types', true);
        }

        if (is_wp_error($typeTaxonomyIds)) {
            throw new TermNotSetException('Type terms not set');
        }

    }


    /**
     * Assigning images to the relevant categories of newly created posts
     *
     * @param $categories
     * @param $post
     */
    public function setImage (array $categories, int $post)
    {
        $actualRbImage = null;

        foreach ($categories as $category) {
            $imageId = get_term_meta($category, 'rb_image', true);
            if($imageId) {
                $actualRbImage = $imageId;
            }
        }
        if($actualRbImage) {
            // Set post thumbnail
            set_post_thumbnail((int) $post, (int) $actualRbImage);
        }

    }

    /**
     * Method that sets extra properties used later for the search table
     *
     * @param SearchInterface $object
     * @param int $postId
     */
    public function setExtraProperties(SearchInterface $object, int $postId)
    {
        $object->setPermalinkSlug(explode('/', get_the_permalink($postId), 4)[3]);

        if (get_the_post_thumbnail_url($postId, 'post-thumbnail')) {
            $object->setThumbnail(explode('/', get_the_post_thumbnail_url($postId, 'full'), 4)[3]);
        }else {
            $object->setThumbnail('');
        }

        $terms = get_the_terms($postId , 'rb_topics' );

        // Save the category terms as a comma separated string
        $categories = [];

        if (!empty($terms) ) {
            foreach ($terms as $term) {

                $categories[] = [
                    'id' => $term->term_id,
                    'name' => trim(html_entity_decode(($term->name)))
                ];
            }
        }

        $object->setTags(json_encode($categories));

    }

    /**
     * Method that retrieves the current insights content from all the multisite posts table
     * @return array
     * @throws Exception
     */
    public function getExistingInsights()
    {
        $blog_ids = get_sites();

        $insights = [];

        foreach( $blog_ids as $blog ){
            switch_to_blog($blog->blog_id);

            $args = array(
                'post_type'      => 'post',
                'orderby'        => 'ID',
                'post_status'    => 'publish',
                'order'          => 'DESC',
                'posts_per_page' => -1 // this will retrieve all the posts that are published
            );

            $results = get_posts($args);

            foreach ($results as $index => $result) {

                $insight = new Insight();
                $insight->setMappedFields($result);
                $insight->setSite($blog->blog_id);

                // Set extra properties to an insight for search purposes
                $this->setExtraProperties($insight, $result->ID);

                $insights[] = $insight;
            }

//            restore_current_blog();
        }

        return $insights;
    }

    /**
     * Method used for the multisite setup to determine what site tables to use for the import
     * Prefix wp_* is used for House of Commons data; wp_2_* is for House of Lords and wp_3_* is for POST
     *
     * @param $briefing
     * @return true
     */
    public function switchToSite($briefing) {

        switch ($briefing->getPublisher()) {
            case 'House of Commons Library':
                switch_to_blog(1);
                break;
            case 'House of Lords Library':
                switch_to_blog(2);
                break;
            case 'POST':
                switch_to_blog(3);
                break;
            default:
                // error?
        }
    }
}
