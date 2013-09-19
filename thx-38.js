( function($) {

	// Set up our namespace...
	var thx = thx || {};

	thx.Theme = Backbone.Model.extend({});

	// Main view controller
	// Unifies and renders all views
	thx.View = Backbone.View.extend({

		// Append to container
		el: '#appearance',

		// Main render control
		render: function() {
			var self = this,
				view;

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
		}
	});

	// Set up the Collection for our theme data
	// @has 'id' 'name' 'screenshot' 'active' ...
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
			$( '#theme-template' ).html()
		),

		// The HTML template for the theme overlay
		overlay: _.template(
			$( '#theme-single-template' ).html()
		),

		events: {
			'click': 'expand'
		},

		render: function() {
			var self = this,
				data = self.model.toJSON();

			self.activeTheme();
			self.$el.html( self.html( data ) );
		},

		// Adds a class to the currently active theme
		activeTheme: function() {
			if ( this.model.has( 'active' ) ) {
				this.$el.addClass( 'active' );
			}
		},

		// Single theme overlay
		// shown when clicking a theme
		expand: function() {
			var self = this,
				container = $( '#appearance' ),
				slug, data;

			data = self.model.toJSON();

			// Hide all themes so window height resets...
			container.find( '.theme' ).hide();

			// Append theme overlay
			self.$el.parent().append( self.overlay( data ) );

			// Closing overlay...
			container.on( 'click', '.back', function() {
				// Restore theme grid visibility and removes overlay
				container.find( '.theme' ).show();
				$( '#theme-overlay' ).remove();
			});

			self.screenshotGallery();
		},

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

	// Store the theme data and settings for organized access
	thx.Data = _THX38;

	// Execute and setup the application
	thx.Run = {
		init: function() {
			var self = this;

			// Create a new collection with data
			self.themes = new thx.Themes( thx.Data.themes );

			// Set up the view
			self.view = new thx.View({
				collection: self.themes
			});

			// Render results
			self.view.render();
		}
	};

	// Ready...
	jQuery( document ).ready( function() {

		// Bring on the themes
		thx.Run.init();

	});

})( jQuery );