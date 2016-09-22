<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Redirect;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Redirect module.
 *
 * @package Inpsyde\MultilingualPress\Module\Redirect
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

		$container['multilingualpress.module.redirect'] = function ( Container $container ) {

			return new \Mlp_Redirect( $container['multilingualpress.languages'] );
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

		$redirect = $container['multilingualpress.module.redirect'];

		$this->on_activation( function () use ( $redirect ) {

			$redirect->setup( $this->module );
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
			'description'  => __( 'Redirect visitors according to browser language settings.', 'multilingual-press' ),
			'display_name' => __( 'HTTP Redirect', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
		] );
	}
}
