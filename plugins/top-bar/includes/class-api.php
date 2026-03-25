<?php
/**
 * REST API endpoints for Top Bar.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class API {

	private const NAMESPACE = 'top-bar/v1';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		// Get all bars
		register_rest_route(
			self::NAMESPACE,
			'/bars',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_bars' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Create new bar
		register_rest_route(
			self::NAMESPACE,
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
			self::NAMESPACE,
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
			self::NAMESPACE,
			'/bars/(?P<id>[a-z0-9_]+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_bar' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// Get feature flags
		register_rest_route(
			self::NAMESPACE,
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

	public function get_bars( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_bars();
		return new \WP_REST_Response( $bars, 200 );
	}

	public function create_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$bars = Options::get_bars();

		if ( count( $bars ) >= FeatureFlags::instance()->max_bars() ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Maximum number of bars reached', 'top-bar' ) ],
				403
			);
		}

		$params  = $request->get_json_params();
		$new_bar = Options::normalize_bar( is_array( $params ) ? $params : [] );
		$bars[]  = $new_bar;
		update_option( Options::OPTION_BARS, $bars );

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
				update_option( Options::OPTION_BARS, $bars );
				return new \WP_REST_Response( $bars[ $idx ], 200 );
			}
		}

		return new \WP_REST_Response(
			[ 'error' => __( 'Bar not found', 'top-bar' ) ],
			404
		);
	}

	public function delete_bar( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = $request->get_param( 'id' );
		$bars = Options::get_bars();

		if ( count( $bars ) <= Options::MIN_BARS ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Cannot delete last bar', 'top-bar' ) ],
				403
			);
		}

		$filtered = array_filter( $bars, fn( $bar ) => $bar['id'] !== $id );

		if ( count( $filtered ) === count( $bars ) ) {
			return new \WP_REST_Response(
				[ 'error' => __( 'Bar not found', 'top-bar' ) ],
				404
			);
		}

		update_option( Options::OPTION_BARS, array_values( $filtered ) );
		return new \WP_REST_Response( null, 204 );
	}

	public function get_feature_flags( \WP_REST_Request $request ): \WP_REST_Response {
		$flags = FeatureFlags::instance();
		return new \WP_REST_Response(
			[
				'max_bars'         => $flags->max_bars(),
				'max_messages'     => $flags->max_messages(),
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
