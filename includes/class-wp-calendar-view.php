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


}
