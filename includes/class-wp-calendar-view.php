<?php
/**
 * WP Calendar Core
 *
 * @package   WP_Calendar_Core
 * @author    Gary Jones <gary@garyjones.co.uk>
 * @license   GPL-2.0+
 * @link      https://github.com/GaryJones/wp-calendar
 * @copyright 2013 Gary Jones, Gamajo Tech
 */

/**
 * Base Class for a building a Calendar view.
 *
 * If loosely intepreted as an MVC, this class, and classes extended from it, would be the View.
 *
 * @since 0.2.0
 *
 * @package WP_Calendar_Core
 * @author  Gary Jones <gary@garyjones.co.uk>
 */
class WP_Calendar_View {

	/**
	 * Data model.
	 *
	 * @since 0.2.0
	 *
	 * @type WP_Calendar
	 */
	protected $calendar;

	/**
	 * Hold view arguments.
	 *
	 * @since 0.2.0
	 *
	 * @type array
	 */
	protected $args;

	/**
	 * Assign dependencies to properties.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Calendar $calendar Data model.
	 */
	public function __construct( WP_Calendar $calendar ) {
		$this->calendar = $calendar;
	}

	public function set_arg( $key, $value ) {
		$this->args[ $key ] = $value;
	}

	public function build() {
		die( 'function WP_Calendar_View::build() must be over-ridden in a sub-class.' );
	}

	public function display() {
		echo $this->build(); // xss ok
	}

	protected function cache_key() {
		return get_class( $this ) . get_class( $this->calendar ) . $this->calendar['month'] . $this->calendar['year'];
	}

	protected function is_cached() {
		return $this->get_cache();
	}

	protected function set_cache( $output ) {
		$cache[ $this->cache_key() ] = $output;
		wp_cache_set( 'wp_calendar_view', $cache, 'calendar' );
	}

	protected function get_cache() {
		$key = $this->cache_key();
		if ( $cache = wp_cache_get( 'wp_calendar_view', 'calendar' ) ) {
			if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
				return $cache[ $key ];
			}
		}
	}


}
