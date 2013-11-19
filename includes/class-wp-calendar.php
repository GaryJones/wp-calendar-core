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
 * @package WP_Calendar_Core
 * @author  Gary Jones <gary@garyjones.co.uk>
 */
class WP_Calendar {

	/**
	 * The current list of items.
	 *
	 * @since 0.1.0
	 *
	 * @type array
	 */
	protected $items;

	/**
	 * Various information about the current calendar.
	 *
	 * @since 0.1.0
	 *
	 * @type array
	 */
	protected $args;

	protected $month;
	protected $year;

	public function __construct( $args = array() ) {
		$default_args = array(
			'view'    => 'table',
			'initial' => true,
		);

		$this->args = wp_parse_args( $args, $default_args );

		$this->set_month_year();
	}

	/**
	 * Prepare the list of items for displaying.
	 *
	 * @since 0.1.0
	 *
	 * @abstract
	 */
	public function prepare_items() {
		die( 'function WP_Calendar::prepare_items() must be over-ridden in a sub-class.' );
	}

	/**
	 * Build the markup and echo it.
	 *
	 * @since 0.1.0
	 */
	public function display() {
		echo $this-build();
	}

	/**
	 * Build the markup for this calendar.
	 *
	 * If a view is set, then look for a build_{view}() method to use.
	 *
	 * @since 0.1.0
	 *
	 * @todo Check and set cache.
	 *
	 * @return string Markup.
	 */
	public function build() {
		if ( isset( $this->args['view'] ) ) {
			if ( method_exists( $this, 'build_' . $this->args['view'] ) ) {
				return $this->{'build_' . $this->args['view']}();
			} else {
				die( 'No build_' . $this->args['view'] . '() method could be found in ' . __CLASS__ . '.' );
			}
		} else {
			die( 'function WP_Calendar::build() must be over-ridden in a sub-class if no view is set.' );
		}
	}

	/**
	 * Build the markup for the table view of a calendar.
	 *
	 * This method can be overwritten in sub-classes, should some other markup be needed when displaying as a table.
	 *
	 * @since 0.1.0
	 *
	 * @uses WP_Calendar::build_table_caption() Table caption.
	 * @uses WP_Calendar::build_table_head() Table caption.
	 * @uses WP_Calendar::build_table_caption() Table caption.
	 * @uses WP_Calendar::build_table_caption() Table caption.
	 *
	 * @return string Table markup for the complete month view of a calendar.
	 */
	protected function build_table() {
		return '<table class="wp-calendar">' .
			$this->build_table_caption() .
			$this->build_table_head() .
			$this->build_table_foot() .
			$this->build_table_body() .
		'</table>';
	}

	protected function build_table_caption() {
		if ( ! isset( $this->args['caption'] ) || ! $this->args['caption'] ) {
			return '';
		}
		return '<caption>' . esc_html( $this->args['caption'] ) . '</caption>';
	}

	protected function build_table_head() {
		global $wp_locale;

		foreach ( range( 0, 6 ) as $week_day_number ) {
			$week_day = $wp_locale->get_weekday( ( $week_day_number + $this->args['week_begins'] ) % 7 );
			$th[] = $this->build_table_day_header( $week_day );
		}

		return '<thead><tr>' . implode( '', $th ) . '</tr></thead>';
	}

	protected function build_table_day_header( $week_day ) {
		global $wp_locale;
		$day_name = $this->args['initial'] ? $wp_locale->get_weekday_initial( $week_day ) : $wp_locale->get_weekday_abbrev( $week_day );
		return "\n\t\t" . '<th scope="col" title="' . esc_attr( $week_day ) . '">' . $day_name . '</th>';
	}

	/**
	 * Build table foot.
	 *
	 * By default, this contains a link to the previous and next months that contain entries.
	 *
	 * @since 0.1.0
	 *
	 * @return string Markup for `tfoot`.
	 */
	protected function build_table_foot() {
		$row  = "\n\t\t" . $this->build_table_foot_nav( 'previous' );
		$row .= "\n\t\t" . '<td class="pad">&nbsp;</td>';
		$row .= "\n\t\t" . $this->build_table_foot_nav( 'next' );
		return '<tfoot><tr>' . $row . '</tr></tfoot>';
	}

	/**
	 * Build single cell for previous or next month link.
	 *
	 * @since 0.1.0
	 *
	 * @param  string $direction 'previous' or 'next'.
	 *
	 * @return string Markup for single cell, containing space or link.
	 */
	protected function build_table_foot_nav ( $direction ) {
		global $wp_locale;

		// If no value exists for the direction, return single-space table cell
		if ( ! isset( $this->args[ $direction ] ) || ! $this->args[ $direction ] ) {
			if ( 'previous' === $direction ) {
				return '<td colspan="3" id="prev" class="pad">&nbsp;</td>';
			} else {
				return '<td colspan="3" id="next" class="pad">&nbsp;</td>';
			}
		}

		// link title
		$title = sprintf(
			__( 'View posts for %1$s %2$s' ),
			$wp_locale->get_month( $this->args[ $direction ]->month ),
			date( 'Y', mktime( 0, 0 , 0, $this->args[ $direction ]->month, 1, $this->args[ $direction ]->year ) )
		);

		if ( 'previous' === $direction ) {
			$pattern = '<td colspan="3" id="prev"><a href="%s" title="%s">&laquo; %s</a></td>';
		} else {
			$pattern = '<td colspan="3" id="next"><a href="%s" title="%s">%s &raquo;</a></td>';
		}

		// return table cell with link
		return sprintf(
			$pattern,
			esc_url( $this->get_month_link( $this->args[ $direction ]->year, $this->args[ $direction ]->month ) ),
			esc_attr( $title ),
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $this->args[ $direction ]->month ) )
		);
	}

	protected function get_month_link( $year, $month ) {
		return get_month_link( $year, $month );
	}

	protected function build_table_body() {
		$body  = '<tbody><tr>';
		$body .= $this->build_padding_start();
		$body .= $this->build_dates();
		$body .= $this->build_padding_end();
		$body .= '</tr></tbody>';

		return $body;
	}

	protected function build_dates() {
		$markup = '';
		for ( $day = 1; $day <= $this->args['days_in_month']; ++$day ) {
			if ( isset( $new_row ) && $new_row ) {
				$markup .= "\n\t</tr>\n\t<tr>\n\t\t";
			}
			$new_row = false;

			$markup .= $this->build_table_single_date( $day );

			// @todo Is this hard-coded 6 correct, for when the week starts on, say, day 3?
			if ( 6 == calendar_week_mod( date( 'w', mktime( 0, 0, 0, $this->month, $day, $this->year ) ) - $this->args['week_begins'] ) ) {
				$new_row = true;
			}
		}
		return $markup;
	}

	protected function build_table_single_date( $day ) {
		$days_with_posts = $this->get_days_with_data();
		$titles_for_days = $this->get_titles_for_days();

		if ( $this->is_today( $day ) ) {
			$markup = '<td id="today">';
		} else {
			$markup = '<td>';
		}

		// any posts today?
		if ( in_array( $day, $days_with_posts ) ) {
			$markup .= sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $this->get_day_link( $day ) ),
				esc_attr( $titles_for_days[ $day ] ),
				esc_html( $day )
			);
		} else {
			$markup .= $day;
		}

		$markup .= '</td>';
		return $markup;
	}

	protected function is_today( $day ) {
		return $day == gmdate( 'j', current_time( 'timestamp' ) ) &&
			$this->month == gmdate( 'm', current_time( 'timestamp' ) ) &&
			$this->year == gmdate( 'Y', current_time( 'timestamp' ) );
	}

	protected function get_day_link( $day ) {
		return get_day_link( $this->year, $this->month, $day );
	}

	protected function build_padding_start() {
		$pad = $this->days_since_start_of_week();
		return $this->build_padding( $pad );
	}

	protected function build_padding_end() {
		$pad = 7 - $this->days_since_start_of_week();
		return $this->build_padding( $pad );
	}

	protected function build_padding( $size ) {
		if ( 0 !== $size ) {
			return "\n\t\t".'<td colspan="'. esc_attr( $size ) .'" class="pad">&nbsp;</td>';
		}
		return '';
	}

	protected function days_since_start_of_week() {
		// 'w' = Numeric representation of the day of the week
		return calendar_week_mod( date( 'w', $this->args['unix_month'] ) - $this->args['week_begins'] );
	}

	protected function get_title_separator() {
		// Apparently these browsers can do multi-line title tooltips...
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false ||
			stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false ||
			stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false) {
			return "\n";
		}
		return ', ';
	}

	/**
	 * Get a list of days that have data associated with it.
	 *
	 * @since 0.1.0
	 *
	 * @abstract
	 */
	protected function get_days_with_data() {
		die( 'function WP_Calendar::get_days_with_data() must be over-ridden in a sub-class.' );
	}

	protected function cache_key() {
		global $m, $monthnum, $year;
		return md5( __CLASS__ . $m . $monthnum . $year . $this->args['view'] );
	}

	protected function set_month_year() {
		global $m, $monthnum, $year;

		if ( isset( $_GET['w'] ) ) {
			$w = '' . intval( $_GET['w'] );
		}

		// Let's figure out when we are
		if ( ! empty( $monthnum ) && ! empty( $year ) ) {
			$this->month = '' . zeroise( intval( $monthnum ), 2 );
			$this->year  = '' . intval( $year );
		} elseif ( ! empty( $w ) ) {
			// We need to get the month from MySQL
			$this->year = '' . intval( substr( $m, 0, 4 ) );
			$d = ( ( $w - 1 ) * 7 ) + 6; // It seems MySQL's weeks disagree with PHP's
			$this->month = $wpdb->get_var( "SELECT DATE_FORMAT((DATE_ADD('{$this->year}0101', INTERVAL $d DAY) ), '%m')" );
		} elseif ( ! empty( $m ) ) {
			$this->year = '' . intval( substr( $m, 0, 4 ) );
			if ( strlen( $m ) < 6 ) {
					$this->month = '01';
			} else {
					$this->month = '' . zeroise( intval( substr( $m, 4, 2 ) ), 2);
			}
		} else {
			$this->year = gmdate( 'Y', current_time( 'timestamp' ) );
			$this->month = gmdate( 'm', current_time( 'timestamp' ) );
		}

		$unix_month = $this->args['unix_month']    = mktime( 0, 0, 0, $this->month, 1, $this->year );
		$last_day   = $this->args['days_in_month'] = intval( date( 't', $unix_month ) ); // 't' = number of days in the given month

		$this->args['start_of_month'] = "$this->year-$this->month-01 00:00:00";
		$this->args['end_of_month']   = "$this->year-$this->month-$last_day 23:59:59";

		// week_begins = 0 stands for Sunday
		$this->args['week_begins'] = intval( get_option( 'start_of_week' ) );
	}


}
