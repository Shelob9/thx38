<?php
/*
Plugin Name: THX_38
Plugin URI:
Description: THX stands for THeme eXperience. A plugin that rebels against their rigidly controlled themes.php in search for hopeful freedom in WordPress 3.8, or beyond. <strong>This is only for development work and the brave of heart, as it totally breaks themes.php</strong>.
Version: 0.4
Author: THX_38 Team
*/

class THX_38 {

	function __construct() {

		add_action( 'load-themes.php',  array( $this, 'themes_screen' ) );
		add_action( 'admin_print_scripts-themes.php', array( $this, 'enqueue' ) );

	}

	/**
	 * The main template file for the themes.php screen
	 *
	 * Replaces entire contents of themes.php
	 * @require admin-header.php and admin-footer.php
	 */
	function themes_screen() {

		// Admin header
		require_once( './admin-header.php' );

		?>
		<div id="appearance" class="wrap">
			<h2><?php esc_html_e( 'Themes' ); ?></h2>
		</div>
		<?php

		// Get the templates
		self::theme_template();
		self::search_template();
		self::theme_single_template();

		// Admin footer
		require( './admin-footer.php' );
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
				'id' => $slug,
				'name' => $theme->get( 'Name' ),
				'screenshot' => self::get_multiple_screenshots( $theme ),
				'description' => $theme->get( 'Description' ),
				'author' => $theme->get( 'Author' ),
				'version' => $theme->Version,
				'active' => ( $slug == self::get_current_theme() ) ? true : NULL,
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
				'active' => __( 'Active' ),
				'add_new' => __( 'Add New Theme' ),
				'install_uri' => admin_url( 'theme-install.php' ),
			)
		) );
	}

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
				<span class="current-label"><?php esc_html_e( 'Active' ); ?></span>
			<% } %>
			<a class="button button-primary"><?php esc_html_e( 'Customize' ); ?></a>
			<a class="button button-secondary"><?php esc_html_e( 'Activate' ); ?></a>
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
				<h2 class="back button"><?php esc_html_e( 'Back to Themes' ); ?></h2>
				<div class="theme-wrap">
					<h3 class="theme-name"><%= name %><span class="theme-version"><%= version %></span></h3>
					<h4 class="theme-author">By <%= author %></h4>

					<div class="theme-screenshots" id="theme-screenshots">
					<% _.each ( screenshot, function( image ) { %>
						<div class="screenshot"><img src="<%= image %>" alt="" /></div>
					<% }); %>
					</div>

					<p class="theme-description"><%= description %></p>
				</div>
			</div>
		</script>
	<?php
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

}

/**
 * Initialize
 */
new THX_38;