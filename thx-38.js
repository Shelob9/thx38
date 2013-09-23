( function($) {

	// Set up our namespace...
	var thx = thx || {};

	// Store the theme data and settings for organized and quick access
	// Set ups shortcuts to regularly used checks
	thx.Data = _THX38;
	// Shortcut for (bool) isBrowsing check
	thx.browsing = thx.Data.settings['isBrowsing'];

	thx.Theme = Backbone.Model.extend({});

	// Main view controller for themes.php and theme-install.php
	// Unifies and renders all available views
	//
	// Hooks to #appearance and organizes the views to be rendered
	// based on which screen we are currently viewing
	thx.View = Backbone.View.extend({

		// Append to container
		el: '#appearance',

		// Main render control
		render: function() {
			var self = this,
				view;

			// If this is a browsing view bypass all the rest of the render function
			// by returning the specific renderBrowsing function
			if ( thx.browsing )
				return self.renderBrowsing();

			// Setups the main theme view...
			self.view = new thx.ThemesView({
				collection: self.collection
			});
			// Render and append
			self.view.render();
			self.$el.append( self.view.el );

			// Search form
			self.renderSearch();
		},

		// Search input and view
		renderSearch: function() {
			var self = this,
				view;

			self.view = new thx.Search({
				collection: self.collection
			});

			self.view.render();
			// Append after screen title
			this.$el.find( '> h2' ).after( self.view.el );
		},

		// Handles all the rendering of the public theme directory
		renderBrowsing: function() {
			var self = this;

			// Set ups the view and passes the section argument
			self.view = new thx.BrowseThemesView({
				collection: self.collection,
				section: self.options.section
			});
			// Render and append
			self.view.render();
			self.$el.append( self.view.el );
		}
	});

	// Set up the Collection for our theme data
	// @has 'id' 'name' 'screenshot' 'author' 'authorURI' 'version' 'active' ...
	thx.Themes = Backbone.Collection.extend({

		model: thx.Theme,

		// Search terms
		terms: '',

		// Controls searching on the current theme collection
		// and triggers an update event
		doSearch: function( value ) {
			var results;

			// Updates terms with the value passed
			this.terms = value;

			// If we have terms, run a search...
			if ( this.terms.length > 0 ) {
				this.search( this.terms );
			}

			// If search is blank, show all themes
			// Useful for resetting the views when you clean the input
			if ( this.terms === '' ) {
				this.reset( thx.Data.themes );
			}

			// Trigger an 'update' event
			this.trigger( 'update' );
		},
		// Performs a search within the collection
		// @uses RegExp
		search: function( term ) {
			var self = this,
				match, results;

			// Start with a full collection
			self.reset( thx.Data.themes );

			// The RegExp to match
			match = new RegExp( term, 'i' );

			// Find results
			// _.filter and .test
			results = self.filter( function( data ) {
				return match.test( data.get( 'name' ) );
			});

			self.reset( results );
		},
	});

	// This is the view that controls each theme item
	// that will be displayed on the screen
	thx.ThemeView = Backbone.View.extend({

		// Wrap theme data on a div.theme element
		className: 'theme',

		// The HTML template for each element to be rendered
		html: _.template(
			$( '#theme-template, #public-theme-template' ).html()
		),

		// The HTML template for the theme overlay
		overlay: _.template(
			$( '#theme-single-template, #public-theme-single-template' ).html()
		),

		events: {
			'click': 'expand'
		},

		render: function() {
			var self = this,
				data = self.model.toJSON();

			self.$el.html( self.html( data ) );
			// Renders active theme styles
			self.activeTheme();
		},

		// Adds a class to the currently active theme
		activeTheme: function() {
			if ( this.model.has( 'active' ) ) {
				this.$el.addClass( 'active' );
			}
		},

		// Single theme overlay screen
		// It's shown when clicking a theme
		expand: function() {
			var self = this,
				container = $( '#appearance' ),
				slug, data;

			data = self.model.toJSON();

			// Hide all themes so window height resets...
			container.find( '.theme' ).hide();

			// Append theme overlay
			// resues the data object to populate the view
			if ( ! thx.browsing )
				self.$el.parent().append( self.overlay( data ) );
			else
				container.append( self.overlay( data ) );

			// Closing overlay...
			container.on( 'click', '.back', function() {
				// Restore theme grid visibility and removes overlay
				container.find( '.theme' ).show();
				$( '#theme-overlay' ).remove();
			});

			// Renders a screenshot gallery with *dot* navigation
			self.screenshotGallery();
		},

		// Setups an image gallery using the theme screenshots supplied by a theme
		screenshotGallery: function() {
			var screenshots = $( '#theme-screenshots' ),
				img;

			screenshots.find( 'div.first' ).next().addClass( 'selected' );

			// Clicking on a screenshot thumbnail drops it
			// at the top of the stack in a larger size
			screenshots.on( 'click', 'div.thumb', function() {
				current = $( this );
				img = $( this ).find( 'img' ).clone();

				current.siblings( '.first' ).html( img );
				current.siblings( '.selected' ).removeClass( 'selected' );
				current.addClass( 'selected' );
			});
		}
	});

	// Controls the rendering of div#themes,
	// a wrapper that will hold all the theme elements
	thx.ThemesView = Backbone.View.extend({

		id: 'themes',

		initialize: function() {
			var self = this;

			// Move the active theme to the beginning of the collection
			self.currentTheme();

			// When the collection is updated by user input...
			self.collection.on( 'update', function() {
				self.currentTheme();
				self.render( this );
			});
		},

		render: function() {
			var self = this,
				view;

			// Clear the DOM, please
			self.$el.html( '' );

			// Loop through the themes and setup each theme view
			self.collection.each( function( theme ) {
				view = new thx.ThemeView({
					model: theme
				});

				// Render the views...
				view.render();
				// and append them to div#themes
				self.$el.append( view.el );
			});

			// 'Add new theme' element shown at the end of the grid
			self.$el.append( '<div class="theme add-new"><a href="' + thx.Data.settings['install_uri'] + '" class="theme-screenshot"><span>' + thx.Data.settings['add_new'] + '</span></a></div>' );
		},

		// Grabs current theme and puts it at the beginning of the collection
		currentTheme: function() {
			var self = this,
				current;

			current = self.collection.findWhere({ active: true });

			// Move the active theme to the beginning of the collection
			if ( current ) {
				self.collection.remove( current );
				self.collection.add( current, { at:0 } );
			}
		}

	});

	// Renders the different Browse Themes sections
	// Thse are submitted by the Data.browse['sections'] property
	thx.BrowseThemesView = Backbone.View.extend({

		className: 'theme-linear-grid',

		initialize: function() {
			var self = this;

			// When the collection is updated by user input...
			self.collection.on( 'update', function() {
				self.render( this );
			});
		},

		render: function() {
			var self = this,
				view;

			// Clear the DOM, please
			self.$el.html( '' );

			// Sets up the theme section titles
			// using the passed option argument
			self.$el.append( '<h3 class="theme-section">' + thx.Data.browse['sections'][ self.options.section ] + '</h3><div class="themes"></div>' );

			// Loop through the themes and setup each theme view
			self.collection.each( function( theme ) {
				view = new thx.ThemeView({
					model: theme
				});

				// Render the views...
				view.render();
				// and append them to div#themes
				self.$el.find( '.themes' ).append( view.el );
			});

			// 'Show more themes' element at the end of the stripe
			self.$el.append( '<div class="show-more-themes"><span class="dashicons dashicons-arr-right"></span></div>' );
			self.showMore();
		},

		// The show more button slides new themes into view
		// It will trigger the Ajax calls to themes_api...
		showMore: function() {
			var self = this,
				height;

			// Adjusts the size of the 'show more' button based on screenshot size
			$( window ).on( 'load resize', function(){
				height = self.$el.height();
				$( '.show-more-themes' ).height( height );
			});
		}

	});

	// Search input view controller
	// renders #search-form
	thx.Search = Backbone.View.extend({

		className: 'search-form',

		// 'keyup' triggers search
		events: {
			'keyup #theme-search': 'search'
		},

		// Grabs template file
		html: _.template( $( '#theme-search-template' ).html() ),

		// Render the search form
		render: function() {
			var self = this;
			self.$el.html( self.html );
		},

		// Runs a search on the theme collection
		// bind on 'keyup' event
		search: function() {
			this.collection.doSearch( $( '#theme-search' ).val() );
		}
	});

	// Execute and setup the application
	thx.Run = {
		init: function() {
			var self = this;

			// Initializes the blog's theme library view
			//
			// If it's the 'browsing themes' screen, render the directory instead

			if ( ! thx.browsing ) {

				// Create a new collection with data
				self.themes = new thx.Themes( thx.Data.themes );

				// Set up the view
				self.view = new thx.View({
					collection: self.themes
				});

				// Render results
				self.view.render();

			} else {

				// Loop through the different theme sections
				// and sets up each one of them to be rendered
				for ( var section in thx.Data.browse['sections'] ) {

					// Create a new collection with the proper theme data
					// for each section
					self.themes = new thx.Themes( thx.Data.browse['publicThemes'][ section ]['themes'] );

					// Set up the view
					// Passes the 'section' as an option
					self.view = new thx.View({
						collection: self.themes,
						section: section
					});

					// Render results
					self.view.render();
				}
			}
		}
	};

	// Ready...
	jQuery( document ).ready( function() {

		// Bring on the themes
		thx.Run.init();

	});

})( jQuery );