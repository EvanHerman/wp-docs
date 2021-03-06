<?php
/*
Plugin Name: WP Docs
Version: 1.0.2
Plugin URI: https://mc4wp.com/kb/
Description: WordPress powered documentation for your products. Beautiful.
Author: Danny van Kooten
Author URI: https://dannyvankooten.com/
Text Domain: wp-docs
Domain Path: /languages/
License: GPL v3

WP Docs plugin
Copyright (C) 2014, Danny van Kooten - support@wpdocs.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WPDocs;

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPDocs {

	/**
	 * @const Slug of the post type
	 */
	const POST_TYPE_NAME = 'wpdocs-doc';

	/**
	 * @const Slug of category taxonomy
	 */
	const TAXONOMY_CATEGORY_NAME = 'wpdocs-category';

	/**
	 * @const Slug of keyword taxonomy
	 */
	const TAXONOMY_KEYWORD_NAME = 'wpdocs-keyword';

	/**
	 * Version of the plugin
	 */
	const VERSION = '1.0.2';


	/**
	 * Main plugin file
	 */
	const FILE = __FILE__;

	/**
	 * @var array
	 */
	static $options = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		require __DIR__ .'/vendor/autoload.php';

		// add actions
		add_action( 'init', array( $this, 'init' ) );

		// register (de)activation hooks
		register_activation_hook( self::FILE, array( $this, 'on_plugin_activation' ) );
		register_deactivation_hook( self::FILE, array( $this, 'on_plugin_deactivation' ) );

		new Breadcrumbs\Manager();

		// load template manager
		new TemplateManager();

		// load search
		new Search();

		// load code highlighter
		new CodeHighlighting();

		// load lister (shortcodes)
		DocList::init();
	}

	/**
	 * Registers all terms, taxonomy's and post types.
	 */
	public function init() {

		$post_type_slug = ( defined( 'WPDOCS_POST_TYPE_SLUG' ) ? WPDOCS_POST_TYPE_SLUG : 'docs' );

		// register docs taxonomy: keyword
		register_taxonomy(
			'wpdocs-keyword',
			'wpdocs-doc',
			array(
				'label' => __( 'Keyword', 'wpdocs' ),
				'rewrite' => array( 'slug' => $post_type_slug . '/keyword' ),
				'hierarchical' => false,
			)
		);

		// register docs taxonomy: category
		register_taxonomy(
			'wpdocs-category',
			'wpdocs-doc',
			array(
				'label' => __( 'Category' ),
				'rewrite' => array( 'slug' => $post_type_slug . '/category' ),
				'hierarchical' => true,
				'query_var' => true
			)
		);

		// register docs post type
		register_post_type(
			'wpdocs-doc',
			array(
				'public' => true,
				'label'  => 'Docs',
				'hierarchical' => true,
				'rewrite' => array( 'slug' => $post_type_slug ),
				'taxonomies' => array( 'wpdocs-category', 'wpdocs-keyword' ),
				'has_archive' => ( WPDocs::get_option( 'custom_archive_page_id' ) === 0 )
			)
		);
	}

	/**
	 * Make sure rewrite rules are flushed on plugin activation
	 */
	public function on_plugin_activation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * Make sure rewrite rules are flushed again on plugin deactivation
	 */
	public function on_plugin_deactivation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * @param $index
	 *
	 * @return mixed
	 */
	public static function get_option( $index ) {

		// have the options been loaded yet?
		if( empty( self::$options ) ) {
			self::load_options();
		}

		// does the option exist?
		if( ! isset( self::$options[ $index ] ) ) {
			return null;
		}

		// return queried option
		return self::$options[ $index ];
	}

	/**
	 * Loads the options, makes sure defaults are taken into considerations
	 */
	private static function load_options() {

		$defaults = array(
			'custom_archive_page_id' => 42540
		);

		$options = get_option( 'wpdocs', array() );

		// merge options with defaults
		$options = wp_parse_args( $options, $defaults );

		self::$options = $options;
	}

	/**
	 * Shows whether an extension exists and is activated
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public static function extension( $slug ) {
		$extensions = apply_filters( 'wpdocs_extensions', array() );
		return( in_array( $slug, $extensions ) );
	}

}

$GLOBALS['wpdocs'] = new WPDocs;