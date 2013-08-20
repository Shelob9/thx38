( function($) {

	// Set up our namespace...
	var thx = thx || {};

	thx.Theme = Backbone.Model.extend({});

	// Main view controller
	// Unifies and renders all views
	thx.View = Backbone.View.extend({

		// Append to container
		el: '#appearance',

		render: function() {
			var self = this;

			// Setups the main theme view...
			self.$view = new thx.ThemesView({
				collection: self.collection
			});
			// Render and append
			self.$view.render();
			self.$el.append( self.$view.el );

			// Other views (search, filters, single-view) will go here...
		}
	});

	// Set up the Collection for our theme data
	// @has 'id' 'name' 'screenshot' 'active' ...
	thx.Themes = Backbone.Collection.extend({
		model: thx.Theme
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

		render: function() {
			var self = this,
				data = self.model.toJSON();

			self.activeTheme();
			self.$el.html( self.html( data ) );
		},
		// Adds a class the currently active theme
		activeTheme: function() {
			if ( this.model.has( 'active' ) ) {
				this.$el.addClass( 'active' );
			}
		}
	});

	// Controls the rendering of div#themes,
	// a wrapper that will hold all the theme elements
	thx.ThemesView = Backbone.View.extend({

		id: 'themes',

		initialize: function() {
			var self = this,
				current = self.collection.findWhere({ active: true });

			// Move the active theme to the beginning of the collection
			if ( current ) {
				self.collection.remove( current );
				self.collection.add( current, { at:0 } );
			}
		},

		render: function() {
			var self = this,
				view;

			self.collection = this.collection;

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