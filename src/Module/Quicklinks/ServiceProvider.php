<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Quicklinks;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Quicklinks module.
 *
 * @package Inpsyde\MultilingualPress\Module\Quicklinks
 * @since   3.0.0
 */
final class ServiceProvider implements ModuleServiceProvider {

	use ActivationAwareness;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->module = 'quicklinks';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.quicklinks'] = function ( Container $container ) {

			return new \Mlp_Quicklink(
				$container['multilingualpress.languages'],
				$container['multilingualpress.assets']
			);
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

		$quicklinks = $container['multilingualpress.module.quicklinks'];

		$this->on_activation( function () use ( $quicklinks ) {

			$quicklinks->initialize( $this->module );
		} );
	}

	/**
	 * Registers the module at the module manager.
	 *
	 * @since 3.0.0
	 *
	 * @param \Mlp_Module_Manager_Interface $module_manager Module manager object.
	 * @param Container                     $container      Container object.
	 *
	 * @return bool Whether or not the module was registerd successfully AND was activated.
	 */
	public function register_module( \Mlp_Module_Manager_Interface $module_manager, Container $container ) {

		return $module_manager->register( [
			'description'  => __( 'Show link to translations in post content.', 'multilingual-press' ),
			'display_name' => __( 'Quicklink', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
			'state'        => 'off',
		] );
	}
}
