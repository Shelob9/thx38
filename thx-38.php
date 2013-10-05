<?php
/*
Plugin Name: THX_38
Plugin URI:
Description: THX stands for THeme eXperience. A plugin that rebels against their rigidly controlled themes.php in search for hopeful freedom in WordPress 3.8, or beyond. <strong>This is only for development work and the brave of heart, as it totally breaks themes.php</strong>.
Version: 0.5.1
Author: THX_38 Team
*/

class THX_38 {

	function __construct() {

		add_action( 'load-themes.php',  array( $this, 'themes_screen' ) );
		add_action( 'admin_print_scripts-themes.php', array( $this, 'enqueue' ) );

		// Browse themes
		add_action( 'load-theme-install.php',  array( $this, 'install_themes_screen' ) );
		add_action( 'admin_print_scripts-theme-install.php', array( $this, 'enqueue' ) );

	}

	/**
	 * The main template file for the themes.php screen
	 *
	 * Replaces entire contents of themes.php
	 * @require admin-header.php and admin-footer.php
	 */
	function themes_screen() {

		// Admin header
		require_once( ABSPATH . 'wp-admin/admin-header.php' );

		?>
		<div id="appearance" class="wrap">
			<h2><?php esc_html_e( 'Themes' ); ?><a href="<?php echo admin_url( 'theme-install.php' ); ?>" class="button button-secondary">Add New</a></h2>
		</div>
		<?php

		// Get the templates
		self::theme_template();
		self::search_template();
		self::theme_single_template();

		// Admin footer
		require( ABSPATH . 'wp-admin/admin-footer.php');
		exit;
	}

	/**
	 * Get the themes and prepare the JS object
	 * Sets attributes 'id' 'name' 'screenshot' 'description' 'author' 'version' 'active' ...
	 *
	 * @uses wp_get_themes self::get_current_theme
	 * @return array theme data
	 */
	protected function get_themes() {
		$themes = wp_get_themes( array(
			'allowed' => true
		) );

		$data = array();

		foreach( $themes as $slug => $theme ) {
			$data[] = array(
				'id'           => $slug,
				'name'         => $theme->get( 'Name' ),
				'screenshot'   => self::get_multiple_screenshots( $theme ),
				'description'  => $theme->get( 'Description' ),
				'author'       => $theme->get( 'Author' ),
				'authorURI'    => $theme->get( 'AuthorURI' ),
				'version'      => $theme->Version,
				'active'       => ( $slug == self::get_current_theme() ) ? true : NULL,
				'activateURI'  => wp_nonce_url( "themes.php?action=activate&amp;template=" . urlencode( $theme->Template ) . "&amp;stylesheet=" . urlencode( $slug ), 'switch-theme_' . $slug ),
				'customizeURI' => admin_url( 'customize.php?theme=' . $slug ),
			);
		}

		$themes = $data;
		return $themes;
	}

	/**
	 * Get current theme
	 * @uses wp_get_theme
	 * @return string theme slug
	 */
	protected function get_current_theme() {
		$theme = wp_get_theme();
		return $theme->template;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue() {

		// Relies on Backbone.js
		wp_enqueue_script( 'thx-38', plugins_url( 'thx-38.js', __FILE__ ), array( 'backbone' ), '20130817', true );
		wp_enqueue_style( 'thx-38', plugins_url( 'thx-38.css', __FILE__ ), array(), '20130817', 'screen' );

		// Passes the theme data and settings
		wp_localize_script( 'thx-38', '_THX38', array(
			'themes'   => $this->get_themes(),
			'settings' => array(
				'active' => __( 'Active Theme' ),
				'add_new' => __( 'Add New Theme' ),
				'isBrowsing' => ( get_current_screen()->id == 'theme-install' ) ? true : false,
				'install_uri' => admin_url( 'theme-install.php' ),
				'customizeURI' => admin_url( 'customize.php' ),
			),
			'browse' => array(
				'sections' => array(
					'featured' => __( 'Featured Themes' ),
					'popular'  => __( 'Popular Themes' ),
					'new'   => __( 'Newest Themes' ),
				),
				'publicThemes' => $this->get_default_public_themes(),
			),
		) );
	}

	/**
	 * Method to get an array of all the screenshots a theme has
	 * It checks for files in the form of 'screenshot-n' at the root
	 * of a theme directory.
	 *
	 * @param a theme object
	 * @returns array screenshot urls (first element is default screenshot)
	 */
	protected function get_multiple_screenshots( $theme ) {
		$base = $theme->get_stylesheet_directory_uri();
		$set = array( 2, 3, 4, 5 );

		// Screenshots array starts with default screenshot at position [0]
		$screenshots = array( $theme->get_screenshot() );

		// Check how many other screenshots a theme has
		foreach ( $set as $number ) {
			// Hard-coding file path for pngs...
			$file = '/screenshot-' . $number . '.png';
			$path = $theme->template_dir . $file;

			if ( ! file_exists( $path ) )
				continue;

			$screenshots[] = $base . $file;
		}

		return $screenshots;
	}

	/**
	 * The main template file for the theme-install.php screen
	 *
	 * Replaces entire contents of theme-install.php
	 * @require admin-header.php and admin-footer.php
	 */
	function install_themes_screen() {

		// Admin header
		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		?>
		<div id="appearance" class="wrap">
			<h2><?php esc_html_e( 'Themes' ); ?><a href="<?php echo admin_url( 'themes.php' ); ?>" class="button button-secondary">Back to your themes</a></h2>
			<div class="theme-categories"><span>Categories:</span> <a href="" class="current">All</a> <a href="">Photography</a> <a href="">Magazine</a> <a href="">Blogging</a>
		</div>
		<?php

		// Get the templates
		self::public_theme_template();
		self::search_template();
		self::public_theme_single_template();

		// Admin footer
		require( ABSPATH . 'wp-admin/admin-footer.php');
		exit;
	}

	/**
	 * Array containing the supported directory sections
	 *
	 * @return array
	 */
	protected function themes_directory_sections() {
		$sections = array(
			'featured' => __( 'Featured Themes' ),
			'popular'  => __( 'Popular Themes' ),
			'new'      => __( 'Newest Themes' ),
		);
		return $sections;
	}

	/**
	 * Gets public themes from the themes directory
	 * Used to populate the initial views
	 *
	 * @uses themes_api themes_directory_sections
	 * @return array with $theme objects
	 */
	protected function get_default_public_themes( $themes = array() ) {
		$sections = self::themes_directory_sections();
		$sections = array_keys( $sections );

		$args = array(
			'page' => 1,
			'per_page' => 4,
		);

		foreach ( $sections as $section ) {
			$args['browse'] = $section;
			$themes[ $section ] = themes_api( 'query_themes', $args );
		}

		return $themes;
	}

	/**
	 * Ajax request handler for public themes
	 *
	 * @uses get_public_themes
	 */
	public function ajax_puclic_themes() {
		$colors = self::get_public_themes( $_REQUEST );
		header( 'Content-Type: text/javascript' );
		echo json_encode( $response );
		die;
	}

	/**
	 * Gets public themes from the themes directory
	 *
	 * @uses get_public_themes
	 */
	public function get_public_themes( $args = array() ) {
		$defaults = array(
			'page' => 1,
			'per_page' => 4,
			'browse' => 'new',
		);

		$args = wp_parse_args( $args, $defaults );
		$themes = themes_api( 'query_themes', $args );
		return $themes;
	}


	/**
	 * ------------------------
	 * Underscores.js Templates
	 * ------------------------
	 */

	/**
	 * Underscores template for rendering the Theme views
	 */
	public function theme_template() {
		?>
		<script id="theme-template" type="text/template">
			<div class="theme-screenshot">
				<img src="<%= screenshot[0] %>" alt="" />
			</div>
			<h3 class="theme-name"><%= name %></h3>
			<% if ( active ) { %>
				<span class="current-label"><%= _THX38.settings['active'] %></span>
			<% } %>
			<a class="button button-primary" href="<%= _THX38.settings['customizeURI'] %>"><?php esc_html_e( 'Customize' ); ?></a>
			<a class="button button-secondary preview" href="<%= customizeURI %>"><?php esc_html_e( 'Preview' ); ?></a>
		</script>
		<?php
	}

	/**
	 * Underscores template for search form
	 */
	public function search_template() {
		?>
		<script id="theme-search-template" type="text/template">
			<input type="text" name="theme-search" id="theme-search" placeholder="Search..." />
		</script>
	<?php
	}

	/**
	 * Underscores template for single Theme views
	 * Displays full theme information, including description,
	 * author, version, larger screenshots.
	 */
	public function theme_single_template() {
		?>
		<script id="theme-single-template" type="text/template">
			<div id="theme-overlay">
				<a class="back button"><?php esc_html_e( 'Back to Themes' ); ?></a>
				<div class="theme-actions">
					<div class="active-theme">
						<a href="<%= _THX38.settings['customizeURI'] %>" class="button button-primary">Customize</a>
                        <a href="<?php echo admin_url( 'widgets.php'); ?>" class="button button-secondary">Widgets</a>
                        <a href="<?php echo admin_url( 'nav-menus.php'); ?>" class="button button-secondary">Menus</a>
					</div>
					<div class="inactive-theme">
						<a href="<%= activateURI %>" class="button button-primary">Activate</a>
						<a href="<%= customizeURI %>" class="button button-secondary">Preview</a>
					</div>
				</div>
				<div class="theme-wrap">
					<h3 class="theme-name"><%= name %><span class="theme-version"><%= version %></span></h3>
					<h4 class="theme-author">By <a href="<%= authorURI %>"><%= author %></a></h4>

					<div class="theme-screenshots" id="theme-screenshots">
						<div class="screenshot first"><img src="<%= screenshot[0] %>" alt="" /></div>
					<%
						if ( _.size( screenshot ) > 1 ) {
							_.each ( screenshot, function( image ) {
					%>
							<div class="screenshot thumb"><img src="<%= image %>" alt="" /></div>
					<%
							});
						}
					%>
					</div>

					<p class="theme-description"><%= description %></p>
				</div>
			</div>
		</script>
	<?php
	}

	/**
	 * Underscores template for rendering the Theme views
	 * on the browse directory
	 */
	public function public_theme_template() {
		?>
		<script id="public-theme-template" type="text/template">
			<div class="theme-screenshot">
				<img src="<%= screenshot_url %>" alt="" />
			</div>
			<h3 class="theme-name"><%= name %></h3>
			<a class="button button-secondary preview"><?php esc_html_e( 'Install' ); ?></a>
		</script>
		<?php
	}

	/**
	 * Underscores template for single Theme views from the public directory
	 * Displays full theme information, including description,
	 * author, version, larger screenshots.
	 */
	public function public_theme_single_template() {
		?>
		<script id="public-theme-single-template" type="text/template">
			<div id="theme-overlay">
				<h2 class="back button"><?php esc_html_e( 'Back to Themes' ); ?></h2>
				<div class="theme-wrap">
					<h3 class="theme-name"><%= name %><span class="theme-version"><%= version %></span></h3>
					<h4 class="theme-author">By <%= author %></h4>

					<div class="theme-screenshots" id="theme-screenshots">
						<div class="screenshot first"><img src="<%= screenshot_url %>" alt="" /></div>
					</div>

					<p class="theme-description"><%= description %></p>
				</div>
			</div>
		</script>
	<?php
	}

}

/**
 * Initialize
 */
new THX_38;