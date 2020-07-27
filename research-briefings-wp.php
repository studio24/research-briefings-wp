<?php
/*
Plugin Name: Research Briefings WP
Plugin URI: http://github.com/ukparliament/research-briefings-wp
Description: Ingests and displays UK Parliament's Research Briefings as posts
Version: 1.0.0
Author: Jake Mulley
*/

if(!defined( 'WPINC' )) {
    die;
}

require __DIR__ . '/vendor/autoload.php';

if (class_exists('WP_CLI')) {
require_once __DIR__ . '/includes/research-briefings-import.php';
require_once __DIR__ . '/includes/author-tags-import.php';

}

include_once ('includes/custom-post-type.php');
//include_once('includes/taxonomy.php');
include_once ('includes/types-custom-taxonomy.php');
include_once('includes/loop-modifiers.php');
//include_once('includes/ingester.php');
//include_once('includes/redirector.php');
include_once('includes/crosstagger.php');
include_once('includes/category-images.php');
include_once ('includes/add-metadata.php');
include_once ('includes/custom-search.php');
include_once ('includes/helper.php');
include_once ('includes/topics-custom-taxonomy.php');
include_once ('includes/authors-custom-taxonomy.php');
include_once ('includes/set-types-on-search.php');



if (!empty($_REQUEST['s24_run'])) {

	// This is only run once in order to remove all the old research briefings posts
	//include_once ('includes/delete-old-posts.php');

	// This is only run once in order to remove all the imported custom research briefings posts
	//include_once ('includes/delete-custom-posts.php');

	// This is only run once in order to update missing featured images on early-RB imports.
	//include_once ('includes/set-featured-image-from-category.php');

}

function research_briefings_wp_cron_activation() {
    if(!wp_next_scheduled('research_briefings_wp_cron_ingester')) {
        wp_schedule_event(time(), 'hourly', 'research_briefings_wp_cron_ingester');
    }
}
register_activation_hook( __FILE__, 'research_briefings_wp_cron_activation');

function research_briefings_wp_cron_deactivation() {
    $timestamp = wp_next_scheduled('research_briefings_wp_cron_ingester');
    wp_unschedule_event($timestamp, 'research_briefings_wp_cron_ingester');
}
register_deactivation_hook( __FILE__, 'research_briefings_wp_cron_deactivation');

add_action('research_briefings_wp_cron_ingester', 'research_briefings_wp_read_research_briefings');
?>
