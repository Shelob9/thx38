<?php
/*
Plugin Name: THX_38
Plugin URI:
Description: THX stands for THeme eXperience. A plugin that rebels against their rigidly controlled themes.php in search for hopeful freedom in WordPress 3.8, or beyond. <strong>This is only for development work and the brave of heart, as it totally breaks themes.php</strong>.
Version: 0.1
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

		self::theme_template();

		// Admin footer
		require( './admin-footer.php' );
		exit;
	}

	/**
	 * Get the themes and prepare the JS object
	 * Sets attributes 'id' 'name' 'screenshot' 'active' ...
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
				'screenshot' => $theme->get_screenshot(),
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
				<img src="<%= screenshot %>" alt="" />
			</div>
			<h3 class="theme-name"><%= name %></h3>
			<% if ( active ) { %>
				<span class="current-label"><?php esc_html_e( 'Active' ); ?></span>
			<% } %>
		</script>
		<?php
	}

}

/**
 * Initialize
 */
new THX_38;