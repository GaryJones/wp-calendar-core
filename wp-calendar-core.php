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

// Comment the following line out when benchmarking.
add_filter( 'get_calendar', 'wp_posts_calendar' );
/**
 * Append the output of get_calendar() with our own markup.
 *
 * This filter is only needed for this plugin - not for integration.
 *
 * @since 0.1.0
 *
 * @return string Output for two posts calendars.
 */
function wp_posts_calendar( $calendar_output ) {
	return $calendar_output . _wp_posts_calendar_grid() . _wp_posts_calendar_grid_prev();
}


/**
 * Temp function to help with hooking the output of this plugin into the existing output.
 *
 * If loosely intepreted as an MVC, this function would be the Controller.
 *
 * @since 0.3.0
 *
 * @return string Calendar grid output.
 */
function _wp_posts_calendar_grid() {
	// Create calendar as data only (Model in MVC)
	//$calendar = new WP_Posts_Calendar();

	// Create a calendar view (View in MVC)
	$calendar_view = new WP_Calendar_View_Grid( new WP_Posts_Calendar );

	// Build the output
	return $calendar_view->build();
}

function _wp_posts_calendar_grid_prev() {
	$calendar_args = array(
		'month' => 3,
		'year'  => 2013,
	);
	$calendar = new WP_Posts_Calendar( $calendar_args );

	// Create a calendar view (View in MVC)
	$calendar_view = new WP_Calendar_View_Grid( $calendar );

	// Build the output
	return $calendar_view->build();
}



add_action( 'wp_footer', 'wp_calendar_benchmark' );
/**
 * Output benchmark results.
 *
 * Remember to comment out line 38 above when benchmarking.
 *
 * @since 0.3.0
 */
function wp_calendar_benchmark() {
	$loops = 10000;

	remove_filter( 'get_calendar', 'wp_posts_calendar' );

	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
		get_calendar( true, false );
	}
	$end = microtime( true );
	echo '<p><code>get_calendar()</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';


	wp_cache_delete( 'wp_calendar', 'calendar' );
	wp_cache_delete( 'wp_calendar_view', 'calendar' );
	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
	 	_wp_posts_calendar_grid();
	}
	$end = microtime( true );
	echo '<p><code>_wp_posts_calendar()</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';


	wp_cache_delete( 'wp_calendar', 'calendar' );
	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
	 	new WP_Posts_Calendar;
	}
	$end = microtime( true );
	echo '<p><code>new WP_Posts_Calendar</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';


	wp_cache_delete( 'wp_calendar', 'calendar' );
	wp_cache_delete( 'wp_calendar_view', 'calendar' );
	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
	 	new WP_Calendar_View_Grid ( new WP_Calendar );
	}
	$end = microtime( true );
	echo '<p><code>new WP_Calendar_View_Grid ( new WP_Calendar )</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';


	wp_cache_delete( 'wp_calendar', 'calendar' );
	wp_cache_delete( 'wp_calendar_view', 'calendar' );
	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
	 	new WP_Calendar_View_Grid ( new WP_Posts_Calendar );
	}
	$end = microtime( true );
	echo '<p><code>new WP_Calendar_View_Grid ( new WP_Posts_Calendar )</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';


	wp_cache_delete( 'wp_calendar', 'calendar' );
	wp_cache_delete( 'wp_calendar_view', 'calendar' );
	$start = microtime( true );
	foreach ( range( 1, $loops ) as $i ) {
	 	$cal = new WP_Calendar_View_Grid ( new WP_Posts_Calendar );
	 	$cal->build();
	}
	$end = microtime( true );
	echo '<p><code>new WP_Calendar_View_Grid ( new WP_Posts_Calendar ) then build()</code> x ' . $loops . ' = ' . ( $end - $start ) . '</p>';
}
