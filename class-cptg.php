<?php
/*
Plugin Name: CPT Generator
Plugin URI: https://github.com/ravijoshi89/CPT-Generator
Description: CPT Generator lets you create Custom Post Types and custom Taxonomies in a easy way.
Version: 1.0.0
Author: Ravi Joshi
Author URI: 
Text Domain: cpt-generator
Domain Path: /languages

Released under the GPL v.2, http://www.gnu.org/copyleft/gpl.html
*/

//restrict direct calls to this file
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Primary class to run the plugin
*/
class cptg {
	/** @var string $dir Plugin dir */
	private $dir;
	/** @var string $path Plugin path */
	private $path;
	/** @var string $version Plugin version */
	private $version;

	/**
	 * Constructor
	 */
	function __construct() {
		// vars
		$this->dir     = plugins_url( '', __FILE__ );
		$this->path    = plugin_dir_path( __FILE__ );
		$this->version = '1.0.0';

		// actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'cptg_create_custom_post_types' ) );
		add_action( 'admin_menu', array( $this, 'cptg_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cptg_styles' ) );
		add_action( 'add_meta_boxes', array( $this, 'cptg_create_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'cptg_save_post' ) );
		add_action( 'admin_init', array( $this, 'cptg_plugin_settings_flush_rewrite' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'cptg_custom_columns' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'cptg_tax_custom_columns' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'cptg_admin_footer' ) );
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'wp_prepare_attachment_for_js' ), 10, 3 );

		// filters
		add_filter( 'manage_cptg_posts_columns', array( $this, 'cptg_change_columns' ) );
		add_filter( 'manage_edit-cptg_sortable_columns', array( $this, 'cptg_sortable_columns' ) );
		add_filter( 'manage_cptg_tax_posts_columns', array( $this, 'cptg_tax_change_columns' ) );
		add_filter( 'manage_edit-cptg_tax_sortable_columns', array( $this, 'cptg_tax_sortable_columns' ) );
		add_filter( 'post_updated_messages', array( $this, 'cptg_post_updated_messages' ) );

		// hooks
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
		register_activation_hook( __FILE__, array( $this, 'cptg_plugin_activate_flush_rewrite' ) );

		// set textdomain
		load_plugin_textdomain( 'cpt-generator', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Create cptg post type
		$labels = array(
			'name'               => __( 'CPT Generator', 'cpt-generator' ),
			'singular_name'      => __( 'Custom Post Type', 'cpt-generator' ),
			'add_new'            => __( 'Add New', 'cpt-generator' ),
			'add_new_item'       => __( 'Add New Custom Post Type', 'cpt-generator' ),
			'edit_item'          => __( 'Edit Custom Post Type', 'cpt-generator' ),
			'new_item'           => __( 'New Custom Post Type', 'cpt-generator' ),
			'view_item'          => __( 'View Custom Post Type', 'cpt-generator' ),
			'search_items'       => __( 'Search Custom Post Types', 'cpt-generator' ),
			'not_found'          => __( 'No Custom Post Types found', 'cpt-generator' ),
			'not_found_in_trash' => __( 'No Custom Post Types found in Trash', 'cpt-generator' ),
		);

		register_post_type(
			'cptg',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'page',
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => 'cptg',
				'supports'        => array(
					'title',
				),
				'show_in_menu'    => false,
			)
		);

		// Create cptg_tax post type
		$labels = array(
			'name'               => __( 'Custom Taxonomies', 'cpt-generator' ),
			'singular_name'      => __( 'Custom Taxonomy', 'cpt-generator' ),
			'add_new'            => __( 'Add New', 'cpt-generator' ),
			'add_new_item'       => __( 'Add New Custom Taxonomy', 'cpt-generator' ),
			'edit_item'          => __( 'Edit Custom Taxonomy', 'cpt-generator' ),
			'new_item'           => __( 'New Custom Taxonomy', 'cpt-generator' ),
			'view_item'          => __( 'View Custom Taxonomy', 'cpt-generator' ),
			'search_items'       => __( 'Search Custom Taxonomies', 'cpt-generator' ),
			'not_found'          => __( 'No Custom Taxonomies found', 'cpt-generator' ),
			'not_found_in_trash' => __( 'No Custom Taxonomies found in Trash', 'cpt-generator' ),
		);

		register_post_type(
			'cptg_tax',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'page',
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => 'cptg_tax',
				'supports'        => array(
					'title',
				),
				'show_in_menu'    => false,
			)
		);

		// Add image size for the Custom Post Type icon
		if ( function_exists( 'add_image_size' ) && ! defined( 'cptg_DONT_GENERATE_ICON' ) ) {
			add_image_size( 'cptg_icon', 16, 16, true );
		}
	}

	/**
	 * Add admin menu items
	 */
	public function cptg_admin_menu() {
		// add cptg page to options menu
		add_menu_page( __( 'CPT Generator', 'cpt-generator' ), __( 'Post Types', 'cpt-generator' ), 'manage_options', 'edit.php?post_type=cptg', '', 'dashicons-layout' );
		add_submenu_page( 'edit.php?post_type=cptg', __( 'Taxonomies', 'cpt-generator' ), __( 'Taxonomies', 'cpt-generator' ), 'manage_options', 'edit.php?post_type=cptg_tax' );
	}

	/**
	 * Register admin styles
	 *
	 * @param string $hook WordPress hook
	 */
	public function cptg_styles( $hook ) {
		// register  style
		if ( 'edit.php' == $hook && isset( $_GET['post_type'] ) && ( 'cptg' == $_GET['post_type'] || 'cptg_tax' == $_GET['post_type'] ) ) {
			wp_register_style( 'cptg_admin_styles', $this->dir . '/css/global.css' );
			wp_enqueue_style( 'cptg_admin_styles' );

			wp_register_script( 'cptg_admin_js', $this->dir . '/js/global.js', 'jquery', '0.0.1', true );
			wp_enqueue_script( 'cptg_admin_js' );

			wp_enqueue_script( array( 'jquery', 'thickbox' ) );
			wp_enqueue_style( array( 'thickbox' ) );
		}

		// register add / edit style
		if ( ( 'post-new.php' == $hook && isset( $_GET['post_type'] ) && 'cptg' == $_GET['post_type'] ) || ( 'post.php' == $hook && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'cptg' ) || ( $hook == 'post-new.php' && isset( $_GET['post_type'] ) && 'cptg_tax' == $_GET['post_type'] ) || ( 'post.php' == $hook && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'cptg_tax' ) ) {
			wp_register_style( 'cptg_add_edit_styles', $this->dir . '/css/style.css' );
			wp_enqueue_style( 'cptg_add_edit_styles' );

			wp_register_script( 'cptg_admin__add_edit_js', $this->dir . '/js/script.js', 'jquery', '0.0.1', true );
			wp_enqueue_script( 'cptg_admin__add_edit_js' );

			wp_enqueue_media();
		}
	}

	/**
	 * Create custom post types
	 */
	public function cptg_create_custom_post_types() {
		// vars
		$cptgs     = array();
		$cptg_taxs = array();

		// query custom post types
		$get_cptg        = array(
			'numberposts'      => -1,
			'post_type'        => 'cptg',
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);
		$cptg_post_types = get_posts( $get_cptg );

		// create array of post meta
		if ( $cptg_post_types ) {
			foreach ( $cptg_post_types as $cptg ) {
				$cptg_meta = get_post_meta( $cptg->ID, '', true );

				// text
				$cptg_name          = ( array_key_exists( 'cptg_name', $cptg_meta ) && $cptg_meta['cptg_name'][0] ? esc_html( $cptg_meta['cptg_name'][0] ) : 'no_name' );
				$cptg_label         = ( array_key_exists( 'cptg_label', $cptg_meta ) && $cptg_meta['cptg_label'][0] ? esc_html( $cptg_meta['cptg_label'][0] ) : $cptg_name );
				$cptg_singular_name = ( array_key_exists( 'cptg_singular_name', $cptg_meta ) && $cptg_meta['cptg_singular_name'][0] ? esc_html( $cptg_meta['cptg_singular_name'][0] ) : $cptg_label );
				$cptg_description   = ( array_key_exists( 'cptg_description', $cptg_meta ) && $cptg_meta['cptg_description'][0] ? $cptg_meta['cptg_description'][0] : '' );

				// Custom post icon (uploaded)
				$cptg_icon_url = ( array_key_exists( 'cptg_icon_url', $cptg_meta ) && $cptg_meta['cptg_icon_url'][0] ? $cptg_meta['cptg_icon_url'][0] : false );

				// Custom post icon (dashicons)
				$cptg_icon_slug = ( array_key_exists( 'cptg_icon_slug', $cptg_meta ) && $cptg_meta['cptg_icon_slug'][0] ? $cptg_meta['cptg_icon_slug'][0] : false );

				// If DashIcon is set ignore uploaded
				if ( ! empty( $cptg_icon_slug ) ) {
					$cptg_icon_name = $cptg_icon_slug;
				} else {
					$cptg_icon_name = $cptg_icon_url;
				}

				$cptg_custom_rewrite_slug = ( array_key_exists( 'cptg_custom_rewrite_slug', $cptg_meta ) && $cptg_meta['cptg_custom_rewrite_slug'][0] ? esc_html( $cptg_meta['cptg_custom_rewrite_slug'][0] ) : $cptg_name );
				$cptg_menu_position       = ( array_key_exists( 'cptg_menu_position', $cptg_meta ) && $cptg_meta['cptg_menu_position'][0] ? (int) $cptg_meta['cptg_menu_position'][0] : null );

				// dropdown
				$cptg_public              = ( array_key_exists( 'cptg_public', $cptg_meta ) && $cptg_meta['cptg_public'][0] == '1' ? true : false );
				$cptg_show_ui             = ( array_key_exists( 'cptg_show_ui', $cptg_meta ) && $cptg_meta['cptg_show_ui'][0] == '1' ? true : false );
				$cptg_has_archive         = ( array_key_exists( 'cptg_has_archive', $cptg_meta ) && $cptg_meta['cptg_has_archive'][0] == '1' ? true : false );
				$cptg_exclude_from_search = ( array_key_exists( 'cptg_exclude_from_search', $cptg_meta ) && $cptg_meta['cptg_exclude_from_search'][0] == '1' ? true : false );
				$cptg_capability_type     = ( array_key_exists( 'cptg_capability_type', $cptg_meta ) && $cptg_meta['cptg_capability_type'][0] ? $cptg_meta['cptg_capability_type'][0] : 'post' );
				$cptg_hierarchical        = ( array_key_exists( 'cptg_hierarchical', $cptg_meta ) && $cptg_meta['cptg_hierarchical'][0] == '1' ? true : false );
				$cptg_rewrite             = ( array_key_exists( 'cptg_rewrite', $cptg_meta ) && $cptg_meta['cptg_rewrite'][0] == '1' ? true : false );
				$cptg_withfront           = ( array_key_exists( 'cptg_withfront', $cptg_meta ) && $cptg_meta['cptg_withfront'][0] == '1' ? true : false );
				$cptg_feeds               = ( array_key_exists( 'cptg_feeds', $cptg_meta ) && $cptg_meta['cptg_feeds'][0] == '1' ? true : false );
				$cptg_pages               = ( array_key_exists( 'cptg_pages', $cptg_meta ) && $cptg_meta['cptg_pages'][0] == '1' ? true : false );
				$cptg_query_var           = ( array_key_exists( 'cptg_query_var', $cptg_meta ) && $cptg_meta['cptg_query_var'][0] == '1' ? true : false );
				$cptg_show_in_rest        = ( array_key_exists( 'cptg_show_in_rest', $cptg_meta ) && $cptg_meta['cptg_show_in_rest'][0] == '1' ? true : false );

				// If it doesn't exist, it must be set to true ( fix for existing installs )
				if ( ! array_key_exists( 'cptg_publicly_queryable', $cptg_meta ) ) {
					$cptg_publicly_queryable = true;
				} elseif ( $cptg_meta['cptg_publicly_queryable'][0] == '1' ) {
					$cptg_publicly_queryable = true;
				} else {
					$cptg_publicly_queryable = false;
				}

				$cptg_show_in_menu = ( array_key_exists( 'cptg_show_in_menu', $cptg_meta ) && $cptg_meta['cptg_show_in_menu'][0] == '1' ? true : false );

				// checkbox
				$cptg_supports           = ( array_key_exists( 'cptg_supports', $cptg_meta ) && $cptg_meta['cptg_supports'][0] ? $cptg_meta['cptg_supports'][0] : 'a:2:{i:0;s:5:"title";i:1;s:6:"editor";}' );
				$cptg_builtin_taxonomies = ( array_key_exists( 'cptg_builtin_taxonomies', $cptg_meta ) && $cptg_meta['cptg_builtin_taxonomies'][0] ? $cptg_meta['cptg_builtin_taxonomies'][0] : 'a:0:{}' );

				$cptg_rewrite_options = array();
				if ( $cptg_rewrite ) {
					$cptg_rewrite_options['slug'] = _x( $cptg_custom_rewrite_slug, 'URL Slug', 'cpt-generator' );
				}

				$cptg_rewrite_options['with_front'] = $cptg_withfront;

				if ( $cptg_feeds ) {
					$cptg_rewrite_options['feeds'] = $cptg_feeds;
				}
				if ( $cptg_pages ) {
					$cptg_rewrite_options['pages'] = $cptg_pages;
				}

				$cptgs[] = array(
					'cptg_id'                  => $cptg->ID,
					'cptg_name'                => $cptg_name,
					'cptg_label'               => $cptg_label,
					'cptg_singular_name'       => $cptg_singular_name,
					'cptg_description'         => $cptg_description,
					'cptg_icon_name'           => $cptg_icon_name,
					'cptg_custom_rewrite_slug' => $cptg_custom_rewrite_slug,
					'cptg_menu_position'       => $cptg_menu_position,
					'cptg_public'              => (bool) $cptg_public,
					'cptg_show_ui'             => (bool) $cptg_show_ui,
					'cptg_has_archive'         => (bool) $cptg_has_archive,
					'cptg_exclude_from_search' => (bool) $cptg_exclude_from_search,
					'cptg_capability_type'     => $cptg_capability_type,
					'cptg_hierarchical'        => (bool) $cptg_hierarchical,
					'cptg_rewrite'             => $cptg_rewrite_options,
					'cptg_query_var'           => (bool) $cptg_query_var,
					'cptg_show_in_rest'        => (bool) $cptg_show_in_rest,
					'cptg_publicly_queryable'  => (bool) $cptg_publicly_queryable,
					'cptg_show_in_menu'        => (bool) $cptg_show_in_menu,
					'cptg_supports'            => unserialize( $cptg_supports ),
					'cptg_builtin_taxonomies'  => unserialize( $cptg_builtin_taxonomies ),
				);

				// register custom post types
				if ( is_array( $cptgs ) ) {
					foreach ( $cptgs as $cptg_post_type ) {

						$labels = array(
							'name'               => __( $cptg_post_type['cptg_label'], 'cpt-generator' ),
							'singular_name'      => __( $cptg_post_type['cptg_singular_name'], 'cpt-generator' ),
							'add_new'            => __( 'Add New', 'cpt-generator' ),
							'add_new_item'       => __( 'Add New ' . $cptg_post_type['cptg_singular_name'], 'cpt-generator' ),
							'edit_item'          => __( 'Edit ' . $cptg_post_type['cptg_singular_name'], 'cpt-generator' ),
							'new_item'           => __( 'New ' . $cptg_post_type['cptg_singular_name'], 'cpt-generator' ),
							'view_item'          => __( 'View ' . $cptg_post_type['cptg_singular_name'], 'cpt-generator' ),
							'search_items'       => __( 'Search ' . $cptg_post_type['cptg_label'], 'cpt-generator' ),
							'not_found'          => __( 'No ' . $cptg_post_type['cptg_label'] . ' found', 'cpt-generator' ),
							'not_found_in_trash' => __( 'No ' . $cptg_post_type['cptg_label'] . ' found in Trash', 'cpt-generator' ),
						);

						$args = array(
							'labels'              => $labels,
							'description'         => $cptg_post_type['cptg_description'],
							'menu_icon'           => $cptg_post_type['cptg_icon_name'],
							'rewrite'             => $cptg_post_type['cptg_rewrite'],
							'menu_position'       => $cptg_post_type['cptg_menu_position'],
							'public'              => $cptg_post_type['cptg_public'],
							'show_ui'             => $cptg_post_type['cptg_show_ui'],
							'has_archive'         => $cptg_post_type['cptg_has_archive'],
							'exclude_from_search' => $cptg_post_type['cptg_exclude_from_search'],
							'capability_type'     => $cptg_post_type['cptg_capability_type'],
							'hierarchical'        => $cptg_post_type['cptg_hierarchical'],
							'show_in_menu'        => $cptg_post_type['cptg_show_in_menu'],
							'query_var'           => $cptg_post_type['cptg_query_var'],
							'show_in_rest'        => $cptg_post_type['cptg_show_in_rest'],
							'publicly_queryable'  => $cptg_post_type['cptg_publicly_queryable'],
							'_builtin'            => false,
							'supports'            => $cptg_post_type['cptg_supports'],
							'taxonomies'          => $cptg_post_type['cptg_builtin_taxonomies'],
						);
						if ( $cptg_post_type['cptg_name'] != 'no_name' ) {
							register_post_type( $cptg_post_type['cptg_name'], $args );
						}
					}
				}
			}
		}

		// query custom taxonomies
		$get_cptg_tax    = array(
			'numberposts'      => -1,
			'post_type'        => 'cptg_tax',
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);
		$cptg_taxonomies = get_posts( $get_cptg_tax );

		// create array of post meta
		if ( $cptg_taxonomies ) {
			foreach ( $cptg_taxonomies as $cptg_tax ) {
				$cptg_meta = get_post_meta( $cptg_tax->ID, '', true );

				// text
				$cptg_tax_name                = ( array_key_exists( 'cptg_tax_name', $cptg_meta ) && $cptg_meta['cptg_tax_name'][0] ? esc_html( $cptg_meta['cptg_tax_name'][0] ) : 'no_name' );
				$cptg_tax_label               = ( array_key_exists( 'cptg_tax_label', $cptg_meta ) && $cptg_meta['cptg_tax_label'][0] ? esc_html( $cptg_meta['cptg_tax_label'][0] ) : $cptg_tax_name );
				$cptg_tax_singular_name       = ( array_key_exists( 'cptg_tax_singular_name', $cptg_meta ) && $cptg_meta['cptg_tax_singular_name'][0] ? esc_html( $cptg_meta['cptg_tax_singular_name'][0] ) : $cptg_tax_label );
				$cptg_tax_custom_rewrite_slug = ( array_key_exists( 'cptg_tax_custom_rewrite_slug', $cptg_meta ) && $cptg_meta['cptg_tax_custom_rewrite_slug'][0] ? esc_html( $cptg_meta['cptg_tax_custom_rewrite_slug'][0] ) : $cptg_tax_name );

				// dropdown
				$cptg_tax_show_ui           = ( array_key_exists( 'cptg_tax_show_ui', $cptg_meta ) && $cptg_meta['cptg_tax_show_ui'][0] == '1' ? true : false );
				$cptg_tax_hierarchical      = ( array_key_exists( 'cptg_tax_hierarchical', $cptg_meta ) && $cptg_meta['cptg_tax_hierarchical'][0] == '1' ? true : false );
				$cptg_tax_rewrite           = ( array_key_exists( 'cptg_tax_rewrite', $cptg_meta ) && $cptg_meta['cptg_tax_rewrite'][0] == '1' ? array( 'slug' => _x( $cptg_tax_custom_rewrite_slug, 'URL Slug', 'cpt-generator' ) ) : false );
				$cptg_tax_query_var         = ( array_key_exists( 'cptg_tax_query_var', $cptg_meta ) && $cptg_meta['cptg_tax_query_var'][0] == '1' ? true : false );
				$cptg_tax_show_in_rest      = ( array_key_exists( 'cptg_tax_show_in_rest', $cptg_meta ) && $cptg_meta['cptg_tax_show_in_rest'][0] == '1' ? true : false );
				$cptg_tax_show_admin_column = ( array_key_exists( 'cptg_tax_show_admin_column', $cptg_meta ) && $cptg_meta['cptg_tax_show_admin_column'][0] == '1' ? true : false );

				// checkbox
				$cptg_tax_post_types = ( array_key_exists( 'cptg_tax_post_types', $cptg_meta ) && $cptg_meta['cptg_tax_post_types'][0] ? $cptg_meta['cptg_tax_post_types'][0] : 'a:0:{}' );

				$cptg_taxs[] = array(
					'cptg_tax_id'                  => $cptg_tax->ID,
					'cptg_tax_name'                => $cptg_tax_name,
					'cptg_tax_label'               => $cptg_tax_label,
					'cptg_tax_singular_name'       => $cptg_tax_singular_name,
					'cptg_tax_custom_rewrite_slug' => $cptg_tax_custom_rewrite_slug,
					'cptg_tax_show_ui'             => (bool) $cptg_tax_show_ui,
					'cptg_tax_hierarchical'        => (bool) $cptg_tax_hierarchical,
					'cptg_tax_rewrite'             => $cptg_tax_rewrite,
					'cptg_tax_query_var'           => (bool) $cptg_tax_query_var,
					'cptg_tax_show_in_rest'        => (bool) $cptg_tax_show_in_rest,
					'cptg_tax_show_admin_column'   => (bool) $cptg_tax_show_admin_column,
					'cptg_tax_builtin_taxonomies'  => unserialize( $cptg_tax_post_types ),
				);

				// register custom post types
				if ( is_array( $cptg_taxs ) ) {
					foreach ( $cptg_taxs as $cptg_taxonomy ) {

						$labels = array(
							'name'                       => _x( $cptg_taxonomy['cptg_tax_label'], 'taxonomy general name', 'cpt-generator' ),
							'singular_name'              => _x( $cptg_taxonomy['cptg_tax_singular_name'], 'taxonomy singular name' ),
							'search_items'               => __( 'Search ' . $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
							'popular_items'              => __( 'Popular ' . $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
							'all_items'                  => __( $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
							'parent_item'                => __( 'Parent ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' ),
							'parent_item_colon'          => __( 'Parent ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' . ':' ),
							'edit_item'                  => __( 'Edit ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' ),
							'update_item'                => __( 'Update ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' ),
							'add_new_item'               => __( 'Add New ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' ),
							'new_item_name'              => __( 'New ' . $cptg_taxonomy['cptg_tax_singular_name'], 'cpt-generator' . ' Name' ),
							'separate_items_with_commas' => __( 'Seperate ' . $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' . ' with commas' ),
							'add_or_remove_items'        => __( 'Add or remove ' . $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
							'choose_from_most_used'      => __( 'Choose from the most used ' . $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
							'menu_name'                  => __( $cptg_taxonomy['cptg_tax_label'], 'cpt-generator' ),
						);

						$args = array(
							'label'             => $cptg_taxonomy['cptg_tax_label'],
							'labels'            => $labels,
							'rewrite'           => $cptg_taxonomy['cptg_tax_rewrite'],
							'show_ui'           => $cptg_taxonomy['cptg_tax_show_ui'],
							'hierarchical'      => $cptg_taxonomy['cptg_tax_hierarchical'],
							'query_var'         => $cptg_taxonomy['cptg_tax_query_var'],
							'show_in_rest'      => $cptg_taxonomy['cptg_tax_show_in_rest'],
							'show_admin_column' => $cptg_taxonomy['cptg_tax_show_admin_column'],
						);

						if ( $cptg_taxonomy['cptg_tax_name'] != 'no_name' ) {
							register_taxonomy( $cptg_taxonomy['cptg_tax_name'], $cptg_taxonomy['cptg_tax_builtin_taxonomies'], $args );
						}
					}
				}
			}
		}
	}

	/**
	 * Create admin meta boxes
	 */
	public function cptg_create_meta_boxes() {
		// add options meta box
		add_meta_box(
			'cptg_options',
			__( 'Options', 'cpt-generator' ),
			array( $this, 'cptg_meta_box' ),
			'cptg',
			'advanced',
			'high'
		);
		add_meta_box(
			'cptg_tax_options',
			__( 'Options', 'cpt-generator' ),
			array( $this, 'cptg_tax_meta_box' ),
			'cptg_tax',
			'advanced',
			'high'
		);
	}

	/**
	 * Create custom post meta box
	 *
	 * @param  object $post WordPress $post object
	 */
	public function cptg_meta_box( $post ) {
		// get post meta values
		$values = get_post_custom( $post->ID );

		// text fields
		$cptg_name          = isset( $values['cptg_name'] ) ? esc_attr( $values['cptg_name'][0] ) : '';
		$cptg_label         = isset( $values['cptg_label'] ) ? esc_attr( $values['cptg_label'][0] ) : '';
		$cptg_singular_name = isset( $values['cptg_singular_name'] ) ? esc_attr( $values['cptg_singular_name'][0] ) : '';
		$cptg_description   = isset( $values['cptg_description'] ) ? esc_attr( $values['cptg_description'][0] ) : '';

		// Custom post icon (uploaded)
		$cptg_icon_url = isset( $values['cptg_icon_url'] ) ? esc_attr( $values['cptg_icon_url'][0] ) : '';

		// Custom post icon (dashicons)
		$cptg_icon_slug = isset( $values['cptg_icon_slug'] ) ? esc_attr( $values['cptg_icon_slug'][0] ) : '';

		// If DashIcon is set ignore uploaded
		if ( ! empty( $cptg_icon_slug ) ) {
			$cptg_icon_name = $cptg_icon_slug;
		} else {
			$cptg_icon_name = $cptg_icon_url;
		}

		$cptg_custom_rewrite_slug = isset( $values['cptg_custom_rewrite_slug'] ) ? esc_attr( $values['cptg_custom_rewrite_slug'][0] ) : '';
		$cptg_menu_position       = isset( $values['cptg_menu_position'] ) ? esc_attr( $values['cptg_menu_position'][0] ) : '';

		// select fields
		$cptg_public              = isset( $values['cptg_public'] ) ? esc_attr( $values['cptg_public'][0] ) : '';
		$cptg_show_ui             = isset( $values['cptg_show_ui'] ) ? esc_attr( $values['cptg_show_ui'][0] ) : '';
		$cptg_has_archive         = isset( $values['cptg_has_archive'] ) ? esc_attr( $values['cptg_has_archive'][0] ) : '';
		$cptg_exclude_from_search = isset( $values['cptg_exclude_from_search'] ) ? esc_attr( $values['cptg_exclude_from_search'][0] ) : '';
		$cptg_capability_type     = isset( $values['cptg_capability_type'] ) ? esc_attr( $values['cptg_capability_type'][0] ) : '';
		$cptg_hierarchical        = isset( $values['cptg_hierarchical'] ) ? esc_attr( $values['cptg_hierarchical'][0] ) : '';
		$cptg_rewrite             = isset( $values['cptg_rewrite'] ) ? esc_attr( $values['cptg_rewrite'][0] ) : '';
		$cptg_withfront           = isset( $values['cptg_withfront'] ) ? esc_attr( $values['cptg_withfront'][0] ) : '';
		$cptg_feeds               = isset( $values['cptg_feeds'] ) ? esc_attr( $values['cptg_feeds'][0] ) : '';
		$cptg_pages               = isset( $values['cptg_pages'] ) ? esc_attr( $values['cptg_pages'][0] ) : '';
		$cptg_query_var           = isset( $values['cptg_query_var'] ) ? esc_attr( $values['cptg_query_var'][0] ) : '';
		$cptg_show_in_rest        = isset( $values['cptg_show_in_rest'] ) ? esc_attr( $values['cptg_show_in_rest'][0] ) : '';
		$cptg_publicly_queryable  = isset( $values['cptg_publicly_queryable'] ) ? esc_attr( $values['cptg_publicly_queryable'][0] ) : '';
		$cptg_show_in_menu        = isset( $values['cptg_show_in_menu'] ) ? esc_attr( $values['cptg_show_in_menu'][0] ) : '';

		// checkbox fields
		$cptg_supports                 = isset( $values['cptg_supports'] ) ? unserialize( $values['cptg_supports'][0] ) : array();
		$cptg_supports_title           = ( isset( $values['cptg_supports'] ) && in_array( 'title', $cptg_supports ) ? 'title' : '' );
		$cptg_supports_editor          = ( isset( $values['cptg_supports'] ) && in_array( 'editor', $cptg_supports ) ? 'editor' : '' );
		$cptg_supports_excerpt         = ( isset( $values['cptg_supports'] ) && in_array( 'excerpt', $cptg_supports ) ? 'excerpt' : '' );
		$cptg_supports_trackbacks      = ( isset( $values['cptg_supports'] ) && in_array( 'trackbacks', $cptg_supports ) ? 'trackbacks' : '' );
		$cptg_supports_custom_fields   = ( isset( $values['cptg_supports'] ) && in_array( 'custom-fields', $cptg_supports ) ? 'custom-fields' : '' );
		$cptg_supports_comments        = ( isset( $values['cptg_supports'] ) && in_array( 'comments', $cptg_supports ) ? 'comments' : '' );
		$cptg_supports_revisions       = ( isset( $values['cptg_supports'] ) && in_array( 'revisions', $cptg_supports ) ? 'revisions' : '' );
		$cptg_supports_featured_image  = ( isset( $values['cptg_supports'] ) && in_array( 'thumbnail', $cptg_supports ) ? 'thumbnail' : '' );
		$cptg_supports_author          = ( isset( $values['cptg_supports'] ) && in_array( 'author', $cptg_supports ) ? 'author' : '' );
		$cptg_supports_page_attributes = ( isset( $values['cptg_supports'] ) && in_array( 'page-attributes', $cptg_supports ) ? 'page-attributes' : '' );
		$cptg_supports_post_formats    = ( isset( $values['cptg_supports'] ) && in_array( 'post-formats', $cptg_supports ) ? 'post-formats' : '' );

		$cptg_builtin_taxonomies            = isset( $values['cptg_builtin_taxonomies'] ) ? unserialize( $values['cptg_builtin_taxonomies'][0] ) : array();
		$cptg_builtin_taxonomies_categories = ( isset( $values['cptg_builtin_taxonomies'] ) && in_array( 'category', $cptg_builtin_taxonomies ) ? 'category' : '' );
		$cptg_builtin_taxonomies_tags       = ( isset( $values['cptg_builtin_taxonomies'] ) && in_array( 'post_tag', $cptg_builtin_taxonomies ) ? 'post_tag' : '' );

		// nonce
		wp_nonce_field( 'cptg_meta_box_nonce_action', 'cptg_meta_box_nonce_field' );

		// set defaults if new Custom Post Type is being created
		global $pagenow;
		$cptg_supports_title   = $pagenow === 'post-new.php' ? 'title' : $cptg_supports_title;
		$cptg_supports_editor  = $pagenow === 'post-new.php' ? 'editor' : $cptg_supports_editor;
		$cptg_supports_excerpt = $pagenow === 'post-new.php' ? 'excerpt' : $cptg_supports_excerpt;
		?>
		<table class="cptg">
			<tr>
				<td class="label">
					<label for="cptg_name"><span class="required">*</span> <?php _e( 'Custom Post Type Name', 'cpt-generator' ); ?></label>
					<p><?php _e( 'The post type name. Used to retrieve custom post type content. Must be all in lower-case and without any spaces.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. movies', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_name" id="cptg_name" class="widefat" tabindex="1" value="<?php echo $cptg_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_label"><?php _e( 'Label', 'cpt-generator' ); ?></label>
					<p><?php _e( 'A plural descriptive name for the post type.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. Movies', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_label" id="cptg_label" class="widefat" tabindex="2" value="<?php echo $cptg_label; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_singular_name"><?php _e( 'Singular Name', 'cpt-generator' ); ?></label>
					<p><?php _e( 'A singular descriptive name for the post type.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. Movie', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_singular_name" id="cptg_singular_name" class="widefat" tabindex="3" value="<?php echo $cptg_singular_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptg_description"><?php _e( 'Description', 'cpt-generator' ); ?></label>
					<p><?php _e( 'A short descriptive summary of what the post type is.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<textarea name="cptg_description" id="cptg_description" class="widefat" tabindex="4" rows="4"><?php echo $cptg_description; ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Visibility', 'cpt-generator' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_public"><?php _e( 'Public', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether a post type is intended to be used publicly either via the admin interface or by front-end users.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_public" id="cptg_public" tabindex="5">
						<option value="1" <?php selected( $cptg_public, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_public, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Rewrite Options', 'cpt-generator' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_rewrite"><?php _e( 'Rewrite', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Triggers the handling of rewrites for this post type.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_rewrite" id="cptg_rewrite" tabindex="6">
						<option value="1" <?php selected( $cptg_rewrite, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_rewrite, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_withfront"><?php _e( 'With Front', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Should the permastruct be prepended with the front base.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_withfront" id="cptg_withfront" tabindex="7">
						<option value="1" <?php selected( $cptg_withfront, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_withfront, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_custom_rewrite_slug"><?php _e( 'Custom Rewrite Slug', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Customize the permastruct slug.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'Default: [Custom Post Type Name]', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_custom_rewrite_slug" id="cptg_custom_rewrite_slug" class="widefat" tabindex="8" value="<?php echo $cptg_custom_rewrite_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Front-end Options', 'cpt-generator' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_feeds"><?php _e( 'Feeds', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Should a feed permastruct be built for this post type. Defaults to "has_archive" value.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_feeds" id="cptg_feeds" tabindex="9">
						<option value="0" <?php selected( $cptg_feeds, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="1" <?php selected( $cptg_feeds, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_pages"><?php _e( 'Pages', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Should the permastruct provide for pagination.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_pages" id="cptg_pages" tabindex="10">
						<option value="1" <?php selected( $cptg_pages, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_pages, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_exclude_from_search"><?php _e( 'Exclude From Search', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether to exclude posts with this post type from front end search results.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_exclude_from_search" id="cptg_exclude_from_search" tabindex="11">
						<option value="0" <?php selected( $cptg_exclude_from_search, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="1" <?php selected( $cptg_exclude_from_search, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_has_archive"><?php _e( 'Has Archive', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Enables post type archives.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_has_archive" id="cptg_has_archive" tabindex="12">
						<option value="0" <?php selected( $cptg_has_archive, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="1" <?php selected( $cptg_has_archive, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Admin Menu Options', 'cpt-generator' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_show_ui"><?php _e( 'Show UI', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether to generate a default UI for managing this post type in the admin.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_show_ui" id="cptg_show_ui" tabindex="13">
						<option value="1" <?php selected( $cptg_show_ui, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_show_ui, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_menu_position"><?php _e( 'Menu Position', 'cpt-generator' ); ?></label>
					<p><?php _e( 'The position in the menu order the post type should appear. "Show in Menu" must be true.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_menu_position" id="cptg_menu_position" class="widefat" tabindex="14" value="<?php echo $cptg_menu_position; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_show_in_menu"><?php _e( 'Show in Menu', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Where to show the post type in the admin menu. "Show UI" must be true.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_show_in_menu" id="cptg_show_in_menu" tabindex="15">
						<option value="1" <?php selected( $cptg_show_in_menu, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_show_in_menu, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="current-cptg-icon"><?php _e( 'Icon', 'cpt-generator' ); ?></label>
					<p><?php _e( 'This icon will be overriden if a Dash Icon is specified in the field below.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<div class="cptg-icon">
						<div class="current-cptg-icon">
						<?php if ( $cptg_icon_url ) { ?><img src="<?php echo $cptg_icon_url; ?>" /><?php } ?></div>
						<a href="/" class="remove-cptg-icon button-secondary"<?php if ( ! $cptg_icon_url ) { ?> style="display: none;"<?php } ?> tabindex="16">Remove Icon</a>
						<a  href="/"class="media-uploader-button button-primary" data-post-id="<?php echo $post->ID; ?>" tabindex="17"><?php if ( ! $cptg_icon_url ) { ?><?php _e( 'Add icon', 'cpt-generator' ); ?><?php } else { ?><?php _e( 'Upload Icon', 'cpt-generator' ); ?><?php } ?></a>
					</div>
					<input type="hidden" name="cptg_icon_url" id="cptg_icon_url" class="widefat" value="<?php echo $cptg_icon_url; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_icon_slug"><?php _e( 'Slug Icon', 'cpt-generator' ); ?></label>
					<p><?php _e( 'This uses (<a href="https://developer.WordPress.org/resource/dashicons/">Dash Icons</a>) and <strong>overrides</strong> the uploaded icon above.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<?php if ( $cptg_icon_slug ) { ?><span id="cptg_icon_slug_before" class="dashicons-before <?php echo $cptg_icon_slug; ?>"><?php } ?></span>
					<input type="text" name="cptg_icon_slug" id="cptg_icon_slug" class="widefat" tabindex="18" value="<?php echo $cptg_icon_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'WordPress Integration', 'cpt-generator' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_capability_type"><?php _e( 'Capability Type', 'cpt-generator' ); ?></label>
					<p><?php _e( 'The post type to use to build the read, edit, and delete capabilities.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_capability_type" id="cptg_capability_type" tabindex="18">
						<option value="post" <?php selected( $cptg_capability_type, 'post' ); ?>><?php _e( 'Post', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="page" <?php selected( $cptg_capability_type, 'page' ); ?>><?php _e( 'Page', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_hierarchical"><?php _e( 'Hierarchical', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether the post type is hierarchical (e.g. page).', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_hierarchical" id="cptg_hierarchical" tabindex="19">
						<option value="0" <?php selected( $cptg_hierarchical, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="1" <?php selected( $cptg_hierarchical, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_query_var"><?php _e( 'Query Var', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Sets the query_var key for this post type.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_query_var" id="cptg_query_var" tabindex="20">
						<option value="1" <?php selected( $cptg_query_var, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_query_var, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_show_in_rest"><?php _e( 'Show in REST', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Sets the show_in_rest key for this post type.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_show_in_rest" id="cptg_show_in_rest" tabindex="21">
						<option value="1" <?php selected( $cptg_show_in_rest, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_show_in_rest, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_publicly_queryable"><?php _e( 'Publicly Queryable', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether the post is visible on the front-end.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_publicly_queryable" id="cptg_publicly_queryable" tabindex="22">
						<option value="1" <?php selected( $cptg_publicly_queryable, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_publicly_queryable, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptg_supports"><?php _e( 'Supports', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Adds the respective meta boxes when creating content for this Custom Post Type.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="checkbox" tabindex="23" name="cptg_supports[]" id="cptg_supports_title" value="title" <?php checked( $cptg_supports_title, 'title' ); ?> /> <label for="cptg_supports_title"><?php _e( 'Title', 'cpt-generator' ); ?> <span class="default">(<?php _e( 'default', 'cpt-generator' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="24" name="cptg_supports[]" id="cptg_supports_editor" value="editor" <?php checked( $cptg_supports_editor, 'editor' ); ?> /> <label for="cptg_supports_editor"><?php _e( 'Editor', 'cpt-generator' ); ?> <span class="default">(<?php _e( 'default', 'cpt-generator' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="25" name="cptg_supports[]" id="cptg_supports_excerpt" value="excerpt" <?php checked( $cptg_supports_excerpt, 'excerpt' ); ?> /> <label for="cptg_supports_excerpt"><?php _e( 'Excerpt', 'cpt-generator' ); ?> <span class="default">(<?php _e( 'default', 'cpt-generator' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="26" name="cptg_supports[]" id="cptg_supports_trackbacks" value="trackbacks" <?php checked( $cptg_supports_trackbacks, 'trackbacks' ); ?> /> <label for="cptg_supports_trackbacks"><?php _e( 'Trackbacks', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="27" name="cptg_supports[]" id="cptg_supports_custom_fields" value="custom-fields" <?php checked( $cptg_supports_custom_fields, 'custom-fields' ); ?> /> <label for="cptg_supports_custom_fields"><?php _e( 'Custom Fields', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="28" name="cptg_supports[]" id="cptg_supports_comments" value="comments" <?php checked( $cptg_supports_comments, 'comments' ); ?> /> <label for="cptg_supports_comments"><?php _e( 'Comments', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="29" name="cptg_supports[]" id="cptg_supports_revisions" value="revisions" <?php checked( $cptg_supports_revisions, 'revisions' ); ?> /> <label for="cptg_supports_revisions"><?php _e( 'Revisions', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="30" name="cptg_supports[]" id="cptg_supports_featured_image" value="thumbnail" <?php checked( $cptg_supports_featured_image, 'thumbnail' ); ?> /> <label for="cptg_supports_featured_image"><?php _e( 'Featured Image', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="31" name="cptg_supports[]" id="cptg_supports_author" value="author" <?php checked( $cptg_supports_author, 'author' ); ?> /> <label for="cptg_supports_author"><?php _e( 'Author', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="32" name="cptg_supports[]" id="cptg_supports_page_attributes" value="page-attributes" <?php checked( $cptg_supports_page_attributes, 'page-attributes' ); ?> /> <label for="cptg_supports_page_attributes"><?php _e( 'Page Attributes', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="33" name="cptg_supports[]" id="cptg_supports_post_formats" value="post-formats" <?php checked( $cptg_supports_post_formats, 'post-formats' ); ?> /> <label for="cptg_supports_post_formats"><?php _e( 'Post Formats', 'cpt-generator' ); ?></label><br />
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptg_builtin_taxonomies"><?php _e( 'Built-in Taxonomies', 'cpt-generator' ); ?></label>
					<p>&nbsp;</p>
				</td>
				<td>
					<input type="checkbox" tabindex="34" name="cptg_builtin_taxonomies[]" id="cptg_builtin_taxonomies_categories" value="category" <?php checked( $cptg_builtin_taxonomies_categories, 'category' ); ?> /> <label for="cptg_builtin_taxonomies_categories"><?php _e( 'Categories', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="35" name="cptg_builtin_taxonomies[]" id="cptg_builtin_taxonomies_tags" value="post_tag" <?php checked( $cptg_builtin_taxonomies_tags, 'post_tag' ); ?> /> <label for="cptg_builtin_taxonomies_tags"><?php _e( 'Tags', 'cpt-generator' ); ?></label><br />
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Create custom post taxonomy meta box
	 *
	 * @param  object $post WordPress $post object
	 */
	public function cptg_tax_meta_box( $post ) {
		// get post meta values
		$values = get_post_custom( $post->ID );

		// text fields
		$cptg_tax_name                = isset( $values['cptg_tax_name'] ) ? esc_attr( $values['cptg_tax_name'][0] ) : '';
		$cptg_tax_label               = isset( $values['cptg_tax_label'] ) ? esc_attr( $values['cptg_tax_label'][0] ) : '';
		$cptg_tax_singular_name       = isset( $values['cptg_tax_singular_name'] ) ? esc_attr( $values['cptg_tax_singular_name'][0] ) : '';
		$cptg_tax_custom_rewrite_slug = isset( $values['cptg_tax_custom_rewrite_slug'] ) ? esc_attr( $values['cptg_tax_custom_rewrite_slug'][0] ) : '';

		// select fields
		$cptg_tax_show_ui           = isset( $values['cptg_tax_show_ui'] ) ? esc_attr( $values['cptg_tax_show_ui'][0] ) : '';
		$cptg_tax_hierarchical      = isset( $values['cptg_tax_hierarchical'] ) ? esc_attr( $values['cptg_tax_hierarchical'][0] ) : '';
		$cptg_tax_rewrite           = isset( $values['cptg_tax_rewrite'] ) ? esc_attr( $values['cptg_tax_rewrite'][0] ) : '';
		$cptg_tax_query_var         = isset( $values['cptg_tax_query_var'] ) ? esc_attr( $values['cptg_tax_query_var'][0] ) : '';
		$cptg_tax_show_in_rest      = isset( $values['cptg_tax_show_in_rest'] ) ? esc_attr( $values['cptg_tax_show_in_rest'][0] ) : '';
		$cptg_tax_show_admin_column = isset( $values['cptg_tax_show_admin_column'] ) ? esc_attr( $values['cptg_tax_show_admin_column'][0] ) : '';

		$cptg_tax_post_types      = isset( $values['cptg_tax_post_types'] ) ? unserialize( $values['cptg_tax_post_types'][0] ) : array();
		$cptg_tax_post_types_post = ( isset( $values['cptg_tax_post_types'] ) && in_array( 'post', $cptg_tax_post_types ) ? 'post' : '' );
		$cptg_tax_post_types_page = ( isset( $values['cptg_tax_post_types'] ) && in_array( 'page', $cptg_tax_post_types ) ? 'page' : '' );

		// nonce
		wp_nonce_field( 'cptg_meta_box_nonce_action', 'cptg_meta_box_nonce_field' );
		?>
		<table class="cptg">
			<tr>
				<td class="label">
					<label for="cptg_tax_name"><span class="required">*</span> <?php _e( 'Custom Taxonomy Name', 'cpt-generator' ); ?></label>
					<p><?php _e( 'The taxonomy name (use lowercase only). Used to retrieve custom taxonomy content. Must be all in lower-case and without any spaces.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. movies', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_tax_name" id="cptg_tax_name" class="widefat" tabindex="1" value="<?php echo $cptg_tax_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_label"><?php _e( 'Label', 'cpt-generator' ); ?></label>
					<p><?php _e( 'A plural descriptive name for the taxonomy.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. Movies', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_tax_label" id="cptg_tax_label" class="widefat" tabindex="2" value="<?php echo $cptg_tax_label; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_singular_name"><?php _e( 'Singular Name', 'cpt-generator' ); ?></label>
					<p><?php _e( 'A singular descriptive name for the taxonomy.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'e.g. Movie', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_tax_singular_name" id="cptg_tax_singular_name" class="widefat" tabindex="3" value="<?php echo $cptg_tax_singular_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_show_ui"><?php _e( 'Show UI', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether to generate a default UI for managing this taxonomy in the admin.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_show_ui" id="cptg_tax_show_ui" tabindex="4">
						<option value="1" <?php selected( $cptg_tax_show_ui, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_tax_show_ui, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_hierarchical"><?php _e( 'Hierarchical', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Whether the taxonomy is hierarchical (e.g. page).', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_hierarchical" id="cptg_tax_hierarchical" tabindex="5">
						<option value="0" <?php selected( $cptg_tax_hierarchical, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="1" <?php selected( $cptg_tax_hierarchical, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_rewrite"><?php _e( 'Rewrite', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Triggers the handling of rewrites for this taxonomy.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_rewrite" id="cptg_tax_rewrite" tabindex="6">
						<option value="1" <?php selected( $cptg_tax_rewrite, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_tax_rewrite, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_custom_rewrite_slug"><?php _e( 'Custom Rewrite Slug', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Customize the permastruct slug.', 'cpt-generator' ); ?></p>
					<p><?php _e( 'Default: [Custom Taxonomy Name]', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptg_tax_custom_rewrite_slug" id="cptg_tax_custom_rewrite_slug" class="widefat" tabindex="7" value="<?php echo $cptg_tax_custom_rewrite_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_query_var"><?php _e( 'Query Var', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Sets the query_var key for this taxonomy.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_query_var" id="cptg_tax_query_var" tabindex="8">
						<option value="1" <?php selected( $cptg_tax_query_var, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_tax_query_var, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptg_tax_show_in_rest"><?php _e( 'Show in REST', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Sets the show_in_rest key for this taxonomy.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_show_in_rest" id="cptg_tax_show_in_rest" tabindex="9">
						<option value="1" <?php selected( $cptg_tax_show_in_rest, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_tax_show_in_rest, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			<tr>
				<td class="label">
					<label for="cptg_tax_show_admin_column"><?php _e( 'Admin Column', 'cpt-generator' ); ?></label>
					<p><?php _e( 'Show this taxonomy as a column in the custom post listing.', 'cpt-generator' ); ?></p>
				</td>
				<td>
					<select name="cptg_tax_show_admin_column" id="cptg_tax_show_admin_column" tabindex="10">
						<option value="1" <?php selected( $cptg_tax_show_admin_column, '1' ); ?>><?php _e( 'True', 'cpt-generator' ); ?> (<?php _e( 'default', 'cpt-generator' ); ?>)</option>
						<option value="0" <?php selected( $cptg_tax_show_admin_column, '0' ); ?>><?php _e( 'False', 'cpt-generator' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptg_tax_post_types"><?php _e( 'Post Types', 'cpt-generator' ); ?></label>
					<p>&nbsp;</p>
				</td>
				<td>
					<input type="checkbox" tabindex="11" name="cptg_tax_post_types[]" id="cptg_tax_post_types_post" value="post" <?php checked( $cptg_tax_post_types_post, 'post' ); ?> /> <label for="cptg_tax_post_types_post"><?php _e( 'Posts', 'cpt-generator' ); ?></label><br />
					<input type="checkbox" tabindex="12" name="cptg_tax_post_types[]" id="cptg_tax_post_types_page" value="page" <?php checked( $cptg_tax_post_types_page, 'page' ); ?> /> <label for="cptg_tax_post_types_page"><?php _e( 'Pages', 'cpt-generator' ); ?></label><br />
					<?php
					$post_types = get_post_types(
						array(
							'public'   => true,
							'_builtin' => false,
						)
					);

					$i = 13;
					foreach ( $post_types as $post_type ) {
						$checked = in_array( $post_type, $cptg_tax_post_types ) ? 'checked="checked"' : '';
						?>
						<input type="checkbox" tabindex="<?php echo $i; ?>" name="cptg_tax_post_types[]" id="cptg_tax_post_types_<?php echo $post_type; ?>" value="<?php echo $post_type; ?>" <?php echo $checked; ?> /> <label for="cptg_tax_post_types_<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></label><br />
						<?php
						$i++;
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save custom post
	 *
	 * @param  int $post_id WordPress Post ID
	 */
	public function cptg_save_post( $post_id ) {
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail
		if ( ! isset( $_POST['cptg_meta_box_nonce_field'] ) || ! wp_verify_nonce( $_POST['cptg_meta_box_nonce_field'], 'cptg_meta_box_nonce_action' ) ) {
			return;
		}

		// update custom post type meta values
		if ( isset( $_POST['cptg_name'] ) ) {
			update_post_meta( $post_id, 'cptg_name', sanitize_text_field( strtolower( str_replace( ' ', '', $_POST['cptg_name'] ) ) ) );
		}

		if ( isset( $_POST['cptg_label'] ) ) {
			update_post_meta( $post_id, 'cptg_label', sanitize_text_field( $_POST['cptg_label'] ) );
		}

		if ( isset( $_POST['cptg_singular_name'] ) ) {
			update_post_meta( $post_id, 'cptg_singular_name', sanitize_text_field( $_POST['cptg_singular_name'] ) );
		}

		if ( isset( $_POST['cptg_description'] ) ) {
			update_post_meta( $post_id, 'cptg_description', esc_textarea( $_POST['cptg_description'] ) );
		}

		if ( isset( $_POST['cptg_icon_slug'] ) ) {
			update_post_meta( $post_id, 'cptg_icon_slug', esc_textarea( $_POST['cptg_icon_slug'] ) );
		}

		if ( isset( $_POST['cptg_icon_url'] ) ) {
			update_post_meta( $post_id, 'cptg_icon_url', esc_textarea( $_POST['cptg_icon_url'] ) );
		}

		if ( isset( $_POST['cptg_public'] ) ) {
			update_post_meta( $post_id, 'cptg_public', esc_attr( $_POST['cptg_public'] ) );
		}

		if ( isset( $_POST['cptg_show_ui'] ) ) {
			update_post_meta( $post_id, 'cptg_show_ui', esc_attr( $_POST['cptg_show_ui'] ) );
		}

		if ( isset( $_POST['cptg_has_archive'] ) ) {
			update_post_meta( $post_id, 'cptg_has_archive', esc_attr( $_POST['cptg_has_archive'] ) );
		}

		if ( isset( $_POST['cptg_exclude_from_search'] ) ) {
			update_post_meta( $post_id, 'cptg_exclude_from_search', esc_attr( $_POST['cptg_exclude_from_search'] ) );
		}

		if ( isset( $_POST['cptg_capability_type'] ) ) {
			update_post_meta( $post_id, 'cptg_capability_type', esc_attr( $_POST['cptg_capability_type'] ) );
		}

		if ( isset( $_POST['cptg_hierarchical'] ) ) {
			update_post_meta( $post_id, 'cptg_hierarchical', esc_attr( $_POST['cptg_hierarchical'] ) );
		}

		if ( isset( $_POST['cptg_rewrite'] ) ) {
			update_post_meta( $post_id, 'cptg_rewrite', esc_attr( $_POST['cptg_rewrite'] ) );
		}

		if ( isset( $_POST['cptg_withfront'] ) ) {
			update_post_meta( $post_id, 'cptg_withfront', esc_attr( $_POST['cptg_withfront'] ) );
		}

		if ( isset( $_POST['cptg_feeds'] ) ) {
			update_post_meta( $post_id, 'cptg_feeds', esc_attr( $_POST['cptg_feeds'] ) );
		}

		if ( isset( $_POST['cptg_pages'] ) ) {
			update_post_meta( $post_id, 'cptg_pages', esc_attr( $_POST['cptg_pages'] ) );
		}

		if ( isset( $_POST['cptg_custom_rewrite_slug'] ) ) {
			update_post_meta( $post_id, 'cptg_custom_rewrite_slug', sanitize_text_field( $_POST['cptg_custom_rewrite_slug'] ) );
		}

		if ( isset( $_POST['cptg_query_var'] ) ) {
			update_post_meta( $post_id, 'cptg_query_var', esc_attr( $_POST['cptg_query_var'] ) );
		}

		if ( isset( $_POST['cptg_show_in_rest'] ) ) {
			update_post_meta( $post_id, 'cptg_show_in_rest', esc_attr( $_POST['cptg_show_in_rest'] ) );
		}

		if ( isset( $_POST['cptg_publicly_queryable'] ) ) {
			update_post_meta( $post_id, 'cptg_publicly_queryable', esc_attr( $_POST['cptg_publicly_queryable'] ) );
		}

		if ( isset( $_POST['cptg_menu_position'] ) ) {
			update_post_meta( $post_id, 'cptg_menu_position', sanitize_text_field( $_POST['cptg_menu_position'] ) );
		}

		if ( isset( $_POST['cptg_show_in_menu'] ) ) {
			update_post_meta( $post_id, 'cptg_show_in_menu', esc_attr( $_POST['cptg_show_in_menu'] ) );
		}

		$cptg_supports = isset( $_POST['cptg_supports'] ) ? $_POST['cptg_supports'] : array(); {
			update_post_meta( $post_id, 'cptg_supports', $cptg_supports );
		}

		$cptg_builtin_taxonomies = isset( $_POST['cptg_builtin_taxonomies'] ) ? $_POST['cptg_builtin_taxonomies'] : array();
		update_post_meta( $post_id, 'cptg_builtin_taxonomies', $cptg_builtin_taxonomies );

		// Update taxonomy meta values
		if ( isset( $_POST['cptg_tax_name'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_name', sanitize_text_field( strtolower( str_replace( ' ', '', $_POST['cptg_tax_name'] ) ) ) );
		}

		if ( isset( $_POST['cptg_tax_label'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_label', sanitize_text_field( $_POST['cptg_tax_label'] ) );
		}

		if ( isset( $_POST['cptg_tax_singular_name'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_singular_name', sanitize_text_field( $_POST['cptg_tax_singular_name'] ) );
		}

		if ( isset( $_POST['cptg_tax_show_ui'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_show_ui', esc_attr( $_POST['cptg_tax_show_ui'] ) );
		}

		if ( isset( $_POST['cptg_tax_hierarchical'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_hierarchical', esc_attr( $_POST['cptg_tax_hierarchical'] ) );
		}

		if ( isset( $_POST['cptg_tax_rewrite'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_rewrite', esc_attr( $_POST['cptg_tax_rewrite'] ) );
		}

		if ( isset( $_POST['cptg_tax_custom_rewrite_slug'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_custom_rewrite_slug', sanitize_text_field( $_POST['cptg_tax_custom_rewrite_slug'] ) );
		}

		if ( isset( $_POST['cptg_tax_query_var'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_query_var', esc_attr( $_POST['cptg_tax_query_var'] ) );
		}

		if ( isset( $_POST['cptg_tax_show_in_rest'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_show_in_rest', esc_attr( $_POST['cptg_tax_show_in_rest'] ) );
		}

		if ( isset( $_POST['cptg_tax_show_admin_column'] ) ) {
			update_post_meta( $post_id, 'cptg_tax_show_admin_column', esc_attr( $_POST['cptg_tax_show_admin_column'] ) );
		}

		$cptg_tax_post_types = isset( $_POST['cptg_tax_post_types'] ) ? $_POST['cptg_tax_post_types'] : array();
			update_post_meta( $post_id, 'cptg_tax_post_types', $cptg_tax_post_types );

			// Update plugin saved
			update_option( 'cptg_plugin_settings_changed', true );
	}

	/**
	 * Flush rewrite rules
	 */
	function cptg_plugin_settings_flush_rewrite() {
		if ( get_option( 'cptg_plugin_settings_changed' ) == true ) {
			flush_rewrite_rules();
			update_option( 'cptg_plugin_settings_changed', false );
		}
	}

	/**
	 * Flush rewrite rules on plugin activation
	 */
	function cptg_plugin_activate_flush_rewrite() {
		$this->cptg_create_custom_post_types();
		flush_rewrite_rules();
	}

	/**
	 * Modify existing columns
	 *
	 * @param  array $cols  Post columns
	 * @return object       Modified columns
	 */
	function cptg_change_columns( $cols ) {
		$cols = array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Post Type', 'cpt-generator' ),
			'custom_post_type_name' => __( 'Custom Post Type Name', 'cpt-generator' ),
			'label'                 => __( 'Label', 'cpt-generator' ),
			'description'           => __( 'Description', 'cpt-generator' ),
		);
		return $cols;
	}

	/**
	 * Make columns sortable
	 *
	 * @return array Sortable array
	 */
	function cptg_sortable_columns() {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom column
	 *
	 * @param  string $column  Column name
	 * @param  int    $post_id WordPress Post ID
	 */
	function cptg_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo get_post_meta( $post_id, 'cptg_name', true );
				break;
			case 'label':
				echo get_post_meta( $post_id, 'cptg_label', true );
				break;
			case 'description':
				echo get_post_meta( $post_id, 'cptg_description', true );
				break;
		}
	}

	/**
	 * Modify existing taxonomy columns
	 *
	 * @param  array $cols Taxonomy columns
	 * @return array       Modified taxonomy columns
	 */
	function cptg_tax_change_columns( $cols ) {
		$cols = array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Taxonomy', 'cpt-generator' ),
			'custom_post_type_name' => __( 'Custom Taxonomy Name', 'cpt-generator' ),
			'label'                 => __( 'Label', 'cpt-generator' ),
		);
		return $cols;
	}

	/**
	 * Make taxonomy columns sortable
	 *
	 * @return array Sortable array
	 */
	function cptg_tax_sortable_columns() {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom taxonomy columns
	 *
	 * @param  string $column  Column name
	 * @param  int    $post_id WordPress Post ID
	 */
	function cptg_tax_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo get_post_meta( $post_id, 'cptg_tax_name', true );
				break;
			case 'label':
				echo get_post_meta( $post_id, 'cptg_tax_label', true );
				break;
		}
	}

	/**
	 * Insert admin footer
	 */
	function cptg_admin_footer() {
		global $post_type;
		?>
		<div id="cptg-col-right" class="hidden">

			<div class="wp-box">
				<div class="inner">
					<h2><?php _e( 'CPT Generator', 'cpt-generator' ); ?></h2>
					<p class="version"><?php _e( 'Version', 'cpt-generator' ); ?> <?php echo $this->version; ?></p>
					<h3><?php _e( 'Useful links', 'cpt-generator' ); ?></h3>
					<ul>
						<li><a class="thickbox" href="<?php echo admin_url( 'plugin-install.php' ); ?>?tab=plugin-information&plugin=cpt-generator-2&section=changelog&TB_iframe=true&width=600&height=550"><?php _e( 'Changelog', 'cpt-generator' ); ?></a></li>
						<li><a href="http://WordPress.org/support/plugin/cpt-generator-2" target="_blank"><?php _e( 'Support Forums', 'cpt-generator' ); ?></a></li>
					</ul>
				</div>
				<div class="footer footer-blue">
					<ul class="left">
						<li><?php _e( 'Created by', 'cptg' ); ?> <a href="http://www.graffino.com" target="_blank" title="Graffino">Graffino</a></li>
			<li></li>
			<li><small>Originally by: http://www.bakhuys.com/</small></li>
					</ul>
					<ul class="right">
						<li><a href="http://WordPress.org/extend/plugins/cpt-generator-2/" target="_blank"><?php _e( 'Vote', 'cpt-generator' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
		if ( 'cptg' == $post_type ) {

			// Get all public Custom Post Types
			$post_types = get_post_types(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'objects'
			);
			// Get all Custom Post Types created by CPT Generator
			$cptg_posts = get_posts( array( 'post_type' => 'cptg' ) );
			// Remove all Custom Post Types created by the CPT Generator plugin
			foreach ( $cptg_posts as $cptg_post ) {
				$values = get_post_custom( $cptg_post->ID );
				unset( $post_types[ $values['cptg_name'][0] ] );
			}

			if ( count( $post_types ) != 0 ) {
				?>
			<div id="cptg-cpt-overview" class="hidden">
				<div id="icon-edit" class="icon32 icon32-posts-cptg"><br></div>
				<h2><?php _e( 'Other registered Custom Post Types', 'cpt-generator' ); ?></h2>
				<p><?php _e( 'The Custom Post Types below are registered in WordPress but were not created by the CPT Generator plugin.', 'cpt-generator' ); ?></p>
				<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column">
							</th>
							<th scope="col" id="title" class="manage-column column-title">
								<span><?php _e( 'Post Type', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="custom_post_type_name" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Post Type Name', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="label" class="manage-column column-label">
								<span><?php _e( 'Label', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="description" class="manage-column column-description">
								<span><?php _e( 'Description', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-cb check-column">
							</th>
							<th scope="col" class="manage-column column-title">
								<span><?php _e( 'Post Type', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Post Type Name', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-label">
								<span><?php _e( 'Label', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-description">
								<span><?php _e( 'Description', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</tfoot>

					<tbody id="the-list">
						<?php
						// Create list of all other registered Custom Post Types
						foreach ( $post_types as $post_type ) {
							?>
						<tr valign="top">
							<th scope="row" class="check-column">
							</th>
							<td class="post-title page-title column-title">
								<strong><?php echo $post_type->labels->name; ?></strong>
							</td>
							<td class="custom_post_type_name column-custom_post_type_name"><?php echo $post_type->name; ?></td>
							<td class="label column-label"><?php echo $post_type->labels->name; ?></td>
							<td class="description column-description"><?php echo $post_type->description; ?></td>
						</tr>
								<?php
						}

						if ( count( $post_types ) == 0 ) {
							?>
						<tr class="no-items"><td class="colspanchange" colspan="5"><?php _e( 'No Custom Post Types found', 'cpt-generator' ); ?>.</td></tr>
								<?php
						}
						?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages one-page">
						<span class="displaying-num">
							<?php
							$count = count( $post_types );
							// Translators: Items
							printf( _n( '%d item', '%d items', $count ), $count );
							?>
						</span>
						<br class="clear">
					</div>
				</div>

			</div>
				<?php
			}
		}
		if ( 'cptg_tax' == $post_type ) {

			// Get all public custom Taxonomies
			$taxonomies = get_taxonomies(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'objects'
			);
			// Get all custom Taxonomies created by CPT Generator
			$cptg_tax_posts = get_posts( array( 'post_type' => 'cptg_tax' ) );
			// Remove all custom Taxonomies created by the CPT Generator plugin
			foreach ( $cptg_tax_posts as $cptg_tax_post ) {
				$values = get_post_custom( $cptg_tax_post->ID );
				unset( $taxonomies[ $values['cptg_tax_name'][0] ] );
			}

			if ( count( $taxonomies ) != 0 ) {
				?>
			<div id="cptg-cpt-overview" class="hidden">
				<div id="icon-edit" class="icon32 icon32-posts-cptg"><br></div>
				<h2><?php _e( 'Other registered custom Taxonomies', 'cpt-generator' ); ?></h2>
				<p><?php _e( 'The custom Taxonomies below are registered in WordPress but were not created by the CPT Generator plugin.', 'cpt-generator' ); ?></p>
				<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column">
							</th>
							<th scope="col" id="title" class="manage-column column-title">
								<span><?php _e( 'Taxonomy', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="custom_post_type_name" class="manage-column column-custom_taxonomy_name">
								<span><?php _e( 'Custom Taxonomy Name', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="label" class="manage-column column-label">
								<span><?php _e( 'Label', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-cb check-column">
							</th>
							<th scope="col" class="manage-column column-title">
								<span><?php _e( 'Taxonomy', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Taxonomy Name', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-label">
								<span><?php _e( 'Label', 'cpt-generator' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</tfoot>

					<tbody id="the-list">
						<?php
						// Create list of all other registered Custom Post Types
						foreach ( $taxonomies as $taxonomy ) {
							?>
						<tr valign="top">
							<th scope="row" class="check-column">
							</th>
							<td class="post-title page-title column-title">
								<strong><?php echo $taxonomy->labels->name; ?></strong>
							</td>
							<td class="custom_post_type_name column-custom_post_type_name"><?php echo $taxonomy->name; ?></td>
							<td class="label column-label"><?php echo $taxonomy->labels->name; ?></td>
						</tr>
							<?php
						}

						if ( count( $taxonomies ) == 0 ) {
							?>
						<tr class="no-items"><td class="colspanchange" colspan="4"><?php _e( 'No custom Taxonomies found', 'cpt-generator' ); ?>.</td></tr>
							<?php
						}
						?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages one-page">
						<span class="displaying-num">
							<?php
							$count = count( $taxonomies );
							// Translators: Items
							printf( _n( '%d item', '%d items', $count ), $count );
							?>
						</span>
						<br class="clear">
					</div>
				</div>

			</div>
				<?php
			}
		}
	}

	/**
	 * Update messages
	 *
	 * @param  array $messages Update messages
	 * @return array           Update messages
	 */
	function cptg_post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['cptg'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Custom Post Type updated.', 'cpt-generator' ),
			2  => __( 'Custom Post Type updated.', 'cpt-generator' ),
			3  => __( 'Custom Post Type deleted.', 'cpt-generator' ),
			4  => __( 'Custom Post Type updated.', 'cpt-generator' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Custom Post Type restored to revision from %s', 'cpt-generator' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Custom Post Type published.', 'cpt-generator' ),
			7  => __( 'Custom Post Type saved.', 'cpt-generator' ),
			8  => __( 'Custom Post Type submitted.', 'cpt-generator' ),
			9  => __( 'Custom Post Type scheduled for.', 'cpt-generator' ),
			10 => __( 'Custom Post Type draft updated.', 'cpt-generator' ),
		);

		return $messages;
	}

	/**
	 * Prepare attachment for Ajax Upload Request
	 * @param  array  $response    Response
	 * @param  string $attachment  File contents
	 * @param  array  $meta        File meta contents
	 *
	 * @return array               Modified response
	 */
	function wp_prepare_attachment_for_js( $response, $attachment, $meta ) {
		// only for image
		if ( $response['type'] != 'image' ) {
			return $response;
		}

		$attachment_url = $response['url'];
		$base_url       = str_replace( wp_basename( $attachment_url ), '', $attachment_url );

		if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $k => $v ) {
				if ( ! isset( $response['sizes'][ $k ] ) ) {
					$response['sizes'][ $k ] = array(
						'height'      => $v['height'],
						'width'       => $v['width'],
						'url'         => $base_url . $v['file'],
						'orientation' => $v['height'] > $v['width'] ? 'portrait' : 'landscape',
					);
				}
			}
		}

		return $response;
	}
}

/**
 * Instantiate the main class
 *
 * @since  1.0.0
 * @access public
 *
 * @var    object $cptg holds the instantiated class {@uses cptg}
 */
$cptg = new cptg();
