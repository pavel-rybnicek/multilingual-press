<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for Core objects.
 *
 * @package Inpsyde\MultilingualPress\Core
 * @since   3.0.0
 */
final class CoreServiceProvider implements BootstrappableServiceProvider {

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		// TODO: Move things to whereever they will belong.

		$container->share( 'multilingualpress.content_relations', function ( Container $container ) {

			return new \Mlp_Content_Relations(
				$GLOBALS['wpdb'],
				$container['multilingualpress.site_relations'],
				$GLOBALS['wpdb']->base_prefix . 'multilingual_linked'
			);
		} );

		$container['multilingualpress.locations'] = function () {

			return new \Mlp_Internal_Locations();
		};

		$container['multilingualpress.module_manager'] = function () {

			return new \Mlp_Module_Manager( 'state_modules' );
		};

		$container['multilingualpress.site_manager'] = function () {

			return new \Mlp_Module_Manager( 'inpsyde_multilingual' );
		};

		$container['multilingualpress.table_list'] = function () {

			return $table_list = new \Mlp_Db_Table_List( $GLOBALS['wpdb'] );
		};
	}

	/**
	 * Bootstraps the registered services.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function bootstrap( Container $container ) {

		$properties  = $container['multilingualpress.properties'];

		$plugin_path = untrailingslashit( $properties->plugin_file_path() );

		$plugin_url = untrailingslashit( $properties->plugin_dir_url() );

		$locations = $container['multilingualpress.locations'];

		$locations->add_dir( $plugin_path, $plugin_url, 'plugin' );
		$locations->add_dir( "{$plugin_path}/assets/css", "{$plugin_url}/assets/css", 'css' );
		$locations->add_dir( "{$plugin_path}/assets/js", "{$plugin_url}/assets/js", 'js' );
		$locations->add_dir( "{$plugin_path}/assets/images", "{$plugin_url}/assets/images", 'images' );
		$locations->add_dir( "{$plugin_path}/assets/images/flags", "{$plugin_url}/assets/images/flags", 'flags' );

		add_action( 'inpsyde_mlp_init', function () use ( $container ) {

			( new CoreSetup() )->setup( $container );
		}, 0 );
	}
}
