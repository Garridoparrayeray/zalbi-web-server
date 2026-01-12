<?php

namespace ImageOptimization\Modules\WhatsNew\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\WPNotificationsPackage\V120\Notifications as Notifications_SDK;

class Notificator {
	private ?Notifications_SDK $notificator = null;

	public function get_notifications_by_conditions( $force_request = false ) {
		return $this->notificator->get_notifications_by_conditions( $force_request );
	}

	public function __construct() {
		require_once IMAGE_OPTIMIZATION_PATH . '/vendor/autoload.php';

		$this->notificator = new Notifications_SDK( [
			'app_name' => 'image-optimizer',
			'app_version' => IMAGE_OPTIMIZATION_VERSION,
			'short_app_name' => 'eio',
			'app_data' => [
				'plugin_basename' => plugin_basename( IMAGE_OPTIMIZATION_FILE ),
			],
		] );
	}
}
