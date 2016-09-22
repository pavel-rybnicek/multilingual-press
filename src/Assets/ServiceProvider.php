<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Assets;

use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for Assets objects.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class ServiceProvider implements BootstrappableServiceProvider {

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container->share( 'multilingualpress.assets', function ( Container $container ) {

			return new \Mlp_Assets( $container['multilingualpress.locations'] );
		} );
	}

	/**
	 * Bootstraps the registered services.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function bootstrap( Container $container ) {

		$assets = $container['multilingualpress.assets'];

		$assets->add(
			'mlp-admin',
			'admin.js',
			[
				'backbone',
			],
			[
				'mlpSettings' => [
					'urlRoot' => esc_url( parse_url( admin_url(), PHP_URL_PATH ) ),
				],
			]
		);
		$assets->add( 'mlp_admin_css', 'admin.css' );
		$assets->add( 'mlp-frontend', 'frontend.js' );
		$assets->add( 'mlp_frontend_css', 'frontend.css' );

		add_action( 'init', [ $assets, 'register' ], 0 );
	}
}
