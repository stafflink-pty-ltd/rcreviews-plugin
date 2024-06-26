<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/admin
 * @author     Julius Genetia <julius@stafflink.com.au>
 */
class rcreviews_admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Add the admin menu
		add_action( 'admin_menu', array( $this, 'display_plugin_admin_menu' ), 9 );

		// Register and build settings fields
		add_action( 'admin_init', array( $this, 'register_and_build_fields' ) );

		// Register default values for settings field
		add_action( 'admin_init', array( $this, 'register_default_values_for_settings_field' ) );

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ) );

		// Register custom taxonomies
		add_action( 'init', array( $this, 'register_custom_taxonomies' ) );

		// Register meta boxes
		add_action( 'init', array( $this, 'register_meta_boxes' ) );

		// Move posts from previous custom post type to new custom post type
		add_action( 'init', array( $this, 'rcreviews_move_posts_from_previous_post_type' ) );

		// Ajax handler function
		add_action( 'admin_init', array( $this, 'rcreviews_ajax_handler_function' ) );

		// Add shortcode
		add_shortcode( 'rcreviews', array( $this, 'rcreviews_shortcode_function' ) );

		// Add cron job
		add_action( 'rcreviews_cron_hook', array( $this, 'rcreviews_cron_exec' ) );

		// Add cron schedules
		add_filter( 'cron_schedules', array( $this, 'rcreviews_cron_schedules' ) );

		// Update cron schedules
		add_action( 'update_option_rcreviews_sync_interval', array( $this, 'rcreviews_cron_refresh' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rcreviews_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rcreviews_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rcreviews-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rcreviews_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rcreviews_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$agency_id = get_option( 'rcreviews_agency_id' );

		$minimum_star_rating = get_option( 'rcreviews_minimum_star_rating' );
		$numbers             = '';

		if ( $minimum_star_rating ) {
			for ( $i = $minimum_star_rating; $i <= 5; $i++ ) {
				$numbers .= $i . ',';
			}
			$minimum_star_rating = '&ratings=' . rtrim( $numbers, ',' );
		} else {
			$minimum_star_rating = '';
		}

		$url_first = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/' . $agency_id . '?since=2010-09-06T12%3A27%3A00.1Z&order=DESC' . $minimum_star_rating;

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rcreviews-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'rcreviews-ajax', plugin_dir_url( __FILE__ ) . '/js/rcreviews-ajax.js', array( 'jquery' ), '1.0', true );
		wp_localize_script(
			'rcreviews-ajax',
			'ajax_object',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'url_first' => $url_first,
			)
		);
	}

	// Register Custom Post Types
	public function register_custom_post_types() {
		// Check post type if not existing

		if ( ! post_type_exists( get_option( 'rcreviews_custom_post_type_slug' ) ) ) {
			$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';
			$labels         = array(
				'name'                  => _x( 'Reviews', 'Post Type General Name', 'text_domain' ),
				'singular_name'         => _x( 'Review', 'Post Type Singular Name', 'text_domain' ),
				'menu_name'             => __( 'Reviews', 'text_domain' ),
				'name_admin_bar'        => __( 'Reviews', 'text_domain' ),
				'archives'              => __( 'Review Archives', 'text_domain' ),
				'attributes'            => __( 'Review Attributes', 'text_domain' ),
				'parent_item_colon'     => __( 'Parent Review:', 'text_domain' ),
				'all_items'             => __( 'All Reviews', 'text_domain' ),
				'add_new_item'          => __( 'Add New Review', 'text_domain' ),
				'add_new'               => __( 'Add New', 'text_domain' ),
				'new_item'              => __( 'New Review', 'text_domain' ),
				'edit_item'             => __( 'Edit Review', 'text_domain' ),
				'update_item'           => __( 'Update Review', 'text_domain' ),
				'view_item'             => __( 'View Review', 'text_domain' ),
				'view_items'            => __( 'View Reviews', 'text_domain' ),
				'search_items'          => __( 'Search Review', 'text_domain' ),
				'not_found'             => __( 'Not found', 'text_domain' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
				'featured_image'        => __( 'Featured Image', 'text_domain' ),
				'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
				'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
				'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
				'insert_into_item'      => __( 'Insert into Review', 'text_domain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Review', 'text_domain' ),
				'items_list'            => __( 'Reviews list', 'text_domain' ),
				'items_list_navigation' => __( 'Reviews list navigation', 'text_domain' ),
				'filter_items_list'     => __( 'Filter Reviews list', 'text_domain' ),
			);
			$args           = array(
				'label'               => __( 'Review', 'text_domain' ),
				'description'         => __( 'Sync Reviews from realestate.com.au to WordPress.', 'text_domain' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'page-attributes' ),
				'taxonomies'          => array( 'rcreviews_suburb', 'rcreviews_state' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-format-quote',
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'rewrite'             => false,
				'capability_type'     => 'post',
			);
			register_post_type( $post_type_slug, $args );
		}
	}

	// Register Custom Taxonomies
	function register_custom_taxonomies() {
		$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		$labels_suburb = array(
			'name'                       => _x( 'Suburbs', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'Suburb', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'Suburbs', 'text_domain' ),
			'all_items'                  => __( 'Suburbs', 'text_domain' ),
			'parent_item'                => __( 'Parent Suburb', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent Suburb:', 'text_domain' ),
			'new_item_name'              => __( 'New Suburb', 'text_domain' ),
			'add_new_item'               => __( 'Add New Suburb', 'text_domain' ),
			'edit_item'                  => __( 'Edit Suburb', 'text_domain' ),
			'update_item'                => __( 'Update Suburb', 'text_domain' ),
			'view_item'                  => __( 'View Suburb', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate suburbs with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove suburbs', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular Suburbs', 'text_domain' ),
			'search_items'               => __( 'Search Suburbs', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No suburbs', 'text_domain' ),
			'items_list'                 => __( 'Suburbs list', 'text_domain' ),
			'items_list_navigation'      => __( 'Suburbs list navigation', 'text_domain' ),
		);
		$args_suburb   = array(
			'labels'            => $labels_suburb,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);
		$labels_state  = array(
			'name'                       => _x( 'States', 'Taxonomy General Name', 'text_domain' ),
			'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'text_domain' ),
			'menu_name'                  => __( 'States', 'text_domain' ),
			'all_items'                  => __( 'States', 'text_domain' ),
			'parent_item'                => __( 'Parent State', 'text_domain' ),
			'parent_item_colon'          => __( 'Parent State:', 'text_domain' ),
			'new_item_name'              => __( 'New State', 'text_domain' ),
			'add_new_item'               => __( 'Add New State', 'text_domain' ),
			'edit_item'                  => __( 'Edit State', 'text_domain' ),
			'update_item'                => __( 'Update State', 'text_domain' ),
			'view_item'                  => __( 'View State', 'text_domain' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'text_domain' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'text_domain' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
			'popular_items'              => __( 'Popular States', 'text_domain' ),
			'search_items'               => __( 'Search States', 'text_domain' ),
			'not_found'                  => __( 'Not Found', 'text_domain' ),
			'no_terms'                   => __( 'No states', 'text_domain' ),
			'items_list'                 => __( 'States list', 'text_domain' ),
			'items_list_navigation'      => __( 'States list navigation', 'text_domain' ),
		);
		$args_state    = array(
			'labels'            => $labels_state,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => true,
			'rewrite'           => false,
		);
		register_taxonomy( 'rcreviews_suburb', array( $post_type_slug ), $args_suburb );
		register_taxonomy( 'rcreviews_state', array( $post_type_slug ), $args_state );
	}

	// Register Meta Boxes
	function register_meta_boxes() {

		function rcreviews_add_meta_boxes() {
			$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			add_meta_box(
				'rcreview_reviewer_rating',
				'Review Rating',
				'rcreviews_reviewer_rating_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_reviewer_role',
				'Reviewer Role',
				'rcreviews_reviewer_role_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_reviewer_name',
				'Reviewer Name',
				'rcreviews_reviewer_name_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_agent_id',
				'Agent ID',
				'rcreviews_agent_id_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_agent_name',
				'Agent Name',
				'rcreviews_agent_name_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_listing_id',
				'Listing ID',
				'rcreviews_listing_id_callback',
				$post_type_slug
			);
			add_meta_box(
				'rcreview_unique_id',
				'Unique ID',
				'rcreviews_unique_id_callback',
				$post_type_slug
			);
		}
		add_action( 'add_meta_boxes', 'rcreviews_add_meta_boxes' );

		function rcreviews_reviewer_rating_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_rating', true ) );
			echo '<input type="text" name="rcreview_reviewer_rating" id="rcreview_reviewer_rating" value="' . $value . '">';
		}
		function rcreviews_reviewer_role_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_role', true ) );
			echo '<input type="text" name="rcreview_reviewer_role" id="rcreview_reviewer_role" value="' . $value . '">';
		}
		function rcreviews_reviewer_name_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_reviewer_name', true ) );
			echo '<input type="text" name="rcreview_reviewer_name" id="rcreview_reviewer_name" value="' . $value . '">';
		}
		function rcreviews_agent_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_agent_id', true ) );
			echo '<input type="text" name="rcreview_agent_id" id="rcreview_agent_id" value="' . $value . '">';
		}
		function rcreviews_agent_name_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_agent_name', true ) );
			echo '<input type="text" name="rcreview_agent_name" id="rcreview_agent_name" value="' . $value . '">';
		}
		function rcreviews_listing_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_listing_id', true ) );
			echo '<input type="text" name="rcreview_listing_id" id="rcreview_listing_id" value="' . $value . '">';
		}
		function rcreviews_unique_id_callback( $post ) {
			$value = esc_html( get_post_meta( $post->ID, 'rcreview_unique_id', true ) );
			echo '<input type="text" name="rcreview_unique_id" id="rcreview_unique_id" value="' . $value . '">';
		}

		function save_post_rcreviews_meta_boxes( $post_id ) {
			$post_type_slug = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( $post_type_slug == get_post_type() ) {
				if ( isset( $_POST['rcreview_reviewer_rating'] ) && $_POST['rcreview_reviewer_rating'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_rating', $_POST['rcreview_reviewer_rating'] );
				}
				if ( isset( $_POST['rcreview_reviewer_role'] ) && $_POST['rcreview_reviewer_role'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_role', $_POST['rcreview_reviewer_role'] );
				}

				if ( isset( $_POST['rcreview_reviewer_name'] ) && $_POST['rcreview_reviewer_name'] != '' ) {
					update_post_meta( $post_id, 'rcreview_reviewer_name', $_POST['rcreview_reviewer_name'] );
				}

				if ( isset( $_POST['rcreview_agent_id'] ) && $_POST['rcreview_agent_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_agent_id', $_POST['rcreview_agent_id'] );
				}

				if ( isset( $_POST['rcreview_agent_name'] ) && $_POST['rcreview_agent_name'] != '' ) {
					update_post_meta( $post_id, 'rcreview_agent_name', $_POST['rcreview_agent_name'] );
				}

				if ( isset( $_POST['rcreview_listing_id'] ) && $_POST['rcreview_listing_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_listing_id', $_POST['rcreview_listing_id'] );
				}

				if ( isset( $_POST['rcreview_unique_id'] ) && $_POST['rcreview_unique_id'] != '' ) {
					update_post_meta( $post_id, 'rcreview_unique_id', $_POST['rcreview_unique_id'] );
				}
			}
		}
		add_action( 'save_post', 'save_post_rcreviews_meta_boxes' );
	}

	// Move posts from previous custom post type to new custom post type
	public function rcreviews_move_posts_from_previous_post_type() {
		// Define the old and new post types
		$prev_post_type    = get_option( 'rcreviews_prev_post_type_slug' );
		$current_post_type = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		if ( '' != $prev_post_type || $prev_post_type != $current_post_type ) {
			// Get all posts of the old post type
			$args       = array(
				'post_type'      => $prev_post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);
			$prev_posts = new WP_Query( $args );

			// Loop through the posts and change their post type
			if ( $prev_posts->have_posts() ) {
				while ( $prev_posts->have_posts() ) {
					$prev_posts->the_post();
					$post_id      = get_the_ID();
					$current_post = array(
						'ID'        => $post_id,
						'post_type' => $current_post_type,
					);
					wp_update_post( $current_post );
				}
				wp_reset_postdata();
			}
		}
	}

	public function display_plugin_admin_menu() {
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( $this->plugin_name, 'RC Reviews', 'administrator', $this->plugin_name, array( $this, 'display_plugin_admin_dashboard' ), 'dashicons-star-filled', 26 );

		// add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( $this->plugin_name, 'RC Reviews Settings', 'Settings', 'administrator', $this->plugin_name . '-settings', array( $this, 'display_plugin_admin_settings' ) );
	}

	public function display_plugin_admin_dashboard() {
		require_once 'partials/' . $this->plugin_name . '-admin-display.php';
	}
	public function display_plugin_admin_settings() {
		// set this var to be used in the settings-display view
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

		if ( isset( $_GET['error_message'] ) ) {
			add_action( 'admin_notices', array( $this, 'rcreviews_settings_messages' ) );
			do_action( 'admin_notices', $_GET['error_message'] );
		}
		require_once 'partials/' . $this->plugin_name . '-admin-settings-display.php';
	}
	public function rcreviews_settings_messages( $error_message ) {
		switch ( $error_message ) {
			case '1':
				$message       = __( 'There was an error adding this setting. Please try again.  If this persists, shoot us an email.', 'my-text-domain' );
				$err_code      = esc_attr( 'rcreviews_example_setting' );
				$setting_field = 'rcreviews_example_setting';
				break;
		}
		$type = 'error';
		add_settings_error(
			$setting_field,
			$err_code,
			$message,
			$type
		);
	}
	public function register_and_build_fields() {
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			// ID used to identify this section and with which to register options
			'rcreviews_settings_section',
			// Title to be displayed on the administration page
			'Client Credentials',
			// Callback used to render the description of the section
			array( $this, 'rcreviews_settings_account' ),
			// Page on which to add this section of options
			'rcreviews_settings'
		);

		add_settings_section(
			// ID used to identify this section and with which to register options
			'rcreviews_main_settings_section',
			// Title to be displayed on the administration page
			'Import Details',
			// Callback used to render the description of the section
			array( $this, 'rcreviews_main_settings_account' ),
			// Page on which to add this section of options
			'rcreviews_main_settings'
		);

		$disabled_id     = '';
		$disabled_secret = '';
		$disabled_agent  = '';
		$disabled_type   = '';

		if ( getenv( 'REA_CLIENT_ID' ) ) {
			$disabled_id = 'disabled';
		}
		if ( getenv( 'REA_CLIENT_SECRET' ) ) {
			$disabled_secret = 'disabled';
		}
		if ( getenv( 'REA_AGENCY_ID' ) ) {
			$disabled_agent = 'disabled';
		}
		if ( getenv( 'REA_POST_TYPE_SLUG' ) ) {
			$disabled_type = 'disabled';
		}

		add_settings_field(
			'rcreviews_client_id',
			'Client ID',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_client_id',
				'name'             => 'rcreviews_client_id',
				'required'         => 'true',
				$disabled_id       => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_client_id'
		);

		add_settings_field(
			'rcreviews_client_secret',
			'Client Secret',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'password',
				'id'               => 'rcreviews_client_secret',
				'name'             => 'rcreviews_client_secret',
				'required'         => 'true',
				$disabled_secret   => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_client_secret'
		);

		add_settings_field(
			'rcreviews_access_token',
			'Access Token',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'password',
				'id'               => 'rcreviews_access_token',
				'name'             => 'rcreviews_access_token',
				'required'         => 'true',
				'disabled'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_access_token'
		);

		add_settings_field(
			'rcreviews_agency_id',
			'Agent ID',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_agency_id',
				'name'             => 'rcreviews_agency_id',
				'required'         => 'true',
				$disabled_agent    => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_agency_id'
		);

		add_settings_field(
			'rcreviews_last_import',
			'Last Import',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'hidden',
				'id'               => 'rcreviews_last_import',
				'name'             => 'rcreviews_last_import',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_last_import'
		);

		add_settings_field(
			'rcreviews_minimum_star_rating',
			'Minimum Star Rating',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_minimum_star_rating',
				'name'             => 'rcreviews_minimum_star_rating',
				'required'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_minimum_star_rating'
		);

		add_settings_field(
			'rcreviews_sync_interval',
			'Sync Interval in Hours',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_sync_interval',
				'name'             => 'rcreviews_sync_interval',
				'required'         => 'true',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_sync_interval'
		);

		add_settings_field(
			'rcreviews_prev_post_type_slug',
			'Previous Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_prev_post_type_slug',
				'name'             => 'rcreviews_prev_post_type_slug',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_prev_post_type_slug'
		);

		add_settings_field(
			'rcreviews_current_post_type_slug',
			'Current Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_main_settings',
			'rcreviews_main_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_current_post_type_slug',
				'name'             => 'rcreviews_current_post_type_slug',
				'required'         => 'true',
				'disabled'         => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_main_settings',
			'rcreviews_current_post_type_slug'
		);

		add_settings_field(
			'rcreviews_custom_post_type_slug',
			'Custom Post Type Slug',
			array( $this, 'rcreviews_render_settings_field' ),
			'rcreviews_settings',
			'rcreviews_settings_section',
			array(
				'type'             => 'input',
				'subtype'          => 'text',
				'id'               => 'rcreviews_custom_post_type_slug',
				'name'             => 'rcreviews_custom_post_type_slug',
				'required'         => 'true',
				$disabled_type     => '',
				'get_options_list' => '',
				'value_type'       => 'normal',
				'wp_data'          => 'option',
			),
		);
		register_setting(
			'rcreviews_settings',
			'rcreviews_custom_post_type_slug'
		);
	}
	public function register_default_values_for_settings_field() {
		if ( getenv( 'REA_CLIENT_ID' ) ) {
			update_option( 'rcreviews_client_id', getenv( 'REA_CLIENT_ID' ) );
		}
		if ( getenv( 'REA_CLIENT_SECRET' ) ) {
			update_option( 'rcreviews_client_secret', getenv( 'REA_CLIENT_SECRET' ) );
		}
		if ( getenv( 'REA_AGENCY_ID' ) ) {
			update_option( 'rcreviews_agency_id', getenv( 'REA_AGENCY_ID' ) );
		}
		if ( getenv( 'REA_POST_TYPE_SLUG' ) ) {
			update_option( 'rcreviews_custom_post_type_slug', getenv( 'REA_POST_TYPE_SLUG' ) );
		}
		if ( '' == get_option( 'rcreviews_current_post_type_slug' ) ) {
			update_option( 'rcreviews_current_post_type_slug', 'rcreviews' );
		}

		$post_type = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		if ( get_option( 'rcreviews_current_post_type_slug' ) != get_option( 'rcreviews_custom_post_type_slug' ) ) {
			update_option( 'rcreviews_prev_post_type_slug', get_option( 'rcreviews_current_post_type_slug' ) );
			update_option( 'rcreviews_current_post_type_slug', $post_type );
		}

		$url           = 'https://api.realestate.com.au/oauth/token';
		$client_id     = get_option( 'rcreviews_client_id' );
		$client_secret = get_option( 'rcreviews_client_secret' );
		$data          = array( 'grant_type' => 'client_credentials' );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, "$client_id:$client_secret" );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

		$output = curl_exec( $ch );

		// if ($output === FALSE) {
		// echo "cURL Error: " . curl_error($ch);
		// }

		curl_close( $ch );

		// Now you can process the output
		$response = json_decode( $output, true );

		if ( isset( $response['access_token'] ) ) {
			update_option( 'rcreviews_access_token', $response['access_token'] );
		} else {
			update_option( 'rcreviews_access_token', '' );
		}
	}

	public function rcreviews_settings_account() {
		echo '<p>Please add the correct API credentials on .env file.</p>';
	}
	public function rcreviews_settings_main_account() {
		echo '<p>Please add the agency ID on .env file.</p>';
	}
	public function rcreviews_render_settings_field( $args ) {
		if ( $args['wp_data'] == 'option' ) {
			$wp_data_value = get_option( $args['name'] );
		} elseif ( $args['wp_data'] == 'post_meta' ) {
			$wp_data_value = get_post_meta( $args['post_id'], $args['name'], true );
		}

		switch ( $args['type'] ) {
			case 'input':
				$value = ( $args['value_type'] == 'serialized' ) ? serialize( $wp_data_value ) : $wp_data_value;
				if ( $args['subtype'] != 'checkbox' ) {
					$prependStart = ( isset( $args['prepend_value'] ) ) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
					$prependEnd   = ( isset( $args['prepend_value'] ) ) ? '</div>' : '';
					$step         = ( isset( $args['step'] ) ) ? 'step="' . $args['step'] . '"' : '';
					$min          = ( isset( $args['min'] ) ) ? 'min="' . $args['min'] . '"' : '';
					$max          = ( isset( $args['max'] ) ) ? 'max="' . $args['max'] . '"' : '';
					if ( isset( $args['disabled'] ) ) {
						// hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '_disabled" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '_disabled" size="40" disabled value="' . esc_attr( $value ) . '" /><input type="hidden" id="' . $args['id'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					} else {
						echo $prependStart . '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" ' . $step . ' ' . $max . ' ' . $min . ' name="' . $args['name'] . '" size="40" value="' . esc_attr( $value ) . '" />' . $prependEnd;
					}
					/*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->plugin_name.'_cost2" name="'.$this->plugin_name.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->plugin_name.'_cost" step="any" name="'.$this->plugin_name.'_cost" value="' . esc_attr( $cost ) . '" />*/

				} else {
					$checked = ( $value ) ? 'checked' : '';
					echo '<input type="' . $args['subtype'] . '" id="' . $args['id'] . '" "' . $args['required'] . '" name="' . $args['name'] . '" size="40" value="1" ' . $checked . ' />';
				}
				break;
			default:
				// code...
				break;
		}
	}

	public function rcreviews_ajax_handler_function() {
		function rcreviews_process_reviews_ajax_handler() {
			$url          = $_POST['url'];
			$item_counter = $_POST['item_counter'];
			$access_token = get_option( 'rcreviews_access_token' );
			$post_type    = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

			$headers   = array();
			$headers[] = 'Accept: application/hal+json';
			$headers[] = 'Authorization: Bearer ' . $access_token;

			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			$result = curl_exec( $ch );

			if ( curl_errno( $ch ) ) {
				echo 'Error:' . curl_error( $ch );
			}

			curl_close( $ch );

			$data         = json_decode( $result, true );
			$rating       = 0;
			$role         = 'Seller';
			$name         = '';
			$created_date = '';
			$content      = '';
			$agent_id     = 0;
			$agent_name   = '';
			$listing_id   = 0;
			$unique_id    = 0;

			foreach ( $data['result'] as $review ) {
				if ( isset( $review['rating'] ) ) {
					$rating = $review['rating'];
				}
				if ( isset( $review['reviewer']['role'] ) ) {
					$role = ucfirst( $review['reviewer']['role'] );
				}
				if ( isset( $review['reviewer']['name'] ) ) {
					$name = ucfirst( $review['reviewer']['name'] );
				}
				if ( isset( $review['createdDate'] ) ) {
					$created_date            = $review['createdDate'];
					$created_date_as_post_id = strtotime( $review['createdDate'] );
				}
				if ( isset( $review['content'] ) ) {
					$content = $review['content'];
				}
				if ( isset( $review['agent']['profileId'] ) ) {
					$agent_id = $review['agent']['profileId'];
				}
				if ( isset( $review['agent']['name'] ) ) {
					$agent_name = $review['agent']['name'];
				}
				if ( isset( $review['listing']['id'] ) ) {
					$listing_id = $review['listing']['id'];
				}
				$unique_id = $listing_id . '-' . $agent_id . '-' . $created_date_as_post_id;

				// Insert post
				$current_post = array(
					'post_title'   => $role . ' of house',
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_date'    => $created_date,
					'post_type'    => $post_type,
					'meta_input'   => array(
						'rcreview_reviewer_rating' => $rating,
						'rcreview_reviewer_role'   => $role,
						'rcreview_reviewer_name'   => $name,
						'rcreview_agent_id'        => $agent_id,
						'rcreview_agent_name'      => $agent_name,
						'rcreview_listing_id'      => $listing_id,
						'rcreview_unique_id'       => $unique_id,
					),
				);

				$args_by_unique_id = array(
					'post_type'  => $post_type,
					'meta_query' => array(
						array(
							'key'   => 'rcreview_unique_id',
							'value' => $unique_id,
						),
					),
				);

				// Insert post
				$posts = get_posts( $args_by_unique_id );

				if ( ! empty( $posts ) ) {
					$current_post['ID'] = $posts[0]->ID;
					wp_update_post( $current_post );
				} else {
					wp_insert_post( $current_post );
				}

				++$item_counter;
			}

			$url_next = $data['_links']['next']['href'];

			update_option( 'rcreviews_last_import', date( 'd F Y H:i:s' ) );

			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$query       = new WP_Query( $args );
			$total_posts = $query->found_posts;

			$response = array(
				'url_next'     => $url_next,
				'last_import'  => get_option( 'rcreviews_last_import' ),
				'item_counter' => $item_counter,
				'total_posts'  => $total_posts,
			);

			header( 'Content-Type: application/json' );
			echo json_encode( $response );

			wp_die();
		}
		add_action( 'wp_ajax_rcreviews_process_reviews', 'rcreviews_process_reviews_ajax_handler' );
		add_action( 'wp_ajax_rcreviews_nopriv_process_reviews', 'rcreviews_process_reviews_ajax_handler' );

		function rcreviews_empty_reviews_ajax_handler() {
			$post_type = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'rcreview_unique_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$reviews = get_posts( $args );

			foreach ( $reviews as $review_id ) {
				wp_delete_post( $review_id, true );
			}

			$query       = new WP_Query( $args );
			$total_posts = $query->found_posts;

			$response = array(
				'total_posts' => $total_posts,
			);

			header( 'Content-Type: application/json' );
			echo json_encode( $response );

			wp_die();
		}
		add_action( 'wp_ajax_rcreviews_empty_reviews', 'rcreviews_empty_reviews_ajax_handler' );
		add_action( 'wp_ajax_nopriv_rcreviews_empty_reviews', 'rcreviews_empty_reviews_ajax_handler' );
	}

	public function rcreviews_shortcode_function( $atts ) {
		$output           = '';
		$badge            = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/images/badge.svg' );
		$class_visibility = ' shown-review';
		$post_type        = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';

		// Set default values for the attributes
		$atts = shortcode_atts(
			array(
				'max_reviews'             => -1,
				'shown_reviews'           => 3,
				'min_stars'               => 5,
				'agent_id'                => '',
				'agent_name'              => '',
				'view'                    => 'list',
				'listing_type'            => 'agent',
				'class_section'           => '',
				'class_container'         => 'container',
				'class_row'               => 'row',
				'class_article'           => 'col-12 mb-3',
				'class_card'              => 'bg-light rounded p-3',
				'class_inner_row'         => 'row align-items-center justify-content-between',
				'class_rating'            => 'col d-flex align-items-center',
				'class_rating_stars'      => 'd-flex align-items-center',
				'class_rating_number'     => 'ps-1',
				'class_badge'             => 'col text-end',
				'class_title'             => '',
				'class_date'              => '',
				'class_content'           => 'mt-2',
				'class_agent'             => 'mt-3 d-flex align-items-center',
				'class_agent_img-wrapper' => 'rounded-circle overflow-hidden me-1',
				'class_agent_img'         => '',
				'class_agent_name'        => '',
				'class_btn_wrapper'       => 'd-flex justify-content-center',
				'class_btn'               => 'btn btn-outline-dark fw-semibold py-3 px-4',
				'class_no_results'        => '',
			),
			$atts,
			'rcreviews'
		);

		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => 'rcreview_reviewer_rating',
				'value'   => $atts['min_stars'],
				'compare' => '>=',
			),
		);

		if ( ! empty( $atts['agent_id'] ) && ! empty( $atts['agent_name'] ) ) {
			$agent_names  = explode( ',', $atts['agent_name'] );
			$agent_ids    = explode( ',', $atts['agent_id'] );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'rcreview_agent_id',
					'value'   => $agent_ids,
					'compare' => 'IN',
				),
				array(
					'key'     => 'rcreview_agent_name',
					'value'   => $agent_names,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agent_id'] ) && empty( $atts['agent_name'] ) ) {
			$agent_ids    = explode( ',', $atts['agent_id'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agent_id',
					'value'   => $agent_ids,
					'compare' => 'IN',
				),
			);
		} elseif ( ! empty( $atts['agent_name'] ) && empty( $atts['agent_id'] ) ) {
			$agent_names  = explode( ',', $atts['agent_name'] );
			$meta_query[] = array(
				array(
					'key'     => 'rcreview_agent_name',
					'value'   => $agent_names,
					'compare' => 'IN',
				),
			);
		}

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $atts['max_reviews'],
			'meta_query'     => $meta_query,
		);

		$query = new WP_Query( $args );

		function rcreviews_rating( $rating ) {
			$star   = file_get_contents( plugin_dir_path( __FILE__ ) . '../assets/images/star.svg' );
			$output = '';

			$rating = intval( $rating );
			for ( $i = 0; $i < $rating; $i++ ) {
				$output .= $star;
			}
			return $output;
		}
		function rcreviews_check_class( $string, $view ) {

			if ( 'unstyled' != $view ) {
				if ( '' != $string ) {
					return ' ' . $string;
				} else {
					return '';
				}
			} else {
				return '';
			}
		}

		if ( $query->have_posts() ) {

			$output .= '<section class="rcreviews--section' . rcreviews_check_class( $atts['class_section'], $atts['view'] ) . ' rcreviews--listing-type-' . $atts['listing_type'] . '">';
			$output .= '<div class="rcreviews--container' . rcreviews_check_class( $atts['class_container'], $atts['view'] ) . '"> ';
			$output .= '<div class="rcreviews--row' . rcreviews_check_class( $atts['class_row'], $atts['view'] ) . '">';

			while ( $query->have_posts() ) {
				$query->the_post();

				if ( $query->current_post <= ( $atts['shown_reviews'] - 1 ) ) {
					$class_visibility = ' rcreviews--shown-review';
				} else {
					$class_visibility = ' rcreviews--hidden-review d-none';
				}

				$output .= '<article class="rcreviews--article col' . $class_visibility . rcreviews_check_class( $atts['class_article'], $atts['view'] ) . '" id="rcreviews-' . get_the_ID() . '" data-agent-id="' . get_post_meta( get_the_ID(), 'rcreview_agent_id', true ) . '">';
				$output .= '<div class="rcreviews--card' . rcreviews_check_class( $atts['class_card'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--inner-row' . rcreviews_check_class( $atts['class_inner_row'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--rating' . rcreviews_check_class( $atts['class_rating'], $atts['view'] ) . '">';
				$output .= '<div class="rcreviews--rating-stars' . rcreviews_check_class( $atts['class_rating_stars'], $atts['view'] ) . '">' . rcreviews_rating( get_post_meta( get_the_ID(), 'rcreview_reviewer_rating', true ) ) . '</div>';
				$output .= '<div class="rcreviews-rating-number' . rcreviews_check_class( $atts['class_rating_number'], $atts['view'] ) . '">' . number_format( get_post_meta( get_the_ID(), 'rcreview_reviewer_rating', true ), 1 ) . '</div>';
				$output .= '</div>';
				$output .= '<div class="rcreviews--badge' . rcreviews_check_class( $atts['class_badge'], $atts['view'] ) . '">' . $badge . 'Verified review</div>';
				$output .= '</div>';
				$output .= '<div class="rcreviews--title' . rcreviews_check_class( $atts['class_title'], $atts['view'] ) . '"><strong>' . get_the_title() . '</strong></div>';
				$output .= '<div class="rcreviews--date' . rcreviews_check_class( $atts['class_date'], $atts['view'] ) . '"><small>' . human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ago</small></div>';
				$output .= '<div class="rcreviews--content' . rcreviews_check_class( $atts['class_content'], $atts['view'] ) . '">' . get_the_content() . '</div>';

				if ( 'agency' == $atts['listing_type'] ) {
					$agent_name = '';
					$agent_img  = '';

					$output .= '<div class="rcreviews--agent' . rcreviews_check_class( $atts['class_agent'], $atts['view'] ) . '">';

					$users = get_users(
						array(
							'role'           => 'author',
							'search'         => '*' . get_post_meta( get_the_ID(), 'rcreview_agent_name', true ) . '*',
							'search_columns' => array(
								'display_name',
							),
						)
					);

					if ( ! empty( $users ) ) {
						$user       = $users[0];
						$agent_name = get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true );
						$agent_img  = get_field( 'static_profile_image', 'user_' . $user->ID )['sizes']['thumbnail'];

					} else {
						$agent_name = get_post_meta( get_the_ID(), 'rcreview_agent_name', true );
					}

					if ( $agent_img ) {
						$output .= '<span class="rcreviews--agent-img-wrapper' . rcreviews_check_class( $atts['class_agent_img-wrapper'], $atts['view'] ) . '">';
						$output .= '<img class="rcreviews--agent-img' . rcreviews_check_class( $atts['class_agent_img-wrapper'], $atts['view'] ) . '" src="' . $agent_img . '" width="24" width="24">';
						$output .= '</span>';
					}

					$output .= '<span class="rcreviews--agent-name' . rcreviews_check_class( $atts['class_agent_name'], $atts['view'] ) . '">' . $agent_name . '</span>';
					$output .= '</div>';
				}

				$output .= '</div>';
				$output .= '</article>';
			}

			$output .= '</div>';

			if ( ! empty( $atts['max_reviews'] ) && $atts['max_reviews'] > 0 ) {
				if ( $atts['max_reviews'] > $atts['shown_reviews'] ) {
					$output .= '<div class="rcreviews--btn-wrapper' . rcreviews_check_class( $atts['class_btn_wrapper'], $atts['view'] ) . '">';
					$output .= '<button class="rcreviews--btn' . rcreviews_check_class( $atts['class_btn'], $atts['view'] ) . '"><span class="rcreviews--label">Show</span> <span class="rcreviews--count">' . $atts['max_reviews'] - $atts['shown_reviews'] . '</span> reviews</button>';
					$output .= '</div>';
				}
			} elseif ( $query->found_posts > $atts['shown_reviews'] ) {
					$output .= '<div class="rcreviews--btn-wrapper' . rcreviews_check_class( $atts['class_btn_wrapper'], $atts['view'] ) . '">';
					$output .= '<button class="rcreviews--btn' . rcreviews_check_class( $atts['class_btn'], $atts['view'] ) . '"><span class="rcreviews--label">Show</span> <span class="rcreviews--count">' . $query->found_posts - $atts['shown_reviews'] . '</span> reviews</button>';
					$output .= '</div>';
			}
			$output .= '</div>';
			$output .= '</section>';

			// Restore original Post Data
			wp_reset_postdata();
		} else {
			// No posts found
			$output .= '<div class="rcreviews--no-results' . rcreviews_check_class( $atts['class_no_results'], $atts['view'] ) . '">';
			$output .= 'No reviews found.';
			$output .= '</div>';
		}

		return $output;
	}

	public function rcreviews_cron_exec() {
		$agency_id           = get_option( 'rcreviews_agency_id' );
		$minimum_star_rating = get_option( 'rcreviews_minimum_star_rating' );
		$numbers             = '';

		if ( $minimum_star_rating ) {
			for ( $i = $minimum_star_rating; $i <= 5; $i++ ) {
				$numbers .= $i . ',';
			}
			$minimum_star_rating = '&ratings=' . rtrim( $numbers, ',' );
		} else {
			$minimum_star_rating = '';
		}

		$date = new DateTime();
		$date->modify( '-30 days' );
		$dateString  = $date->format( 'Y-m-d\TH:i:s\Z' );
		$encodedDate = urlencode( $dateString );

		$url_first = 'https://api.realestate.com.au/customer-profile/v1/ratings-reviews/agencies/' . $agency_id . '?since=' . $encodedDate . '&order=DESC' . $minimum_star_rating;

		function rcreviews_cron_exec_feed( $url ) {
			$access_token = get_option( 'rcreviews_access_token' );
			$post_type    = get_option( 'rcreviews_custom_post_type_slug' ) ? : 'rcreviews';
			$ch           = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

			$headers   = array();
			$headers[] = 'Accept: application/hal+json';
			$headers[] = 'Authorization: Bearer ' . $access_token;

			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			$result = curl_exec( $ch );

			if ( curl_errno( $ch ) ) {
				echo 'Error:' . curl_error( $ch );
			}

			curl_close( $ch );

			$data         = json_decode( $result, true );
			$rating       = 0;
			$role         = 'Seller';
			$name         = '';
			$created_date = '';
			$content      = '';
			$agent_id     = 0;
			$agent_name   = '';
			$listing_id   = 0;
			$unique_id    = 0;

			foreach ( $data['result'] as $review ) {
				if ( isset( $review['rating'] ) ) {
					$rating = $review['rating'];
				}
				if ( isset( $review['reviewer']['role'] ) ) {
					$role = ucfirst( $review['reviewer']['role'] );
				}
				if ( isset( $review['reviewer']['name'] ) ) {
					$name = ucfirst( $review['reviewer']['name'] );
				}
				if ( isset( $review['createdDate'] ) ) {
					$created_date            = $review['createdDate'];
					$created_date_as_post_id = strtotime( $review['createdDate'] );
				}
				if ( isset( $review['content'] ) ) {
					$content = $review['content'];
				}
				if ( isset( $review['agent']['profileId'] ) ) {
					$agent_id = $review['agent']['profileId'];
				}
				if ( isset( $review['agent']['name'] ) ) {
					$agent_name = $review['agent']['name'];
				}
				if ( isset( $review['listing']['id'] ) ) {
					$listing_id = $review['listing']['id'];
				}
				$unique_id = $listing_id . '-' . $agent_id . '-' . $created_date_as_post_id;

				// Insert post
				$current_post = array(
					'post_title'   => $role . ' of house',
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_date'    => $created_date,
					'post_type'    => $post_type,
					'meta_input'   => array(
						'rcreview_reviewer_rating' => $rating,
						'rcreview_reviewer_role'   => $role,
						'rcreview_reviewer_name'   => $name,
						'rcreview_agent_id'        => $agent_id,
						'rcreview_agent_name'      => $agent_name,
						'rcreview_listing_id'      => $listing_id,
						'rcreview_unique_id'       => $unique_id,
					),
				);

				$args_by_unique_id = array(
					'post_type'  => $post_type,
					'meta_query' => array(
						array(
							'key'   => 'rcreview_unique_id',
							'value' => $unique_id,
						),
					),
				);

				// Insert post
				$posts = get_posts( $args_by_unique_id );

				if ( ! empty( $posts ) ) {
					$current_post['ID'] = $posts[0]->ID;
					wp_update_post( $current_post );
				} else {
					wp_insert_post( $current_post );
				}
			}

			$url_next = $data['_links']['next']['href'];

			update_option( 'rcreviews_last_import', date( 'd F Y H:i:s' ) );

			// error_log( $url );

			if ( $url_next ) {
				rcreviews_cron_exec_feed( $url_next );
			}
		}

		rcreviews_cron_exec_feed( $url_first );
	}

	public function rcreviews_cron_schedules( $schedules ) {
		$hour     = get_option( 'rcreviews_sync_interval' ) ? : 24;
		$interval = $hour * 60 * 60;

		$schedules['rcreviews_interval'] = array(
			'interval' => $interval,
			'display'  => esc_html__( 'Every ' . $hour . ' Hour(s)' ),
		);
		return $schedules;
	}

	public function rcreviews_cron_refresh() {
		// Unschedule the existing event
		$timestamp = wp_next_scheduled( 'rcreviews_cron_hook' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'rcreviews_cron_hook' );
		}

		// Schedule a new event
		wp_schedule_event( time(), 'rcreviews_interval', 'rcreviews_cron_hook' );
	}
}
