<?php

namespace ImageOptimization\Modules\Connect\Rest;

use ImageOptimization\Modules\Connect\Classes\{
	Data,
	Route_Base,
	Service,
	Utils
};

use ImageOptimization\Modules\Connect\Module as Connect;
use Throwable;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Version
 */
class Version extends Route_Base {
	public string $path = 'version';
	public const NONCE_NAME = 'wp_rest';

	public function get_methods(): array {
		return [ 'GET' ];
	}

	public function get_name(): string {
		return 'version';
	}

	public function GET( WP_REST_Request $request ) {
		$valid = $this->verify_nonce_and_capability(
			$request->get_param( self::NONCE_NAME ),
			self::NONCE_NAME
		);

		if ( is_wp_error( $valid ) ) {
			return $this->respond_error_json( [
				'message' => $valid->get_error_message(),
				'code' => 'forbidden',
			] );
		}

		try {
			return $this->respond_success_json([
				'version' => 2,
			]);
		} catch ( Throwable $t ) {
			return $this->respond_error_json( [
				'message' => $t->getMessage(),
				'code' => 'internal_server_error',
			] );
		}
	}
}
