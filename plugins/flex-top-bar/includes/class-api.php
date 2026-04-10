<?php
/**
 * REST API endpoints for Top Bar.
 *
 * @package FlexTopBar
 */

declare(strict_types=1);

namespace FlexTopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class API {

	private const NAMESPACE_NEW = 'flex-top-bar/v1';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$this->register_routes_for_namespace( self::NAMESPACE_NEW );
	}

	private function register_routes_for_namespace( string $ns ): void {
		// Public endpoint for frontend (no auth required)
		register_rest_route(
			$ns,
			'/public-bars',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_public_bars' ],
				'permission_callback' => '__return_true', // Public access
			]
		);

		// Get all bars
		register_rest_route(
			$ns,
			'/bars',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_bars' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Get published bars (for admin "unpublished changes" indicator).
		register_rest_route(
			$ns,
			'/published-bars',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_published_bars' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Create new bar
		register_rest_route(
			$ns,
			'/bars',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_bar' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_bar_schema(),
			]
		);

		// Update bar
		register_rest_route(
			$ns,
			'/bars/(?P<id>[a-z0-9_]+)',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_bar' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_bar_schema(),
			]
		);

		// Delete bar
		register_rest_route(
			$ns,
			'/bars/(?P<id>[a-z0-9_]+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_bar' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Publish draft -> published.
		register_rest_route(
			$ns,
			'/publish',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'publish' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Publish a single bar (draft -> published).
		register_rest_route(
			$ns,
			'/bars/(?P<id>[a-z0-9_]+)/publish',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'publish_bar' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Get feature flags
		register_rest_route(
			$ns,
			'/feature-flags',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_feature_flags' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get public bars for frontend display (no auth required).
	 * Only returns visible bars without sensitive admin fields.
	 */
	public function get_public_bars( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_active_bars();

		// Remove admin-only fields for security
		$public_bars = array_map( function( $bar ) {
			// Only include fields needed for frontend display
			return [
				'id'                       => $bar['id'] ?? '',
				'position'                 => $bar['position'] ?? 'top',
				'effect'                   => $bar['effect'] ?? 'none',
				'messages'                 => $bar['messages'] ?? [],
				'messages_mobile_visible'  => $bar['messages_mobile_visible'] ?? true,
				'columns'                  => $bar['columns'] ?? [],
				'bg_color'                 => $bar['bg_color'] ?? '#1d2327',
				'frame_color'              => $bar['frame_color'] ?? '',
				'frame_width'              => $bar['frame_width'] ?? 0,
				'hide_on_scroll'           => $bar['hide_on_scroll'] ?? false,
				'visible'                  => $bar['visible'] ?? true,
				'scheduled_enabled'        => $bar['scheduled_enabled'] ?? false,
				'scheduled_from_datetime'  => $bar['scheduled_from_datetime'] ?? '',
				'scheduled_to_datetime'    => $bar['scheduled_to_datetime'] ?? '',
			];
		}, $bars );

		return new \WP_REST_Response( $public_bars, 200 );
	}

	public function get_bars( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_bars();
		return new \WP_REST_Response( $bars, 200 );
	}

	public function get_published_bars( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_published_bars();
		return new \WP_REST_Response( $bars, 200 );
	}

	public function create_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_bars();

		if ( count( $bars ) >= FeatureFlags::instance()->max_bars() ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Maximum number of bars reached', 'flex-top-bar' ) ],
				403
			);
		}

		$params  = $request->get_json_params();
		$new_bar = Options::normalize_bar( is_array( $params ) ? $params : [] );
		$bars[]  = $new_bar;
		update_option( Options::OPTION_BARS_DRAFT, $bars );

		return new \WP_REST_Response( $new_bar, 201 );
	}

	public function update_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = $request->get_param( 'id' );
		$bars = Options::get_bars();

		foreach ( $bars as $idx => $bar ) {
			if ( $bar['id'] === $id ) {
				$params       = $request->get_json_params();
				$bars[ $idx ] = Options::normalize_bar(
					array_merge( $bar, is_array( $params ) ? $params : [] )
				);
				update_option( Options::OPTION_BARS_DRAFT, $bars );
				return new \WP_REST_Response( $bars[ $idx ], 200 );
			}
		}

		return new \WP_REST_Response(
			[ 'error' => __( 'Bar not found', 'flex-top-bar' ) ],
			404
		);
	}

	public function delete_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = $request->get_param( 'id' );
		$bars = Options::get_bars();

		if ( count( $bars ) <= Options::MIN_BARS ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Cannot delete last bar', 'flex-top-bar' ) ],
				403
			);
		}

		$filtered = array_filter( $bars, fn( $bar ) => $bar['id'] !== $id );

		if ( count( $filtered ) === count( $bars ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Bar not found', 'flex-top-bar' ) ],
				404
			);
		}

		update_option( Options::OPTION_BARS_DRAFT, array_values( $filtered ) );
		Options::remove_bar_from_published( $id );
		return new \WP_REST_Response( null, 204 );
	}

	public function publish( \WP_REST_Request $request ): \WP_REST_Response {
		$published = Options::publish_draft_to_published();
		return new \WP_REST_Response( $published, 200 );
	}

	public function publish_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (string) $request->get_param( 'id' );
		$bar = Options::publish_bar( $id );
		if ( ! is_array( $bar ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Bar not found', 'flex-top-bar' ) ],
				404
			);
		}
		return new \WP_REST_Response( $bar, 200 );
	}

	public function get_feature_flags( \WP_REST_Request $request ): \WP_REST_Response {
		$flags = FeatureFlags::instance();
		return new \WP_REST_Response(
			[
				'plan_name'        => $flags->plan_name(),
				'max_bars'         => $flags->max_bars(),
				'max_messages'     => $flags->max_messages(),
				'max_columns'      => $flags->max_columns(),
				'schedule_enabled' => $flags->is_schedule_enabled(),
			],
			200
		);
	}

	private function get_bar_schema(): array {
		return [
			'id'                      => [
				'type'     => 'string',
				'required' => false,
			],
			'name'                    => [
				'type'     => 'string',
				'required' => false,
			],
			'visible'                 => [
				'type'     => 'boolean',
				'required' => false,
			],
			'admin_visibile'          => [
				'type'     => 'boolean',
				'required' => false,
			],
			'scheduled_enabled'       => [
				'type'     => 'boolean',
				'required' => false,
			],
			'scheduled_from_datetime' => [
				'type'     => 'string',
				'required' => false,
			],
			'scheduled_to_datetime'   => [
				'type'     => 'string',
				'required' => false,
			],
			'position'                => [
				'type'     => 'string',
				'enum'     => [ 'top', 'bottom' ],
				'required' => false,
			],
			'effect'                  => [
				'type'     => 'string',
				'enum'     => [ 'none', 'slider', 'fadein', 'blink' ],
				'required' => false,
			],
			'messages'                => [
				'type'     => 'array',
				'items'    => [ 'type' => 'string' ],
				'required' => false,
			],
			'messages_mobile_visible' => [
				'type'     => 'boolean',
				'required' => false,
			],
			'columns'                 => [
				'type'     => 'array',
				'required' => false,
			],
			'bg_color'                => [
				'type'     => 'string',
				'required' => false,
			],
			'frame_color'             => [
				'type'     => 'string',
				'required' => false,
			],
			'frame_width'             => [
				'type'     => 'integer',
				'required' => false,
			],
			'hide_on_scroll'          => [
				'type'     => 'boolean',
				'required' => false,
			],
		];
	}
}
