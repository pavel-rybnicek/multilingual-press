<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Redirect;

use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\ModuleServiceProviderOnActivationTrait;

/**
 * Service provider for alternative language title in admin bar module.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class ServiceProvider implements ModuleServiceProvider {

	use ModuleServiceProviderOnActivationTrait;

	const MODULE = 'redirect';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.redirect' ] = function ( Container $container ) {

			return new \Mlp_Redirect( $container[ 'mlp.api' ], NULL );
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		$this->on_activation(
			function () use ( $container ) {

				/** @var \Mlp_Redirect $redirect */
				$redirect = $container[ 'mlp.module.redirect' ];
				$redirect->setup( $this->provided_module() );
			}
		);

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function provided_module() {

		return self::MODULE;
	}

	/**
	 * @inheritdoc
	 */
	public function setup_module( \Mlp_Module_Manager $module_manager, Container $container ) {

		return $module_manager->register(
			[
				'display_name' => __( 'HTTP Redirect', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'description'  => __(
					'Redirect visitors according to browser language settings.', 'multilingual-press'
				),
			]
		);
	}
}