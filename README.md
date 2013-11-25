# WP Calendar Core [![Build Status](https://secure.travis-ci.org/GaryJones/wp-calendar-core.png?branch=master)](http://travis-ci.org/GaryJones/wp-calendar-core) [![Coverage Status](https://coveralls.io/repos/GaryJones/wp-calendar-core/badge.png)](https://coveralls.io/r/GaryJones/wp-calendar-core)

Designed to be a replacement for the monstrous [`get_calendar()`](http://core.trac.wordpress.org/browser/tags/3.7.1/src/wp-includes/general-template.php#L1124) with the intention to integrate with core when stable.

## Description

While a similar name, this plugin bears no connection to [WP Calendar](http://wordpress.org/plugins/wp-calendar), an existing plugin.

### Why create this plugin?

I have an old plugin, [Calendar Category](https://github.com/GaryJones/calendar-category/), which aims to limit the posts shown in a typical calendar widget to just those from a certain category. To add in the limitations needed for this, I had to duplicate the whole of `get_category()` just to amend a few lines, and then go further to tell the widget to use my custom function for generating the calendar grid. The duplicate function makes use of smaller functions that are specific to getting day and month links, and these too had to be duplicated and tweaked.

I've wanted to see this code improved for a few years. While there are a few empty lines dating back ten years (Jan 2004), and a few more recent tweaks, the bulk of the `get_category()` function is around 8 years old.

### Goals

The goals of this feature-as-a-plugin are:

* Break up `get_calendar()`, so that there's different code for creating _a_ calendar, and creating this specific published posts calendar.
* Separate functions / methods for checking of cache, getting data (raw SQL), and displaying data(HTML), etc.
* See if new date queries can be used instead of raw queries.
* Allow event or other plugins to re-use some functionality, but with different data and views.

### Approach

I'd like to take a step back and break down `get_category()` into several classes. The following are all tentatively named.

#### `WP_Calendar`

A base class for gathering calendar data. Sets up some time-based data values that will apply to any calendar. Contains a few generic methods that specific calendars might find useful.

#### `WP_Posts_Calendar`

An extension of `WP_Calendar`, it is one example of a calendar that gathers specific data - in this case, dates and titles of published posts (to match existing functionality). Other examples could be for events (CPT), revision history, dates when new users joined, ecommerce sales, or dashboard activity. If data has an associated date, it can have a calendar class which gathers that data. In an MVC system, this class would be considered to be most like a Model.

The data is expected to be a multi-dimensional array. Numerical associative keys are for each day (themselves an array containing one or more pieces of data e.g. post titles), while non-numerical keys are for other pieces of data. The latter may have a few required keys, such as `monthly_label`, but otherwise these classes can include whatever data is known to be needed, given a limited number of views it will be used with. In reality, a custom calendar and custom view can communicate data however they want since the whole model object is injected as a dependency into the view object.

My Category Calendar plugin, as an example, could extend this class and over-ride just the methods that collect the data to ensure only posts in a certain category are included, leaving the building up of the grid view essentially untouched.

#### `WP_Calendar_View`

A base class for calendar views, it contains minimal methods that act as the API for interacting with the presentation of calendars - set an argument, build the output and display that output.

#### `WP_Calendar_View_Grid`

A specific implementation of presenting calendar data, extended from `WP_Calendar_View`. Fictional sibling classes might be `WP_Calendar_View_List`, `WP_Calendar_View_Vcard` or `WP_Calendar_View_Xml`. It builds up the markup from the data provided - for instance, a label of "November 2013" might be shown as a `<caption>` in the grid (table) view, but as a heading in a list, a field in a vcard, or a title element or root element attribute in XML. In an MVC system, this class would be considered to be the view. It receives the model class in the constructor.


With these classes in place, I see the 199-line `get_calendar()` function being rewritten as something like:

~~~php
function get_calendar( $initial = true, $echo = true ) {
	$calendar = new WP_Posts_Calendar();

	$calendar_view = new WP_Calendar_View_Grid( $calendar );
	$calendar_view_>set_arg( 'initial', $initial );

	if ( $echo ) {
		$calendar_view->display();
	} else {
		return $calendar_view->build();
	}
}
~~~

An example function for a different type and view of calendar, perhaps implemented in an events plugin:

~~~php
function prefix_show_events_list() {
	// Args for getting the correct data
	$calendar_args = array(
		'include_future_events' => 'true',
	);

	// Get the calender data
	$events_data = new Prefix_Events_Calendar( $calendar_args );

	// Build the calendar with the data
	$events_calander = new Prefix_Events_Calendar_View_List( $events_data );

	// Show the calendar as a list
	$events_calendar->display();
}
~~~

### Over-engeering?

A valid point was raised to me, to avoid over-engineering this improvement. I've taken two examples within WordPress core to show how I don't think this plugin is over-engineered.

#### 1. `WP_Lists_Table`

If you look at the sub-classes of `WP_Lists_Table`, many of them contain methods of the same name, when over-riding the appearance of the displaying rows, for instance. `WP_Lists_Table` contains a basic implementation itself though as a sensible default. The `WP_Calendar_View_Grid` class in this plugin takes the same approach. Should `WP_List_Table` ever support showing as a grid or something other than a table, I would expect a base class of `WP_List` to be created, which matches up to a similar abstraction level as `WP_Calendar_View`.

#### 2. `WP_Upgrader` + `WP_Upgrader_Skin`

The code for handling upgrades is broadly split into two groups of classes - the bit that does the updating, and the bit that shows feedback from that.

The first is handled by a base class of `WP_Upgrader` which is then extended into specific classes for plugins, themes, language packs and core, and this matches up to the abstraction level of `WP_Calendar` then `WP_Posts_Calendar` (and whatever ever other specific calendar data-collection classes other plugins or core might introduce).

The second group is a base class of `WP_Upgrader_Skin`, then skins for plugin updates, theme updates, general bulk updating, bulk plugin updates, bulk theme updates, plugin installer, theme installer, language packs updater and core updater. This separation matches up to `WP_Calender_View` then `WP_Calendar_View_Grid` plus any further view classes that might be introduced by core or plugins.

Beyond these examples, the implementation of communication between any new calendar and calendar view classes is left open - there's a couple of pre-set property names (and even these could be ignored since the whole model is passed to the view), but otherwise there's few limitations.

### Considerations

Markup for the calendar widget should stay the same, although extra classes might be useful. Other plugins should be able to filter the default markup though.

Look at existing event plugins, and others which display a calendar of some sort, to see what features they would need to be able to make use of the new code.

Decide whether a more formal OOP approach should be used e.g. registering data to individual days, instead of grabbing an array of all days with data and passing that through. The former could allow multiple types of data being added to the same calendar.

The naming of the classes has been considered. Different _types_ of calendar (data) are given before the word "calendar", and different views are given after. This allows combination of names to represent which calendar is being shown with which view e.g. `$wp_posts_calendar_grid`, `$wp_posts_calendar_list`, `$wp_activity_calendar_grid`, `$wp_upcomingevents_calendar_json`, etc.

### Progress

The plugin is at a working stage - by default it will add a second calendar to the `get_calendar()` output, so that comparison between the old code and new code output can be made.

The following tasks are outstanding:

* Consider how existing `id` attributes in HTML can be addressed, since multiple instances will cause invalid markup, but removing them might break theme styling.
* Document code.
* Add any filters (though most methods are small enough that sub-classes can over-ride them anyway).
* Optimize caching for all stages.
* Create proof of concept examples that re-use the new calendar classes for other instances (not just published posts).

### Unit Tests

Currently includes one unit test (asserting true is true) to show testing works. Needs proper unit tests.

### Performance Benchmarks

See the main plugin file for the attempt at benchmarking.

When there is a single loop, the `WP_Posts_Calender + WP_Calender_View_Grid` solution takes approximately twice as long as `get_calendar()`, and when there are 10,000 loops, it's a 4~5x increase. Caching has been implemented, but perhaps not in the best way.

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
