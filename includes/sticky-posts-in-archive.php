<?php
/*
Plugin Name: Sticky in Archives
Plugin URI: http://www.damiencarbery.com/2017/09/sticky-posts-in-category-archives/
Description: Move sticky posts to top of archive listings.
Version: 0.1
Author: Damien Carbery
Author URI: http://www.damiencarbery.com/
*/

class StickyInCategory {

	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * The array of categories that will have sticky posts moved to the top.
	 */
	protected $categories;

	/**
	 * Returns an instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new StickyInCategory();
		}

		return self::$instance;

	}

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {

		$this->categories = array();

		// Filter retrieved posts
		add_filter(	'the_posts', array( $this, 'prepend_sticky_posts' ), 10, 2 );

	}


	/**
	 * Move sticky posts to the top of the
	 */
	public function prepend_sticky_posts( $posts, $query ) {

		if ( !is_admin() && is_main_query() ) {

			// Hook to initialise the categories if none specified.
			if ( empty( $this->categories ) ) {
				$this->categories = apply_filters( 'sic_sticky_categories', $this->categories );
			}

			// Only continue if we are viewing a category archive.
			if ( array_key_exists( 'category_name', $query->query_vars ) ) {
				$category_matched = false;
				if ( empty( $this->categories ) ) {
					// If no categories were supplied by the apply_filters() then operate on all categories.
					$category_matched = true;
				}
				else {
					// Check whether the current category is in the list.
					$category_matched = in_array( $query->query_vars['category_name'], $this->categories );
				}

				if ( $category_matched ) {
					// Copied from the bottom of WP_Query::get_posts() in wp-includes/class-wp-query.php
					$sticky_posts = get_option( 'sticky_posts' );
					$num_posts = count( $posts );
					$sticky_offset = 0;
					// Loop over posts and relocate stickies to the front.
					for ( $i = 0; $i < $num_posts; $i++ ) {
						if ( in_array( $posts[ $i ]->ID, $sticky_posts ) ) {
							$sticky_post = $posts[ $i ];
							// Remove sticky from current position
							array_splice( $posts, $i, 1 );
							// Move to front, after other stickies
							array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
							// Increment the sticky offset. The next sticky will be placed at this offset.
							$sticky_offset++;
							// Remove post from sticky posts array
							$offset = array_search( $sticky_post->ID, $sticky_posts );
							unset( $sticky_posts[ $offset ] );
						}
					}
				}
			}
		}
		return $posts;
	}
}

add_action( 'plugins_loaded', array( 'StickyInCategory', 'get_instance' ) );
