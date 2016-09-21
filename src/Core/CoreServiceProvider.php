<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ContainerException;

/**
 * Service provider for internal locations object.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class CoreServiceProvider implements BootableServiceProvider {

	/**
	 * @param Container $container
	 *
	 * @return bool
	 * @throws ContainerException
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.internal_locations' ] = function () {

			return new \Mlp_Internal_Locations();
		};

		$container[ 'mlp.module_manager' ] = function () {

			return new \Mlp_Module_Manager( 'state_modules' );
		};

		$container[ 'mlp.site_manager' ] = function () {

			return new \Mlp_Module_Manager( 'inpsyde_multilingual' );
		};

		$container[ 'mlp.table_list' ] = function () {

			return $table_list = new \Mlp_Db_Table_List( $GLOBALS[ 'wpdb' ] );
		};

		$container[ 'mlp.content_relations' ] = function ( Container $container ) {

			return new \Mlp_Content_Relations(
				$GLOBALS[ 'wpdb' ],
				$container[ 'mlp.site_relations' ],
				NULL,
				$GLOBALS[ 'wpdb' ]->base_prefix . 'multilingual_linked'
			);
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		/** @var Properties $properties */
		$properties  = $container[ 'mlp.properties' ];
		$plugin_path = untrailingslashit( $properties->plugin_file_path() );
		$plugin_url  = untrailingslashit( $properties->plugin_dir_url() );

		/** @var \Mlp_Internal_Locations $locations */
		$locations = $container[ 'mlp.internal_locations' ];
		$locations->add_dir( $plugin_path, $plugin_url, 'plugin' );
		$locations->add_dir( "{$plugin_path}/assets/css", "{$plugin_url}/assets/css", 'css' );
		$locations->add_dir( "{$plugin_path}/assets/js", "{$plugin_url}/assets/js", 'js' );
		$locations->add_dir( "{$plugin_path}/assets/images", "{$plugin_url}/assets/images", 'images' );
		$locations->add_dir( "{$plugin_path}/assets/images/flags", "{$plugin_url}/assets/images/flags", 'flags' );

		add_action(
			'inpsyde_mlp_loaded',
			function () use ( $container ) {

				$setup = new CoreSetup();
				$setup->setup( $container );
			},
			0
		);
	}

}