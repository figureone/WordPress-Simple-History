<?php

namespace Simple_History;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * REST API controller for events.
 */
class WP_REST_Events_Controller extends WP_REST_Controller {
	/**
	 * Simple History instance.
	 *
	 * @var Simple_History
	 */
	protected Simple_History $simple_history;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rest_base = 'events';
		$this->namespace = 'simple-history/v1';
		$this->simple_history = Simple_History::get_instance();
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		// GET /wp-json/simple-history/v1/events.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				'schema'      => [ $this, 'get_public_item_schema' ],
			],
		);

		// GET /wp-json/simple-history/v1/events/<event-id>.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for the post.', 'simple-history' ),
						'type'        => 'integer',
					],
				],
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					// 'args'                => $get_item_args,
				),
			],
		);
	}

	/**
	 * Retrieves a single event.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$event = $this->get_single_event( $request['id'] );

		$data     = $this->prepare_item_for_response( $event, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}


	/**
	 * Checks if a given request has access to read a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		// User must be logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view events.', 'simple-history' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Event must exist.
		if ( ! $this->event_exists( $request['id'] ) ) {
			return new WP_Error(
				'rest_event_invalid_id',
				__( 'Invalid event ID.', 'simple-history' ),
				array( 'status' => 404 )
			);
		}

		$log_event = $this->get_single_event( $request['id'] );
		if ( $log_event === false ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view this event.', 'simple-history' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get a single event using the log query API.
	 *
	 * @param int $event_id Event ID.
	 * @return false|object Event data on success, false on failure.
	 */
	protected function get_single_event( $event_id ) {
		$query_result = ( new Log_Query() )->query(
			[
				'post__in' => [ $event_id ],
			]
		);

		if ( isset( $query_result['log_rows'][0] ) ) {
			return $query_result['log_rows'][0];
		}

		return false;
	}

	/**
	 * Checks if a event exists in the database.
	 *
	 * @param int $event_id Event ID.
	 * @return bool True if event exists, false otherwise.
	 */
	protected function event_exists( $event_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE id = %d',
				$this->simple_history->get_events_table_name(),
				$event_id
			)
		);
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 4.7.0
	 * @since 5.4.0 The `tax_relation` query parameter was added.
	 * @since 5.7.0 The `modified_after` and `modified_before` query parameters were added.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['return_type'] = array(
			'description' => __( 'Type of result to return.', 'simple-history' ),
			'type'        => 'string',
			'default'     => 'overview',
			'enum'        => array( 'overview', 'occasions' ),
		);

		$query_params['logRowID'] = array(
			'description' => __( 'Limit result set to rows with id lower than this.', 'simple-history' ),
			'type'        => 'integer',
		);

		$query_params['occasionsID'] = array(
			'description' => __( 'Limit result set to rows with occasionsID equal to this.', 'simple-history' ),
			'type'        => 'string',
		);

		$query_params['occasionsCount'] = array(
			'description' => __( 'The number of occasions to get.', 'simple-history' ),
			'type'        => 'integer',
		);

		$query_params['occasionsCountMaxReturn'] = array(
			'description' => __( 'The max number of occasions to return.', 'simple-history' ),
			'type'        => 'integer',
		);

		$query_params['per_page'] = array(
			'description' => __( 'Maximum number of items to be returned in result set.', 'simple-history' ),
			'type'        => 'integer',
			'default'     => 10,
			'minimum'     => 1,
			'maximum'     => 100,
		);

		$query_params['page'] = array(
			'description' => __( 'Current page of the collection.', 'simple-history' ),
			'type'        => 'integer',
			'default'     => 1,
			'minimum'     => 1,
		);

		$query_params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => null,
		);

		// If max_id_first_page is then then only include rows
		// with id equal to or earlier than this, i.e. older than this.
		$query_params['max_id_first_page'] = array(
			'description' => __( 'Limit result set to rows with id equal or lower than this.', 'simple-history' ),
			'type'        => 'integer',
		);

		// Add where clause for since_id,
		// to include rows with id greater than since_id, i.e. more recent than since_id.
		$query_params['since_id'] = array(
			'description' => __( 'Limit result set to rows with id greater than this, i.e. more recent than since_id.', 'simple-history' ),
			'type'        => 'integer',
		);

		// Date to in unix timestamp format.
		$query_params['date_from'] = array(
			'description' => __( 'Limit result set to rows with date greater than or equal to this unix timestamp.', 'simple-history' ),
			'type'        => 'integer',
		);

		// Date to.
		// If date_to is set it is a timestamp.
		$query_params['date_to'] = array(
			'description' => __( 'Limit result set to rows with date less than or equal to this unix timestamp.', 'simple-history' ),
			'type'        => 'integer',
		);

		/**
		 * If "months" they translate to $args["months"] because we already have support for that
		 * can't use months and dates and the same time.
		 *
		 * $arr_dates can be a month:
		 * Array
		 * (
		 *  [0] => month:2021-11
		 * )
		 *
		 * $arr_dates can be a number of days:
		 * Array
		 * (
		 *  [0] => lastdays:7
		 * )
		 *
		 * $arr_dates can be allDates
		 * Array
		 * (
		 *  [0] => allDates
		 * )
		*/
		$query_params['dates'] = array(
			'description' => __( 'Limit result set to rows with date within this range.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		// lastdays = int with number of days back to show the history.
		$query_params['lastdays'] = array(
			'description' => __( 'Limit result set to rows with date within this range.', 'simple-history' ),
			'type'        => 'integer',
		);

		// months in format "Y-m"
		// array or comma separated.
		$query_params['months'] = array(
			'description' => __( 'Limit result set to rows with date within this range. Format: Y-m.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		// log levels to include. comma separated or as array. defaults to all.
		$query_params['loglevels'] = array(
			'description' => __( 'Limit result set to rows with log levels.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		// loggers to include. comma separated. defaults to all the authed user can read.
		$query_params['loggers'] = array(
			'description' => __( 'Limit result set to rows with loggers.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		$query_params['messages'] = array(
			'description' => __( 'Limit result set to rows with messages. Format: LoggerSlug:message.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
		);

		// User ids, comma separated or array.
		$query_params['users'] = array(
			'description' => __( 'Limit result set to rows with user ids.', 'simple-history' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);

		$query_params['user'] = array(
			'description' => __( 'Limit result set to rows with user id.', 'simple-history' ),
			'type'        => 'integer',
		);

		$query_params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.', 'simple-history' ),
			'type'        => 'integer',
		);

		return $query_params;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'simple-history-event',
			'type'       => 'object',
			'properties' => array(
				'date'         => array(
					'description' => __( "The date the event was added, in the site's timezone.", 'simple-history' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
				),
				'date_gmt'     => array(
					'description' => __( 'The date the event was added, as GMT.', 'simple-history' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
				),
				'id'           => array(
					'description' => __( 'Unique identifier for the event.', 'simple-history' ),
					'type'        => 'integer',
				),
				'link'         => array(
					'description' => __( 'URL to the event.', 'simple-history' ),
					'type'        => 'string',
					'format'      => 'uri',
				),
				'message'   => array(
					'description' => __( 'The interpolated message of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'message_uninterpolated'    => array(
					'description' => __( 'The uninterpolated message of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'logger'       => array(
					'description' => __( 'The logger of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'message_key'   => array(
					'description' => __( 'The message key of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'loglevel'     => array(
					'description' => __( 'The log level of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'initiator' => array(
					'description' => __( 'The initiator of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'occasions_id' => array(
					'description' => __( 'The occasions ID of the event.', 'simple-history' ),
					'type'        => 'string',
				),
				'subsequent_occasions_count' => array(
					'description' => __( 'The subsequent occasions count of the event.', 'simple-history' ),
					'type'        => 'integer',
				),
				'context' => array(
					'description' => __( 'The context of the event.', 'simple-history' ),
					'type'        => 'object',
				),
			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		// User must be logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view events.', 'simple-history' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get items.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_items( $request ) {
		$events = [];

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = [];

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'include'        => 'post__in',
			'offset'         => 'offset',
			'page'           => 'paged',
			'per_page'       => 'posts_per_page',
			'search'         => 'search',
			'logRowID'       => 'logRowID',
			'occasionsID'    => 'occasionsID',
			'occasionsCount' => 'occasionsCount',
			'occasionsCountMaxReturn' => 'occasionsCountMaxReturn',
			'return_type'    => 'return_type',
			'max_id_first_page' => 'max_id_first_page',
			'since_id'       => 'since_id',
			'date_from'      => 'date_from',
			'date_to'        => 'date_to',
			'dates'          => 'dates',
			'lastdays'       => 'lastdays',
			'months'         => 'months',
			'loglevels'      => 'loglevels',
			'loggers'        => 'loggers',
			'messages'       => 'messages',
			'users'          => 'users',
			'user'           => 'user',
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		$log_query = new Log_Query();
		$query_result = $log_query->query( $args );

		foreach ( $query_result['log_rows'] as $event_row ) {
			$data     = $this->prepare_item_for_response( $event_row, $request );
			$events[] = $this->prepare_response_for_collection( $data );
		}

		$page        = (int) $query_result['page_current'];
		$total_posts = (int) $query_result['total_row_count'];
		$max_pages   = (int) $query_result['pages_count'];

		$response = rest_ensure_response( $events );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$collection_url = rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) );
		$base           = add_query_arg( urlencode_deep( $request_params ), $collection_url );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Prepares a single post output for response.
	 *
	 * @since 4.7.0
	 * @since 5.9.0 Renamed `$post` to `$item` to match parent class for PHP 8 named parameter support.
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @param object          $item    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = [];

		/**
		 * Default Array when no ?_fields= is set.
		 * (
		 *  [0] => date
		 *  [1] => date_gmt
		 *  [2] => id
		 *  [3] => link
		 *  [4] => message
		 *  [5] => message_uninterpolated
		 *  [6] => logger
		 *  [7] => message_key
		 *  [8] => loglevel
		 *  [9] => initiator
		 *  [10] => occasions_id
		 *  [11] => subsequent_occasions_count
		 *  [12] => context
		 *  [13] => _links
		 * )
		 */
		$fields = $this->get_fields_for_response( $request );

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = (int) $item->id;
		}

		if ( rest_is_field_included( 'date', $fields ) ) {
			$data['date'] = mysql_to_rfc3339( $item->date );
		}

		if ( rest_is_field_included( 'date_gmt', $fields ) ) {
			$data['date_gmt'] = mysql_to_rfc3339( get_date_from_gmt( mysql_to_rfc3339( $item->date ) ) );
		}

		if ( rest_is_field_included( 'message', $fields ) ) {
			$data['message'] = html_entity_decode( $this->simple_history->get_log_row_plain_text_output( $item ) );
		}

		if ( rest_is_field_included( 'message_uninterpolated', $fields ) ) {
			$data['message_uninterpolated'] = $item->message;
		}

		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = $this->simple_history->get_view_history_page_admin_url() . "#item/{$item->id}";
		}

		if ( rest_is_field_included( 'logger', $fields ) ) {
			$data['logger'] = $item->logger;
		}

		if ( rest_is_field_included( 'message_key', $fields ) ) {
			$data['message_key'] = $item->context_message_key;
		}

		if ( rest_is_field_included( 'loglevel', $fields ) ) {
			$data['loglevel'] = $item->level;
		}

		if ( rest_is_field_included( 'initiator', $fields ) ) {
			$data['initiator'] = $item->initiator;
		}

		if ( rest_is_field_included( 'occasions_id', $fields ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['occasions_id'] = $item->occasionsID;
		}

		if ( rest_is_field_included( 'subsequent_occasions_count', $fields ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['subsequent_occasions_count'] = $item->subsequentOccasions;
		}

		if ( rest_is_field_included( 'context', $fields ) ) {
			$data['context'] = $item->context;
		}

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		return $response;
	}
}