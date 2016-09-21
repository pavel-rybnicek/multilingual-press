<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\API;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for alternative language title in admin bar module.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class ServiceProvider implements BootableServiceProvider {

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.language_db_access' ] = function ( Container $container ) {

			return new \Mlp_Language_Db_Access( 'mlp_languages' );
		};

		$container->share(
			'mlp.api',
			function ( Container $container ) {

				return new \Mlp_Language_Api(
					$container[ 'mlp.locations' ],
					$container[ 'mlp.language_db_access' ],
					'mlp_languages',
					$container[ 'mlp.site_relations' ],
					$container[ 'mlp.content_relations' ],
					$GLOBALS[ 'wpdb' ]
				);
			}
		);

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		add_action(
			'wp_loaded',
			function () use ( $container ) {

				new \Mlp_Language_Manager_Controller(
					$container[ 'mlp.assets' ],
					$container[ 'language_db_access' ],
					$GLOBALS[ 'wpdb' ]
				);
			}
		);
	}
}