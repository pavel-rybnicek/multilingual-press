<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\API;

use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for API objects.
 *
 * @package Inpsyde\MultilingualPress\API
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

		// TODO: Move things to whereever they will belong.

		$container['multilingualpress.language_db_access'] = function ( Container $container ) {

			return new \Mlp_Language_Db_Access( 'mlp_languages' );
		};

		$container->share( 'multilingualpress.languages', function ( Container $container ) {

			// TODO: Maybe introduce a WordPressServiceProvider (for wpdb etc.)...?

			return new \Mlp_Language_Api(
				$container['multilingualpress.locations'],
				$container['multilingualpress.language_db_access'],
				'mlp_languages',
				$container['multilingualpress.site_relations'],
				$container['multilingualpress.content_relations'],
				$GLOBALS['wpdb']
			);
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

		$language_db_access = $container['multilingualpress.language_db_access'];

		add_action( 'wp_loaded', function () use ( $container, $language_db_access ) {

			// TODO: Make constructor not self-firing by moving hooking up actions and filters to a dedicated method.

			new \Mlp_Language_Manager_Controller(
				$container['multilingualpress.assets'],
				$language_db_access,
				$GLOBALS['wpdb']
			);
		} );
	}
}
