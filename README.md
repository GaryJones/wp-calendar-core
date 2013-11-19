# WP Calendar Core

Designed to be a replacement for the monstrous get_calendar() with the intention to integrate with core when stable.

## Description

While a similar name, this plugin bears no connection to [WP Calendar](http://wordpress.org/plugins/wp-calendar), an existing plugin.

### Why create this plugin?

I have an old plugin, [https://github.com/GaryJones/calendar-category/](Calendar Category), which aims to limit the posts shown in a typical calendar widget to just those from a certain category. To add in the limitations needed for this, I had to duplicate the whole of `get_category()` just to amend a few lines, and then go further to tell the widget to use my custom function for generating the calendar table.

I've wanted to see this code improved for a few years. While there are a few empty lines dating back ten years (Jan 2004), and a few more recent tweaks, the bulk of the `get_category()` function is around 8 years old.

### Goals

The goals of this feature-as-a-plugin are:

* Break up `get_calendar()`, so that there's different code for creating _a_ calendar, and creating this specific calendar.
* Separate functions / methods for checking of cache, getting data (raw SQL), and displaying data(HTML), etc.
* See if new date queries can be used instead of raw queries.
* Allow event plugins to potentially re-use some functionality, but with different data.
* Ensure creation of calendar markup is separate from widget, so it could also be used via a shortcode, etc.

### Approach

I'd like to take a step back from this, and break down `get_category()`. There should be a base class (tentatively `WP_Calendar`) for displaying any data on a calendar, like `WP_List_Table` does for lists. This could be events (CPT), or dates when new users joined, ecommerce sales, or dashboard activity etc. Some of the method names would likely use the appropriate ones from `WP_Lists_Table` for consistency.

Secondly, a class (tentatively `WP_Posts_Calendar`) for displaying published posts on a calendar, that replaces the specific data-source implementation within `get_calendar()`. Ultimately, I see `get_calendar()` as being as simple as:

~~~php
function get_calendar( $initial = true, $echo = true ) {
	$calendar = new WP_Posts_Calendar( $initial );
	if ( $echo ) {
		$calendar->display();
	} else {
		return $calendar->build();
	}
}
~~~

The base calendar class could include both a table view and a list view, though this would be considered as a later feature.

### Considerations

Markup for the calendar widget should stay the same, although extra classes might be useful. Other plugins should be able to filter the default markup though.

Look at existing event plugins, and others which display a calendar of some sort, to see what features they would need to be able to make use of the new code.

### Progress

The plugin is at a working stage - by default it will add a second calendar to the `get_calendar()` output, so that comparison between the old code and new code output can be made.

The following tasks are outstanding:

* Move methods between classes, consider creating a separate table view class. It's currently a mess.
* Consider how existing `id` attributes in HTML can be addressed, since multiple instances will cause invalid markup, but removing them might break theme styling.
* Document code.
* Add any filters (though most methods are small enough that sub-classes can over-ride them anyway).
* Reimplement caching for generated markup.
* Create proof of concept examples that re-use the new calendar classes for other instances (not just published posts).

## Contributions

I'm open for contributions from others, for code, suggestions for an improved approach, and unit tests. If you've got a plugin that could be rewritten to make use of the code in this plugin, then I'd love to hear from you to be able to use it as another proof of concept.

Patches to the develop branch please (git-flow).

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

Using git, browse to your `/wp-content/plugins/` directory and clone this repository:

`git clone git@github.com:GaryJones/wp-calendar-core.git`

Then go to your Plugins screen and click __Activate__.

## Updates

This plugin supports the [GitHub Updater](https://github.com/afragen/github-updater) plugin, so if you install that, this plugin becomes automatically updateable direct from GitHub. Any integration with WP will make this redundant.

## Usage

Just activate the plugin, then display a calendar widget.

Future versions will include further proof of concept examples to show re-usability of the code.

## Credits

Built by [Gary Jones](https://twitter.com/GaryJ)  
Copyright 2013 [Gamajo Tech](http://gamajo.com/)
