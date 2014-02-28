<?php

/**
 * Plugin Name: CLI Content Generator
 * Plugin URI: http://hmn.md
 * Description: Generate Random Content
 * Version: 0.1
 * Author: Matthew Haines-Young
 * Author URI: http://matth.eu
 * License: GPL2
 */


define( 'MPH_GENERATOR_FLICKR_API_KEY', '13c14b4cdf855a0627994e2ee92f26db' );

if ( ! 'MPH_GENERATOR_FLICKR_API_KEY' )
	die( 'You must define the Flickr API key.' );

require_once(  'inc/strings.php' );
require_once( 'inc/class.content-generator.php' );
// require_once( 'inc/class.image-generator.php' );
require_once( 'inc/class.post-generator.php' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Manage posts.
	 *
	 * @package wp-cli
	 */
	class MPH_Post_CLI extends WP_CLI_Command {

		protected $terms = array();

		public function __construct() {

			$this->CLIContentGenerator = new CLIContentGenerator();
			// $this->CLIImageGenerator = new CLIImageGenerator();

		}

		/**
		 * Generate some posts.
		 *
		 * ## OPTIONS
		 *
		 * [--count=<number>]
		 * : How many posts to generate. Default: 100
		 *
		 * [--post_type=<type>]
		 * : The type of the generated posts. Default: 'post'
		 *
		 * [--post_status=<status>]
		 * : The status of the generated posts. Default: 'publish'
		 *
		 * [--post_author=<login>]
		 * : The author of the generated posts. Default: none
		 *
		 * [--post_date=<yyyy-mm-dd>]
		 * : The date of the generated posts. Default: current date
		 *
		 * [--post_date_start=<yyyy-mm-dd>]
		 * : The start date range for generated posts. P Default: false
		 *
		 * [--max_depth=<number>]
		 * : For hierarchical post types, generate child posts down to a certain depth. Default: 1
		 *
		 * ## EXAMPLES
		 *
		 *     wp mph_post generate --count=10 --post_type=post --post_date_start=1999-01-04
		 */
		public function generate( $args, $assoc_args ) {

			global $wpdb;

			$defaults = array(
				'count' => 100,
				'max_depth' => 4,
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_author' => false,
				'post_date' => current_time( 'mysql' ),
				'post_date_start' => false,
			);

			extract( array_merge( $defaults, $assoc_args ), EXTR_SKIP );

			if ( ! post_type_exists( $post_type ) ) {
				WP_CLI::error( sprintf( "'%s' is not a registered post type.", $post_type ) );
			}

			if ( $post_author ) {
				$post_author = get_user_by( 'login', $post_author );
				if ( $post_author )
					$post_author = $post_author->ID;
			}

			$hierarchical = get_post_type_object( $post_type )->hierarchical;

			$notify = \WP_CLI\Utils\make_progress_bar( 'Generating posts', $count );

			$current_depth = 1;
			$current_parent = 0;

			// Generate Random Categories up to 25.
			$this->generate_categories();

			for ( $i = 0; $i < $count; $i++ ) {

				if ( $hierarchical && isset( $id ) ) {
					if ( $this->maybe_make_child() && $current_depth < $max_depth ) {
						$current_parent = $id;
						$current_depth++;
					} else if ( $this->maybe_reset_depth() ) {
						$current_depth = 1;
						$current_parent = 0;
					}
				}

				if ( $post_date_start ) {
					$insert_date = date( 'Y-m-d H:i:s', rand( strtotime( $post_date_start ), strtotime( $post_date ) ) );
				} else {
					$insert_date = date( 'Y-m-d H:i:s', strtotime( $post_date ) );
				}

				$args = array(
					'post_type' => $post_type,
					'post_title' => $this->CLIContentGenerator->get_lorem_ipsum( 1 ),
					'post_status' => $post_status,
					'post_author' => $post_author,
					'post_parent' => $current_parent,
					'post_name' => "post-$i",
					'post_date' => $insert_date,
					'post_content' => $this->CLIContentGenerator->get_post_content()
				);

				var_dump( $args );


				$id = wp_insert_post( $args, true );

				$this->insert_post_meta( $id );

				// Get X (random 1-5) random terms;
				$terms = $this->terms['category'];
				shuffle($terms);
				$random_terms = array_slice( $terms, 0, mt_rand(1,5) );
				wp_set_post_terms( $id, $random_terms, 'category' );

				$notify->tick();
			}
			$notify->finish();
		}

		function generate_categories( $taxonomy = 'category', $count = 25 ) {

			if ( ! isset( $this->terms[$taxonomy] ) )
				$this->terms[$taxonomy] = array();

			// Get existing terms
			$terms = get_terms( $taxonomy, array( 'orderby' => 'count', 'hide_empty' => 0 ) );

			foreach ( $terms as $term ) {
				$this->terms[$taxonomy][] = $term->term_id;
			}

			$count = $count - count( $terms );

			if ( $count === 0 )
				return;

			$strings = $this->CLIContentGenerator->get_lorem_strings( $count );

			foreach ( $strings as $string ) {
				$length = $this->CLIContentGenerator->normal_rand( 2, 1 );
				$name = implode( ' ', array_slice( explode( " ", $string ), 0, $length ) );
				$parent_term_id = 0; // @todo - randomly assign term parent.
				$term = wp_insert_term( $name, $taxonomy, array( 'description'=> $string, 'parent'=> $parent_term_id ) );
				$this->terms[$taxonomy][] = $term['term_id'];
			}

		}

		/**
		 * Insert some random post meta for each post.
		 * This will just be a random string, between 5 and 20 characters.
		 *
		 * @param  [type] $post_id [description]
		 * @return [type]          [description]
		 */
		function insert_post_meta( $post_id, $meta_count = 15 ) {
			for ( $i = 0; $i < $meta_count; $i++ ) {
				add_post_meta(
					$post_id,
					'meta_' . $i,
					$this->CLIContentGenerator->get_random_string( rand( 5, 20 ) ),
					true
				);
			}
		}

		private function maybe_make_child() {
			// 50% chance of making child post
			return ( mt_rand(1, 2) == 1 );
		}

		private function maybe_reset_depth() {
			// 33.333% chance of reseting to root depth
			return ( mt_rand(1, 3) == 1 );
		}

	}

	WP_CLI::add_command( 'mph_post', 'MPH_Post_CLI' );

}