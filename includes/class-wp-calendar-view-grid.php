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
 * Base Class for building a grid (table) Calendar view.
 *
 * If loosely intepreted as an MVC, this class would be a specific View.
 *
 * @since 0.2.0
 *
 * @package WP_Calendar_Core
 * @author  Gary Jones <gary@garyjones.co.uk>
 */
class WP_Calendar_View_Grid extends WP_Calendar_View {

	/**
	 * Define view arguments.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Calendar $calendar Data model.
	 */
	public function __construct( WP_Calendar $calendar ) {
		parent::__construct( $calendar );

		$args = array(
			'initial' => true,
		);

		$this->args = $args;
	}

	/**
	 * Build the markup for the table view of a calendar.
	 *
	 * This method can be overwritten in sub-classes, should some other markup be needed when displaying as a table.
	 *
	 * @since 0.1.0
	 *
	 * @uses WP_Calendar_View_Grid::build_caption() Table caption.
	 * @uses WP_Calendar_View_Grid::build_head() Table head.
	 * @uses WP_Calendar_View_Grid::build_caption() Table foot.
	 * @uses WP_Calendar_View_Grid::build_caption() Table body.
	 *
	 * @return string Table markup for the complete month view of a calendar.
	 */
	public function build() {
		if ( $output = $this->get_cache() ) {
			return $output;
		}

		$output = '<table class="wp-calendar">' .
			$this->build_caption() .
			$this->build_head() .
			$this->build_foot() .
			$this->build_body() .
			'</table>';

		$this->set_cache( $output );
		return $output;
	}

	protected function build_caption() {
		if ( ! isset( $this->calendar->data['month_label'] ) || ! $this->calendar->data['month_label'] ) {
			return '';
		}
		return '<caption>' . esc_html( $this->calendar->data['month_label'] ) . '</caption>';
	}

	protected function build_head() {
		global $wp_locale;

		foreach ( range( 0, 6 ) as $week_day_number ) {
			$week_day = $wp_locale->get_weekday( ( $week_day_number + $this->calendar->data['week_begins'] ) % 7 );
			$th[] = $this->build_day_header( $week_day );
		}

		return '<thead><tr>' . implode( '', $th ) . '</tr></thead>';
	}

	protected function build_day_header( $week_day ) {
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
	protected function build_foot() {
		$row  = "\n\t\t" . $this->build_foot_nav( 'previous' );
		$row .= "\n\t\t" . '<td class="pad">&nbsp;</td>';
		$row .= "\n\t\t" . $this->build_foot_nav( 'next' );
		return '<tfoot><tr>' . $row . '</tr></tfoot>';
	}

	/**
	 * Build single cell for previous or next month link.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $direction 'previous' or 'next'.
	 *
	 * @return string Markup for single cell, containing space or link.
	 */
	protected function build_foot_nav( $direction ) {
		global $wp_locale;

		// If no value exists for the direction, return single-space table cell
		if ( ! isset( $this->calendar->data[ $direction ] ) || ! $this->calendar->data[ $direction ] ) {
			if ( 'previous' === $direction ) {
				return '<td colspan="3" id="prev" class="pad">&nbsp;</td>';
			} else {
				return '<td colspan="3" id="next" class="pad">&nbsp;</td>';
			}
		}

		// link title
		$title = sprintf(
			__( 'View posts for %1$s %2$s' ),
			$wp_locale->get_month( $this->calendar->data[ $direction ]->month ),
			date( 'Y', mktime( 0, 0 , 0, $this->calendar->data[ $direction ]->month, 1, $this->calendar->data[ $direction ]->year ) )
		);

		if ( 'previous' === $direction ) {
			$pattern = '<td colspan="3" id="prev"><a href="%s" title="%s">&laquo; %s</a></td>';
		} else {
			$pattern = '<td colspan="3" id="next"><a href="%s" title="%s">%s &raquo;</a></td>';
		}

		// return table cell with link
		return sprintf(
			$pattern,
			esc_url( $this->get_month_link( $this->calendar->data[ $direction ]->year, $this->calendar->data[ $direction ]->month ) ),
			esc_attr( $title ),
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $this->calendar->data[ $direction ]->month ) )
		);
	}

	protected function get_month_link( $year, $month ) {
		return get_month_link( $year, $month );
	}

	protected function build_body() {
		$body  = '<tbody><tr>';
		$body .= $this->build_padding_start();
		$body .= $this->build_dates();
		$body .= $this->build_padding_end();
		$body .= '</tr></tbody>';

		return $body;
	}

	protected function build_dates() {
		$markup = '';
		for ( $day = 1; $day <= $this->calendar->data['days_in_month']; ++$day ) {
			if ( isset( $new_row ) && $new_row ) {
				$markup .= "\n\t</tr>\n\t<tr>\n\t\t";
			}
			$new_row = false;

			$markup .= $this->build_single_date( $day );

			// @todo Is this hard-coded 6 correct, for when the week starts on, say, day 3?
			if ( 6 == calendar_week_mod( date( 'w', mktime( 0, 0, 0, $this->calendar->data['month'], $day, $this->calendar->data['year'] ) ) - $this->calendar->data['week_begins'] ) ) {
				$new_row = true;
			}
		}
		return $markup;
	}

	protected function build_single_date( $day ) {
		$days_with_posts = $this->calendar->get_days_with_data();
		$titles_for_days = $this->calendar->get_titles_for_days();

		if ( $this->calendar->is_today( $day ) ) {
			$markup = '<td id="today">';
		} else {
			$markup = '<td>';
		}

		// any posts today?
		if ( in_array( $day, $days_with_posts ) ) {
			$markup .= sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $this->calendar->get_day_link( $day ) ),
				esc_attr( $titles_for_days[ $day ] ),
				esc_html( $day )
			);
		} else {
			$markup .= $day;
		}

		$markup .= '</td>';
		return $markup;
	}

	protected function build_padding_start() {
		$pad = $this->calendar->days_since_start_of_week();
		return $this->build_padding( $pad );
	}

	protected function build_padding_end() {
		$pad = 7 - $this->calendar->days_since_start_of_week();
		return $this->build_padding( $pad );
	}

	protected function build_padding( $size ) {
		if ( 0 !== $size ) {
			return "\n\t\t".'<td colspan="'. esc_attr( $size ) .'" class="pad">&nbsp;</td>';
		}
		return '';
	}

}
