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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-posts-calendar.php';

add_filter( 'get_calendar', 'wp_posts_calendar' );
/**
 * Replace the output of get_calendar() with our own markup.
 *
 * This filter is only needed for this plugin - not for integration.
 *
 * @since 0.1.0
 *
 * @return string Output for a posts calendar.
 */
function wp_posts_calendar( $calendar_output ) {
	$calendar = new WP_Posts_Calendar();
	return $calendar_output . $calendar->build();
}
