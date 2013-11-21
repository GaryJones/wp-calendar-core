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
 * Specify data for published posts calendar, as per `get_calendar()`.
 *
 * If loosely intepreted as an MVC, this class would be a specific Model.
 *
 * @since 0.1.0
 *
 * @package WP_Calendar_Core
 * @author  Gary Jones <gary@garyjones.co.uk>
 */
class WP_Posts_Calendar extends WP_Calendar {

	/**
	 * Populate properties.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Calendar args.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->set_month_label();
		$this->set_previous_next();
	}

	protected function set_month_label() {
		global $wp_locale;
		/* translators: Calendar caption: 1: month name, 2: 4-digit year */
		$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
		$this->data['month_label'] = sprintf( $calendar_caption, $wp_locale->get_month( $this->data['month'] ), $this->data['year'] );
	}

	/**
	 * Set the previous and next link object, containing month and year properties.
	 *
	 * @since 0.1.0
	 */
	protected function set_previous_next() {
		global $wpdb;

		// Get the next and previous month and year with at least one post
		$this->data['previous'] = $wpdb->get_row(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts
			WHERE post_date < '{$this->data['start_of_month']}'
				AND post_type = 'post'
				AND post_status = 'publish'
			ORDER BY post_date DESC
			LIMIT 1"
		);

		$this->data['next'] = $wpdb->get_row(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts
			WHERE post_date > '{$this->data['end_of_month']}'
				AND post_type = 'post'
				AND post_status = 'publish'
			ORDER BY post_date ASC
			LIMIT 1"
		);
	}

	/**
	 * Get the days in the relevant month which had posts published.
	 *
	 * @since 0.1.0
	 *
	 * @return array List of day numbers.
	 */
	public function get_days_with_data() {
		global $wpdb;
		$days_with_posts = $wpdb->get_results(
			"SELECT DISTINCT DAYOFMONTH(post_date)
			FROM $wpdb->posts
			WHERE post_date >= '{$this->data['start_of_month']}'
				AND post_date <= '{$this->data['end_of_month']}'
				AND post_type = 'post'
				AND post_status = 'publish'", ARRAY_N
		);

		// get_results() returns multi-dimensional array, so we just need the first field of each.
		if ( $days_with_posts ) {
			foreach ( (array) $days_with_posts as $day_with_post ) {
				$days_with_data[] = $day_with_post[0];
			}
		} else {
			$days_with_data = array();
		}

		return $days_with_data;
	}

	public function get_titles_for_days() {
		global $wpdb;
		$posts = $wpdb->get_results(
			"SELECT ID, post_title, DAYOFMONTH(post_date) as dom
			FROM $wpdb->posts
			WHERE post_date >= '{$this->data['start_of_month']}'
				AND post_date <= '{$this->data['end_of_month']}'
				AND post_type = 'post'
				AND post_status = 'publish'"
		);

		if ( ! $posts ) {
			return array();
		}

		foreach ( (array) $posts as $a_post ) {
			/** This filter is documented in wp-includes/post-template.php */
			$post_title = apply_filters( 'the_title', $a_post->post_title, $a_post->ID );

			if ( empty( $titles_for_days[ $a_post->dom ] ) ) {
				// first one
				$titles_for_days[ $a_post->dom ] = $post_title;
			} else {
				$titles_for_days[ $a_post->dom ] .= $this->get_title_separator() . $post_title;
			}
		}

		return $titles_for_days;
	}

	public function get_day_link( $day ) {
		return get_day_link( $this->data['year'], $this->data['month'], $day );
	}

	/**
	 * Return title attribute separator.
	 *
	 * Comma or new line, between pieces of data shown via the title attribute tooltip.
	 *
	 * @since 0.1.0
	 *
	 * @return string Title attribute seperator
	 */
	public function get_title_separator() {
		if ( $this->supports_multiline_titles() ) {
			return "\n";
		}
		return ', ';
	}
}
