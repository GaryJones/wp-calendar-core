<?php
/**
 * WP Calendar Core
 *
 * Designed to be a replacement for the monstrous get_calendar() with the intention to integrate with core when stable.
 *
 * @package           WP_Calendar_Core
 * @author            Gary Jones <gary@garyjones.co.uk>
 * @license           GPL-2.0+
 * @link              https://github.com/GaryJones/wp-calendar
 * @copyright         2013 Gary Jones, Gamajo Tech
 *
 * @wordpress-plugin
 * Plugin Name:       WP Calendar Core
 * Plugin URI:        https://github.com/GaryJones/wp-calendar-core
 * Description:       Designed to be a replacement for the monstrous get_calendar() with the intention to integrate with core when stable.
 * Version:           0.1.0
 * Author:            Gary Jones
 * Author URI:        http://gamajo.com/
 * Text Domain:       wp-calendar-core
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/GaryJones/wp-calendar-core
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-calendar.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-calendar-view.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-calendar-view-grid.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-posts-calendar.php';

add_filter( 'get_calendar', 'wp_posts_calendar' );
/**
 * Replace the output of get_calendar() with our own markup.
 *
 * This filter is only needed for this plugin - not for integration.
 *
 * If loosely intepreted as an MVC, this function would be the Controller.
 *
 * @since 0.1.0
 *
 * @return string Output for a posts calendar.
 */
function wp_posts_calendar( $calendar_output ) {
	// Create calendar as data only (Model in MVC)
	$calendar = new WP_Posts_Calendar();

	// Create a calendar view (View in MVC)
	$calendar_view = new WP_Calendar_View_Grid( $calendar );

	// Build the output
	return $calendar_output . $calendar_view->build();
}

/**
 * An example function for a different type and view of calendar, perhaps implemented in an events plugin.
 *
 * Attach to an action hook, or use within a theme via:
 *
 * ~~~
 * if ( function_exists( 'prefix_show_events_lists' ) ) {
 *     prefix_show_events_lists();
 * }
 * ~~~
 */
function prefix_show_events_list() {
	// Args for getting the right data
	$calendar_args = array(
		'include_future_events' => 'true',
	);
	// Get the calender data
	$events_data = new Prefix_Events_Calendar( $calendar_args );

	// Build the calendar with the data
	$events_calander = new Prefix_Events_Calendar_View_List( $events_data );

	// Show the calendar
	$events_calendar->display();
}
