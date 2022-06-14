<?php

namespace SimpleHistory\Dropin;
use WP_CLI;

/*
Dropin Name: WP CLI
Dropin URI: https://simple-history.com/
Author: Pär Thernström
*/

class WPCLI {
	// Simple History instance.
	private $sh;
	
	public function __construct( $sh ) {
		$this->sh = $sh;

		if ( defined( WP_CLI::class ) && WP_CLI ) {
			$this->register_commands();
		}
	}

	private function register_commands() {
		WP_CLI::add_command(
			'simple-history', 
			__NAMESPACE__ . '\Commands', 
			[
				'shortdesc' => __('List events from the Simple History log.', 'simple-history'),
			]
		);
	}
}

class Commands {
	/** Simple History instance. */
	private $sh;

	public function __construct()
	{
		$this->sh = \SimpleHistory::get_instance();
	}

	/**
	 * Display the latest events from the history.
	 * 
	 * ## Options
	 * 
	 * [--format=<format>]
	 * : Format to output log in.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * 
	 * [--count=<count>]
	 * : How many events to show.
	 * ---
	 * default: 10
	 * 
	 * ## Examples
	 * 
	 *     wp simple-history list --count=20 --format=json
	 * 
	 * @when after_wp_load
	 */
	// Usage: wp simple-history
	public function list ( $args, $assoc_args ) {
		if ( ! is_numeric( $assoc_args['count'] ) ) {
			WP_CLI::error( __( 'Error: parameter "count" must be a number', 'simple-history' ) );
		}

		// Override capability check: if you can run wp cli commands you can read all loggers.
		add_action( 'simple_history/loggers_user_can_read/can_read_single_logger', '__return_true', 10, 3 );

		$query = new \SimpleHistoryLogQuery();

		$query_args = array(
			'paged' => 1,
			'posts_per_page' => $assoc_args['count'],
		);

		$events = $query->query( $query_args );

		// A cleaned version of the events, formatted for wp cli table output.
		$eventsCleaned = array();

		foreach ( $events['log_rows'] as $row ) {
			$header_output = $this->sh->getLogRowHeaderOutput( $row );
			$text_output = $this->sh->getLogRowPlainTextOutput( $row );
			$header_output = strip_tags( html_entity_decode( $header_output, ENT_QUOTES, 'UTF-8' ) );
			$header_output = trim( preg_replace( '/\s\s+/', ' ', $header_output ) );

			$text_output = strip_tags( html_entity_decode( $text_output, ENT_QUOTES, 'UTF-8' ) );

			$eventsCleaned[] = array(
				'date' => get_date_from_gmt( $row->date ),
				'initiator' => $this->getInitiatorTextFromRow( $row ),
				'logger' => $row->logger,
				'level' => $row->level,
				'who_when' => $header_output,
				'description' => $text_output,
				'count' => $row->subsequentOccasions,
			);
		}

		$fields = array(
			'date',
			'initiator',
			'description',
			'level',
			'count',
		);

		WP_CLI\Utils\format_items( $assoc_args['format'], $eventsCleaned, $fields );
	}

	private function getInitiatorTextFromRow( $row ) {
		$context = [];
		if ( ! isset( $row->initiator ) ) {
			return false;
		}

		$initiator = $row->initiator;
		$initiatorText = '';

		switch ( $initiator ) {
			case 'wp':
				$initiatorText = 'WordPress';
				break;
			case 'wp_cli':
				$initiatorText = 'WP-CLI';
				break;
			case 'wp_user':
				$user_id = $row->context['_user_id'] ?? null;
				$user = get_user_by( 'id', $user_id );

				if ( $user_id > 0 && $user ) {
					// User still exists
					$initiatorText = sprintf(
						'%1$s (%2$s)',
						$user->user_login,  // 1
						$user->user_email   // 2
					);
				} elseif ( $user_id > 0 ) {
					// Sender was a user, but user is deleted now.
					$initiatorText = sprintf(
						__( 'Deleted user (had id %1$s, email %2$s, login %3$s)', 'simple-history' ),
						$context['_user_id'], // 1
						$context['_user_email'], // 2
						$context['_user_login'] // 3
					);
				} // End if().
				break;
			case 'web_user':
				$initiatorText = __( 'Anonymous web user', 'simple-history' );
				break;
			case 'other':
				$initiatorText = _x( 'Other', 'Event header output, when initiator is unknown', 'simple-history' );
				break;
			default:
				$initiatorText = $initiator;
		}// End switch().

		return $initiatorText;
	}
}