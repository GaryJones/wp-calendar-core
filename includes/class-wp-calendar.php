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
 * Base class for calender representations.
 *
 * If loosely intepreted as an MVC, this class, and classes extended from it, would be the Model.
 *
 * @since 0.1.0
 *
 * @package WP_Calendar_Core
 * @author  Gary Jones <gary@garyjones.co.uk>
 */
class WP_Calendar implements ArrayAccess {

	protected $data;

	/**
	 * Various information about the current calendar.
	 *
	 * @since 0.1.0
	 *
	 * @type array
	 */
	protected $args;

	/**
	 * Populate properties.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Calendar args.
	 */
	public function __construct( $args = array() ) {
		$this->args = $args;

		$this->set_data();
	}

	/**
	 * Is the date being considered, today?
	 *
	 * @since 0.1.0
	 *
	 * @param  integer $day A day of the month, e.g. 1, 14, or 31.
	 * @return boolean      True if the year, month and day match today.
	 */
	public function is_today( $day ) {
		$now = current_time( 'timestamp' );

		return $day == gmdate( 'j', $now ) && $this->data['month'] == gmdate( 'm', $now ) &&
			$this->data['year'] == gmdate( 'Y', $now );
	}

	/**
	 * Return the number of days since the start of the week.
	 *
	 * The start of the week comes from the site setting of the same name.
	 *
	 * @since 0.1.0
	 *
	 * @return integer Days since the start of the week.
	 */
	public function days_since_start_of_week() {
		// 'w' = Numeric representation of the day of the week
		return calendar_week_mod( date( 'w', $this->data['unix_month'] ) - $this->data['week_begins'] );
	}

	public function offsetSet( $offset, $value ) {
        if ( is_null( $offset ) ) {
            $this->data[] = $value;
        } else {
            $this->data[ $offset ] = $value;
        }
    }

    public function offsetExists( $offset ) {
        return isset( $this->data[ $offset ] );
    }

    public function offsetUnset( $offset ) {
        unset($this->data[ $offset ] );
    }

    public function offsetGet( $offset ) {
        return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
    }

	/**
	 * Identify browsers from UA strings that can handle multiline title attributes.
	 *
	 * Quirky functionality that should be moved outside of this class, since it's more generic than just for calendars.
	 *
	 * @todo Check how accurate this multiline tooltip functionality check is.
	 *
	 * @since 0.2.0
	 *
	 * @return boolean True if the user-agent string contains "MSIE", "camino" or "safari".
	 */
	protected function supports_multiline_titles() {
		// Apparently these browsers can do multi-line title tooltips...
		return strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) !== false ||
			stripos( $_SERVER['HTTP_USER_AGENT'], 'camino' ) !== false ||
			stripos( $_SERVER['HTTP_USER_AGENT'], 'safari' ) !== false;
	}

	/**
	 * Work out which month and year is being considered.
	 *
	 * @since 0.1.0
	 */
	protected function set_data() {
		global $m, $monthnum, $year;

		if ( isset( $_GET['w'] ) ) {
			$w = '' . intval( $_GET['w'] );
		}

		// Let's figure out when we are
		if ( ! empty( $this->args['month'] ) && ! empty( $this->args['year'] ) ) {
			$this->data['month'] = '' . zeroise( intval( $this->args['month'] ), 2 );
			$this->data['year']  = '' . intval( $this->args['year'] );
		} elseif ( ! empty( $monthnum ) && ! empty( $year ) ) {
			$this->data['month'] = '' . zeroise( intval( $monthnum ), 2 );
			$this->data['year']  = '' . intval( $year );
		} elseif ( ! empty( $w ) ) {
			// We need to get the month from MySQL
			$d = ( ( $w - 1 ) * 7 ) + 6; // It seems MySQL's weeks disagree with PHP's
			$this->data['month'] = $wpdb->get_var( "SELECT DATE_FORMAT((DATE_ADD('{$this->data['year']}0101', INTERVAL $d DAY) ), '%m')" );
			$this->data['year']  = '' . intval( substr( $m, 0, 4 ) );
		} elseif ( ! empty( $m ) ) {
			if ( strlen( $m ) < 6 ) {
				$this->data['month'] = '01';
			} else {
				$this->data['month'] = '' . zeroise( intval( substr( $m, 4, 2 ) ), 2 );
			}
			$this->data['year'] = '' . intval( substr( $m, 0, 4 ) );
		} else {
			$this->data['month'] = gmdate( 'm', current_time( 'timestamp' ) );
			$this->data['year']  = gmdate( 'Y', current_time( 'timestamp' ) );
		}

		// Cache can't be checked for until the month and year are definitely known.
		$key = $this->cache_key();
		if ( $cache = wp_cache_get( 'wp_calendar', 'calendar' ) ) {
			if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
				$this->data = $cache[ $key ];
				return;
			}
		}

		$unix_month = $this->data['unix_month']    = mktime( 0, 0, 0, $this->data['month'], 1, $this->data['year'] );
		$last_day   = $this->data['days_in_month'] = intval( date( 't', $unix_month ) ); // 't' = number of days in the given month

		$this->data['start_of_month'] = $this->data['year'] . '-' . $this->data['month'] . '-01 00:00:00';
		$this->data['end_of_month']   = $this->data['year'] . '-' . $this->data['month'] . '-' . $last_day . ' 23:59:59';

		// week_begins = 0 stands for Sunday
		$this->data['week_begins'] = intval( get_option( 'start_of_week' ) );

		$cache[ $key ] = $this->data;
		wp_cache_set( 'wp_calendar', $cache, 'calendar' );
	}

	protected function cache_key() {
		return get_class( $this ) . $this->data['month'] . $this->data['year'];
	}

	protected function add_to_cache( $data_key, $value ) {
		$cache = wp_cache_get( 'wp_calendar', 'calendar' );
		$cache[ $this->cache_key() ][ $data_key ] = $value;
		wp_cache_set( 'wp_calendar', $cache, 'calendar' );
	}

	protected function get_from_cache( $data_key ) {
		$key = $this->cache_key();
		if ( $cache = wp_cache_get( 'wp_calendar', 'calendar' ) ) {
			if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
				if ( is_array( $cache[ $key ] ) && isset( $cache[ $key ][ $data_key ] ) ) {
					return $cache[ $key ][ $data_key ];
				}
			}
		}
	}

	// public function add_data( $data, $day ) {
	// 	// if $day already exists, add data to it
	// 	$this->data[$day][] = $data;
	// }

	// public function get_data() {
	// 	return $this->data;
	// }


}
