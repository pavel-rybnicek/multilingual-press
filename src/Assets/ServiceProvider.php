<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Assets;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ContainerException;

/**
 * Service provider for internal locations object.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class ServiceProvider implements BootableServiceProvider {

	/**
	 * @param Container $container
	 *
	 * @return bool
	 * @throws ContainerException
	 */
	public function provide( Container $container ) {

		$container->share(
			'mlp.assets',
			function ( Container $container ) {

				return new \Mlp_Assets( $container[ 'mlp.internal_locations' ] );
			}
		);

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		/** @type \Mlp_Assets $assets */
		$assets = $container[ 'mlp.assets' ];

		$admin_url = esc_url( parse_url( admin_url(), PHP_URL_PATH ) );
		$assets->add( 'mlp-admin', 'admin.js', [ 'backbone' ], [ 'mlpSettings' => [ 'urlRoot' => $admin_url, ], ] );
		$assets->add( 'mlp_admin_css', 'admin.css' );
		$assets->add( 'mlp-frontend', 'frontend.js' );
		$assets->add( 'mlp_frontend_css', 'frontend.css' );

		add_action( 'init', [ $assets, 'register' ], 0 );

		return TRUE;
	}
}